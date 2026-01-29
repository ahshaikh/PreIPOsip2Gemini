<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Authorization is handled by the ProductPolicy, checked in the controller.
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
            'name' => 'sometimes|required|string|max:255',
            'sector' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'face_value_per_unit' => 'sometimes|required|numeric|min:0.01',
            'current_market_price' => 'sometimes|required|numeric|min:0.01',
            'min_investment' => 'sometimes|nullable|numeric|min:0',
            'expected_ipo_date' => 'sometimes|nullable|date',
        ];
    }
}