<?php

declare(strict_types=1);

namespace Modules\Product\Tests\Unit;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Product\Models\Product;
use Modules\Product\Services\ProductService;
use Modules\Product\Tests\TestCase;
use Modules\Product\Tests\Traits\WithProductFactory;
use Psr\Log\NullLogger;

final class ProductServiceTest extends TestCase
{
    use WithProductFactory;

    private ProductService $productService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->productService = new ProductService(new NullLogger());
    }

    /** @test */
    public function it_can_duplicate_product(): void
    {
        $original = $this->createProductWithImages(2);

        $duplicated = $this->productService->duplicate($original, [
            'name' => 'Duplicated Product',
            'is_active' => false,
        ]);

        $this->assertNotEquals($original->id, $duplicated->id);
        $this->assertEquals('Duplicated Product', $duplicated->name);
        $this->assertStringContainsString('-copy-', $duplicated->slug);
        $this->assertEquals($original->price, $duplicated->price);
        $this->assertFalse($duplicated->is_active);

        // Проверяем, что изображения не скопировались (по умолчанию)
        $this->assertCount(0, $duplicated->images);
    }

    /** @test */
    public function it_can_duplicate_product_with_images(): void
    {
        $original = $this->createProductWithImages(3);

        // Создаем файлы изображений в storage
        foreach ($original->images as $index => $image) {
            $fakeFile = UploadedFile::fake()->image("image-{$index}.jpg");
            Storage::disk('public')->putFileAs(
                dirname($image->path),
                $fakeFile,
                basename($image->path)
            );
        }

        $duplicated = $this->productService->duplicateWithImages($original, [
            'name' => 'Product With Images Copy',
        ]);

        $this->assertCount(3, $duplicated->images);

        // Проверяем, что файлы существуют
        foreach ($duplicated->images as $image) {
            Storage::disk('public')->assertExists($image->path);
        }

        // Проверяем, что ID изображений разные
        $this->assertNotEquals(
            $original->images->pluck('id')->toArray(),
            $duplicated->images->pluck('id')->toArray()
        );

        // Проверяем, что главное изображение сохранило флаг
        $originalMain = $original->images->firstWhere('is_main', true);
        $duplicatedMain = $duplicated->images->firstWhere('is_main', true);

        $this->assertNotNull($originalMain);
        $this->assertNotNull($duplicatedMain);
    }

    /** @test */
    public function it_can_toggle_product_status(): void
    {
        $product = $this->createActiveProduct();

        $result = $this->productService->toggleStatus($product->id);

        $this->assertFalse($result->is_active);

        $result = $this->productService->toggleStatus($product->id);

        $this->assertTrue($result->is_active);
    }
}
