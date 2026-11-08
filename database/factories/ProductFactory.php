<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->words(3, true)),
            'description' => fake()->paragraph(),
            'base_price' => fake()->randomFloat(2, 5, 500),
            'thumbnail' => null,
            'sku' => fake()->unique()->bothify('SKU-####-??'),
            'created_by' => User::factory()->admin(),
        ];
    }
}
