<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SeoSetting;
use App\Support\StoreSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SeoSettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.seo-settings.edit', [
            'seoSetting' => SeoSetting::current(),
            'storeName' => StoreSettings::get('store_name', config('app.name', 'Store')),
            'storeUrl' => rtrim((string) config('app.url'), '/'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'meta_keywords' => ['nullable', 'string', 'max:1000'],
            'google_site_verification' => ['nullable', 'string', 'max:255'],
        ]);

        $seoSetting = SeoSetting::current();
        $seoSetting->fill([
            'site_title' => $this->nullableTrim($validated['site_title'] ?? null),
            'meta_description' => $this->nullableTrim($validated['meta_description'] ?? null),
            'meta_keywords' => $this->normalizeKeywords($validated['meta_keywords'] ?? null),
            'google_site_verification' => $this->nullableTrim($validated['google_site_verification'] ?? null),
        ]);
        $seoSetting->save();

        SeoSetting::forgetCache();

        return redirect()
            ->route('admin.seo-settings.edit')
            ->with('success', 'SEO settings saved successfully.');
    }

    private function nullableTrim(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalizeKeywords(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $keywords = collect(preg_split('/\s*,\s*/', $value) ?: [])
            ->map(fn (string $keyword): string => trim($keyword))
            ->filter()
            ->unique()
            ->values();

        return $keywords->isEmpty() ? null : $keywords->implode(', ');
    }
}
