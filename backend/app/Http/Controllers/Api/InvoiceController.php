<?php
// V-FINAL-1730-202

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function download(Request $request, Payment $payment)
    {
        // Security: Ensure user owns the payment OR is an admin
        $user = $request->user();
        if ($user->id !== $payment->user_id && !$user->hasRole(['admin', 'super-admin'])) {
            abort(403);
        }

        $payment->load(['user.profile', 'subscription.plan']);

        $data = [
            'payment' => $payment,
            'user' => $payment->user,
            'plan' => $payment->subscription->plan,
            'company' => [
                'name' => setting('site_name', 'PreIPO SIP'),
                'address' => '123 Financial District, Mumbai, India',
                'gst' => '27AABCU9603R1ZM',
                'website' => env('FRONTEND_URL')
            ]
        ];

        $pdf = Pdf::loadView('invoices.receipt', $data);
        
        return $pdf->download("receipt-{$payment->id}.pdf");
    }
}