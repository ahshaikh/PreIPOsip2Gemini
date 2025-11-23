<?php
// V-SECURITY-XSS-PROTECTION - Input Sanitization Middleware

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeInput
{
    /**
     * Fields that should NOT be sanitized (e.g., passwords, HTML content)
     */
    protected array $except = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'html_content', // For CMS pages
        'body_html',    // For email templates
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $input = $request->all();

        $sanitized = $this->sanitizeArray($input);

        $request->merge($sanitized);

        return $next($request);
    }

    /**
     * Recursively sanitize an array of inputs
     */
    protected function sanitizeArray(array $data): array
    {
        foreach ($data as $key => $value) {
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
     * Sanitize a single string value
     */
    protected function sanitizeString(string $value): string
    {
        // Remove null bytes
        $value = str_replace(chr(0), '', $value);

        // Trim whitespace
        $value = trim($value);

        // Strip HTML tags (basic XSS protection)
        // For fields that need HTML, they should be in $except
        $value = strip_tags($value, '<b><i><u><strong><em><br><p><ul><ol><li><a>');

        // Encode special HTML characters
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);

        return $value;
    }
}
