<?php

declare(strict_types=1);

namespace Modules\Product\Tests\Unit;

use Modules\Catalogue\Models\Category;
use Modules\Product\Http\Requests\StoreProductRequest;
use Modules\Product\DTOs\ProductData;
use Modules\Product\Tests\TestCase;
use Modules\Product\Tests\Traits\WithCategories;
use PHPUnit\Framework\Attributes\Test;

final class ProductDataDTOTest extends TestCase
{
    use WithCategories;

    #[Test]
    public function it_creates_from_valid_request(): void
    {
        // Создаем категорию через трейт
        $category = $this->createCategory(['name' => 'Electronics']);

        // Создаем запрос
        $request = new StoreProductRequest();
        $request->merge([
            'name' => 'Test Product',
            'price' => 99.99,
            'category_id' => $category->id,
            'stock_quantity' => 10,
        ]);

        $request->setContainer(app());
        $request->validateResolved();

        $dto = ProductData::fromRequest($request);

        $this->assertEquals('Test Product', $dto->name);
        $this->assertEquals(99.99, $dto->price);
        $this->assertEquals($category->id, $dto->categoryId);
        $this->assertEquals(10, $dto->stockQuantity);
        $this->assertTrue($dto->isActive);

        // Проверяем, что категория действительно существует в БД
        $this->assertCategoryExists(['id' => $category->id, 'name' => 'Electronics']);
    }

    #[Test]
    public function it_generates_slug_from_name(): void
    {
        $request = new StoreProductRequest();
        $request->merge([
            'name' => 'Test Product Name',
            'price' => 99.99,
        ]);

        $request->setContainer(app());
        $request->validateResolved();

        $dto = ProductData::fromRequest($request);

        $this->assertEquals('test-product-name', $dto->slug);
    }

    #[Test]
    public function it_uses_provided_slug(): void
    {
        $request = new StoreProductRequest();
        $request->merge([
            'name' => 'Test Product',
            'slug' => 'custom-slug',
            'price' => 99.99,
        ]);

        $request->setContainer(app());
        $request->validateResolved();

        $dto = ProductData::fromRequest($request);

        $this->assertEquals('custom-slug', $dto->slug);
    }

    /** @test */
    public function it_handles_null_category(): void
    {
        $request = new StoreProductRequest();
        $request->merge([
            'name' => 'Test Product',
            'price' => 99.99,
            'category_id' => null,
        ]);

        $request->setContainer(app());
        $request->validateResolved();

        $dto = ProductData::fromRequest($request);

        $this->assertNull($dto->categoryId);
    }

    /** @test */
    public function it_handles_minimal_data(): void
    {
        $request = new StoreProductRequest();
        $request->merge([
            'name' => 'Minimal Product',
            'price' => 49.99,
        ]);

        $request->setContainer(app());
        $request->validateResolved();

        $dto = ProductData::fromRequest($request);

        $this->assertEquals('Minimal Product', $dto->name);
        $this->assertEquals(49.99, $dto->price);
        $this->assertNull($dto->categoryId);
        $this->assertEquals(0, $dto->stockQuantity);
        $this->assertTrue($dto->isActive);
    }

    /** @test */
    public function it_converts_to_array_correctly(): void
    {
        $category = $this->createCategory(['name' => 'Books']);

        $request = new StoreProductRequest();
        $request->merge([
            'name' => 'Test Book',
            'price' => 29.99,
            'category_id' => $category->id,
            'stock_quantity' => 5,
            'is_active' => false,
        ]);

        $request->setContainer(app());
        $request->validateResolved();

        $dto = ProductData::fromRequest($request);
        $array = $dto->toArray();

        $this->assertEquals([
            'name' => 'Test Book',
            'slug' => 'test-book',
            'price' => 29.99,
            'stock_quantity' => 5,
            'category_id' => $category->id,
            'is_active' => false,
        ], $array);
    }
}
