<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id', 'type', 'path', 'youtube_id', 'title', 'sort_order'])]
class ProductVideo extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isYoutube(): bool
    {
        return $this->type === 'youtube';
    }

    public function isUpload(): bool
    {
        return $this->type === 'upload';
    }

    public function embedUrl(): ?string
    {
        if (! $this->isYoutube() || blank($this->youtube_id)) {
            return null;
        }

        return 'https://www.youtube.com/embed/'.$this->youtube_id;
    }

    public function thumbnailUrl(): ?string
    {
        if ($this->isYoutube() && filled($this->youtube_id)) {
            return 'https://img.youtube.com/vi/'.$this->youtube_id.'/hqdefault.jpg';
        }

        return null;
    }

    public function publicUrl(): ?string
    {
        if ($this->isUpload() && filled($this->path)) {
            return asset('storage/'.$this->path);
        }

        return $this->embedUrl();
    }
}
