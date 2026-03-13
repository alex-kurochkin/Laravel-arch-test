<?php

declare(strict_types=1);

namespace Modules\Product\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Product\DTOs\ProductData;
use Modules\Product\Models\Product;

interface ProductRepositoryInterface
{
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator;

    public function findById(int $id): Product|null;

    public function findByIdOrFail(int $id): Product;

    public function findBySlug(string $slug): Product|null;

    public function create(ProductData $data): Product;

    public function update(int $id, ProductData $data): Product;

    public function delete(int $id): bool;

    public function getActiveProducts(): Collection;

    public function updateStock(int $id, int $quantity): Product;
}
