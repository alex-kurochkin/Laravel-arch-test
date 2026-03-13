<?php

declare(strict_types=1);

namespace Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateProductImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'alt' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_main' => ['nullable', 'boolean'],
        ];
    }
}
