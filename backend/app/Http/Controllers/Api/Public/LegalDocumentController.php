<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\LegalAgreement;
use App\Models\UserLegalAcceptance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class LegalDocumentController extends Controller
{
    /**
     * Get all active legal documents
     */
    public function index()
    {
        $documents = LegalAgreement::active()
            ->select('id', 'type', 'title', 'description', 'version', 'status', 'effective_date', 'updated_at')
            ->orderBy('type')
            ->get();

        return response()->json($documents);
    }

    /**
     * Get active legal document by type
     */
    public function show($type)
    {
        $document = LegalAgreement::active()
            ->byType($type)
            ->first();

        if (!$document) {
            return response()->json([
                'message' => 'Legal document not found or not active'
            ], 404);
        }

        return response()->json($document);
    }

    /**
     * Get user's acceptance status for a document
     */
    public function acceptanceStatus(Request $request, $type)
    {
        if (!Auth::check()) {
            return response()->json([
                'accepted' => false,
                'message' => 'Not authenticated'
            ], 401);
        }

        $document = LegalAgreement::active()->byType($type)->first();

        if (!$document) {
            return response()->json([
                'message' => 'Legal document not found'
            ], 404);
        }

        $acceptance = UserLegalAcceptance::forUser(Auth::id())
            ->forDocument($type)
            ->where('accepted_version', $document->version)
            ->latest()
            ->first();

        return response()->json([
            'accepted' => $acceptance !== null,
            'accepted_at' => $acceptance ? $acceptance->created_at : null,
            'accepted_version' => $acceptance ? $acceptance->accepted_version : null,
            'current_version' => $document->version,
            'needs_reacceptance' => $acceptance && $acceptance->accepted_version !== $document->version,
        ]);
    }

    /**
     * Record user's acceptance of a document
     */
    public function accept(Request $request, $type)
    {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Authentication required'
            ], 401);
        }

        $document = LegalAgreement::active()->byType($type)->first();

        if (!$document) {
            return response()->json([
                'message' => 'Legal document not found'
            ], 404);
        }

        // Check if already accepted this version
        $existingAcceptance = UserLegalAcceptance::forUser(Auth::id())
            ->forDocument($type)
            ->where('accepted_version', $document->version)
            ->first();

        if ($existingAcceptance) {
            return response()->json([
                'message' => 'You have already accepted this version',
                'accepted_at' => $existingAcceptance->created_at,
            ]);
        }

        // Record the acceptance
        $acceptance = UserLegalAcceptance::create([
            'user_id' => Auth::id(),
            'legal_agreement_id' => $document->id,
            'document_type' => $type,
            'accepted_version' => $document->version,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Log this in audit trail
        $document->logAudit(
            'accepted',
            "User " . Auth::user()->name . " accepted version {$document->version}",
            null,
            Auth::user()
        );

        return response()->json([
            'message' => 'Acceptance recorded successfully',
            'accepted_at' => $acceptance->created_at,
            'version' => $document->version,
        ]);
    }

    /**
     * Download legal document as PDF
     */
    public function download($type)
    {
        $document = LegalAgreement::active()->byType($type)->first();

        if (!$document) {
            return response()->json([
                'message' => 'Legal document not found'
            ], 404);
        }

        // Log the download
        $user = Auth::check() ? Auth::user() : null;
        $document->logAudit(
            'downloaded',
            $user ? "Downloaded by " . $user->name : "Downloaded by guest",
            null,
            $user
        );

        // Generate PDF
        $pdf = Pdf::loadView('pdf.legal-document', [
            'document' => $document,
        ]);

        return $pdf->download($type . '-v' . $document->version . '.pdf');
    }
}
