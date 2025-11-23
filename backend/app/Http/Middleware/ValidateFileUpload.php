<?php
// V-SECURITY-FILEUPLOAD - File Upload Security Middleware

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class ValidateFileUpload
{
    /**
     * Allowed MIME types by category
     */
    protected array $allowedMimeTypes = [
        'image' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
        ],
        'document' => [
            'application/pdf',
            'image/jpeg',
            'image/png',
        ],
        'kyc' => [
            'application/pdf',
            'image/jpeg',
            'image/png',
        ],
        'spreadsheet' => [
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/csv',
        ],
    ];

    /**
     * Max file sizes by category (in bytes)
     */
    protected array $maxSizes = [
        'image' => 5 * 1024 * 1024,      // 5MB
        'document' => 10 * 1024 * 1024,   // 10MB
        'kyc' => 5 * 1024 * 1024,         // 5MB
        'spreadsheet' => 20 * 1024 * 1024, // 20MB
    ];

    /**
     * Dangerous file signatures (magic bytes)
     */
    protected array $dangerousSignatures = [
        "\x4D\x5A",                     // EXE/DLL
        "\x7F\x45\x4C\x46",            // ELF
        "\x50\x4B\x03\x04",            // ZIP (could contain malware)
        "\x52\x61\x72\x21\x1A\x07",    // RAR
        "<?php",                        // PHP
        "<?=",                          // PHP short tag
        "<script",                      // JavaScript
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $category = 'document'): Response
    {
        $files = $request->allFiles();

        if (empty($files)) {
            return $next($request);
        }

        foreach ($files as $key => $file) {
            if (is_array($file)) {
                foreach ($file as $singleFile) {
                    $result = $this->validateFile($singleFile, $category);
                    if ($result !== true) {
                        return $this->rejectFile($result, $key);
                    }
                }
            } else {
                $result = $this->validateFile($file, $category);
                if ($result !== true) {
                    return $this->rejectFile($result, $key);
                }
            }
        }

        return $next($request);
    }

    /**
     * Validate a single uploaded file
     */
    protected function validateFile(UploadedFile $file, string $category): bool|string
    {
        // 1. Check if file is valid
        if (!$file->isValid()) {
            return 'File upload failed. Please try again.';
        }

        // 2. Check file size
        $maxSize = $this->maxSizes[$category] ?? $this->maxSizes['document'];
        if ($file->getSize() > $maxSize) {
            $maxMB = $maxSize / 1024 / 1024;
            return "File size exceeds maximum allowed ({$maxMB}MB).";
        }

        // 3. Check MIME type
        $allowedMimes = $this->allowedMimeTypes[$category] ?? $this->allowedMimeTypes['document'];
        $actualMime = $file->getMimeType();

        if (!in_array($actualMime, $allowedMimes, true)) {
            return "File type '{$actualMime}' is not allowed. Allowed types: " . implode(', ', $allowedMimes);
        }

        // 4. Verify MIME type matches extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!$this->validateMimeExtensionMatch($actualMime, $extension)) {
            Log::warning('File MIME/extension mismatch detected', [
                'mime' => $actualMime,
                'extension' => $extension,
                'original_name' => $file->getClientOriginalName(),
            ]);
            return 'File type does not match extension. Please upload a valid file.';
        }

        // 5. Check for dangerous content/signatures
        $content = file_get_contents($file->getPathname(), false, null, 0, 1024);
        if ($this->containsDangerousContent($content)) {
            Log::alert('Potentially malicious file upload detected', [
                'original_name' => $file->getClientOriginalName(),
                'mime' => $actualMime,
                'user_id' => request()->user()?->id,
                'ip' => request()->ip(),
            ]);
            return 'File contains potentially dangerous content.';
        }

        // 6. Sanitize filename
        $originalName = $file->getClientOriginalName();
        if ($this->hasDangerousFilename($originalName)) {
            return 'Invalid filename. Please rename the file and try again.';
        }

        return true;
    }

    /**
     * Validate that MIME type matches the file extension
     */
    protected function validateMimeExtensionMatch(string $mime, string $extension): bool
    {
        $mimeExtensionMap = [
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png' => ['png'],
            'image/gif' => ['gif'],
            'image/webp' => ['webp'],
            'application/pdf' => ['pdf'],
            'application/vnd.ms-excel' => ['xls'],
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['xlsx'],
            'text/csv' => ['csv'],
        ];

        $allowedExtensions = $mimeExtensionMap[$mime] ?? [];

        return in_array($extension, $allowedExtensions, true);
    }

    /**
     * Check for dangerous file signatures
     */
    protected function containsDangerousContent(string $content): bool
    {
        foreach ($this->dangerousSignatures as $signature) {
            if (str_contains($content, $signature)) {
                return true;
            }
        }

        // Check for embedded PHP
        if (preg_match('/<\?(?:php|=)/i', $content)) {
            return true;
        }

        // Check for JavaScript
        if (preg_match('/<script[\s>]/i', $content)) {
            return true;
        }

        // Check for null bytes (path traversal attempt)
        if (str_contains($content, "\x00")) {
            return true;
        }

        return false;
    }

    /**
     * Check for dangerous filenames
     */
    protected function hasDangerousFilename(string $filename): bool
    {
        // Dangerous extensions
        $dangerousExtensions = [
            'php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phar',
            'exe', 'dll', 'bat', 'cmd', 'sh', 'bash',
            'js', 'html', 'htm', 'asp', 'aspx', 'jsp',
            'cgi', 'pl', 'py', 'rb', 'htaccess',
        ];

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($extension, $dangerousExtensions, true)) {
            return true;
        }

        // Check for double extensions (e.g., malware.pdf.php)
        if (preg_match('/\.(' . implode('|', $dangerousExtensions) . ')\.?\w*$/i', $filename)) {
            return true;
        }

        // Check for path traversal attempts
        if (str_contains($filename, '..') || str_contains($filename, '/') || str_contains($filename, '\\')) {
            return true;
        }

        // Check for null bytes
        if (str_contains($filename, "\x00")) {
            return true;
        }

        return false;
    }

    /**
     * Return rejection response
     */
    protected function rejectFile(string $message, string $field): Response
    {
        return response()->json([
            'message' => 'File validation failed.',
            'errors' => [
                $field => [$message],
            ],
        ], 422);
    }
}
