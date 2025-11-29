<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class UserDashboardController extends Controller
{
    /**
     * Get Latest Announcements
     * Endpoint: /api/v1/announcements/latest
     * * Currently returns static data to prevent 500 errors if the 
     * 'announcements' table hasn't been migrated yet.
     */
    public function announcements(): JsonResponse
    {
        // Mock Data Strategy:
        // We return a standard structure so the frontend renders the widget correctly.
        return response()->json([
            'data' => [
                [
                    'id' => 1,
                    'title' => 'Diwali Investment Bonanza!',
                    'content' => 'Get 2% extra units on all investments above ₹50k. Limited time offer.',
                    'type' => 'info', // info, warning, alert
                    'created_at' => now()->subDays(2)->toIso8601String(),
                ],
                [
                    'id' => 2,
                    'title' => 'System Maintenance',
                    'content' => 'Scheduled maintenance on Sunday 2 AM - 4 AM. Please plan your transactions accordingly.',
                    'type' => 'warning',
                    'created_at' => now()->subDays(5)->toIso8601String(),
                ]
            ]
        ]);
    }

    /**
     * Get Active Offers
     * Endpoint: /api/v1/offers/active
     */
    public function offers(): JsonResponse
    {
        return response()->json([
            'data' => [
                [
                    'id' => 101,
                    'code' => 'WELCOME500',
                    'description' => 'Flat ₹500 off on your first SIP installment.',
                    'discount_amount' => 500,
                    'valid_until' => now()->addMonth()->toIso8601String(),
                ],
                [
                    'id' => 102,
                    'code' => 'REFER2X',
                    'description' => 'Double referral bonus for the next 48 hours!',
                    'discount_amount' => 0,
                    'valid_until' => now()->addDays(2)->toIso8601String(),
                ]
            ]
        ]);
    }
}