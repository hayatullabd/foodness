<?php

namespace App\Models;

use App\Support\PlaceholderImage;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'slug', 'image', 'status', 'is_front', 'sort_order'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'is_front' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function subcategories(): HasMany
    {
        return $this->hasMany(Subcategory::class);
    }

    public function imageUrl(): string
    {
        if ($this->image && (Storage::disk('public')->exists($this->image) || file_exists(public_path('storage/'.$this->image)) || str_starts_with($this->image, 'http'))) {
            return str_starts_with($this->image, 'http') ? $this->image : asset('storage/'.$this->image);
        }

        if ($this->relationLoaded('products') && $this->products->isNotEmpty()) {
            foreach ($this->products as $p) {
                $pImg = $p->primaryImagePath();
                if ($pImg && file_exists(public_path('storage/'.$pImg))) {
                    return asset('storage/'.$pImg);
                }
            }
        }

        return PlaceholderImage::url();
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('status', true);
    }

    protected static function booted(): void
    {
        static::saved(function (): void {
            Cache::forget('store.categories.active');
        });

        static::deleted(function (): void {
            Cache::forget('store.categories.active');
        });
    }
}
