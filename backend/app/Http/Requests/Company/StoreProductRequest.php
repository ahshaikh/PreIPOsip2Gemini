<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Authorization is handled by the ProductPolicy
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'sector' => 'required|string|max:255',
            'description' => 'nullable|string',
            'face_value_per_unit' => 'required|numeric|min:0.01',
            'current_market_price' => 'required|numeric|min:0.01',
            'min_investment' => 'nullable|numeric|min:0',
            'expected_ipo_date' => 'nullable|date',
        ];
    }
}