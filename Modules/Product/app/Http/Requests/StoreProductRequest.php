<?php

declare(strict_types=1);

namespace Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Product\Models\Product;

final class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique(Product::class)],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    #[\Override] public function messages(): array
    {
        return [
            'name.required' => 'Product name is required',
            'price.required' => 'Product price is required',
            'price.min' => 'Price cannot be negative',
            'slug.unique' => 'This slug is already in use',
        ];
    }
}
