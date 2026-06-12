<?php

namespace App\Plugins\TrustVerification\Services;

use App\Plugins\TrustVerification\Models\TvOrderDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class TrustVerificationDocumentUploadValidator
{
    public const MAX_BYTES = 5 * 1024 * 1024;

    /** @var list<string> */
    public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

    /** @var list<string> */
    public const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'application/pdf',
    ];

    /** @var list<string> */
    private const BLOCKED_EXTENSIONS = [
        'exe', 'bat', 'cmd', 'com', 'msi', 'scr', 'ps1', 'vbs', 'js', 'mjs', 'jar',
        'php', 'phtml', 'phar', 'asp', 'aspx', 'jsp', 'cgi', 'pl', 'py', 'rb', 'sh',
        'html', 'htm', 'svg', 'xml', 'xhtml', 'zip', 'rar', '7z', 'gz', 'tar',
        'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'dll', 'so', 'dmg', 'app',
    ];

    /**
     * @return array{mime: string, extension: string, contents: string, size_bytes: int}
     */
    public static function validateAndNormalize(UploadedFile $file, ?int $maxBytes = null): array
    {
        $maxBytes = $maxBytes ?? self::MAX_BYTES;
        if (! $file->isValid()) {
            throw new DocumentUploadRejectedException('Upload failed or file is invalid.', 'invalid_upload');
        }

        $size = (int) $file->getSize();
        if ($size <= 0) {
            throw new DocumentUploadRejectedException('Empty files are not allowed.', 'empty_file');
        }

        if ($size > $maxBytes) {
            throw new DocumentUploadRejectedException('File exceeds '.(int) round($maxBytes / (1024 * 1024)).' MB limit.', 'file_too_large');
        }

        self::assertSafeClientFilename($file->getClientOriginalName());

        $path = $file->getRealPath();
        if (! $path || ! is_readable($path)) {
            throw new DocumentUploadRejectedException('Could not read uploaded file.', 'unreadable');
        }

        $detectedMime = self::detectMimeType($path);
        if (! in_array($detectedMime, self::ALLOWED_MIMES, true)) {
            throw new DocumentUploadRejectedException(
                'File type not allowed. Use JPG, PNG, WEBP, or PDF.',
                'mime_not_allowed'
            );
        }

        $extension = self::extensionForMime($detectedMime);
        self::assertExtensionMatchesMime($file->getClientOriginalExtension(), $extension, $detectedMime);

        $contents = (string) file_get_contents($path);
        if ($contents === '') {
            throw new DocumentUploadRejectedException('Empty files are not allowed.', 'empty_file');
        }

        if (str_starts_with($detectedMime, 'image/')) {
            $contents = self::validateAndNormalizeImage($contents, $detectedMime);
            $detectedMime = 'image/jpeg';
            $extension = 'jpg';
        } else {
            self::validatePdf($contents);
            $extension = 'pdf';
            $detectedMime = 'application/pdf';
        }

        if (strlen($contents) > self::MAX_BYTES) {
            throw new DocumentUploadRejectedException('File exceeds 5 MB limit after processing.', 'file_too_large');
        }

        return [
            'mime' => $detectedMime,
            'extension' => $extension,
            'contents' => $contents,
            'size_bytes' => strlen($contents),
        ];
    }

    public static function sanitizeDisplayFilename(?string $originalName): string
    {
        $base = basename((string) $originalName);
        $base = preg_replace('/[^\w.\- ]+/u', '_', $base) ?? 'document';
        $base = trim(preg_replace('/\s+/', ' ', $base) ?? '', ' .');

        if ($base === '' || $base === '.' || $base === '..') {
            return 'document';
        }

        return Str::limit($base, 180, '');
    }

    public static function generateStorageFilename(int $orderId, string $docType, string $extension): string
    {
        $safeType = preg_replace('/[^a-z0-9_\-]/i', '_', $docType) ?: 'doc';

        return sprintf(
            'tv_order_%d_%s_%s_%s.%s',
            $orderId,
            $safeType,
            now()->format('YmdHis'),
            Str::lower(Str::random(10)),
            $extension
        );
    }

    public static function safeDownloadFilename(TvOrderDocument $doc): string
    {
        $display = self::sanitizeDisplayFilename($doc->original_name);
        $ext = self::extensionForMime((string) $doc->mime_type) ?: pathinfo($display, PATHINFO_EXTENSION);

        if ($ext && ! str_ends_with(strtolower($display), '.'.$ext)) {
            $display .= '.'.$ext;
        }

        return $display ?: ($doc->doc_type.'-document.'.$ext);
    }

    private static function assertSafeClientFilename(?string $filename): void
    {
        $name = strtolower(trim((string) $filename));
        if ($name === '') {
            return;
        }

        if (str_contains($name, '..') || str_contains($name, '/') || str_contains($name, '\\') || str_contains($name, "\0")) {
            throw new DocumentUploadRejectedException('Invalid filename.', 'unsafe_filename');
        }

        $segments = array_filter(explode('.', $name), fn ($part) => $part !== '');
        if ($segments === []) {
            throw new DocumentUploadRejectedException('Invalid filename.', 'unsafe_filename');
        }

        foreach ($segments as $segment) {
            if (in_array($segment, self::BLOCKED_EXTENSIONS, true)) {
                throw new DocumentUploadRejectedException('Executable or disallowed file types are not permitted.', 'blocked_extension');
            }
        }

        $last = (string) end($segments);
        if (! in_array($last, self::ALLOWED_EXTENSIONS, true)) {
            throw new DocumentUploadRejectedException('File extension not allowed.', 'extension_not_allowed');
        }

        if (count($segments) > 2) {
            foreach (array_slice($segments, 0, -1) as $segment) {
                if (in_array($segment, self::ALLOWED_EXTENSIONS, true)) {
                    throw new DocumentUploadRejectedException('Double extensions are not allowed.', 'double_extension');
                }
            }
        }
    }

    private static function detectMimeType(string $path): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detected = $finfo ? finfo_file($finfo, $path) : false;
        if ($finfo) {
            finfo_close($finfo);
        }

        $mime = is_string($detected) ? strtolower($detected) : '';
        if ($mime === 'image/jpg') {
            $mime = 'image/jpeg';
        }

        return $mime;
    }

    private static function assertExtensionMatchesMime(?string $clientExt, string $expectedExt, string $mime): void
    {
        $client = strtolower(trim((string) $clientExt));
        if ($client === '') {
            return;
        }

        $aliases = [
            'jpg' => ['jpg', 'jpeg'],
            'jpeg' => ['jpg', 'jpeg'],
            'png' => ['png'],
            'webp' => ['webp'],
            'pdf' => ['pdf'],
        ];

        $allowed = $aliases[$expectedExt] ?? [$expectedExt];
        if (! in_array($client, $allowed, true)) {
            throw new DocumentUploadRejectedException('File extension does not match file content.', 'extension_mismatch');
        }

        if ($mime === 'application/pdf' && $client !== 'pdf') {
            throw new DocumentUploadRejectedException('File extension does not match file content.', 'extension_mismatch');
        }
    }

    private static function extensionForMime(string $mime): string
    {
        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            default => '',
        };
    }

    private static function validateAndNormalizeImage(string $contents, string $mime): string
    {
        if (! function_exists('imagecreatefromstring')) {
            if (@getimagesizefromstring($contents) === false) {
                throw new DocumentUploadRejectedException('Image file is corrupted or invalid.', 'corrupt_image');
            }

            return $contents;
        }

        $image = @imagecreatefromstring($contents);
        if ($image === false) {
            throw new DocumentUploadRejectedException('Image file is corrupted or invalid.', 'corrupt_image');
        }

        $width = imagesx($image);
        $height = imagesy($image);
        if ($width < 1 || $height < 1) {
            imagedestroy($image);
            throw new DocumentUploadRejectedException('Image file is corrupted or invalid.', 'corrupt_image');
        }

        ob_start();
        $ok = imagejpeg($image, null, 90);
        imagedestroy($image);
        $jpeg = ob_get_clean();

        if (! $ok || $jpeg === false || $jpeg === '') {
            throw new DocumentUploadRejectedException('Image file could not be processed safely.', 'corrupt_image');
        }

        return $jpeg;
    }

    private static function validatePdf(string $contents): void
    {
        if (! str_starts_with($contents, '%PDF')) {
            throw new DocumentUploadRejectedException('PDF file is invalid or corrupted.', 'corrupt_pdf');
        }

        $sample = substr($contents, 0, min(strlen($contents), 131072));

        if (preg_match('/\/Encrypt\b/i', $sample)) {
            throw new DocumentUploadRejectedException('Password-protected PDF files are not allowed.', 'encrypted_pdf');
        }

        if (preg_match('/\/JavaScript\b/i', $sample) || preg_match('/\/JS\s*\(/i', $sample)) {
            throw new DocumentUploadRejectedException('PDF contains disallowed embedded scripts.', 'pdf_scripts');
        }
    }
}
