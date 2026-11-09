<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiIntegration;
use App\Services\PaymentMethodService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentGatewayController extends Controller
{
    public function edit(): View
    {
        $cod = ApiIntegration::forProvider(ApiIntegration::PROVIDER_COD);
        $sslcommerz = ApiIntegration::forProvider(ApiIntegration::PROVIDER_SSLCOMMERZ);
        $manualPayment = ApiIntegration::forProvider(ApiIntegration::PROVIDER_MANUAL_PAYMENT);

        return view('admin.api-integrations.payment-gateway', [
            'cod' => $cod,
            'sslcommerz' => $sslcommerz,
            'sslcommerzMaskedStorePassword' => $sslcommerz->maskedCredential('store_password'),
            'manualPayment' => $manualPayment,
        ]);
    }

    public function update(Request $request, string $provider): RedirectResponse
    {
        return match ($provider) {
            ApiIntegration::PROVIDER_COD => $this->updateCod($request),
            ApiIntegration::PROVIDER_SSLCOMMERZ => $this->updateSslCommerz($request),
            ApiIntegration::PROVIDER_MANUAL_PAYMENT => $this->updateManualPayment($request),
            default => abort(404),
        };
    }

    private function updateCod(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'instructions' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $integration = ApiIntegration::forProvider(ApiIntegration::PROVIDER_COD);
        $integration->mergeCredentials([
            'title' => $validated['title'] ?? 'Cash on Delivery',
            'instructions' => $validated['instructions'] ?? 'Pay in cash when you receive the parcel at your doorstep.',
        ]);
        $integration->is_active = $request->boolean('is_active');
        $integration->save();

        PaymentMethodService::clearCache();

        return redirect()
            ->route('admin.api-integrations.payment-gateway.edit')
            ->with('success', 'Cash on Delivery (COD) configuration saved successfully.');
    }

    private function updateSslCommerz(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_id' => ['nullable', 'string', 'max:255'],
            'store_password' => ['nullable', 'string', 'max:2000'],
            'is_sandbox' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $integration = ApiIntegration::forProvider(ApiIntegration::PROVIDER_SSLCOMMERZ);
        $integration->mergeCredentials([
            'store_id' => $validated['store_id'] ?? '',
            'store_password' => $validated['store_password'] ?? '',
            'is_sandbox' => $request->boolean('is_sandbox'),
        ], $integration->secretKeys());
        $integration->is_active = $request->boolean('is_active');
        $integration->save();

        PaymentMethodService::clearCache();

        return redirect()
            ->route('admin.api-integrations.payment-gateway.edit')
            ->with('success', 'SSLCommerz configuration saved successfully.');
    }

    private function updateManualPayment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'account_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $integration = ApiIntegration::forProvider(ApiIntegration::PROVIDER_MANUAL_PAYMENT);
        $integration->mergeCredentials([
            'title' => $validated['title'] ?? '',
            'account_name' => $validated['account_name'] ?? '',
            'account_number' => $validated['account_number'] ?? '',
            'bank_name' => $validated['bank_name'] ?? '',
            'instructions' => $validated['instructions'] ?? '',
        ]);
        $integration->is_active = $request->boolean('is_active');
        $integration->save();

        PaymentMethodService::clearCache();

        return redirect()
            ->route('admin.api-integrations.payment-gateway.edit')
            ->with('success', 'Manual Payment configuration saved successfully.');
    }
}
