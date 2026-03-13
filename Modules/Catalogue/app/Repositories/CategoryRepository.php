<?php

declare(strict_types=1);

namespace Modules\Catalogue\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Catalogue\DTOs\CategoryData;
use Modules\Catalogue\Exceptions\CategoryNotFoundException;
use Modules\Catalogue\Interfaces\CategoryRepositoryInterface;
use Modules\Catalogue\Models\Category;
use Psr\Log\LoggerInterface;
use RuntimeException;

final readonly class CategoryRepository implements CategoryRepositoryInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator
    {
        try {
            return Category::query()
                ->with('parent')
                ->orderBy('sort_order')
                ->paginate($perPage);
        } catch (RuntimeException $e) {
            $this->logger->error('Failed to fetch paginated categories', [
                'error' => $e->getMessage(),
                'per_page' => $perPage,
            ]);

            throw new RuntimeException('Unable to retrieve categories', 0, $e);
        }
    }

    public function findById(int $id): Category|null
    {
        try {
            return Category::query()
                ->with('parent', 'children')
                ->find($id);
        } catch (RuntimeException $e) {
            $this->logger->error('Failed to find category by ID', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Unable to find category', 0, $e);
        }
    }

    public function findByIdOrFail(int $id): Category
    {
        $category = $this->findById($id);

        if (null === $category) {
            throw new CategoryNotFoundException("Category with ID {$id} not found");
        }

        return $category;
    }

    public function findBySlug(string $slug): Category|null
    {
        try {
            return Category::query()
                ->with('parent', 'children')
                ->where('slug', $slug)
                ->first();
        } catch (RuntimeException $e) {
            $this->logger->error('Failed to find category by slug', [
                'slug' => $slug,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Unable to find category', 0, $e);
        }
    }

    public function create(CategoryData $data): Category
    {
        try {
            /** @var Category $category */
            $category = Category::query()->create($data->toArray());

            $this->logger->info('Category created successfully', [
                'id' => $category->id,
                'name' => $category->name,
            ]);

            return $category->load('parent', 'children');
        } catch (RuntimeException $e) {
            $this->logger->error('Failed to create category', [
                'data' => $data->toArray(),
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Unable to create category', 0, $e);
        }
    }

    public function update(int $id, CategoryData $data): Category
    {
        $category = $this->findByIdOrFail($id);

        try {
            $category->update($data->toArray());

            $this->logger->info('Category updated successfully', [
                'id' => $category->id,
                'name' => $category->name,
            ]);

            return $category->fresh('parent', 'children');
        } catch (RuntimeException $e) {
            $this->logger->error('Failed to update category', [
                'id' => $id,
                'data' => $data->toArray(),
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Unable to update category', 0, $e);
        }
    }

    public function delete(int $id): bool
    {
        $category = $this->findByIdOrFail($id);

        try {
            $deleted = $category->delete();

            if ($deleted) {
                $this->logger->info('Category deleted successfully', [
                    'id' => $id,
                    'name' => $category->name,
                ]);
            }

            return $deleted;
        } catch (RuntimeException $e) {
            $this->logger->error('Failed to delete category', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Unable to delete category', 0, $e);
        }
    }

    public function getActiveCategories(): Collection
    {
        try {
            return Category::active()
                ->with('parent')
                ->orderBy('sort_order')
                ->get();
        } catch (RuntimeException $e) {
            $this->logger->error('Failed to fetch active categories', [
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Unable to retrieve active categories', 0, $e);
        }
    }

    /**
     * Загрузить отношения в запрос
     */
    public function getChildren(int $parentId): Collection
    {
        try {
            return Category::query()
                ->with('children')
                ->where('parent_id', $parentId)
                ->orderBy('sort_order')
                ->get();
        } catch (RuntimeException $e) {
            $this->logger->error('Failed to fetch child categories', [
                'parent_id' => $parentId,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Unable to retrieve child categories', 0, $e);
        }
    }

    /**
     * Получить категорию с отношениями
     */
    public function getCategoryWithRelationsOrFail(int $id, array $options = []): Category|null
    {
        try {
            $query = Category::query()->select()->where(['id' => $id]);

            $this->loadRelations($query, $options);

            return $query->first($id);
        } catch (RuntimeException $e) {
            $this->logger->error('Failed to fetch category with relations', [
                'id' => $id,
                'error' => $e->getMessage(),
                'options' => $options,
            ]);
            throw new RuntimeException('Unable to retrieve category', 0, $e);
        }
    }

    /**
     * Загрузить отношения в запрос
     */
    private function loadRelations(Builder $query, array $options): void
    {
        if (in_array('with-parent', $options, true)) {
            $query->with('parent');
        }

        if (in_array('with-children', $options, true)) {
            $query->with('children');
        }

        if (in_array('with-ancestors', $options, true)) {
            $query->with('ancestors');
        }

        if (in_array('with-descendants', $options, true)) {
            $query->with('descendants');
        }
    }
}
