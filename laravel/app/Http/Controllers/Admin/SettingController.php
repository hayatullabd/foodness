<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\ImageUploadService;
use App\Support\StoreSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingController extends Controller
{
    /**
     * @var list<string>
     */
    private const KEYS = [
        'store_name',
        'store_phone',
        'store_email',
        'store_address',
        'store_logo',
        'currency',
        'currency_symbol',
        'invoice_prefix',
        'anti_inspect_enabled',
        'anti_copy_enabled',
    ];

    public function edit(): View
    {
        $settings = Setting::query()
            ->whereIn('key', self::KEYS)
            ->pluck('value', 'key');

        return view('admin.settings.edit', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_name' => ['required', 'string', 'max:255'],
            'store_phone' => ['nullable', 'string', 'max:50'],
            'store_email' => ['nullable', 'email', 'max:255'],
            'store_address' => ['nullable', 'string', 'max:1000'],
            'store_logo' => ['nullable', 'image', 'max:2048'],
            'currency' => ['required', 'string', 'max:10'],
            'currency_symbol' => ['required', 'string', 'max:10'],
            'invoice_prefix' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9_-]+$/'],
            'anti_inspect_enabled' => ['nullable', 'boolean'],
            'anti_copy_enabled' => ['nullable', 'boolean'],
        ]);

        $prefix = strtoupper(trim((string) ($validated['invoice_prefix'] ?? '')));
        $prefix = $prefix !== '' ? $prefix : 'ORD';

        foreach (['store_name', 'store_phone', 'store_email', 'store_address', 'currency', 'currency_symbol'] as $key) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $validated[$key] ?? null],
            );
        }

        Setting::query()->updateOrCreate(
            ['key' => 'invoice_prefix'],
            ['value' => $prefix],
        );

        Setting::query()->updateOrCreate(
            ['key' => 'anti_inspect_enabled'],
            ['value' => $request->boolean('anti_inspect_enabled') ? '1' : '0'],
        );

        Setting::query()->updateOrCreate(
            ['key' => 'anti_copy_enabled'],
            ['value' => $request->boolean('anti_copy_enabled') ? '1' : '0'],
        );

        StoreSettings::forget();

        if ($request->hasFile('store_logo')) {
            $existing = Setting::query()->where('key', 'store_logo')->value('value');
            if ($existing) {
                Storage::disk('public')->delete($existing);
            }

            $path = ImageUploadService::upload(
                $request->file('store_logo'),
                'settings',
                'store-logo'
            );

            Setting::query()->updateOrCreate(
                ['key' => 'store_logo'],
                ['value' => $path],
            );
        } elseif ($request->filled('store_logo_media_path') && ImageUploadService::exists($request->input('store_logo_media_path'))) {
            $path = ImageUploadService::sanitizePath($request->input('store_logo_media_path'));
            Setting::query()->updateOrCreate(
                ['key' => 'store_logo'],
                ['value' => $path],
            );
        }

        return redirect()
            ->route('admin.settings.edit')
            ->with('success', 'Settings saved successfully.');
    }
}
