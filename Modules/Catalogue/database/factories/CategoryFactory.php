<?php

declare(strict_types=1);

namespace Modules\Catalogue\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Catalogue\Models\Category;

/**
 * @extends Factory<Category>
 */
final class CategoryFactory extends Factory
{
    protected $model = Category::class;

    #[\Override]
    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->faker->optional()->paragraph(),
            'parent_id' => null,
            'sort_order' => $this->faker->numberBetween(0, 100),
            'is_active' => $this->faker->boolean(90),
            'created_at' => $this->faker->dateTimeBetween('-1 year'),
            'updated_at' => static fn (array $attributes): \DateTime => $attributes['created_at'],
        ];
    }

    public function active(): static
    {
        return $this->state(static fn (array $attributes): array => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(static fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function withParent(): static
    {
        return $this->state(static fn (array $attributes): array => [
            'parent_id' => Category::factory(),
        ]);
    }

    public function root(): static
    {
        return $this->state(static fn (array $attributes): array => [
            'parent_id' => null,
        ]);
    }
}
