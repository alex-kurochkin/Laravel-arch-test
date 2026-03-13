<?php

declare(strict_types=1);

namespace Modules\Product\Tests\Feature;

use Illuminate\Testing\Fluent\AssertableJson;
use Modules\Catalogue\Models\Category;
use Modules\Product\Models\Product;
use Modules\Product\Tests\TestCase;
use Modules\Product\Tests\Traits\WithProductFactory;

final class ProductCategoryTest extends TestCase
{
    use WithProductFactory;

    private const string API_PREFIX = '/api/products';

    /** @test */
    public function it_can_filter_products_by_category(): void
    {
        $category = Category::factory()->create();

        $this->createProductsInCategory($category, 3);
        $this->createProducts(2); // продукты без категории

        $response = $this->getJson(self::API_PREFIX . '?category_id=' . $category->id);

        $response->assertOk()
            ->assertJson(fn (AssertableJson $json) =>
            $json->has('data', 3)
                ->has('data.0', fn ($json) =>
                $json->where('category.id', $category->id)
                    ->etc()
                )
                ->etc()
            );
    }

    /** @test */
    public function it_returns_products_with_category_data(): void
    {
        $category = Category::factory()->create([
            'name' => 'Electronics',
            'slug' => 'electronics',
        ]);

        $product = $this->createProductWithCategory($category);

        $response = $this->getJson(self::API_PREFIX . '/' . $product->id);

        $response->assertOk()
            ->assertJson(fn (AssertableJson $json) =>
            $json->has('data.category')
                ->where('data.category.id', $category->id)
                ->where('data.category.name', 'Electronics')
                ->where('data.category.slug', 'electronics')
                ->etc()
            );
    }

    /** @test */
    public function it_returns_null_category_for_products_without_category(): void
    {
        $product = $this->createProduct();

        $response = $this->getJson(self::API_PREFIX . '/' . $product->id);

        $response->assertOk()
            ->assertJson(fn (AssertableJson $json) =>
            $json->where('data.category', null)
                ->etc()
            );
    }

    /** @test */
    public function it_can_update_product_category(): void
    {
        $product = $this->createProduct();
        $category = Category::factory()->create();

        $response = $this->putJson(self::API_PREFIX . '/' . $product->id, [
            'category_id' => $category->id,
        ]);

        $response->assertOk()
            ->assertJson(fn (AssertableJson $json) =>
            $json->where('data.category.id', $category->id)
                ->etc()
            );

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'category_id' => $category->id,
        ]);
    }

    /** @test */
    public function it_can_remove_product_category(): void
    {
        $category = Category::factory()->create();
        $product = $this->createProductWithCategory($category);

        $response = $this->putJson(self::API_PREFIX . '/' . $product->id, [
            'category_id' => null,
        ]);

        $response->assertOk()
            ->assertJson(fn (AssertableJson $json) =>
            $json->where('data.category', null)
                ->etc()
            );

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'category_id' => null,
        ]);
    }

    /** @test */
    public function it_validates_category_exists(): void
    {
        $product = $this->createProduct();

        $response = $this->putJson(self::API_PREFIX . '/' . $product->id, [
            'category_id' => 99999, // несуществующая категория
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id']);
    }

    /** @test */
    public function it_can_get_products_by_multiple_categories(): void
    {
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();
        $category3 = Category::factory()->create();

        $this->createProductsInCategory($category1, 2);
        $this->createProductsInCategory($category2, 3);
        $this->createProductsInCategory($category3, 1);

        $response = $this->getJson(self::API_PREFIX . '?categories[]=' . $category1->id . '&categories[]=' . $category2->id);

        $response->assertOk()
            ->assertJson(fn (AssertableJson $json) =>
            $json->has('data', 5)
                ->etc()
            );
    }

    /** @test */
    public function it_returns_category_full_path(): void
    {
        $parent = Category::factory()->create(['name' => 'Electronics']);
        $child = Category::factory()->create([
            'name' => 'Phones',
            'parent_id' => $parent->id
        ]);

        $product = $this->createProductWithCategory($child);

        $response = $this->getJson(self::API_PREFIX . '/' . $product->id);

        $response->assertOk()
            ->assertJson(fn (AssertableJson $json) =>
            $json->where('data.category.full_path', 'Electronics > Phones')
                ->etc()
            );
    }

    /** @test */
    public function it_can_get_products_count_by_category(): void
    {
        $category = Category::factory()->create();

        $this->createProductsInCategory($category, 5);

        $response = $this->getJson('/api/categories/' . $category->id . '/products/count');

        $response->assertOk()
            ->assertJson(['count' => 5]);
    }

    /** @test */
    public function it_can_move_products_to_another_category(): void
    {
        $oldCategory = Category::factory()->create();
        $newCategory = Category::factory()->create();

        $products = $this->createProductsInCategory($oldCategory, 3);

        $response = $this->postJson('/api/categories/' . $oldCategory->id . '/move-products', [
            'target_category_id' => $newCategory->id,
        ]);

        $response->assertOk()
            ->assertJson(['moved' => 3]);

        foreach ($products as $product) {
            $this->assertEquals($newCategory->id, $product->fresh()->category_id);
        }
    }
}
