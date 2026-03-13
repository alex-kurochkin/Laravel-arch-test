<?php

declare(strict_types=1);

namespace Modules\Product\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Product\DTOs\ProductData;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductImage;
use Modules\Product\Repositories\ProductRepository;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class ProductService
{
    private ProductRepository $repository;
    private const string DISK = 'public';

    public function __construct(
        private readonly LoggerInterface $logger,
        ProductRepository|null $repository = null,
    )
    {
        $this->repository = $repository ?? app(ProductRepository::class);
    }

    public function duplicate(Product $product, array $options = []): Product
    {
        try {
            DB::beginTransaction();

            $data = ProductData::fromProduct($product, [
                'name' => $options['name'] ?? $product->name . ' (Copy)',
                'slug' => $options['slug'] ?? $product->slug . '-copy-' . uniqid('', true),
                'is_active' => $options['is_active'] ?? false,
            ]);

            $newProduct = $this->repository->create($data);

            if ($options['copy_images'] ?? false) {
                $this->duplicateImages($product, $newProduct);
            }

            DB::commit();

            $this->logger->info('Product duplicated successfully', [
                'original_id' => $product->id,
                'new_id' => $newProduct->id,
            ]);

            return $newProduct->load('images');
        } catch (\RuntimeException $e) {
            DB::rollBack();

            $this->logger->error('Failed to duplicate product', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Unable to duplicate product: ' . $e->getMessage(), 0, $e);
        }
    }

    public function duplicateWithImages(Product $product, array $options = []): Product
    {
        return $this->duplicate($product, array_merge($options, ['copy_images' => true]));
    }

    public function toggleStatus(int $productId): Product
    {
        $product = $this->repository->findByIdOrFail($productId);

        $product->update(['is_active' => !$product->is_active]);

        $this->logger->info('Product status toggled', [
            'product_id' => $productId,
            'new_status' => $product->is_active,
        ]);

        return $product;
    }

    private function duplicateImages(Product $originalProduct, Product $newProduct): void
    {
        foreach ($originalProduct->images as $image) {
            try {
                $this->duplicateSingleImage($image, $newProduct);
            } catch (\RuntimeException $e) {
                $this->logger->warning('Failed to copy image during duplication', [
                    'original_image_id' => $image->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function duplicateSingleImage(ProductImage $originalImage, Product $newProduct): void
    {
        $originalPath = $originalImage->path;
        $pathInfo = pathinfo($originalPath);

        $newFileName = $this->generateUniqueFileName($pathInfo['basename']);
        $newPath = 'products/' . $newProduct->id . '/' . $newFileName;

        if (Storage::disk(self::DISK)->exists($originalPath)) {
            Storage::disk(self::DISK)->copy($originalPath, $newPath);
        }

        $originalThumbPath = $this->getThumbPath($originalPath);
        if (Storage::disk(self::DISK)->exists($originalThumbPath)) {
            $newThumbPath = $this->getThumbPath($newPath);
            Storage::disk(self::DISK)->copy($originalThumbPath, $newThumbPath);
        }

        $newProduct->images()->create([
            'path' => $newPath,
            'alt' => $originalImage->alt,
            'sort_order' => $originalImage->sort_order,
            'is_main' => $originalImage->is_main,
        ]);
    }

    private function generateUniqueFileName(string $originalName): string
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);

        return uniqid('', true) . '_' . time() . '.' . $extension;
    }

    private function getThumbPath(string $path): string
    {
        $pathInfo = pathinfo($path);

        return $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_thumb.' . $pathInfo['extension'];
    }
}
