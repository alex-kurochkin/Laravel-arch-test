<?php

declare(strict_types=1);

namespace Modules\Product\Tests\Traits;

use Modules\Catalogue\Models\Category;
use Modules\Product\Models\Product;

trait WithProductFactory
{
    protected function createProduct(array $attributes = []): Product
    {
        return Product::factory()->create($attributes);
    }

    protected function createProducts(int $count, array $attributes = []): iterable
    {
        return Product::factory()->count($count)->create($attributes);
    }

    protected function createProductWithCategory(
        array $productAttributes = [],
        array $categoryAttributes = []
    ): Product {
        $category = $this->createCategory($categoryAttributes);

        return Product::factory()
            ->forCategory($category)
            ->create($productAttributes);
    }

    protected function createProductWithCategoryObject(
        Category $category,
        array $productAttributes = []
    ): Product {
        return Product::factory()
            ->forCategory($category)
            ->create($productAttributes);
    }

    protected function createProductWithImages(int $imageCount = 1, array $productAttributes = []): Product
    {
        return Product::factory()
            ->hasImages($imageCount)
            ->create($productAttributes);
    }

    protected function createActiveProduct(array $attributes = []): Product
    {
        return Product::factory()
            ->active()
            ->create($attributes);
    }

    protected function createInactiveProduct(array $attributes = []): Product
    {
        return Product::factory()
            ->inactive()
            ->create($attributes);
    }

    protected function createProductWithStock(int $stock = 5, array $attributes = []): Product
    {
        return Product::factory()->create(array_merge(
            ['stock_quantity' => $stock],
            $attributes
        ));
    }

    protected function createCategory(array $attributes = []): Category
    {
        return Category::factory()->create($attributes);
    }

    protected function createCategories(int $count, array $attributes = []): iterable
    {
        return Category::factory()->count($count)->create($attributes);
    }

    protected function createCategoryTree(int $depth = 2, int $childrenPerLevel = 2): array
    {
        $root = $this->createCategory(['name' => 'Root Category']);
        $allCategories = [$root];

        $currentLevel = [$root];

        for ($level = 1; $level < $depth; $level++) {
            $nextLevel = [];

            foreach ($currentLevel as $parent) {
                for ($i = 0; $i < $childrenPerLevel; $i++) {
                    $child = $this->createCategory([
                        'name' => "Level {$level} Child {$i} of {$parent->id}",
                        'parent_id' => $parent->id,
                    ]);
                    $nextLevel[] = $child;
                    $allCategories[] = $child;
                }
            }

            $currentLevel = $nextLevel;
        }

        return [
            'root' => $root,
            'all' => $allCategories,
        ];
    }
}
