<?php

declare(strict_types=1);

namespace Modules\Product\Tests\Traits;

use Modules\Catalogue\Models\Category;

trait WithCategories
{
    protected function createCategory(array $attributes = []): Category
    {
        $defaults = [
            'name' => 'Test Category ' . uniqid(),
            'slug' => 'test-category-' . uniqid(),
            'is_active' => true,
            'sort_order' => 0,
        ];

        return Category::create(array_merge($defaults, $attributes));
    }

    protected function createCategoryTree(int $levels = 2, int $childrenPerLevel = 2): array
    {
        $root = $this->createCategory(['name' => 'Root Category']);
        $allCategories = [$root];

        $currentLevel = [$root];

        for ($level = 1; $level < $levels; $level++) {
            $nextLevel = [];

            foreach ($currentLevel as $parent) {
                for ($i = 0; $i < $childrenPerLevel; $i++) {
                    $child = $this->createCategory([
                        'name' => "Category Level {$level} Child {$i} of {$parent->id}",
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
            'tree' => $this->buildTree($allCategories)
        ];
    }

    protected function buildTree(iterable $categories, ?int $parentId = null): array
    {
        $tree = [];

        foreach ($categories as $category) {
            if ($category->parent_id === $parentId) {
                $children = $this->buildTree($categories, $category->id);
                $tree[] = [
                    'category' => $category,
                    'children' => $children,
                ];
            }
        }

        return $tree;
    }

    protected function assertCategoryExists(array $attributes): void
    {
        $this->assertDatabaseHas('categories', $attributes);
    }

    protected function assertCategoryMissing(array $attributes): void
    {
        $this->assertDatabaseMissing('categories', $attributes);
    }
}
