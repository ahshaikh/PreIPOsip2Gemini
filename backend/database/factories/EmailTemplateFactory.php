<?php
// V-WAVE2-FIX: Created missing factory for EmailTemplate model

namespace Database\Factories;

use App\Models\EmailTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmailTemplateFactory extends Factory
{
    protected $model = EmailTemplate::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);

        return [
            'name' => ucwords($name),
            'slug' => \Illuminate\Support\Str::slug($name),
            'subject' => $this->faker->sentence(),
            'body' => $this->faker->paragraphs(3, true),
            'variables' => ['name', 'email', 'amount'],
        ];
    }

    /**
     * Template for payment confirmation emails.
     */
    public function paymentConfirmation(): static
    {
        return $this->state(fn(array $attributes) => [
            'name' => 'Payment Confirmation',
            'slug' => 'payment-confirmation',
            'subject' => 'Your payment of {{amount}} has been received',
            'body' => 'Dear {{name}}, your payment has been successfully processed.',
            'variables' => ['name', 'amount', 'payment_id'],
        ]);
    }

    /**
     * Template for bonus credited emails.
     */
    public function bonusCredited(): static
    {
        return $this->state(fn(array $attributes) => [
            'name' => 'Bonus Credited',
            'slug' => 'bonus-credited',
            'subject' => 'Bonus of {{amount}} credited to your wallet',
            'body' => 'Dear {{name}}, a bonus of {{amount}} has been credited.',
            'variables' => ['name', 'amount', 'bonus_type'],
        ]);
    }
}
