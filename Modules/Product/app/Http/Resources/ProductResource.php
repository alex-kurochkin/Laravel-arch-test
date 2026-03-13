<?php

declare(strict_types=1);

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Catalogue\Models\Category;
use Modules\Product\Models\Product;

/**
 * @mixin Product
 */
final class ProductResource extends JsonResource
{
    #[\Override] public function toArray(Request $request): array
    {
        parent::toArray($request);
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'formatted_price' => $this->formatted_price,
            'stock_quantity' => $this->stock_quantity,
            'in_stock' => $this->in_stock,
            'is_active' => $this->is_active,
            'status' => $this->status,
            'category' => $this->whenLoaded('category', function () {
                /** @var Category|null $category */
                $category = $this->category;

                if (null === $category) {
                    return null;
                }

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'full_path' => $category->full_path, // IDE теперь видит это поле
                    'level' => $category->level,
                ];
            }),
            'images' => $this->whenLoaded('images', fn () => ProductImageResource::collection(
                $this->images->sortByDesc('is_main')->sortBy('sort_order')
            )),
            'main_image' => $this->main_image_url,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
