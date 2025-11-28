<?php

namespace Database\Factories;

use App\Models\ProductFounder;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFounderFactory extends Factory
{
    protected $model = ProductFounder::class;

    public function definition(): array
    {
        $titles = ['CEO & Co-Founder', 'CTO & Co-Founder', 'COO & Co-Founder', 'Chief Product Officer', 'Head of Engineering'];

        return [
            'product_id' => Product::factory(),
            'name' => $this->faker->name(),
            'title' => $this->faker->randomElement($titles),
            'photo_url' => $this->faker->imageUrl(300, 300, 'people'),
            'linkedin_url' => 'https://linkedin.com/in/' . $this->faker->userName(),
        ];
    }
}
