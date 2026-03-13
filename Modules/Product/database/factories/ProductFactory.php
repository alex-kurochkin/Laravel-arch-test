<?php

declare(strict_types=1);

namespace Modules\Product\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Catalogue\Models\Category;
use Modules\Product\Models\Product;

/**
 * @extends Factory<Product>
 */
final class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->faker->optional(0.7)->paragraph(),
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'stock_quantity' => $this->faker->numberBetween(0, 100),
            'category_id' => null, // По умолчанию без категории
            'is_active' => $this->faker->boolean(80),
            'metadata' => null,
            'created_at' => $this->faker->dateTimeBetween('-1 year'),
            'updated_at' => fn (array $attributes): \DateTime => $attributes['created_at'],
        ];
    }

    /**
     * Указываем конкретную категорию для продукта
     */
    public function forCategory(Category $category): static
    {
        return $this->state(fn (array $attributes): array => [
            'category_id' => $category->id,
        ]);
    }

    /**
     * Создаем продукт со случайной категорией
     */
    public function withRandomCategory(): static
    {
        return $this->state(function (array $attributes): array {
            // Создаем категорию если её нет
            if (!Category::count()) {
                $category = Category::factory()->create();
            } else {
                $category = Category::inRandomOrder()->first();
            }

            return [
                'category_id' => $category->id,
            ];
        });
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function inStock(): static
    {
        return $this->state(fn (array $attributes): array => [
            'stock_quantity' => $this->faker->numberBetween(1, 100),
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes): array => [
            'stock_quantity' => 0,
        ]);
    }

    public function withPrice(float $price): static
    {
        return $this->state(fn (array $attributes): array => [
            'price' => $price,
        ]);
    }
}
