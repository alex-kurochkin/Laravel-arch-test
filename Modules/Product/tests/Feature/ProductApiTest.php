<?php

declare(strict_types=1);

namespace Modules\Product\Tests\Feature;

use Modules\Product\Models\Product;
use Modules\Product\Tests\TestCase;

final class ProductApiTest extends TestCase
{
    /** @test */
    public function it_can_list_products(): void
    {
        $this->createProducts(3);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'price', 'slug', 'formatted_price']
                ],
                'meta' => ['total', 'per_page']
            ]);

        $this->assertEquals(3, $response->json('meta.total'));
    }

    /** @test */
    public function it_can_show_product(): void
    {
        // Используем метод createProductWithCategory
        $product = $this->createProductWithCategory(
            ['name' => 'Test Product', 'price' => 199.99],
            ['name' => 'Electronics']
        );

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $product->id,
                    'name' => 'Test Product',
                    'price' => 199.99,
                    'category' => [
                        'name' => 'Electronics',
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_can_create_product_with_category(): void
    {
        $category = $this->createCategory(['name' => 'Books']);

        $productData = [
            'name' => 'New Book',
            'price' => 29.99,
            'category_id' => $category->id,
            'stock_quantity' => 10,
        ];

        $response = $this->postJson('/api/products', $productData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'name' => 'New Book',
                    'price' => 29.99,
                    'category' => [
                        'name' => 'Books',
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_can_duplicate_product_with_category(): void
    {
        $product = $this->createProductWithCategory(
            ['name' => 'Original Book', 'price' => 29.99],
            ['name' => 'Fiction']
        );

        $response = $this->postJson("/api/products/{$product->id}/duplicate", [
            'name' => 'Duplicated Book',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Product duplicated successfully',
                'data' => [
                    'name' => 'Duplicated Book',
                    'price' => 29.99,
                ],
            ]);

        // Проверяем, что категория скопировалась
        $duplicatedId = $response->json('data.id');
        $duplicated = Product::with('category')->find($duplicatedId);

        $this->assertEquals('Fiction', $duplicated->category->name);
    }
}
