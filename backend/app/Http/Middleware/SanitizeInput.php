<?php
/**
 * V-AUDIT-REFACTOR-2025 | V-HARDENED-SANITIZATION | V-METADATA-SHIELD
 * * ARCHITECTURAL FIX: 
 * Prevents "Log Poisoning" by sanitizing non-body data (headers/IP).
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeInput
{
    protected array $except = [
        'password', 'password_confirmation', 'current_password', 
        'new_password', 'html_content', 'body_html',
        'meta_title_suffix', 'robots_txt'
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // 1. Sanitize Body Data
        $request->merge($this->sanitizeArray($request->all()));

        // 2. [SECURITY FIX]: Sanitize Headers to prevent Log Poisoning
        // This ensures the User-Agent and IP cannot inject scripts into Audit Logs.
        $userAgent = $request->header('User-Agent');
        if ($userAgent) {
            $request->headers->set('User-Agent', $this->sanitizeString($userAgent));
        }

        return $next($request);
    }

    protected function sanitizeArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (in_array($key, $this->except, true)) continue;

            if (is_array($value)) {
                $data[$key] = $this->sanitizeArray($value);
            } elseif (is_string($value)) {
                $data[$key] = $this->sanitizeString($value);
            }
        }
        return $data;
    }

    protected function sanitizeString(string $value): string
    {
        // Remove null bytes and hidden characters
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
        
        $value = trim($value);
        
        // Strip tags but keep basic formatting
        $value = strip_tags($value, '<b><i><u><strong><em><br>');

        // Strict HTML encoding for the storage layer
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);
    }
}