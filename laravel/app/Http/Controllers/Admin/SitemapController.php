<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SitemapGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Throwable;

class SitemapController extends Controller
{
    public function __construct(private SitemapGenerator $sitemapGenerator) {}

    public function edit(): View
    {
        return view('admin.sitemap.edit', [
            'status' => $this->sitemapGenerator->status(),
        ]);
    }

    public function generate(): RedirectResponse
    {
        try {
            $result = $this->sitemapGenerator->generate();
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('admin.sitemap.edit')
                ->with('error', 'Sitemap generation failed: '.$exception->getMessage());
        }

        return redirect()
            ->route('admin.sitemap.edit')
            ->with(
                'success',
                sprintf(
                    'Sitemap generated successfully with %d URLs (%d products, %d categories).',
                    $result['url_count'],
                    $result['product_count'],
                    $result['category_count'],
                ),
            );
    }
}
