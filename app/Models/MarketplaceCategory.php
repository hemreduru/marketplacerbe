<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplaceCategory extends Model
{
    protected $fillable = [
        'marketplace_id',
        'marketplace_category_id',
        'name',
        'parent_id',
        'full_path',
        'level',
        'is_leaf',
        'attributes',
        'marketplace_raw_data',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'is_leaf' => 'boolean',
            'attributes' => 'array',
            'marketplace_raw_data' => 'array',
        ];
    }

    /**
     * Get the marketplace that owns the category.
     */
    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class);
    }

    /**
     * Get the parent category.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(MarketplaceCategory::class, 'parent_id');
    }

    /**
     * Get the child categories.
     */
    public function children(): HasMany
    {
        return $this->hasMany(MarketplaceCategory::class, 'parent_id');
    }

    /**
     * Get all descendants recursively.
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    /**
     * Scope to get only root categories (level 0).
     */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id')->orWhere('level', 0);
    }

    /**
     * Scope to get only leaf categories (can have products).
     */
    public function scopeLeaves($query)
    {
        return $query->where('is_leaf', true);
    }
}
