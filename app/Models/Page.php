<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'slug',
    'title',
    'subtitle',
    'content',
    'extra_data',
    'meta_title',
    'meta_description',
    'is_active',
    'sort_order',
])]
class Page extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'extra_data' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Scope to only include active / published pages.
     *
     * @param  Builder<static>  $query
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
