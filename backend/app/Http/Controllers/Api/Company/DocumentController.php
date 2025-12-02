<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /**
     * Get all documents for the company
     */
    public function index(Request $request)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        $query = CompanyDocument::where('company_id', $company->id)
            ->with('uploadedBy:id,contact_person_name');

        if ($request->filled('document_type')) {
            $query->ofType($request->document_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $documents = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $documents->items(),
            'pagination' => [
                'total' => $documents->total(),
                'per_page' => $documents->perPage(),
                'current_page' => $documents->currentPage(),
                'last_page' => $documents->lastPage(),
            ],
        ], 200);
    }

    /**
     * Upload a new document
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'document_type' => 'required|in:logo,banner,pitch_deck,investor_presentation,legal_document,certificate,agreement,other',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'required|file|max:20480', // 20MB max
            'is_public' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $companyUser = $request->user();
        $company = $companyUser->company;

        try {
            // Store the file
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $fileType = $file->getMimeType();
            $fileSize = $file->getSize();
            $path = $file->store('company-documents/' . $company->id, 'public');

            // Create the document record
            $document = CompanyDocument::create([
                'company_id' => $company->id,
                'uploaded_by' => $companyUser->id,
                'document_type' => $request->document_type,
                'title' => $request->title,
                'description' => $request->description,
                'file_path' => $path,
                'file_name' => $fileName,
                'file_type' => $fileType,
                'file_size' => $fileSize,
                'is_public' => $request->is_public ?? false,
                'status' => 'active',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document uploaded successfully',
                'document' => $document,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload document',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific document
     */
    public function show(Request $request, $id)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        $document = CompanyDocument::where('company_id', $company->id)
            ->where('id', $id)
            ->with('uploadedBy:id,contact_person_name')
            ->first();

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'document' => $document,
            'download_url' => Storage::url($document->file_path),
        ], 200);
    }

    /**
     * Update document details
     */
    public function update(Request $request, $id)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        $document = CompanyDocument::where('company_id', $company->id)
            ->where('id', $id)
            ->first();

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'is_public' => 'sometimes|boolean',
            'status' => 'sometimes|in:active,archived',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $document->update($request->only(['title', 'description', 'is_public', 'status']));

        return response()->json([
            'success' => true,
            'message' => 'Document updated successfully',
            'document' => $document->fresh(),
        ], 200);
    }

    /**
     * Delete a document
     */
    public function destroy(Request $request, $id)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        $document = CompanyDocument::where('company_id', $company->id)
            ->where('id', $id)
            ->first();

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found',
            ], 404);
        }

        try {
            // Delete the file from storage
            if (Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }

            $document->delete();

            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete document',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download a document
     */
    public function download(Request $request, $id)
    {
        $companyUser = $request->user();
        $company = $companyUser->company;

        $document = CompanyDocument::where('company_id', $company->id)
            ->where('id', $id)
            ->first();

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found',
            ], 404);
        }

        if (!Storage::disk('public')->exists($document->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found',
            ], 404);
        }

        return Storage::disk('public')->download($document->file_path, $document->file_name);
    }
}
