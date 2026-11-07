<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['ip', 'reason'])]
class IpBlock extends Model
{
    public static function isBlocked(string $ip): bool
    {
        return static::query()->where('ip', $ip)->exists();
    }
}
