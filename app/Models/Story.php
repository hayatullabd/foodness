<?php

namespace App\Models;

use App\Support\PlaceholderImage;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'title',
    'tag',
    'media_type',
    'media_path',
    'thumbnail_path',
    'link_url',
    'link_text',
    'duration',
    'sort_order',
    'is_active',
    'views_count',
])]
class Story extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'duration' => 'integer',
            'sort_order' => 'integer',
            'views_count' => 'integer',
        ];
    }

    /**
     * Scope for active stories ordered by sort_order.
     *
     * @param  Builder<Story>  $query
     * @return Builder<Story>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->orderBy('sort_order')
            ->latest('id');
    }

    public function isImage(): bool
    {
        return $this->media_type === 'image';
    }

    public function isVideo(): bool
    {
        return $this->media_type === 'video';
    }

    public function youtubeId(): ?string
    {
        $path = $this->media_path ?? '';
        if (empty($path)) {
            return null;
        }

        if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?|shorts)/|.*[?&]v=)|youtu\.be/)([^"&?/\s]{11})%i', $path, $match)) {
            return $match[1];
        }

        return null;
    }

    public function isYoutube(): bool
    {
        return ! empty($this->youtubeId());
    }

    public function hasImageThumbnail(): bool
    {
        return ! empty($this->thumbnail_path) || $this->isImage() || $this->isYoutube();
    }

    public function mediaUrl(): string
    {
        if (str_starts_with($this->media_path, 'http://') || str_starts_with($this->media_path, 'https://')) {
            return $this->media_path;
        }

        return asset('storage/'.$this->media_path);
    }

    public function thumbnailUrl(): string
    {
        if (! empty($this->thumbnail_path)) {
            if (str_starts_with($this->thumbnail_path, 'http://') || str_starts_with($this->thumbnail_path, 'https://')) {
                return $this->thumbnail_path;
            }

            return asset('storage/'.$this->thumbnail_path);
        }

        if ($this->isImage()) {
            return $this->mediaUrl();
        }

        if ($ytId = $this->youtubeId()) {
            return 'https://img.youtube.com/vi/'.$ytId.'/hqdefault.jpg';
        }

        if ($this->isVideo() && ! empty($this->media_path)) {
            return $this->mediaUrl();
        }

        return PlaceholderImage::url();
    }
}
