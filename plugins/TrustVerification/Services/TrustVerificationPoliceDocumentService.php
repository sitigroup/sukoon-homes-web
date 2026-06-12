<?php

namespace App\Plugins\TrustVerification\Services;

use App\Models\Customer;
use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Models\TvPoliceVerification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TrustVerificationPoliceDocumentService
{
    public const DISK = 'local';

    public const MAX_ACK_BYTES = 5 * 1024 * 1024;

    public const MAX_CERT_BYTES = 10 * 1024 * 1024;

    /** @var list<string> */
    public const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'application/pdf',
    ];

    public static function storeAcknowledgement(
        TvPoliceVerification $verification,
        UploadedFile $file,
        ?Customer $customer = null,
        bool $adminUpload = false
    ): TvPoliceVerification {
        if ($adminUpload) {
            // Admin controller already verified ROLE_UPDATE.
        } elseif ($customer !== null) {
            self::assertCustomerOwnership($verification, $customer);
        } else {
            abort(403, 'Unauthorized');
        }

        $validated = TrustVerificationPoliceUploadValidator::validateAndNormalize(
            $file,
            self::MAX_ACK_BYTES
        );

        return self::storeFile(
            $verification,
            'acknowledgement',
            $validated,
            TrustVerificationDocumentUploadValidator::sanitizeDisplayFilename($file->getClientOriginalName())
        );
    }

    public static function storeCertificate(
        TvPoliceVerification $verification,
        UploadedFile $file
    ): TvPoliceVerification {
        $validated = TrustVerificationPoliceUploadValidator::validateAndNormalize(
            $file,
            self::MAX_CERT_BYTES
        );

        return self::storeFile(
            $verification,
            'certificate',
            $validated,
            TrustVerificationDocumentUploadValidator::sanitizeDisplayFilename($file->getClientOriginalName())
        );
    }

    /**
     * @param  array{mime: string, extension: string, contents: string, size_bytes: int}  $validated
     */
    private static function storeFile(
        TvPoliceVerification $verification,
        string $kind,
        array $validated,
        string $displayName
    ): TvPoliceVerification {
        $orderId = (int) $verification->order_id;
        $filename = TrustVerificationDocumentUploadValidator::generateStorageFilename(
            $orderId,
            'police_'.$kind,
            $validated['extension']
        );
        $relativePath = 'trust-verification/police/'.$orderId.'/'.$filename;
        $directory = 'trust-verification/police/'.$orderId;

        if (! Storage::disk(self::DISK)->exists($directory)) {
            Storage::disk(self::DISK)->makeDirectory($directory);
        }

        $pathField = $kind.'_document_path';
        $nameField = $kind.'_original_name';
        $mimeField = $kind.'_mime';
        $sizeField = $kind.'_size';

        if ($verification->{$pathField}) {
            Storage::disk(self::DISK)->delete($verification->{$pathField});
        }

        Storage::disk(self::DISK)->put($relativePath, $validated['contents']);

        $verification->update([
            $pathField => $relativePath,
            $nameField => $displayName,
            $mimeField => $validated['mime'],
            $sizeField => $validated['size_bytes'],
            'updated_by' => Auth::id(),
        ]);

        return $verification->fresh();
    }

    public static function acknowledgementExists(TvPoliceVerification $verification): bool
    {
        return self::fileExists($verification->acknowledgement_document_path);
    }

    public static function certificateExists(TvPoliceVerification $verification): bool
    {
        return self::fileExists($verification->certificate_document_path);
    }

    public static function downloadAcknowledgement(
        TvPoliceVerification $verification,
        ?Customer $customer = null,
        bool $admin = false
    ): StreamedResponse {
        if (! $admin) {
            self::assertCustomerOwnership($verification, $customer);
        } elseif (! TrustVerificationPermissionService::can(TrustVerificationPermissionService::ROLE_DOCUMENTS)
            && ! TrustVerificationPermissionService::can(TrustVerificationPermissionService::ROLE_UPDATE)) {
            abort(403, TrustVerificationPermissionService::DENIED_MESSAGE);
        }

        return self::streamDownload(
            $verification->acknowledgement_document_path,
            $verification->acknowledgement_original_name,
            $verification->acknowledgement_mime
        );
    }

    public static function downloadCertificate(
        TvPoliceVerification $verification,
        ?Customer $customer = null,
        bool $admin = false
    ): StreamedResponse {
        if (! $admin) {
            self::assertCustomerOwnership($verification, $customer);
            if (! self::certificateExists($verification)) {
                abort(404, 'Certificate not available');
            }
        } elseif (! TrustVerificationPermissionService::can(TrustVerificationPermissionService::ROLE_REPORTS)
            && ! TrustVerificationPermissionService::can(TrustVerificationPermissionService::ROLE_UPDATE)) {
            abort(403, TrustVerificationPermissionService::DENIED_MESSAGE);
        }

        return self::streamDownload(
            $verification->certificate_document_path,
            $verification->certificate_original_name,
            $verification->certificate_mime
        );
    }

    private static function streamDownload(?string $path, ?string $originalName, ?string $mime): StreamedResponse
    {
        if (! self::fileExists($path)) {
            abort(404, 'Document not found or has been removed');
        }

        $filename = TrustVerificationDocumentUploadValidator::sanitizeDisplayFilename($originalName ?: 'police-document');
        if ($mime === 'application/pdf' && ! str_ends_with(strtolower($filename), '.pdf')) {
            $filename .= '.pdf';
        }

        return response()->streamDownload(
            static function () use ($path) {
                $stream = Storage::disk(self::DISK)->readStream($path);
                if ($stream === false) {
                    return;
                }
                fpassthru($stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }
            },
            $filename,
            ['Content-Type' => $mime ?: 'application/octet-stream']
        );
    }

    private static function fileExists(?string $path): bool
    {
        return $path && Storage::disk(self::DISK)->exists($path);
    }

    private static function assertCustomerOwnership(
        TvPoliceVerification $verification,
        ?Customer $customer
    ): void {
        if (! $customer) {
            abort(403, 'Unauthorized');
        }

        $verification->loadMissing('order');
        if ((int) $verification->order->customer_id !== (int) $customer->id) {
            abort(403, 'Unauthorized');
        }
    }
}
