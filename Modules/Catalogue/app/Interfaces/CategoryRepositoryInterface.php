<?php

declare(strict_types=1);

namespace Modules\Catalogue\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Catalogue\DTOs\CategoryData;
use Modules\Catalogue\Models\Category;

interface CategoryRepositoryInterface
{
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator;

    public function findById(int $id): Category|null;

    public function findByIdOrFail(int $id): Category;

    public function findBySlug(string $slug): Category|null;

    public function create(CategoryData $data): Category;

    public function update(int $id, CategoryData $data): Category;

    public function delete(int $id): bool;

    public function getActiveCategories(): Collection;
}
