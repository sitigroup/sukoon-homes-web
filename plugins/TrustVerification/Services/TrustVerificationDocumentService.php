<?php

namespace App\Plugins\TrustVerification\Services;

use App\Models\Customer;
use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Models\TvOrderDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TrustVerificationDocumentService
{
    public const DISK = 'local';

    public const MAX_BYTES = TrustVerificationDocumentUploadValidator::MAX_BYTES;

    /** @var list<string> */
    public const ALLOWED_MIMES = TrustVerificationDocumentUploadValidator::ALLOWED_MIMES;

    public static function store(
        TvOrder $order,
        string $docType,
        UploadedFile $file,
        ?Customer $customer = null
    ): TvOrderDocument {
        if (! array_key_exists($docType, TrustVerificationSettingsService::DOCUMENT_TYPES)) {
            throw new \InvalidArgumentException('Invalid document type.');
        }

        if ($customer && (int) $order->customer_id !== (int) $customer->id) {
            throw new \RuntimeException('Unauthorized');
        }

        $validated = TrustVerificationDocumentUploadValidator::validateAndNormalize($file);
        $displayName = TrustVerificationDocumentUploadValidator::sanitizeDisplayFilename(
            $file->getClientOriginalName()
        );

        $filename = TrustVerificationDocumentUploadValidator::generateStorageFilename(
            (int) $order->id,
            $docType,
            $validated['extension']
        );
        $relativePath = 'trust-verification/documents/'.$order->id.'/'.$filename;
        $directory = 'trust-verification/documents/'.$order->id;
        if (! Storage::disk(self::DISK)->exists($directory)) {
            Storage::disk(self::DISK)->makeDirectory($directory);
        }

        Storage::disk(self::DISK)->put($relativePath, $validated['contents']);

        $existing = TvOrderDocument::where('order_id', $order->id)->where('doc_type', $docType)->first();
        if ($existing) {
            if ($existing->file_path && ! $existing->deleted_at) {
                Storage::disk(self::DISK)->delete($existing->file_path);
            }
            $existing->update([
                'file_path' => $relativePath,
                'original_name' => $displayName,
                'mime_type' => $validated['mime'],
                'size_bytes' => $validated['size_bytes'],
                'uploaded_by_customer_id' => $customer?->id,
                'deleted_at' => null,
                'deleted_by_type' => null,
                'deleted_by_id' => null,
                'delete_reason' => null,
            ]);

            return $existing->fresh();
        }

        return TvOrderDocument::create([
            'order_id' => $order->id,
            'doc_type' => $docType,
            'file_path' => $relativePath,
            'original_name' => $displayName,
            'mime_type' => $validated['mime'],
            'size_bytes' => $validated['size_bytes'],
            'uploaded_by_customer_id' => $customer?->id,
        ]);
    }

    public static function formatDocument(TvOrderDocument $doc): array
    {
        $displayName = TrustVerificationDocumentUploadValidator::sanitizeDisplayFilename($doc->original_name);

        return [
            'id' => $doc->id,
            'doc_type' => $doc->doc_type,
            'label' => TrustVerificationSettingsService::DOCUMENT_TYPES[$doc->doc_type] ?? $doc->doc_type,
            'original_name' => $displayName,
            'mime_type' => $doc->mime_type,
            'size_bytes' => $doc->size_bytes,
            'uploaded_at' => optional($doc->created_at)?->toIso8601String(),
            'file_available' => TrustVerificationPiiRetentionService::documentFileAvailable($doc),
            'deleted_at' => optional($doc->deleted_at)?->toIso8601String(),
        ];
    }

    public static function fileExists(TvOrderDocument $doc): bool
    {
        return TrustVerificationPiiRetentionService::documentFileAvailable($doc);
    }

    public static function downloadResponse(TvOrderDocument $doc): StreamedResponse
    {
        if (! self::fileExists($doc)) {
            abort(404, 'Document not found or has been removed');
        }

        $mime = self::resolveDownloadMimeType($doc);
        $filename = TrustVerificationDocumentUploadValidator::safeDownloadFilename($doc);
        $path = $doc->file_path;

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
            [
                'Content-Type' => $mime,
                'Content-Disposition' => 'attachment; filename="'.self::escapeFilename($filename).'"',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
                'Pragma' => 'no-cache',
            ]
        );
    }

    /** @return list<string> */
    public static function missingRequiredTypes(TvOrder $order): array
    {
        $settings = TrustVerificationSettingsService::all();
        if (! ($settings['documents_enabled'] ?? false) || ! ($settings['documents_required'] ?? false)) {
            return [];
        }

        $required = array_values((array) ($settings['required_document_types'] ?? []));
        $uploaded = $order->documents()
            ->whereNull('deleted_at')
            ->pluck('doc_type')
            ->all();

        return array_values(array_diff($required, $uploaded));
    }

    private static function resolveDownloadMimeType(TvOrderDocument $doc): string
    {
        $mime = strtolower((string) $doc->mime_type);
        if (in_array($mime, self::ALLOWED_MIMES, true)) {
            return $mime;
        }

        $ext = strtolower(pathinfo((string) $doc->file_path, PATHINFO_EXTENSION));

        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            default => 'application/octet-stream',
        };
    }

    private static function escapeFilename(string $filename): string
    {
        return str_replace(['"', "\r", "\n"], '', $filename);
    }

}
