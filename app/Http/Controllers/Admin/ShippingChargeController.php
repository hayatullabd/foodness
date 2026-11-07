<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\ShippingCharge;
use App\Support\StoreSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShippingChargeController extends Controller
{
    public function index(): View
    {
        $shippingCharges = ShippingCharge::query()
            ->orderBy('name')
            ->paginate(15);

        $freeShippingThreshold = StoreSettings::get('free_shipping_threshold');

        return view('admin.shipping-charges.index', compact('shippingCharges', 'freeShippingThreshold'));
    }

    public function updateThreshold(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'free_shipping_threshold' => ['nullable', 'numeric', 'min:0'],
        ]);

        $threshold = $validated['free_shipping_threshold'];
        Setting::query()->updateOrCreate(
            ['key' => 'free_shipping_threshold'],
            ['value' => filled($threshold) ? (string) $threshold : null]
        );

        StoreSettings::forget();

        return redirect()
            ->route('admin.shipping-charges.index')
            ->with('success', 'Free delivery threshold updated successfully.');
    }

    public function create(): View
    {
        return view('admin.shipping-charges.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        ShippingCharge::query()->create([
            'name' => $validated['name'],
            'amount' => $validated['amount'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('admin.shipping-charges.index')
            ->with('success', 'Shipping charge created.');
    }

    public function edit(ShippingCharge $shippingCharge): View
    {
        return view('admin.shipping-charges.edit', compact('shippingCharge'));
    }

    public function update(Request $request, ShippingCharge $shippingCharge): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $shippingCharge->update([
            'name' => $validated['name'],
            'amount' => $validated['amount'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.shipping-charges.index')
            ->with('success', 'Shipping charge updated.');
    }

    public function destroy(ShippingCharge $shippingCharge): RedirectResponse
    {
        $shippingCharge->delete();

        return redirect()
            ->route('admin.shipping-charges.index')
            ->with('success', 'Shipping charge deleted.');
    }
}
