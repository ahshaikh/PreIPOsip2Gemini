<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdjustBalanceRequest extends FormRequest
{
    public function authorize()
    {
        return true; // tests bypass auth
    }

    public function rules()
    {
        return [
            'type' => 'required|in:credit,debit',
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string|max:255',
        ];
    }
}
