<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FraudSetting;
use App\Models\Order;
use App\Models\User;
use App\Services\BdCourierFraudService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $customers = User::query()
            ->withCount('orders')
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.$request->string('q')->toString().'%';
                $query->where(function ($inner) use ($term): void {
                    $inner->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('phone', 'like', $term);
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.customers.index', compact('customers'));
    }

    public function show(User $customer): View
    {
        $customer->load(['orders' => fn ($query) => $query->latest()]);

        $lastFraudOrder = $customer->orders
            ->first(fn (Order $order): bool => $order->fraud_score !== null);

        return view('admin.customers.show', [
            'customer' => $customer,
            'lastFraudOrder' => $lastFraudOrder,
        ]);
    }

    public function fraudCheck(User $customer, BdCourierFraudService $fraudService): RedirectResponse
    {
        if (! FraudSetting::current()->isConfigured()) {
            return back()->with('error', 'Courier Ratio API is disabled or missing a key. Configure it under Manage Courier Ratio API.');
        }

        $customer->loadMissing('orders');

        $phone = trim((string) ($customer->phone ?: $customer->orders->first()?->customer_phone));

        if ($phone === '') {
            return back()->with('error', 'This customer has no phone number to check.');
        }

        $result = $fraudService->check($phone);

        if (! $result['ok']) {
            return back()->with('error', $result['message']);
        }

        $order = Order::query()
            ->where(function ($query) use ($customer, $phone): void {
                $query->where('user_id', $customer->id)
                    ->orWhere('customer_phone', $phone);
            })
            ->latest()
            ->first();

        if ($order) {
            $order->update([
                'fraud_score' => $result['score'],
                'fraud_report' => $result['report'],
            ]);
        }

        $scoreLabel = $result['score'] !== null
            ? number_format($result['score'], 2).'% ratio'
            : 'no summary score';

        return back()->with('success', 'Courier ratio check complete: '.$scoreLabel.'.');
    }

    /**
     * Delete a customer account. Only Super Admin is authorized to perform this action.
     */
    public function destroy(User $customer): RedirectResponse
    {
        $admin = auth('admin')->user();
        if (! $admin || ! $admin->isSuperAdmin()) {
            abort(403, 'Only a Super Admin can delete customer accounts.');
        }

        $customerName = $customer->name;
        $customer->delete();

        return redirect()
            ->route('admin.customers.index')
            ->with('success', "Customer '{$customerName}' has been deleted successfully.");
    }
}
