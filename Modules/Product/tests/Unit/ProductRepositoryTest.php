<?php

declare(strict_types=1);

namespace Modules\Product\Tests\Unit;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Catalogue\Models\Category;
use Modules\Product\DTOs\ProductData;
use Modules\Product\Exceptions\ProductNotFoundException;
use Modules\Product\Models\Product;
use Modules\Product\Repositories\ProductRepository;
use Modules\Product\Tests\TestCase;
use Psr\Log\NullLogger;

final class ProductRepositoryTest extends TestCase
{
    private ProductRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new ProductRepository(new NullLogger());
    }

    /** @test */
    public function it_returns_paginated_products(): void
    {
        Product::factory()->count(20)->create();

        $result = $this->repository->getAllPaginated(15);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(15, $result->perPage());
        $this->assertEquals(20, $result->total());
    }

    /** @test */
    public function it_finds_product_by_id(): void
    {
        $product = Product::factory()->create();

        $found = $this->repository->findById($product->id);

        $this->assertInstanceOf(Product::class, $found);
        $this->assertEquals($product->id, $found->id);
    }

    /** @test */
    public function it_returns_null_when_product_not_found(): void
    {
        $found = $this->repository->findById(999);

        $this->assertNull($found);
    }

    /** @test */
    public function it_finds_product_or_fails(): void
    {
        $this->expectException(ProductNotFoundException::class);

        $product = Product::factory()->create();
        $found = $this->repository->findByIdOrFail($product->id);

        $this->assertInstanceOf(Product::class, $found);

        $this->repository->findByIdOrFail(999);
    }

    /** @test */
    public function it_finds_product_by_slug(): void
    {
        $product = Product::factory()->create(['slug' => 'test-slug']);

        $found = $this->repository->findBySlug('test-slug');

        $this->assertInstanceOf(Product::class, $found);
        $this->assertEquals($product->id, $found->id);
    }

    /** @test */
    public function it_creates_product(): void
    {
        $data = new ProductData(
            name: 'New Product',
            slug: 'new-product',
            description: 'Description',
            price: 199.99,
            stockQuantity: 20,
            categoryId: null,
            isActive: true
        );

        $product = $this->repository->create($data);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'New Product',
            'slug' => 'new-product',
            'price' => 199.99,
        ]);
    }

    /** @test */
    public function it_updates_product(): void
    {
        $product = Product::factory()->create();

        $data = new ProductData(
            name: 'Updated Name',
            slug: 'updated-name',
            description: 'Updated description',
            price: 299.99,
            stockQuantity: 30,
            categoryId: null,
            isActive: false
        );

        $updated = $this->repository->update($product->id, $data);

        $this->assertEquals('Updated Name', $updated->name);
        $this->assertEquals('updated-name', $updated->slug);
        $this->assertEquals(299.99, $updated->price);
        $this->assertFalse($updated->is_active);
    }

    /** @test */
    public function it_deletes_product(): void
    {
        $product = Product::factory()->create();

        $result = $this->repository->delete($product->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    /** @test */
    public function it_returns_active_products(): void
    {
        Product::factory()->active()->count(5)->create();
        Product::factory()->inactive()->count(3)->create();

        $activeProducts = $this->repository->getActiveProducts();

        $this->assertCount(5, $activeProducts);
    }

    /** @test */
    public function it_updates_stock(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $updated = $this->repository->updateStock($product->id, 25);

        $this->assertEquals(25, $updated->stock_quantity);
    }

    /** @test */
    public function it_gets_products_by_category(): void
    {
        $category = Category::factory()->create();

        Product::factory()->count(3)->forCategory($category)->create();
        Product::factory()->count(2)->create(); // другие категории

        $products = $this->repository->getProductsByCategory($category->id);

        $this->assertCount(3, $products);
    }
}
