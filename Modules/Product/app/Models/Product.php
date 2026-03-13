<?php

declare(strict_types=1);

namespace Modules\Product\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Catalogue\Models\Category;
use Modules\Product\Database\Factories\ProductFactory;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property float $price
 * @property int $stock_quantity
 * @property int|null $category_id
 * @property bool $is_active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Category|null $category
 * @property-read Collection<int, ProductImage> $images
 * @property-read ProductImage|null $mainImage
 * @property-read string $formatted_price
 * @property-read bool $in_stock
 * @property-read string $status
 * @property-read array $image_urls
 * @property-read string|null $main_image_url
 */
final class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected $table = 'products';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'stock_quantity',
        'category_id',
        'is_active',
    ];

    protected $casts = [
        'price' => 'float',
        'stock_quantity' => 'integer',
        'category_id' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'formatted_price',
        'in_stock',
        'status',
        'main_image_url',
    ];

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function mainImage(): HasMany
    {
        return $this->hasMany(ProductImage::class)
            ->where('is_main', true)
            ->orderBy('sort_order')
            ->limit(1);
    }

    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 2);
    }

    public function getInStockAttribute(): bool
    {
        return $this->stock_quantity > 0;
    }

    public function getStatusAttribute(): string
    {
        if ( ! $this->is_active) {
            return 'inactive';
        }

        return $this->in_stock ? 'in_stock' : 'out_of_stock';
    }

    public function getImageUrlsAttribute(): array
    {
        return $this->images->map(fn (ProductImage $image): string => $image->url)->toArray();
    }

    public function getMainImageUrlAttribute(): ?string
    {
        $mainImage = $this->images->firstWhere('is_main', true);

        return $mainImage?->url;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }
}
