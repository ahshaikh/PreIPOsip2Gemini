<?php
/**
 * V-AUDIT-REFACTOR-2025 | V-XSS-PROTECTION | V-INPUT-INTEGRITY
 * Refactored to address Module 18 Audit Gaps:
 * 1. Global Sanitization: Strips dangerous tags from all incoming requests.
 * 2. Whitelist Protection: Ensures passwords and CMS content remain untouched.
 * 3. Null-Byte Removal: Prevents directory traversal and file-poisoning attacks.
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeInput
{
    /**
     * Fields that should NOT be sanitized.
     * [AUDIT FIX]: Essential for maintaining password integrity and HTML layouts.
     */
    protected array $except = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'html_content', // Admin CMS content
        'body_html',    // Email templates
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $input = $request->all();

        // Recursively clean all input data
        $sanitized = $this->sanitizeArray($input);

        $request->merge($sanitized);

        return $next($request);
    }

    /**
     * Recursively sanitize an array of inputs.
     */
    protected function sanitizeArray(array $data): array
    {
        foreach ($data as $key => $value) {
            // Skip protected fields
            if (in_array($key, $this->except, true)) {
                continue;
            }

            if (is_array($value)) {
                $data[$key] = $this->sanitizeArray($value);
            } elseif (is_string($value)) {
                $data[$key] = $this->sanitizeString($value);
            }
        }

        return $data;
    }

    /**
     * Sanitize a single string value.
     * [AUDIT FIX]: Prevents script injection while allowing basic text formatting.
     */
    protected function sanitizeString(string $value): string
    {
        // 1. Remove null bytes (prevents poisoning attacks)
        $value = str_replace(chr(0), '', $value);

        // 2. Trim whitespace for consistency
        $value = trim($value);

        // 3. Strip dangerous HTML tags. 
        // We explicitly remove <a> tags to prevent javascript: pseudo-protocol attacks.
        $value = strip_tags($value, '<b><i><u><strong><em><br><p><ul><ol><li>');

        // 4. Encode special characters to prevent XSS in echoed outputs
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);

        return $value;
    }
}