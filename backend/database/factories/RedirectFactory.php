<?php

namespace Database\Factories;

use App\Models\Redirect;
use Illuminate\Database\Eloquent\Factories\Factory;

class RedirectFactory extends Factory
{
    protected $model = Redirect::class;

    public function definition(): array
    {
        return [
            'from_path' => '/' . $this->faker->unique()->slug(),
            'to_path' => '/' . $this->faker->slug(),
            'status_code' => $this->faker->randomElement([301, 302]),
            'is_active' => true,
        ];
    }
}
