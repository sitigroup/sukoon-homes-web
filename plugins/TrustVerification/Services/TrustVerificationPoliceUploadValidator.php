<?php

namespace App\Plugins\TrustVerification\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class TrustVerificationPoliceUploadValidator
{
    /** @var list<string> */
    public const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'application/pdf',
    ];

    /**
     * @return array{mime: string, extension: string, contents: string, size_bytes: int}
     */
    public static function validateAndNormalize(UploadedFile $file, int $maxBytes): array
    {
        if (! $file->isValid()) {
            throw new DocumentUploadRejectedException('Upload failed or file is invalid.', 'invalid_upload');
        }

        $size = (int) $file->getSize();
        if ($size <= 0) {
            throw new DocumentUploadRejectedException('Empty files are not allowed.', 'empty_file');
        }

        if ($size > $maxBytes) {
            $mb = (int) round($maxBytes / (1024 * 1024));

            throw new DocumentUploadRejectedException("File exceeds {$mb} MB limit.", 'file_too_large');
        }

        $path = $file->getRealPath();
        if (! $path || ! is_readable($path)) {
            throw new DocumentUploadRejectedException('Could not read uploaded file.', 'unreadable');
        }

        $detectedMime = self::detectMimeType($path);
        if (! in_array($detectedMime, self::ALLOWED_MIMES, true)) {
            throw new DocumentUploadRejectedException(
                'File type not allowed. Use JPG, JPEG, PNG, or PDF.',
                'mime_not_allowed'
            );
        }

        $contents = (string) file_get_contents($path);
        if ($contents === '') {
            throw new DocumentUploadRejectedException('Empty files are not allowed.', 'empty_file');
        }

        if (str_starts_with($detectedMime, 'image/')) {
            $contents = TrustVerificationDocumentUploadValidator::validateAndNormalize($file, $maxBytes)['contents'];
            $detectedMime = 'image/jpeg';
            $extension = 'jpg';
        } else {
            self::validatePdfHeader($contents);
            $extension = 'pdf';
            $detectedMime = 'application/pdf';
        }

        return [
            'mime' => $detectedMime,
            'extension' => $extension,
            'contents' => $contents,
            'size_bytes' => strlen($contents),
        ];
    }

    private static function detectMimeType(string $path): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($path);

        return is_string($mime) ? strtolower($mime) : 'application/octet-stream';
    }

    private static function validatePdfHeader(string $contents): void
    {
        if (! str_starts_with($contents, '%PDF')) {
            throw new DocumentUploadRejectedException('PDF file is invalid or corrupted.', 'corrupt_pdf');
        }
    }
}
