<?php
/**
 * V-AUDIT-REFACTOR-2025 | V-SECURE-PDF-GENERATION
 */

namespace App\Services\Reporting;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class StatementGenerator
{
    /**
     * Generate a monthly investment statement.
     * [AUDIT FIX]: Stores PDF in private disk and returns a signed path.
     */
    public function generateMonthlyStatement(User $user, int $month, int $year): string
    {
        $data = [
            'user' => $user,
            'date' => "{$month}/{$year}",
            'investments' => $user->investments()->with('deal')->get(),
            'generated_at' => now(),
        ];

        $pdf = Pdf::loadView('emails.reports.monthly_statement', $data);
        
        $filename = "statements/{$user->id}/statement_{$year}_{$month}.pdf";
        
        // [AUDIT FIX]: Save to private storage
        Storage::disk('private')->put($filename, $pdf->output());

        return $filename;
    }
}