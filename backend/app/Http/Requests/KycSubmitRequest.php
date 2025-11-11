<?php
// V-PHASE1-1730-021

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KycSubmitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pan_number' => 'required|string|regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
            'aadhaar_number' => 'required|string|regex:/^[0-9]{12}$/',
            'demat_account' => 'required|string|max:100',
            'bank_account' => 'required|string|max:50',
            'bank_ifsc' => 'required|string|regex:/^[A-Z]{4}0[A-Z0-9]{6}$/',
            
            'aadhaar_front' => 'required|file|mimes:jpg,png,pdf|max:5120', // 5MB
            'aadhaar_back' => 'required|file|mimes:jpg,png,pdf|max:5120',
            'pan' => 'required|file|mimes:jpg,png,pdf|max:5120',
            'bank_proof' => 'required|file|mimes:jpg,png,pdf|max:5120',
            'demat_proof' => 'required|file|mimes:jpg,png,pdf|max:5120',
        ];
    }
}