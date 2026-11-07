<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchasePayment;
use App\Models\Size;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PurchaseController extends Controller
{
    public function index(Request $request): View
    {
        $year = (int) now()->year;
        $month = (int) now()->month;

        $stats = [
            'year_total' => (float) Purchase::query()
                ->whereYear('purchase_date', $year)
                ->sum('total'),
            'month_total' => (float) Purchase::query()
                ->whereYear('purchase_date', $year)
                ->whereMonth('purchase_date', $month)
                ->sum('total'),
            'day_total' => (float) Purchase::query()
                ->whereDate('purchase_date', now()->toDateString())
                ->sum('total'),
            'supplier_due' => (float) Purchase::query()->sum('due'),
        ];

        $purchases = Purchase::query()
            ->with(['supplier', 'items.product', 'items.variant'])
            ->latest('purchase_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.purchases.index', [
            'stats' => $stats,
            'purchases' => $purchases,
            'suppliers' => Supplier::query()->orderBy('name')->get(),
            'products' => Product::query()
                ->with(['variants' => fn ($q) => $q->orderBy('sort_order')])
                ->orderBy('name')
                ->get(['id', 'name', 'sku', 'stock']),
            'colors' => Color::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'hex']),
            'sizes' => Size::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'name']),
            'invoiceNo' => Purchase::generateInvoiceNo(),
            'exportMonth' => (int) $request->input('month', $month),
            'exportYear' => (int) $request->input('year', $year),
            'exportFrom' => $request->input('from'),
            'exportTo' => $request->input('to'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedPurchase($request);

        DB::transaction(function () use ($validated): void {
            $quantity = (int) $validated['quantity'];
            $unitCost = (float) $validated['unit_cost'];
            $subtotal = round($quantity * $unitCost, 2);

            $totals = Purchase::computeTotals([
                'subtotal' => $subtotal,
                'discount' => $validated['discount'] ?? 0,
                'shipping_cost' => $validated['shipping_cost'] ?? 0,
                'paid_amount' => $validated['paid_amount'] ?? 0,
            ]);

            $purchase = Purchase::query()->create([
                'supplier_id' => $validated['supplier_id'],
                'invoice_no' => $validated['invoice_no'],
                'purchase_date' => $validated['purchase_date'],
                'note' => $validated['note'] ?? null,
                ...$totals,
            ]);

            $variant = ! empty($validated['variant_id'])
                ? ProductVariant::query()->where('product_id', $validated['product_id'])->find($validated['variant_id'])
                : null;

            $color = $validated['color'] ?? $variant?->attributeMap()['Color'] ?? null;
            $size = $validated['size'] ?? $variant?->attributeMap()['Size'] ?? null;
            $variationName = $validated['variation_name'] ?? $variant?->name ?? null;

            PurchaseItem::query()->create([
                'purchase_id' => $purchase->id,
                'product_id' => $validated['product_id'],
                'variant_id' => $variant?->id,
                'variation_name' => $variationName,
                'color' => $color,
                'size' => $size,
                'quantity' => $quantity,
                'unit_cost' => number_format($unitCost, 2, '.', ''),
                'line_total' => number_format($subtotal, 2, '.', ''),
            ]);

            Product::query()
                ->whereKey($validated['product_id'])
                ->lockForUpdate()
                ->firstOrFail()
                ->increment('stock', $quantity);

            if ($variant) {
                $variant->increment('stock', $quantity);
            }

            if ((float) $totals['paid_amount'] > 0) {
                PurchasePayment::query()->create([
                    'purchase_id' => $purchase->id,
                    'amount' => $totals['paid_amount'],
                    'note' => 'Initial payment',
                    'paid_at' => now(),
                ]);
            }
        });

        return redirect()
            ->route('admin.purchases.index')
            ->with('success', 'Purchase saved successfully.');
    }

    public function show(Purchase $purchase): View
    {
        $purchase->load(['supplier', 'items.product', 'items.variant', 'payments']);

        return view('admin.purchases.show', compact('purchase'));
    }

    public function edit(Purchase $purchase): View
    {
        $purchase->load(['items.variant']);

        return view('admin.purchases.edit', [
            'purchase' => $purchase,
            'item' => $purchase->items->first(),
            'suppliers' => Supplier::query()->orderBy('name')->get(),
            'products' => Product::query()
                ->with(['variants' => fn ($q) => $q->orderBy('sort_order')])
                ->orderBy('name')
                ->get(['id', 'name', 'sku', 'stock']),
            'colors' => Color::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'hex']),
            'sizes' => Size::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Purchase $purchase): RedirectResponse
    {
        $validated = $this->validatedPurchase($request, $purchase);

        DB::transaction(function () use ($validated, $purchase): void {
            $purchase->load('items');
            $existing = $purchase->items->first();

            if ($existing) {
                Product::query()
                    ->whereKey($existing->product_id)
                    ->lockForUpdate()
                    ->firstOrFail()
                    ->decrement('stock', $existing->quantity);

                if ($existing->variant_id) {
                    ProductVariant::query()
                        ->whereKey($existing->variant_id)
                        ->lockForUpdate()
                        ->first()
                        ?->decrement('stock', $existing->quantity);
                }
            }

            $quantity = (int) $validated['quantity'];
            $unitCost = (float) $validated['unit_cost'];
            $subtotal = round($quantity * $unitCost, 2);

            $totals = Purchase::computeTotals([
                'subtotal' => $subtotal,
                'discount' => $validated['discount'] ?? 0,
                'shipping_cost' => $validated['shipping_cost'] ?? 0,
                'paid_amount' => $validated['paid_amount'] ?? 0,
            ]);

            $purchase->update([
                'supplier_id' => $validated['supplier_id'],
                'invoice_no' => $validated['invoice_no'],
                'purchase_date' => $validated['purchase_date'],
                'note' => $validated['note'] ?? null,
                ...$totals,
            ]);

            $variant = ! empty($validated['variant_id'])
                ? ProductVariant::query()->where('product_id', $validated['product_id'])->find($validated['variant_id'])
                : null;

            $color = $validated['color'] ?? $variant?->attributeMap()['Color'] ?? null;
            $size = $validated['size'] ?? $variant?->attributeMap()['Size'] ?? null;
            $variationName = $validated['variation_name'] ?? $variant?->name ?? null;

            $itemData = [
                'product_id' => $validated['product_id'],
                'variant_id' => $variant?->id,
                'variation_name' => $variationName,
                'color' => $color,
                'size' => $size,
                'quantity' => $quantity,
                'unit_cost' => number_format($unitCost, 2, '.', ''),
                'line_total' => number_format($subtotal, 2, '.', ''),
            ];

            if ($existing) {
                $existing->update($itemData);
            } else {
                PurchaseItem::query()->create([
                    'purchase_id' => $purchase->id,
                    ...$itemData,
                ]);
            }

            Product::query()
                ->whereKey($validated['product_id'])
                ->lockForUpdate()
                ->firstOrFail()
                ->increment('stock', $quantity);

            if ($variant) {
                $variant->increment('stock', $quantity);
            }
        });

        return redirect()
            ->route('admin.purchases.index')
            ->with('success', 'Purchase updated successfully.');
    }

    public function destroy(Purchase $purchase): RedirectResponse
    {
        DB::transaction(function () use ($purchase): void {
            $purchase->load('items');

            foreach ($purchase->items as $item) {
                $product = Product::query()
                    ->whereKey($item->product_id)
                    ->lockForUpdate()
                    ->first();

                if ($product) {
                    $newStock = max(0, $product->stock - $item->quantity);
                    $product->update(['stock' => $newStock]);
                }

                if ($item->variant_id) {
                    $variant = ProductVariant::query()
                        ->whereKey($item->variant_id)
                        ->lockForUpdate()
                        ->first();
                    if ($variant) {
                        $newVarStock = max(0, $variant->stock - $item->quantity);
                        $variant->update(['stock' => $newVarStock]);
                    }
                }
            }

            $purchase->delete();
        });

        return redirect()
            ->route('admin.purchases.index')
            ->with('success', 'Purchase deleted successfully.');
    }

    public function payDue(Request $request, Purchase $purchase): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        if ($purchase->isPaid()) {
            return back()->with('error', 'This purchase is already fully paid.');
        }

        $amount = round((float) $validated['amount'], 2);

        if ($amount > (float) $purchase->due) {
            return back()->with('error', 'Payment amount cannot exceed the due balance.');
        }

        DB::transaction(function () use ($purchase, $amount, $validated): void {
            PurchasePayment::query()->create([
                'purchase_id' => $purchase->id,
                'amount' => number_format($amount, 2, '.', ''),
                'note' => $validated['note'] ?? 'Due payment',
                'paid_at' => now(),
            ]);

            $paid = round((float) $purchase->paid_amount + $amount, 2);
            $due = max(0, round((float) $purchase->total - $paid, 2));

            $purchase->update([
                'paid_amount' => number_format($paid, 2, '.', ''),
                'due' => number_format($due, 2, '.', ''),
            ]);
        });

        return redirect()
            ->route('admin.purchases.index')
            ->with('success', 'Payment recorded successfully.');
    }

    public function export(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $query = Purchase::query()->with(['supplier', 'items.product', 'items.variant'])->orderBy('purchase_date')->orderBy('id');

        if (! empty($validated['from']) || ! empty($validated['to'])) {
            if (! empty($validated['from'])) {
                $query->whereDate('purchase_date', '>=', $validated['from']);
            }
            if (! empty($validated['to'])) {
                $query->whereDate('purchase_date', '<=', $validated['to']);
            }
        } else {
            $month = (int) ($validated['month'] ?? now()->month);
            $year = (int) ($validated['year'] ?? now()->year);
            $query->whereYear('purchase_date', $year)->whereMonth('purchase_date', $month);
        }

        $filename = 'purchases-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Invoice', 'Supplier', 'Products / Attributes', 'Subtotal', 'Discount', 'Shipping', 'Total', 'Paid', 'Due', 'Note']);

            $query->chunk(200, function ($purchases) use ($handle): void {
                foreach ($purchases as $purchase) {
                    $itemsDesc = $purchase->items->map(function ($it): string {
                        $label = $it->attributeLabel();

                        return ($it->product?->name ?: 'Item').($label ? ' ['.$label.']' : '').' (x'.$it->quantity.')';
                    })->implode('; ');

                    fputcsv($handle, [
                        $purchase->purchase_date?->format('Y-m-d'),
                        $purchase->invoice_no,
                        $purchase->supplier?->name,
                        $itemsDesc,
                        $purchase->subtotal,
                        $purchase->discount,
                        $purchase->shipping_cost,
                        $purchase->total,
                        $purchase->paid_amount,
                        $purchase->due,
                        $purchase->note,
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function reports(Request $request): View
    {
        $purchases = $this->filteredPurchases($request)
            ->with(['supplier', 'items.product', 'items.variant'])
            ->latest('purchase_date')
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.purchases.reports', [
            'purchases' => $purchases,
            'suppliers' => Supplier::query()->orderBy('name')->get(),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
            'supplierId' => $request->input('supplier_id'),
            'totalAmount' => (float) $this->filteredPurchases($request)->sum('total'),
            'totalDue' => (float) $this->filteredPurchases($request)->sum('due'),
        ]);
    }

    /**
     * @return Builder<Purchase>
     */
    private function filteredPurchases(Request $request)
    {
        return Purchase::query()
            ->when($request->filled('from'), fn ($query) => $query->whereDate('purchase_date', '>=', $request->string('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('purchase_date', '<=', $request->string('to')))
            ->when($request->filled('supplier_id'), fn ($query) => $query->where('supplier_id', $request->integer('supplier_id')));
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPurchase(Request $request, ?Purchase $purchase = null): array
    {
        $invoiceRule = ['required', 'string', 'max:100', 'unique:purchases,invoice_no'];
        if ($purchase) {
            $invoiceRule[3] = 'unique:purchases,invoice_no,'.$purchase->id;
        }

        return $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'invoice_no' => $invoiceRule,
            'purchase_date' => ['required', 'date'],
            'product_id' => ['required', 'exists:products,id'],
            'variant_id' => ['nullable', 'exists:product_variants,id'],
            'color' => ['nullable', 'string', 'max:100'],
            'size' => ['nullable', 'string', 'max:100'],
            'variation_name' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
