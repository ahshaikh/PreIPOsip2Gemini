<?php
// V-PHASE1-1730-017

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Get the authenticated user's profile.
     */
    public function show(Request $request)
    {
        $user = $request->user()->load('profile', 'kyc');
        return response()->json($user);
    }

    /**
     * Update the authenticated user's profile.
     */
    public function update(Request $request)
    {
        $user = $request->user();
        
        $validatedData = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'dob' => 'required|date|before:-18 years',
            'gender' => 'required|in:male,female,other',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'pincode' => 'required|string|size:6',
        ]);

        $user->profile->update($validatedData);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'profile' => $user->profile,
        ]);
    }
}