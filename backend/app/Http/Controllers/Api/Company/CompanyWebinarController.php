<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyWebinar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CompanyWebinarController extends Controller
{
    /**
     * Get all webinars
     */
    public function index(Request $request)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        // FIX: Add null check to prevent crash if company relationship missing
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
            ], 404);
        }


        $query = CompanyWebinar::where('company_id', $company->id)
            ->with('registrations');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $webinars = $query->orderBy('scheduled_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $webinars->items(),
            'pagination' => [
                'total' => $webinars->total(),
                'per_page' => $webinars->perPage(),
                'current_page' => $webinars->currentPage(),
                'last_page' => $webinars->lastPage(),
            ],
        ], 200);
    }

    /**
     * Create webinar
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:webinar,investor_call,ama,product_demo',
            'scheduled_at' => 'required|date|after:now',
            'duration_minutes' => 'required|integer|min:15|max:480',
            'meeting_link' => 'nullable|url',
            'meeting_id' => 'nullable|string',
            'meeting_password' => 'nullable|string',
            'max_participants' => 'nullable|integer|min:1',
            'speakers' => 'nullable|array',
            'agenda' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $companyUser = $request->user();
        $company = $companyUser->company;

        // FIX: Add null check to prevent crash if company relationship missing
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
            ], 404);
        }


        $webinar = CompanyWebinar::create([
            'company_id' => $company->id,
            'created_by' => $companyUser->id,
            'title' => $request->title,
            'description' => $request->description,
            'type' => $request->type,
            'scheduled_at' => $request->scheduled_at,
            'duration_minutes' => $request->duration_minutes,
            'meeting_link' => $request->meeting_link,
            'meeting_id' => $request->meeting_id,
            'meeting_password' => $request->meeting_password,
            'max_participants' => $request->max_participants,
            'speakers' => $request->speakers,
            'agenda' => $request->agenda,
            'status' => 'scheduled',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Webinar created successfully',
            'webinar' => $webinar,
        ], 201);
    }

    /**
     * Get single webinar
     */
    public function show(Request $request, $id)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        // FIX: Add null check to prevent crash if company relationship missing
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
            ], 404);
        }


        $webinar = CompanyWebinar::where('company_id', $company->id)
            ->where('id', $id)
            ->with('registrations')
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'webinar' => $webinar,
        ], 200);
    }

    /**
     * Update webinar
     */
    public function update(Request $request, $id)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        // FIX: Add null check to prevent crash if company relationship missing
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
            ], 404);
        }


        $webinar = CompanyWebinar::where('company_id', $company->id)
            ->where('id', $id)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|in:webinar,investor_call,ama,product_demo',
            'scheduled_at' => 'sometimes|date',
            'duration_minutes' => 'sometimes|integer|min:15|max:480',
            'meeting_link' => 'nullable|url',
            'meeting_id' => 'nullable|string',
            'meeting_password' => 'nullable|string',
            'max_participants' => 'nullable|integer|min:1',
            'speakers' => 'nullable|array',
            'agenda' => 'nullable|string',
            'status' => 'sometimes|in:scheduled,live,completed,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $webinar->update($request->only([
            'title', 'description', 'type', 'scheduled_at', 'duration_minutes',
            'meeting_link', 'meeting_id', 'meeting_password', 'max_participants',
            'speakers', 'agenda', 'status'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Webinar updated successfully',
            'webinar' => $webinar->fresh(),
        ], 200);
    }

    /**
     * Get webinar registrations
     */
    public function registrations(Request $request, $id)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        // FIX: Add null check to prevent crash if company relationship missing
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
            ], 404);
        }


        $webinar = CompanyWebinar::where('company_id', $company->id)
            ->where('id', $id)
            ->with('registrations.user')
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'webinar' => $webinar,
            'registrations' => $webinar->registrations,
        ], 200);
    }

    /**
     * Upload recording
     */
    public function uploadRecording(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'recording_url' => 'required|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $companyUser = $request->user();
        $company = $companyUser->company;

        // FIX: Add null check to prevent crash if company relationship missing
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
            ], 404);
        }


        $webinar = CompanyWebinar::where('company_id', $company->id)
            ->where('id', $id)
            ->firstOrFail();

        $webinar->update([
            'recording_url' => $request->recording_url,
            'recording_available' => true,
            'status' => 'completed',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Recording uploaded successfully',
            'webinar' => $webinar->fresh(),
        ], 200);
    }

    /**
     * Delete webinar
     */
    public function destroy(Request $request, $id)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        // FIX: Add null check to prevent crash if company relationship missing
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
            ], 404);
        }


        $webinar = CompanyWebinar::where('company_id', $company->id)
            ->where('id', $id)
            ->firstOrFail();

        // Can't delete live or completed webinars with registrations
        if (in_array($webinar->status, ['live', 'completed']) && $webinar->registered_count > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete webinars with registrations. Cancel it instead.',
            ], 400);
        }

        $webinar->delete();

        return response()->json([
            'success' => true,
            'message' => 'Webinar deleted successfully',
        ], 200);
    }

    /**
     * Get statistics
     */
    public function statistics(Request $request)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        // FIX: Add null check to prevent crash if company relationship missing
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
            ], 404);
        }


        $stats = [
            'total' => CompanyWebinar::where('company_id', $company->id)->count(),
            'upcoming' => CompanyWebinar::where('company_id', $company->id)->upcoming()->count(),
            'completed' => CompanyWebinar::where('company_id', $company->id)->completed()->count(),
            'total_registrations' => CompanyWebinar::where('company_id', $company->id)->sum('registered_count'),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ], 200);
    }
}
