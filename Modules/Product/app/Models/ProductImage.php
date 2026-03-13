<?php

declare(strict_types=1);

namespace Modules\Product\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Product\Database\Factories\ProductImageFactory;

/**
 * @property int $id
 * @property int $product_id
 * @property string $path
 * @property string|null $alt
 * @property int $sort_order
 * @property bool $is_main
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Product $product
 * @property-read string $url
 * @property-read string $thumbnail_url
 * @property-read string $full_path
 * @property-read array $dimensions
 */
final class ProductImage extends Model
{
    /** @use HasFactory<ProductImageFactory> */
    use HasFactory;

    protected $table = 'product_images';

    protected $fillable = [
        'product_id',
        'path',
        'alt',
        'sort_order',
        'is_main',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'sort_order' => 'integer',
        'is_main' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'url',
        'thumbnail_url',
        'full_path',
    ];

    protected static function newFactory(): ProductImageFactory
    {
        return ProductImageFactory::new();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->path);
    }

    public function getThumbnailUrlAttribute(): string
    {
        $pathInfo = pathinfo($this->path);

        return asset('storage/' . $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_thumb.' . $pathInfo['extension']);
    }

    public function getFullPathAttribute(): string
    {
        return storage_path('app/public/' . $this->path);
    }

    public function getDimensionsAttribute(): array
    {
        $fullPath = $this->full_path;

        if ( ! file_exists($fullPath)) {
            return ['width' => 0, 'height' => 0];
        }

        [$width, $height] = getimagesize($fullPath);

        return [
            'width' => $width,
            'height' => $height,
        ];
    }

    protected static function booted(): void
    {
        static::saving(static function (ProductImage $image): void {
            if ($image->is_main) {
                static::where('product_id', $image->product_id)
                    ->where('id', '!=', $image->id)
                    ->update(['is_main' => false]);
            }
        });

        static::deleted(static function (ProductImage $image): void {
            $filePath = $image->full_path;
            $thumbPath = storage_path('app/public/' . pathinfo($image->path, PATHINFO_DIRNAME) . '/' . pathinfo($image->path, PATHINFO_FILENAME) . '_thumb.' . pathinfo($image->path, PATHINFO_EXTENSION));

            if (file_exists($filePath)) {
                unlink($filePath);
            }

            if (file_exists($thumbPath)) {
                unlink($thumbPath);
            }
        });
    }
}
