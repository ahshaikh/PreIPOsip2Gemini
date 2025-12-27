<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * BulkPurchase Store Request Validation.
 */
class StoreBulkPurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Required core fields
            'company_id' => 'required|exists:companies,id',
            'product_id' => 'required|exists:products,id',
            'purchase_date' => 'required|date',

            // Share Purchase Details
            'total_shares_purchased' => 'required|numeric|min:1',
            'price_per_share' => 'required|numeric|min:0.01',
            'face_value_per_share' => 'required|numeric|min:0.01',

            // Calculated fields (auto-computed if not provided)
            'total_value_received' => 'nullable|numeric|min:0',
            'value_remaining' => 'nullable|numeric|min:0',

            // Payment & Status
            'purchase_method' => 'nullable|in:cash,bank_transfer,company_listing,negotiated',
            'payment_status' => 'nullable|in:pending,completed,failed,partial',

            // Additional Info
            'notes' => 'nullable|string',
            'purchase_documents' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'Company is required for bulk purchase.',
            'product_id.required' => 'Product is required for bulk purchase.',
            'purchase_date.required' => 'Purchase date is required.',
            'total_shares_purchased.required' => 'Total shares purchased is required.',
            'total_shares_purchased.min' => 'Must purchase at least 1 share.',
            'price_per_share.required' => 'Price per share is required.',
            'price_per_share.min' => 'Price per share must be greater than zero.',
            'face_value_per_share.required' => 'Face value per share is required.',
        ];
    }

    protected function prepareForValidation()
    {
        // Auto-calculate total value if not provided
        if (!$this->has('total_value_received') && $this->has('total_shares_purchased') && $this->has('price_per_share')) {
            $totalValue = $this->total_shares_purchased * $this->price_per_share;
            $this->merge([
                'total_value_received' => $totalValue,
                'value_remaining' => $this->value_remaining ?? $totalValue,
            ]);
        }

        // Set defaults
        $this->merge([
            'purchase_method' => $this->purchase_method ?? 'bank_transfer',
            'payment_status' => $this->payment_status ?? 'completed',
        ]);
    }
}
