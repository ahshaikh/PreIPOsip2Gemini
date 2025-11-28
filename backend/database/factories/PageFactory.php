<?php

namespace Database\Factories;

use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PageFactory extends Factory
{
    protected $model = Page::class;

    public function definition(): array
    {
        $title = $this->faker->unique()->sentence(3);

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'content' => $this->faker->paragraphs(5, true),
            'seo_meta' => json_encode([
                'meta_title' => $title,
                'meta_description' => $this->faker->sentence(10),
                'meta_keywords' => implode(', ', $this->faker->words(5)),
            ]),
            'status' => $this->faker->randomElement(['draft', 'published']),
            'current_version' => 1,
            'require_user_acceptance' => $this->faker->boolean(10),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
        ]);
    }

    public function requiresAcceptance(): static
    {
        return $this->state(fn (array $attributes) => [
            'require_user_acceptance' => true,
            'status' => 'published',
        ]);
    }
}
