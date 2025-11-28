<?php

namespace Database\Factories;

use App\Models\KbArticle;
use App\Models\KbCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class KbArticleFactory extends Factory
{
    protected $model = KbArticle::class;

    public function definition(): array
    {
        $title = $this->faker->unique()->sentence(5);

        return [
            'kb_category_id' => KbCategory::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'content' => $this->faker->paragraphs(5, true),
            'excerpt' => $this->faker->sentence(15),
            'author_id' => User::factory(),
            'status' => $this->faker->randomElement(['draft', 'published']),
            'view_count' => $this->faker->numberBetween(0, 1000),
            'is_featured' => $this->faker->boolean(20),
            'order' => $this->faker->numberBetween(0, 10),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
            'status' => 'published',
        ]);
    }
}
