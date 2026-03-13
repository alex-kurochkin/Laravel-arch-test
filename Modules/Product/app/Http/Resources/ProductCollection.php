<?php

declare(strict_types=1);

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

final class ProductCollection extends ResourceCollection
{
    public $collects = ProductResource::class;

    #[\Override] public function toArray(Request $request): array
    {
        parent::toArray($request);
        $response = [
            'data' => $this->collection,
        ];

        // Добавляем мета-информацию только если это пагинатор
        if ($this->resource instanceof LengthAwarePaginator) {
            $response['meta'] = [
                'total' => $this->resource->total(),
                'per_page' => $this->resource->perPage(),
                'current_page' => $this->resource->currentPage(),
                'last_page' => $this->resource->lastPage(),
            ];
        }

        return $response;
    }
}
