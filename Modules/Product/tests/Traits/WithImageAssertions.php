<?php

declare(strict_types=1);

namespace Modules\Product\Tests\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Product\Models\ProductImage;

trait WithImageAssertions
{
    protected function assertImageExists(ProductImage $image): void
    {
        Storage::disk('public')->assertExists($image->path);

        $thumbPath = $this->getThumbPath($image->path);
        Storage::disk('public')->assertExists($thumbPath);
    }

    protected function assertImageMissing(ProductImage $image): void
    {
        Storage::disk('public')->assertMissing($image->path);

        $thumbPath = $this->getThumbPath($image->path);
        Storage::disk('public')->assertMissing($thumbPath);
    }

    protected function assertImageHasCorrectDimensions(UploadedFile $file, int $width, int $height): void
    {
        [$imageWidth, $imageHeight] = getimagesize($file->getRealPath());

        $this->assertEquals($width, $imageWidth);
        $this->assertEquals($height, $imageHeight);
    }

    protected function assertThumbnailExists(string $path): void
    {
        $thumbPath = $this->getThumbPath($path);
        Storage::disk('public')->assertExists($thumbPath);
    }

    protected function assertThumbnailHasCorrectSize(string $path, int $maxWidth = 300, int $maxHeight = 300): void
    {
        $thumbPath = $this->getThumbPath($path);
        $fullThumbPath = Storage::disk('public')->path($thumbPath);

        [$width, $height] = getimagesize($fullThumbPath);

        $this->assertLessThanOrEqual($maxWidth, $width);
        $this->assertLessThanOrEqual($maxHeight, $height);
    }

    protected function assertImageIsMain(ProductImage $image): void
    {
        $this->assertTrue($image->is_main);

        // Проверяем, что это единственное главное изображение для продукта
        $mainCount = ProductImage::where('product_id', $image->product_id)
            ->where('is_main', true)
            ->count();

        $this->assertEquals(1, $mainCount);
    }

    protected function assertImageIsSecondary(ProductImage $image): void
    {
        $this->assertFalse($image->is_main);
    }

    protected function assertImagesOrdered(Product $product, array $expectedOrder): void
    {
        $actualOrder = $product->images()
            ->orderBy('sort_order')
            ->pluck('id')
            ->toArray();

        $this->assertEquals($expectedOrder, $actualOrder);
    }

    protected function assertImageBelongsToProduct(ProductImage $image, Product $product): void
    {
        $this->assertEquals($product->id, $image->product_id);
    }

    protected function getThumbPath(string $path): string
    {
        $pathInfo = pathinfo($path);

        return $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_thumb.' . $pathInfo['extension'];
    }

    protected function createTestImage(string $name = 'test.jpg', int $width = 800, int $height = 600): UploadedFile
    {
        return UploadedFile::fake()->image($name, $width, $height);
    }

    protected function createTestImages(int $count = 3): array
    {
        $images = [];

        for ($i = 0; $i < $count; $i++) {
            $images[] = $this->createTestImage("test-{$i}.jpg");
        }

        return $images;
    }
}
