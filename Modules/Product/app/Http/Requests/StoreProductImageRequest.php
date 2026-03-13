<?php

declare(strict_types=1);

namespace Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreProductImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'], // 5MB max
            'alt' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_main' => ['nullable', 'boolean'],
        ];
    }

    #[\Override] public function messages(): array
    {
        return [
            'image.required' => 'Please select an image',
            'image.image' => 'File must be an image',
            'image.mimes' => 'Image must be jpeg, png, jpg, gif, or webp',
            'image.max' => 'Image size must not exceed 5MB',
        ];
    }
}
