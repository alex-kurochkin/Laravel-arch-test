<?php

declare(strict_types=1);

namespace Modules\Catalogue\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Catalogue\Http\Traits\HasQueryOptions;
use Modules\Catalogue\Models\Category;

/**
 * @mixin Category
 */
final class CategoryResource extends JsonResource
{
    use HasQueryOptions;

    #[\Override] public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'parent_id' => $this->parent_id,
            'level' => $this->level,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'status' => $this->is_active ? 'active' : 'inactive',
            'full_path' => $this->full_path,
            'has_children' => $this->has_children,
            'breadcrumbs' => $this->breadcrumbs,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];

        // Добавляем parent только если отношение загружено и запрошено
        if ($this->hasOption($request, 'with-parent') && $this->relationLoaded('parent')) {
            $data['parent'] = new self($this->parent);
        }

        // Добавляем children только если отношение загружено и запрошено
        if ($this->hasOption($request, 'with-children') && $this->relationLoaded('children')) {
            $data['children'] = self::collection($this->children);
        }

        // Добавляем ancestors только если отношение загружено и запрошено
        if ($this->hasOption($request, 'with-ancestors') && $this->relationLoaded('ancestors')) {
            $data['ancestors'] = self::collection($this->ancestors);
        }

//        $descendants = $this->getDescendants();

        // Добавляем descendants только если отношение загружено и запрошено
        if ($this->hasOption($request, 'with-descendants') && $descendants = $this->getDescendants()/*$this->relationLoaded('descendants')*/) {
            $data['descendants'] = self::collection($descendants);
        }

        return $data;
    }

    private function hasOption(Request $request, string $option): bool
    {
        $options = $request->query('options', '');

        if (empty($options)) {
            return false;
        }

        return in_array($option, array_map('trim', explode(',', $options)), true);
    }
}
