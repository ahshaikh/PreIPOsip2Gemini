<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Get the user ID from the route parameter
        $userId = $this->route('user')->id;

        return [
            'username' => ['sometimes', 'string', 'alpha_dash', 'min:3', 'max:50', Rule::unique('users')->ignore($userId)],
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($userId)],
            'mobile' => ['sometimes', 'string', 'regex:/^[0-9]{10}$/', Rule::unique('users')->ignore($userId)],
            'status' => 'sometimes|in:active,suspended,blocked',
            'password' => 'sometimes|string|min:8',
            'profile' => 'sometimes|array',
            'profile.first_name' => 'sometimes|string|max:100',
            'profile.last_name' => 'sometimes|string|max:100',
            'profile.city' => 'sometimes|string|max:100',
            'profile.state' => 'sometimes|string|max:100',
            'profile.address' => 'sometimes|string',
        ];
    }
}