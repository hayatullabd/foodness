<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['enabled', 'min_success_ratio'])]
class FraudOrderRuleSetting extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'min_success_ratio' => 'integer',
        ];
    }

    public static function current(): self
    {
        $existing = static::query()->first();

        if ($existing) {
            return $existing;
        }

        return static::query()->create([
            'enabled' => false,
            'min_success_ratio' => 50,
        ]);
    }
}
