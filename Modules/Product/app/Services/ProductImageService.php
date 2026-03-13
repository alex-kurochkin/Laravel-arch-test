<?php

declare(strict_types=1);

namespace Modules\Product\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductImage;
use Psr\Log\LoggerInterface;
use RuntimeException;

final readonly class ProductImageService
{
    private const string DISK = 'public';
    private const string THUMB_PREFIX = '_thumb';
    private const int THUMB_WIDTH = 300;
    private const int THUMB_HEIGHT = 300;

    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function uploadImage(Product $product, UploadedFile $file, null|string $alt = null, bool $isMain = false): ProductImage
    {
        try {
            $fileName = $this->generateFileName($file);
            $path = 'products/' . $product->id . '/' . $fileName;

            // Сохраняем оригинал
            Storage::disk(self::DISK)->putFileAs(
                'products/' . $product->id,
                $file,
                $fileName
            );

            // Создаем и сохраняем миниатюру
            $this->createThumbnail($file, $product->id, $fileName);

            // Если изображение главное, сбрасываем флаг у других
            if ($isMain) {
                $product->images()->update(['is_main' => false]);
            }

            // Создаем запись в БД
            /** @var ProductImage $image */
            $image = $product->images()->create([
                'path' => $path,
                'alt' => $alt,
                'is_main' => $isMain,
                'sort_order' => $this->getNextSortOrder($product),
            ]);

            $this->logger->info('Product image uploaded', [
                'product_id' => $product->id,
                'image_id' => $image->id,
                'path' => $path,
            ]);

            return $image;
        } catch (\RuntimeException $e) {
            $this->logger->error('Failed to upload product image', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Unable to upload image', 0, $e);
        }
    }

    public function uploadMultiple(Product $product, array $files, null|array $alts = null): array
    {
        $uploaded = [];
        $index = 0;

        foreach ($files as $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                $alt = $alts[$index] ?? null;
                $isMain = $index === 0 && $product->images()->count() === 0;

                $uploaded[] = $this->uploadImage($product, $file, $alt, $isMain);
                $index++;
            }
        }

        return $uploaded;
    }

    public function setMainImage(ProductImage $image): bool
    {
        try {
            $image->product->images()
                ->where('id', '!=', $image->id)
                ->update(['is_main' => false]);

            $updated = $image->update(['is_main' => true]);

            $this->logger->info('Main product image updated', [
                'product_id' => $image->product_id,
                'image_id' => $image->id,
            ]);

            return $updated;
        } catch (\RuntimeException $e) {
            $this->logger->error('Failed to set main image', [
                'image_id' => $image->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function deleteImage(ProductImage $image): bool
    {
        try {
            // Файлы удаляются в модели через события
            return (bool) $image->delete();
        } catch (\RuntimeException $e) {
            $this->logger->error('Failed to delete product image', [
                'image_id' => $image->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function reorderImages(Product $product, array $order): bool
    {
        try {
            foreach ($order as $index => $imageId) {
                ProductImage::where('id', $imageId)
                    ->where('product_id', $product->id)
                    ->update(['sort_order' => $index]);
            }

            $this->logger->info('Product images reordered', [
                'product_id' => $product->id,
            ]);

            return true;
        } catch (\RuntimeException $e) {
            $this->logger->error('Failed to reorder images', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function generateFileName(UploadedFile $file): string
    {
        return time() . '_' . uniqid('', true) . '.' . $file->getClientOriginalExtension();
    }

    private function createThumbnail(UploadedFile $file, int $productId, string $fileName): void
    {
        $pathInfo = pathinfo($fileName);
        $thumbName = $pathInfo['filename'] . self::THUMB_PREFIX . '.' . $pathInfo['extension'];
        $thumbPath = 'products/' . $productId . '/' . $thumbName;

        $image = Image::read($file->getRealPath());
        $image->cover(self::THUMB_WIDTH, self::THUMB_HEIGHT);

        Storage::disk(self::DISK)->put(
            $thumbPath,
            (string) $image->encode()
        );
    }

    private function getNextSortOrder(Product $product): int
    {
        $maxOrder = $product->images()->max('sort_order');

        return null === $maxOrder ? 0 : $maxOrder + 1;
    }
}
