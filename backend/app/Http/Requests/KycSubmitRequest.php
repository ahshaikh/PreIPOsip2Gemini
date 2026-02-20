<?php
// V-PHASE1-1730-021 (Created) | V-FINAL-1730-424 (Logic Upgraded) | V-FINAL-1730-629 (Manual KYC - Added address_proof, photo, signature)

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\UserKyc; // <-- IMPORT MODEL FOR REGEX

class KycSubmitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization is handled by auth:sanctum middleware
    }

    public function rules(): array
    {
        // Use the centralized Regex from the model for consistency
        $panRegex = UserKyc::PAN_REGEX;
        
        // This is a simplified 12-digit regex for input, as Aadhaar can have spaces
        $aadhaarRegex = '/^\d{4}\s?\d{4}\s?\d{4}$/';
        
        $ifscRegex = '/^[A-Z]{4}0[A-Z0-9]{6}$/';

        return [
            // --- Text Fields ---
            'pan_number' => ['required', 'string', "regex:{$panRegex}"],
            'aadhaar_number' => ['required', 'string', "regex:{$aadhaarRegex}"],
            'demat_account' => 'required|string|min:8|max:100',
            'bank_account' => 'required|string|min:9|max:50',
            'bank_ifsc' => ['required', 'string', "regex:{$ifscRegex}"],
            'bank_name' => 'required|string|min:3|max:100',
            
            // --- File Uploads (FSD-KYC-001) - Manual Verification Required ---
            'aadhaar_front' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB
            'aadhaar_back' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'pan' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'bank_proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'demat_proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'address_proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'photo' => 'required|file|mimes:jpg,jpeg,png|max:2048', // 2MB for photo
            'signature' => 'required|file|mimes:jpg,jpeg,png|max:2048', // 2MB for signature
        ];
    }
}