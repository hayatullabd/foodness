<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class PlaceholderImage
{
    /**
     * Standard square/card placeholder image URL (stored in storage).
     */
    public static function url(): string
    {
        return asset('storage/placeholder.svg');
    }

    /**
     * Wide banner/slider placeholder image URL (stored in storage).
     */
    public static function bannerUrl(): string
    {
        return asset('storage/placeholder-banner.svg');
    }

    /**
     * Resolve a stored image path, or return the standard placeholder URL if not found.
     */
    public static function resolve(?string $path, bool $isBanner = false): string
    {
        if (! empty($path)) {
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                return $path;
            }

            if (Storage::disk('public')->exists($path) || file_exists(public_path('storage/'.$path))) {
                return asset('storage/'.$path);
            }
        }

        return $isBanner ? self::bannerUrl() : self::url();
    }
}
