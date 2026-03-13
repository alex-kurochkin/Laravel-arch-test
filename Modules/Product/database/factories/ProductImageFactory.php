<?php

declare(strict_types=1);

namespace Modules\Product\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductImage;

/**
 * @extends Factory<ProductImage>
 */
final class ProductImageFactory extends Factory
{
    protected $model = ProductImage::class;

    #[\Override] public function definition(): array
    {
        $productId = Product::factory();
        $isMain = $this->faker->boolean(20);

        return [
            'product_id' => $productId,
            'path' => 'products/' . $this->faker->uuid() . '.jpg',
            'alt' => $this->faker->optional()->words(3, true),
            'sort_order' => $this->faker->numberBetween(0, 100),
            'is_main' => $isMain,
            'created_at' => $this->faker->dateTimeBetween('-1 year'),
            'updated_at' => static fn (array $attributes): \DateTime => $attributes['created_at'],
        ];
    }

    public function forProduct(Product $product): static
    {
        return $this->state(static fn (array $attributes): array => [
            'product_id' => $product->id,
        ]);
    }

    public function main(): static
    {
        return $this->state(static fn (array $attributes): array => [
            'is_main' => true,
        ]);
    }

    public function secondary(): static
    {
        return $this->state(static fn (array $attributes): array => [
            'is_main' => false,
        ]);
    }
}
