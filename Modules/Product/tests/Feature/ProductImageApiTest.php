<?php

declare(strict_types=1);

namespace Modules\Product\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductImage;
use Modules\Product\Tests\TestCase;

final class ProductImageApiTest extends TestCase
{
    private Product $product;
    private const string API_PREFIX = '/api/products';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->product = Product::factory()->create();
    }

    /** @test */
    public function it_can_upload_image(): void
    {
        $file = UploadedFile::fake()->image('product.jpg', 800, 600);

        $response = $this->postJson(self::API_PREFIX . '/' . $this->product->id . '/images', [
            'image' => $file,
            'alt' => 'Test image',
            'is_main' => true,
        ]);

        $response->assertCreated()
            ->assertJson(fn ($json) =>
            $json->has('data')
                ->where('data.alt', 'Test image')
                ->where('data.is_main', true)
                ->has('data.url')
                ->has('data.thumbnail_url')
                ->etc()
            );

        Storage::disk('public')->assertExists('products/' . $this->product->id . '/' . $file->hashName());

        $thumbName = pathinfo($file->hashName(), PATHINFO_FILENAME) . '_thumb.' . $file->extension();
        Storage::disk('public')->assertExists('products/' . $this->product->id . '/' . $thumbName);
    }

    /** @test */
    public function it_can_list_product_images(): void
    {
        ProductImage::factory()
            ->count(3)
            ->forProduct($this->product)
            ->create();

        $response = $this->getJson(self::API_PREFIX . '/' . $this->product->id . '/images');

        $response->assertOk()
            ->assertJson(fn ($json) =>
            $json->has('data', 3)
                ->etc()
            );
    }

    /** @test */
    public function it_can_update_image(): void
    {
        $image = ProductImage::factory()
            ->forProduct($this->product)
            ->create(['alt' => 'Old alt', 'sort_order' => 1]);

        $response = $this->putJson(
            self::API_PREFIX . '/' . $this->product->id . '/images/' . $image->id,
            [
                'alt' => 'New alt',
                'sort_order' => 5,
            ]
        );

        $response->assertOk()
            ->assertJson(fn ($json) =>
            $json->has('data')
                ->where('data.alt', 'New alt')
                ->where('data.sort_order', 5)
                ->etc()
            );

        $this->assertDatabaseHas('product_images', [
            'id' => $image->id,
            'alt' => 'New alt',
            'sort_order' => 5,
        ]);
    }

    /** @test */
    public function it_can_set_main_image(): void
    {
        $image1 = ProductImage::factory()
            ->forProduct($this->product)
            ->main()
            ->create();

        $image2 = ProductImage::factory()
            ->forProduct($this->product)
            ->secondary()
            ->create();

        $response = $this->patchJson(
            self::API_PREFIX . '/' . $this->product->id . '/images/' . $image2->id . '/main'
        );

        $response->assertOk()
            ->assertJson(['message' => 'Main image set successfully']);

        $this->assertFalse($image1->fresh()->is_main);
        $this->assertTrue($image2->fresh()->is_main);
    }

    /** @test */
    public function it_can_reorder_images(): void
    {
        $images = ProductImage::factory()
            ->count(3)
            ->forProduct($this->product)
            ->create();

        $order = [$images[2]->id, $images[0]->id, $images[1]->id];

        $response = $this->postJson(
            self::API_PREFIX . '/' . $this->product->id . '/images/reorder',
            ['order' => $order]
        );

        $response->assertOk()
            ->assertJson(['message' => 'Images reordered successfully']);

        $this->assertEquals(0, $images[2]->fresh()->sort_order);
        $this->assertEquals(1, $images[0]->fresh()->sort_order);
        $this->assertEquals(2, $images[1]->fresh()->sort_order);
    }

    /** @test */
    public function it_can_delete_image(): void
    {
        $image = ProductImage::factory()
            ->forProduct($this->product)
            ->create();

        $response = $this->deleteJson(
            self::API_PREFIX . '/' . $this->product->id . '/images/' . $image->id
        );

        $response->assertNoContent();

        $this->assertDatabaseMissing('product_images', ['id' => $image->id]);
    }

    /** @test */
    public function it_deletes_image_file_when_deleting_record(): void
    {
        $file = UploadedFile::fake()->image('delete-test.jpg');

        $response = $this->postJson(self::API_PREFIX . '/' . $this->product->id . '/images', [
            'image' => $file,
        ]);

        $imageId = $response->json('data.id');
        $path = 'products/' . $this->product->id . '/' . $file->hashName();

        Storage::disk('public')->assertExists($path);

        $this->deleteJson(self::API_PREFIX . '/' . $this->product->id . '/images/' . $imageId);

        Storage::disk('public')->assertMissing($path);
    }
}
