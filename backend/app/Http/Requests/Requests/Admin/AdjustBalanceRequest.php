<?php
// V-FINAL-1730-293

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdjustBalanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Ensure the user is an admin
        return $this->user()->hasRole(['Admin', 'Super Admin', 'Finance Manager']);
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:credit,debit',
            'amount' => 'required|numeric|min:0.01|max:1000000', // Reasonable limits
            'description' => 'required|string|min:5|max:255',
        ];
    }
}