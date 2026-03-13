<?php

declare(strict_types=1);

namespace Modules\Catalogue\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Catalogue\Database\Factories\CategoryFactory;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int|null $parent_id
 * @property int $sort_order
 * @property bool $is_active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Category|null $parent
 * @property-read Collection<int, Category> $children
 * @property-read string $full_path
 * @property-read int $level
 * @property-read bool $has_children
 * @property-read array $breadcrumbs
 *
 * @method static Builder|Category active()
 * @method static Builder|Category root()
 * @method static Builder|Category whereParentId($value)
 * @method static Builder|Category whereSortOrder($value)
 * @method static Builder|Category whereIsActive($value)
 * @method static Builder|Category withParent()
 * @method static Builder|Category withChildren()
 */
final class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    protected $table = 'categories';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        '_lft' => 'integer',
        '_rgt' => 'integer',
        'depth' => 'integer', // Добавляем каст для depth
    ];

    protected $appends = [
        'full_path',
        'level',
        'has_children',
        'breadcrumbs',
    ];

    protected static function newFactory(): CategoryFactory
    {
        return CategoryFactory::new();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * Явно определяем отношение ancestors
     */
    public function ancestors(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Явно определяем отношение descendants
     */
    public function descendants(): Collection
    {
        $descendants = new Collection();

        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->descendants());
        }

        return $descendants;
    }

    public function getFullPathAttribute(): string
    {
        $names = [];
        $category = $this;

        while (null !== $category) {
            $names[] = $category->name;
            $category = $category->parent;
        }

        return implode(' > ', array_reverse($names));
    }

    public function getLevelAttribute(): int
    {
        $level = 0;
        $category = $this;

        while (null !== $category->parent) {
            $level++;
            $category = $category->parent;
        }

        return $level;
    }

    public function getHasChildrenAttribute(): bool
    {
        return $this->children()->count() > 0;
    }

    public function getBreadcrumbsAttribute(): array
    {
        $breadcrumbs = [];
        $category = $this;

        while (null !== $category) {
            $breadcrumbs[] = [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ];
            $category = $category->parent;
        }

        return array_reverse($breadcrumbs);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Переопределяем метод для правильной сортировки
     */
    protected function getScopeAttributes(): array
    {
        return ['parent_id'];
    }
}
