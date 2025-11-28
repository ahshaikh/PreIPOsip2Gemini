<?php

namespace Database\Factories;

use App\Models\KbCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class KbCategoryFactory extends Factory
{
    protected $model = KbCategory::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->faker->optional()->sentence(),
            'icon' => $this->faker->optional()->randomElement(['book', 'help-circle', 'info', 'file-text']),
            'parent_id' => null,
            'order' => $this->faker->numberBetween(0, 10),
            'is_active' => true,
        ];
    }
}
