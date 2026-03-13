<?php

declare(strict_types=1);

namespace Modules\Catalogue\DTOs;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

final readonly class CategoryData
{
    public function __construct(
        public string      $name,
        public string      $slug,
        public string|null $description,
        public int|null    $parentId,
        public int         $sortOrder,
        public bool        $isActive,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            name: $validated['name'],
            slug: $validated['slug'] ?? Str::slug($validated['name']),
            description: $validated['description'] ?? null,
            parentId: isset($validated['parent_id']) ? (int) $validated['parent_id'] : null,
            sortOrder: (int) ($validated['sort_order'] ?? 0),
            isActive: (bool) ($validated['is_active'] ?? true),
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'parent_id' => $this->parentId,
            'sort_order' => $this->sortOrder,
            'is_active' => $this->isActive,
        ];
    }
}
