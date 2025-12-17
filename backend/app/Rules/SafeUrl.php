<?php
// V-AUDIT-MODULE12-002 (MEDIUM): Extracted reusable URL validation rule from CmsController
// Created: 2025-12-17 | Prevents XSS via javascript:, data:, and vbscript: protocols

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

/**
 * SafeUrl Validation Rule
 *
 * V-AUDIT-MODULE12-002 (MEDIUM): Extract safeUrlRule into reusable Validation Rule class
 *
 * Previous Issue:
 * The CmsController had a private safeUrlRule() method that validated URLs to prevent XSS
 * via dangerous protocols (javascript:, data:, vbscript:). This logic was not reusable across
 * other controllers that needed URL validation (e.g., SocialProfileController, UserController).
 *
 * Fix:
 * Extracted the validation logic into a dedicated Rule class following Laravel conventions.
 * This allows any controller or FormRequest to use: 'url' => ['required', new SafeUrl()]
 *
 * Benefits:
 * - DRY: Reusable across the entire application
 * - Security: Centralized security logic is easier to audit and update
 * - Maintainability: Changes to URL validation rules only need to be made in one place
 * - Testable: Can write dedicated unit tests for this rule
 *
 * Usage Example:
 * ```php
 * $request->validate([
 *     'profile_url' => ['nullable', 'string', 'max:2048', new SafeUrl()],
 *     'redirect_url' => ['required', new SafeUrl()],
 * ]);
 * ```
 */
class SafeUrl implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // Allow empty values (use 'required' rule separately if needed)
        if (empty($value)) {
            return true;
        }

        // V-AUDIT-MODULE12-002: Only allow http, https, and relative paths
        $parsed = parse_url($value);
        if (isset($parsed['scheme']) && !in_array(strtolower($parsed['scheme']), ['http', 'https'])) {
            return false;
        }

        // V-AUDIT-MODULE12-002: Block javascript:, data:, vbscript: even if parse_url doesn't catch them
        // This regex check is a safety net for edge cases where parse_url might not properly parse malformed URLs
        if (preg_match('/^(javascript|data|vbscript):/i', $value)) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute must use http or https protocol only. Dangerous protocols (javascript:, data:, vbscript:) are not allowed.';
    }
}
