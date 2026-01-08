<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyUser;
use App\Services\CompanyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    protected $companyService;

    // FIX: Inject CompanyService to handle registration logic
    public function __construct(CompanyService $companyService)
    {
        $this->companyService = $companyService;
    }

    /**
     * Register a new company user
     */
    public function register(Request $request)
    {
        \Log::info('[COMPANY-AUTH] Registration attempt started', [
            'email' => $request->input('email'),
            'ip' => $request->ip(),
            'url' => $request->fullUrl(),
        ]);

        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
            'sector' => 'required|string|max:255',
            'email' => 'required|email|unique:company_users,email',
            'password' => 'required|string|min:8|confirmed',
            'contact_person_name' => 'required|string|max:255',
            'contact_person_designation' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // FIX: Delegated logic to CompanyService.
            // This removes logic leakage and ensures consistency.
            $result = $this->companyService->registerCompany($request->all());
            
            $company = $result['company'];
            $companyUser = $result['user'];

            return response()->json([
                'success' => true,
                'message' => 'Registration successful! Your account is pending approval from admin.',
                'user' => [
                    'id' => $companyUser->id,
                    'email' => $companyUser->email,
                    'company_name' => $company->name,
                    'status' => $companyUser->status,
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Login company user
     */
    public function login(Request $request)
    {
        \Log::info('[COMPANY-AUTH] Login attempt started', [
            'email' => $request->input('email'),
            'ip' => $request->ip(),
            'url' => $request->fullUrl(),
        ]);

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $companyUser = CompanyUser::where('email', $request->email)->first();

        if (!$companyUser || !Hash::check($request->password, $companyUser->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        // Check if account is active
        if ($companyUser->status === 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Your account is pending approval. Please wait for admin approval.',
            ], 403);
        }

        if ($companyUser->status === 'suspended') {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been suspended. Please contact support.',
            ], 403);
        }

        if ($companyUser->status === 'rejected') {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been rejected. Reason: ' . ($companyUser->rejection_reason ?? 'Not specified'),
            ], 403);
        }

        // Create token
        $token = $companyUser->createToken('company-auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $companyUser->id,
                'email' => $companyUser->email,
                'company_id' => $companyUser->company_id,
                'company_name' => $companyUser->company->name ?? '',
                'contact_person_name' => $companyUser->contact_person_name,
                'status' => $companyUser->status,
                'is_verified' => $companyUser->is_verified,
            ],
        ], 200);
    }

    /**
     * Get authenticated company user profile
     */
    public function profile(Request $request)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $companyUser->id,
                'email' => $companyUser->email,
                'contact_person_name' => $companyUser->contact_person_name,
                'contact_person_designation' => $companyUser->contact_person_designation,
                'phone' => $companyUser->phone,
                'status' => $companyUser->status,
                'is_verified' => $companyUser->is_verified,
            ],
            'company' => $company ? [
                'id' => $company->id,
                'name' => $company->name,
                'slug' => $company->slug,
                'description' => $company->description,
                'logo' => $company->logo,
                'website' => $company->website,
                'sector' => $company->sector,
                'founded_year' => $company->founded_year,
                'headquarters' => $company->headquarters,
                'ceo_name' => $company->ceo_name,
                'latest_valuation' => $company->latest_valuation,
                'funding_stage' => $company->funding_stage,
                'total_funding' => $company->total_funding,
                'linkedin_url' => $company->linkedin_url,
                'twitter_url' => $company->twitter_url,
                'facebook_url' => $company->facebook_url,
                'is_verified' => $company->is_verified,
                'profile_completed' => $company->profile_completed,
                'profile_completion_percentage' => $company->profile_completion_percentage,
                'status' => $company->status,
            ] : null,
        ], 200);
    }

    /**
     * Update company user profile
     */
    public function updateProfile(Request $request)
    {
        $companyUser = $request->user();

        $validator = Validator::make($request->all(), [
            'contact_person_name' => 'sometimes|string|max:255',
            'contact_person_designation' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $companyUser->update($request->only([
            'contact_person_name',
            'contact_person_designation',
            'phone',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'user' => $companyUser,
        ], 200);
    }

    /**
     * Change password
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $companyUser = $request->user();

        if (!Hash::check($request->current_password, $companyUser->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect',
            ], 401);
        }

        $companyUser->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully',
        ], 200);
    }

    /**
     * Logout company user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ], 200);
    }
}