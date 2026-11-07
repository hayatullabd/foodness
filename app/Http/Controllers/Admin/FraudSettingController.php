<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FraudSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FraudSettingController extends Controller
{
    public function edit(): View
    {
        $fraudSetting = FraudSetting::current();

        return view('admin.fraud-settings.edit', [
            'fraudSetting' => $fraudSetting,
            'maskedBdCourierKey' => $fraudSetting->maskedApiKey(FraudSetting::PROVIDER_BDCOURIER),
            'maskedHoorinKey' => $fraudSetting->maskedApiKey(FraudSetting::PROVIDER_HOORIN),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'target_provider' => ['nullable', 'string', 'in:bdcourier,hoorin'],
            'provider' => ['nullable', 'string', 'in:bdcourier,hoorin'],
            'bdcourier_api_key' => ['nullable', 'string', 'max:2000'],
            'hoorin_api_key' => ['nullable', 'string', 'max:2000'],
            'api_key' => ['nullable', 'string', 'max:2000'], // for backwards compatibility
            'is_active' => ['nullable', 'boolean'],
            'activate_provider' => ['nullable', 'boolean'],
        ]);

        $fraudSetting = FraudSetting::current();
        $providerKeys = is_array($fraudSetting->provider_keys) ? $fraudSetting->provider_keys : [];

        // If target_provider is provided, update that specific provider's card
        $target = $validated['target_provider'] ?? $validated['provider'] ?? null;

        if ($target === FraudSetting::PROVIDER_BDCOURIER) {
            $key = trim((string) ($validated['bdcourier_api_key'] ?? $validated['api_key'] ?? ''));
            if ($key !== '') {
                $providerKeys[FraudSetting::PROVIDER_BDCOURIER] = $key;
                $fraudSetting->api_key = $key;
            }

            if ($request->boolean('is_active')) {
                $fraudSetting->provider = FraudSetting::PROVIDER_BDCOURIER;
                $fraudSetting->is_active = true;
            } elseif ($fraudSetting->provider === FraudSetting::PROVIDER_BDCOURIER) {
                // If it was the active provider and user toggled it off
                $fraudSetting->is_active = false;
            }
        } elseif ($target === FraudSetting::PROVIDER_HOORIN) {
            $key = trim((string) ($validated['hoorin_api_key'] ?? ''));
            if ($key !== '') {
                $providerKeys[FraudSetting::PROVIDER_HOORIN] = $key;
            }

            if ($request->boolean('is_active')) {
                $fraudSetting->provider = FraudSetting::PROVIDER_HOORIN;
                $fraudSetting->is_active = true;
            } elseif ($fraudSetting->provider === FraudSetting::PROVIDER_HOORIN) {
                // If it was the active provider and user toggled it off
                $fraudSetting->is_active = false;
            }
        } else {
            // General update if submitted together
            $bdcourierKey = trim((string) ($validated['bdcourier_api_key'] ?? $validated['api_key'] ?? ''));
            if ($bdcourierKey !== '') {
                $providerKeys[FraudSetting::PROVIDER_BDCOURIER] = $bdcourierKey;
                $fraudSetting->api_key = $bdcourierKey;
            }

            $hoorinKey = trim((string) ($validated['hoorin_api_key'] ?? ''));
            if ($hoorinKey !== '') {
                $providerKeys[FraudSetting::PROVIDER_HOORIN] = $hoorinKey;
            }

            if (! empty($validated['provider'])) {
                $fraudSetting->provider = (string) $validated['provider'];
            }
            $fraudSetting->is_active = $request->boolean('is_active');
        }

        $fraudSetting->provider_keys = $providerKeys;
        $fraudSetting->save();

        $providerLabel = $target === FraudSetting::PROVIDER_HOORIN ? 'Hoorin' : 'BD Courier';

        return redirect()
            ->route('admin.fraud-settings.edit')
            ->with('success', "{$providerLabel} settings updated successfully.");
    }
}
