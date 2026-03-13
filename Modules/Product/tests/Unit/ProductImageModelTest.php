<?php

declare(strict_types=1);

namespace Modules\Product\Tests\Unit;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductImage;
use Modules\Product\Tests\TestCase;

final class ProductImageModelTest extends TestCase
{
    private Product $product;
    private ProductImage $image;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->product = Product::factory()->create();
        $this->image = ProductImage::factory()->forProduct($this->product)->create([
            'path' => 'products/test-image.jpg',
            'is_main' => true,
        ]);
    }

    /** @test */
    public function it_belongs_to_product(): void
    {
        $this->assertInstanceOf(Product::class, $this->image->product);
        $this->assertEquals($this->product->id, $this->image->product->id);
    }

    /** @test */
    public function it_generates_url(): void
    {
        $expectedUrl = asset('storage/products/test-image.jpg');
        $this->assertEquals($expectedUrl, $this->image->url);
    }

    /** @test */
    public function it_generates_thumbnail_url(): void
    {
        $expectedThumbUrl = asset('storage/products/test-image_thumb.jpg');
        $this->assertEquals($expectedThumbUrl, $this->image->thumbnail_url);
    }

    /** @test */
    public function it_gets_full_path(): void
    {
        $expectedPath = storage_path('app/public/products/test-image.jpg');
        $this->assertEquals($expectedPath, $this->image->full_path);
    }

    /** @test */
    public function it_returns_dimensions_when_file_exists(): void
    {
        // Создаем тестовое изображение
        $file = UploadedFile::fake()->image('test-image.jpg', 800, 600);
        Storage::disk('public')->putFileAs('products', $file, 'test-image.jpg');

        $dimensions = $this->image->dimensions;

        $this->assertIsArray($dimensions);
        $this->assertArrayHasKey('width', $dimensions);
        $this->assertArrayHasKey('height', $dimensions);
    }

    /** @test */
    public function it_returns_zero_dimensions_when_file_missing(): void
    {
        $dimensions = $this->image->dimensions;

        $this->assertEquals(['width' => 0, 'height' => 0], $dimensions);
    }

    /** @test */
    public function it_ensures_only_one_main_image_per_product(): void
    {
        // Создаем второе изображение и делаем его главным
        $secondImage = ProductImage::factory()->forProduct($this->product)->create();
        $secondImage->update(['is_main' => true]);

        // Проверяем, что первое изображение перестало быть главным
        $this->assertFalse($this->image->fresh()->is_main);
        $this->assertTrue($secondImage->fresh()->is_main);
    }

    /** @test */
    public function it_deletes_file_when_image_is_deleted(): void
    {
        // Создаем файл
        $file = UploadedFile::fake()->image('test-image.jpg');
        $path = Storage::disk('public')->putFileAs('products', $file, 'delete-test.jpg');

        $image = ProductImage::factory()->forProduct($this->product)->create([
            'path' => $path,
        ]);

        Storage::disk('public')->assertExists($path);

        $image->delete();

        Storage::disk('public')->assertMissing($path);
    }

    /** @test */
    public function it_deletes_thumbnail_when_image_is_deleted(): void
    {
        // Создаем файл
        $file = UploadedFile::fake()->image('test-image.jpg');
        $path = 'products/thumb-test.jpg';
        Storage::disk('public')->putFileAs('products', $file, 'thumb-test.jpg');

        // Создаем миниатюру
        $thumbPath = 'products/thumb-test_thumb.jpg';
        Storage::disk('public')->put($thumbPath, 'fake-thumb-content');

        $image = ProductImage::factory()->forProduct($this->product)->create([
            'path' => $path,
        ]);

        Storage::disk('public')->assertExists($path);
        Storage::disk('public')->assertExists($thumbPath);

        $image->delete();

        Storage::disk('public')->assertMissing($path);
        Storage::disk('public')->assertMissing($thumbPath);
    }
}
