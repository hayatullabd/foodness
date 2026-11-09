<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'section', 'is_active', 'sort_order'])]
class OrderStatus extends Model
{
    public const SECTION_ORDERS = 'orders';

    public const SECTION_COURIER = 'courier';

    public const SECTIONS = [
        self::SECTION_ORDERS,
        self::SECTION_COURIER,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    #[Scope]
    protected function ordered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    #[Scope]
    protected function forSection(Builder $query, string $section): Builder
    {
        return $query->where('section', $section);
    }

    #[Scope]
    protected function ordersSection(Builder $query): Builder
    {
        return $query->where('section', self::SECTION_ORDERS);
    }

    #[Scope]
    protected function courierSection(Builder $query): Builder
    {
        return $query->where('section', self::SECTION_COURIER);
    }

    public function sectionLabel(): string
    {
        return match ($this->section) {
            self::SECTION_COURIER => 'Courier',
            default => 'All Orders',
        };
    }

    public function isCourier(): bool
    {
        return $this->section === self::SECTION_COURIER;
    }

    public function isOrders(): bool
    {
        return $this->section === self::SECTION_ORDERS;
    }
}
