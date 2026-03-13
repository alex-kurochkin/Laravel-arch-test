<?php

declare(strict_types=1);

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Product\Models\ProductImage;

/**
 * @mixin ProductImage
 */
final class ProductImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'path' => $this->path,
            'url' => $this->url, // Из аксессора
            'thumbnail_url' => $this->thumbnail_url, // Из аксессора
            'alt' => $this->alt,
            'sort_order' => $this->sort_order,
            'is_main' => $this->is_main,
            'dimensions' => $this->when($request->has('with_dimensions'), fn () => $this->dimensions),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
