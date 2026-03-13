<?php

declare(strict_types=1);

namespace Modules\Product\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Product\DTOs\ProductData;
use Modules\Product\Exceptions\ProductNotFoundException;
use Modules\Product\Interfaces\ProductRepositoryInterface;
use Modules\Product\Models\Product;
use Psr\Log\LoggerInterface;
use RuntimeException;

final readonly class ProductRepository implements ProductRepositoryInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator
    {
        try {
            return Product::query()
                ->with(['category', 'images' => static function ($query): void {
                    $query->orderBy('is_main', 'desc')->orderBy('sort_order');
                }])
                ->latest()
                ->paginate($perPage);
        } catch (RuntimeException $e) {
            $this->logger->error('Failed to fetch paginated products', [
                'error' => $e->getMessage(),
                'per_page' => $perPage,
            ]);

            throw new RuntimeException('Unable to retrieve products', 0, $e);
        }
    }

    public function findById(int $id): Product|null
    {
        try {
            return Product::query()
                ->with(['category', 'images'])
                ->find($id);
        } catch (RuntimeException $e) {
            $this->logger->error('Failed to find product by ID', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Unable to find product', 0, $e);
        }
    }

    public function findByIdOrFail(int $id): Product
    {
        $product = $this->findById($id);

        if (null === $product) {
            throw new ProductNotFoundException("Product with ID {$id} not found");
        }

        return $product;
    }

    public function findBySlug(string $slug): Product|null
    {
        try {
            return Product::query()
                ->with('category')
                ->where('slug', $slug)
                ->first();
        } catch (RuntimeException $e) {
            $this->logger->error('Failed to find product by slug', [
                'slug' => $slug,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Unable to find product', 0, $e);
        }
    }

    public function create(ProductData $data): Product
    {
        try {
            /** @var Product $product */
            $product = Product::query()->create($data->toArray());

            $this->logger->info('Product created successfully', [
                'id' => $product->id,
                'name' => $product->name,
            ]);

            return $product->load('category');
        } catch (RuntimeException $e) {
            $this->logger->error('Failed to create product', [
                'data' => $data->toArray(),
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Unable to create product', 0, $e);
        }
    }

    public function update(int $id, ProductData $data): Product
    {
        $product = $this->findByIdOrFail($id);

        try {
            $product->update($data->toArray());

            $this->logger->info('Product updated successfully', [
                'id' => $product->id,
                'name' => $product->name,
            ]);

            return $product->fresh('category');
        } catch (RuntimeException $e) {
            $this->logger->error('Failed to update product', [
                'id' => $id,
                'data' => $data->toArray(),
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Unable to update product', 0, $e);
        }
    }

    public function delete(int $id): bool
    {
        $product = $this->findByIdOrFail($id);

        try {
            $deleted = $product->delete();

            if ($deleted) {
                $this->logger->info('Product deleted successfully', [
                    'id' => $id,
                    'name' => $product->name,
                ]);
            }

            return $deleted;
        } catch (RuntimeException $e) {
            $this->logger->error('Failed to delete product', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Unable to delete product', 0, $e);
        }
    }

    public function getActiveProducts(): Collection
    {
        try {
            return Product::query()
                ->with('category')
                ->where('is_active', true)
                ->where('stock_quantity', '>', 0)
                ->latest()
                ->get();
        } catch (RuntimeException $e) {
            $this->logger->error('Failed to fetch active products', [
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Unable to retrieve active products', 0, $e);
        }
    }

    public function updateStock(int $id, int $quantity): Product
    {
        $product = $this->findByIdOrFail($id);

        try {
            $product->update(['stock_quantity' => $quantity]);

            $this->logger->info('Product stock updated', [
                'id' => $id,
                'new_quantity' => $quantity,
            ]);

            return $product;
        } catch (RuntimeException $e) {
            $this->logger->error('Failed to update product stock', [
                'id' => $id,
                'quantity' => $quantity,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Unable to update product stock', 0, $e);
        }
    }
}
