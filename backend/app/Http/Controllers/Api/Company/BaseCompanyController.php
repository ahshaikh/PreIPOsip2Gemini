<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyUser;
use Illuminate\Http\JsonResponse;

/**
 * Base Controller for Company Portal
 *
 * Provides common helper methods for all company controllers to:
 * 1. Safely retrieve company from authenticated CompanyUser
 * 2. Standardize error responses
 * 3. Enforce null checking to prevent crashes
 *
 * USAGE: All company controllers should extend this instead of Controller
 */
abstract class BaseCompanyController extends Controller
{
    /**
     * Get the authenticated company user's company with null safety
     *
     * FIX: Critical bug - All company controllers access $companyUser->company
     * without null checking. This causes crashes if relationship is missing.
     *
     * This method provides centralized null checking with proper error response.
     *
     * @param CompanyUser $companyUser The authenticated company user
     * @param bool $returnResponse If true, returns JsonResponse on error; if false, returns null
     * @return Company|JsonResponse|null
     */
    protected function getCompanyOrFail(CompanyUser $companyUser, bool $returnResponse = true)
    {
        $company = $companyUser->company;

        if (!$company) {
            \Log::error('[COMPANY-CONTROLLER] Company not found for authenticated user', [
                'company_user_id' => $companyUser->id,
                'email' => $companyUser->email,
                'company_id' => $companyUser->company_id,
            ]);

            if ($returnResponse) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company not found. Your account may not be properly configured. Please contact support.',
                    'error_code' => 'COMPANY_NOT_FOUND',
                ], 404);
            }

            return null;
        }

        return $company;
    }

    /**
     * Check if company exists and return error response if not
     *
     * USAGE in controller methods:
     * ```
     * public function index(Request $request) {
     *     $companyUser = $request->user();
     *     $company = $this->getCompanyOrFail($companyUser);
     *
     *     // If $company is a JsonResponse (error), return it immediately
     *     if ($company instanceof JsonResponse) {
     *         return $company;
     *     }
     *
     *     // Continue with normal logic
     *     $data = $company->someRelationship()->get();
     *     return response()->json(['success' => true, 'data' => $data]);
     * }
     * ```
     *
     * @param CompanyUser $companyUser
     * @return Company The company instance
     * @throws \RuntimeException if company not found (when not in HTTP context)
     */
    protected function getCompanyOrThrow(CompanyUser $companyUser): Company
    {
        $company = $companyUser->company;

        if (!$company) {
            \Log::error('[COMPANY-CONTROLLER] Company not found for user', [
                'company_user_id' => $companyUser->id,
                'company_id' => $companyUser->company_id,
            ]);

            throw new \RuntimeException(
                "Company not found for user ID {$companyUser->id}. " .
                "This indicates a data integrity issue - company_id references non-existent company."
            );
        }

        return $company;
    }

    /**
     * Standard success response
     *
     * @param mixed $data The data to return
     * @param string $message Success message
     * @param int $statusCode HTTP status code
     * @return JsonResponse
     */
    protected function successResponse($data, string $message = 'Success', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    /**
     * Standard error response
     *
     * @param string $message Error message
     * @param mixed $errors Validation errors or additional error details
     * @param int $statusCode HTTP status code
     * @return JsonResponse
     */
    protected function errorResponse(string $message, $errors = null, int $statusCode = 400): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }
}
