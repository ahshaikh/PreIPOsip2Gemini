<?php

namespace Database\Factories;

use App\Models\KycDocument;
use App\Models\UserKyc;
use Illuminate\Database\Eloquent\Factories\Factory;

class KycDocumentFactory extends Factory
{
    protected $model = KycDocument::class;

    public function definition(): array
    {
        $docTypes = ['pan_card', 'aadhaar_front', 'aadhaar_back', 'bank_statement', 'demat_proof'];

        return [
            'user_kyc_id' => UserKyc::factory(),
            'doc_type' => $this->faker->randomElement($docTypes),
            'file_path' => 'kyc/' . $this->faker->uuid() . '.pdf',
            'file_name' => $this->faker->word() . '.pdf',
            'mime_type' => 'application/pdf',
            'status' => 'pending',
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'verified_at' => now(),
        ]);
    }
}
