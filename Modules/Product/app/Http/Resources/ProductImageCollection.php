<?php

declare(strict_types=1);

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

final class ProductImageCollection extends ResourceCollection
{
    public $collects = ProductImageResource::class;

    #[\Override] public function toArray(Request $request): array
    {
        parent::toArray($request);
        return [
            'data' => $this->collection,
        ];
    }
}
