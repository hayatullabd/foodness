<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

class SitemapGenerator
{
    public const RELATIVE_PATH = 'sitemap.xml';

    /**
     * @return array{
     *     path: string,
     *     url: string,
     *     url_count: int,
     *     product_count: int,
     *     category_count: int,
     *     generated_at: Carbon
     * }
     */
    public function generate(): array
    {
        $baseUrl = rtrim((string) config('app.url'), '/');
        $now = now()->toAtomString();

        $urls = [
            $this->urlEntry($baseUrl.'/', $now, 'daily', '1.0'),
            $this->urlEntry(route('shop.index'), $now, 'daily', '0.9'),
        ];

        $categories = Category::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'slug', 'updated_at']);

        foreach ($categories as $category) {
            $urls[] = $this->urlEntry(
                route('shop.category', $category),
                optional($category->updated_at)?->toAtomString() ?? $now,
                'weekly',
                '0.8',
            );
        }

        $products = Product::query()
            ->storeVisible()
            ->orderByDesc('updated_at')
            ->get(['id', 'slug', 'updated_at']);

        foreach ($products as $product) {
            $urls[] = $this->urlEntry(
                route('product.show', $product),
                optional($product->updated_at)?->toAtomString() ?? $now,
                'weekly',
                '0.7',
            );
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n"
            .implode('', $urls)
            .'</urlset>'."\n";

        $path = public_path(self::RELATIVE_PATH);
        File::put($path, $xml);

        $generatedAt = now();

        return [
            'path' => $path,
            'url' => $baseUrl.'/'.self::RELATIVE_PATH,
            'url_count' => count($urls),
            'product_count' => $products->count(),
            'category_count' => $categories->count(),
            'generated_at' => $generatedAt,
        ];
    }

    /**
     * @return array{
     *     exists: bool,
     *     path: string,
     *     relative_path: string,
     *     url: string,
     *     size_bytes: int|null,
     *     url_count: int|null,
     *     generated_at: Carbon|null
     * }
     */
    public function status(): array
    {
        $path = public_path(self::RELATIVE_PATH);
        $exists = File::exists($path);
        $baseUrl = rtrim((string) config('app.url'), '/');

        $urlCount = null;
        $generatedAt = null;
        $sizeBytes = null;

        if ($exists) {
            $contents = File::get($path);
            $sizeBytes = File::size($path);
            $urlCount = substr_count($contents, '<url>');
            $generatedAt = Carbon::createFromTimestamp(File::lastModified($path));
        }

        return [
            'exists' => $exists,
            'path' => $path,
            'relative_path' => '/public/'.self::RELATIVE_PATH,
            'url' => $baseUrl.'/'.self::RELATIVE_PATH,
            'size_bytes' => $sizeBytes,
            'url_count' => $urlCount,
            'generated_at' => $generatedAt,
        ];
    }

    private function urlEntry(string $loc, string $lastmod, string $changefreq, string $priority): string
    {
        return '  <url>'."\n"
            .'    <loc>'.e($loc).'</loc>'."\n"
            .'    <lastmod>'.e($lastmod).'</lastmod>'."\n"
            .'    <changefreq>'.e($changefreq).'</changefreq>'."\n"
            .'    <priority>'.e($priority).'</priority>'."\n"
            .'  </url>'."\n";
    }
}
