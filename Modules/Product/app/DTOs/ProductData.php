<?php

declare(strict_types=1);

namespace Modules\Product\DTOs;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Modules\Product\Models\Product;

final readonly class ProductData
{
    public function __construct(
        public string $name,
        public string $slug,
        public null|string $description,
        public float $price,
        public int $stockQuantity,
        public null|int $categoryId,
        public bool $isActive,
        public null|array $metadata,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            name: $validated['name'],
            slug: $validated['slug'] ?? Str::slug($validated['name']),
            description: $validated['description'] ?? null,
            price: (float) $validated['price'],
            stockQuantity: (int) ($validated['stock_quantity'] ?? 0),
            categoryId: isset($validated['category_id']) ? (int) $validated['category_id'] : null,
            isActive: (bool) ($validated['is_active'] ?? true),
            metadata: $validated['metadata'] ?? null,
        );
    }

    public static function fromProduct(Product $product, array $overrides = []): self
    {
        return new self(
            name: $overrides['name'] ?? $product->name,
            slug: $overrides['slug'] ?? $product->slug,
            description: $overrides['description'] ?? $product->description,
            price: (float) ($overrides['price'] ?? $product->price),
            stockQuantity: (int) ($overrides['stock_quantity'] ?? $product->stock_quantity),
            categoryId: $overrides['category_id'] ?? $product->category_id,
            isActive: (bool) ($overrides['is_active'] ?? $product->is_active),
            metadata: $overrides['metadata'] ?? $product->metadata,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'stock_quantity' => $this->stockQuantity,
            'category_id' => $this->categoryId,
            'is_active' => $this->isActive,
            'metadata' => $this->metadata,
        ], static fn($value): bool => null !== $value);
    }
}
