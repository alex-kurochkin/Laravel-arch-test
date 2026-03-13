<?php

declare(strict_types=1);

namespace Modules\Product\Tests\Unit;

use Modules\Catalogue\Models\Category;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductImage;
use Modules\Product\Tests\TestCase;

final class ProductModelTest extends TestCase
{
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->product = Product::factory()->create([
            'name' => 'Test Product',
            'price' => 99.99,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_can_create_a_product(): void
    {
        $this->assertDatabaseHas('products', [
            'id' => $this->product->id,
            'name' => 'Test Product',
            'price' => 99.99,
            'stock_quantity' => 10,
        ]);
    }

    /** @test */
    public function it_generates_slug_from_name(): void
    {
        $product = Product::factory()->create([
            'name' => 'New Test Product',
            'slug' => null,
        ]);

        $this->assertEquals('new-test-product', $product->slug);
    }

    /** @test */
    public function it_calculates_formatted_price(): void
    {
        $this->assertEquals('99.99', $this->product->formatted_price);
    }

    /** @test */
    public function it_determines_if_in_stock(): void
    {
        $this->assertTrue($this->product->in_stock);

        $this->product->update(['stock_quantity' => 0]);
        $this->assertFalse($this->product->fresh()->in_stock);
    }

    /** @test */
    public function it_determines_correct_status(): void
    {
        // Активный и в наличии
        $this->assertEquals('in_stock', $this->product->status);

        // Активный, но не в наличии
        $this->product->update(['stock_quantity' => 0]);
        $this->assertEquals('out_of_stock', $this->product->fresh()->status);

        // Неактивный
        $this->product->update(['is_active' => false, 'stock_quantity' => 10]);
        $this->assertEquals('inactive', $this->product->fresh()->status);
    }

    /** @test */
    public function it_belongs_to_category(): void
    {
        $category = Category::factory()->create();
        $this->product->update(['category_id' => $category->id]);

        $this->assertInstanceOf(Category::class, $this->product->category);
        $this->assertEquals($category->id, $this->product->category->id);
    }

    /** @test */
    public function it_has_many_images(): void
    {
        ProductImage::factory()->count(3)->forProduct($this->product)->create();

        $this->assertCount(3, $this->product->images);
        $this->assertInstanceOf(ProductImage::class, $this->product->images->first());
    }

    /** @test */
    public function it_gets_main_image(): void
    {
        ProductImage::factory()->forProduct($this->product)->secondary()->create();
        $mainImage = ProductImage::factory()->forProduct($this->product)->main()->create();

        $this->assertEquals($mainImage->id, $this->product->mainImage->first()?->id);
    }

    /** @test */
    public function it_returns_null_main_image_url_when_no_main_image(): void
    {
        $this->assertNull($this->product->main_image_url);
    }

    /** @test */
    public function it_returns_main_image_url_when_main_image_exists(): void
    {
        ProductImage::factory()->forProduct($this->product)->main()->create([
            'path' => 'products/test/image.jpg',
        ]);

        $this->assertStringContainsString('image.jpg', $this->product->fresh()->main_image_url);
    }

    /** @test */
    public function it_scopes_active_products(): void
    {
        Product::factory()->count(2)->active()->create();
        Product::factory()->count(3)->inactive()->create();

        $activeCount = Product::active()->count();

        $this->assertEquals(3, $activeCount); // 1 из setUp + 2 активных
    }

    /** @test */
    public function it_scopes_products_in_stock(): void
    {
        Product::factory()->count(2)->create(['stock_quantity' => 5]);
        Product::factory()->count(3)->create(['stock_quantity' => 0]);

        $inStockCount = Product::inStock()->count();

        $this->assertEquals(3, $inStockCount); // 1 из setUp + 2 в наличии
    }

    /** @test */
    public function it_scopes_products_by_category(): void
    {
        $category = Category::factory()->create();
        Product::factory()->count(2)->forCategory($category)->create();

        $categoryCount = Product::byCategory($category->id)->count();

        $this->assertEquals(2, $categoryCount);
    }
}
