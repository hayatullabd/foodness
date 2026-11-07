<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FraudOrderRuleSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FraudOrderRuleSettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.fraud-order-rules.edit', [
            'settings' => FraudOrderRuleSetting::current(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'min_success_ratio' => ['required', 'integer', 'min:0', 'max:100'],
            'enabled' => ['nullable', 'boolean'],
        ]);

        $settings = FraudOrderRuleSetting::current();
        $settings->fill([
            'enabled' => $request->boolean('enabled'),
            'min_success_ratio' => (int) $validated['min_success_ratio'],
        ]);
        $settings->save();

        return redirect()
            ->route('admin.fraud-order-rules.edit')
            ->with('success', 'Courier Ratio rules saved successfully.');
    }
}
