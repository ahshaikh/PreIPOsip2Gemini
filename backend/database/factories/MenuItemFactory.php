<?php

namespace Database\Factories;

use App\Models\MenuItem;
use App\Models\Menu;
use Illuminate\Database\Eloquent\Factories\Factory;

class MenuItemFactory extends Factory
{
    protected $model = MenuItem::class;

    public function definition(): array
    {
        return [
            'menu_id' => Menu::factory(),
            'label' => $this->faker->words(2, true),
            'url' => '/' . $this->faker->slug(),
            'target' => '_self',
            'icon' => $this->faker->optional()->randomElement(['home', 'user', 'cog', 'help']),
            'parent_id' => null,
            'order' => $this->faker->numberBetween(0, 10),
            'is_active' => true,
        ];
    }
}
