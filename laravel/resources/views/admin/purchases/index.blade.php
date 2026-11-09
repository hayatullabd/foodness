@extends('layouts.admin')

@section('title', 'Purchase Management')
@section('heading', 'Purchase Management')
@section('subheading', 'Supplier purchases, payments, and inventory intake')

@section('actions')
    <a href="{{ route('admin.purchases.reports') }}" class="admin-btn-secondary">View Reports / Logs</a>
@endsection

@section('content')
    <div class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="border border-emerald-200 bg-white px-3.5 py-3">
            <p class="text-xs font-medium uppercase tracking-wide text-emerald-700">This Year ({{ now()->year }})</p>
            <p class="mt-1 text-sm text-muted">Total Purchase</p>
            <p class="mt-1 text-xl font-semibold text-ink">{{ number_format($stats['year_total'], 2) }} ৳</p>
        </div>
        <div class="border border-sky-200 bg-white px-3.5 py-3">
            <p class="text-xs font-medium uppercase tracking-wide text-sky-700">{{ now()->format('F') }}</p>
            <p class="mt-1 text-sm text-muted">Monthly Purchase</p>
            <p class="mt-1 text-xl font-semibold text-ink">{{ number_format($stats['month_total'], 2) }} ৳</p>
        </div>
        <div class="border border-ink/15 bg-white px-3.5 py-3">
            <p class="text-xs font-medium uppercase tracking-wide text-ink">Today ({{ now()->format('d M') }})</p>
            <p class="mt-1 text-sm text-muted">Daily Purchase</p>
            <p class="mt-1 text-xl font-semibold text-ink">{{ number_format($stats['day_total'], 2) }} ৳</p>
        </div>
        <div class="border border-red-200 bg-white px-3.5 py-3">
            <p class="text-xs font-medium uppercase tracking-wide text-red-700">Supplier Due</p>
            <p class="mt-1 text-sm text-muted">Total Liability</p>
            <p class="mt-1 text-xl font-semibold text-red-700">{{ number_format($stats['supplier_due'], 2) }} ৳</p>
        </div>
    </div>

    <div class="mb-4 grid gap-3 lg:grid-cols-12">
        <div class="admin-card admin-card-body lg:col-span-8">
            <h2 class="mb-2.5 admin-section-title">New Purchase Entry</h2>

            <form method="POST" action="{{ route('admin.purchases.store') }}" class="space-y-4">
                @csrf

                <div class="grid gap-3 md:grid-cols-3">
                    <div>
                        <label class="admin-label" for="supplier_id">Supplier</label>
                        <select id="supplier_id" name="supplier_id" class="field" required>
                            <option value="">--Select Supplier--</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                        @error('supplier_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="admin-label" for="invoice_no">Invoice No</label>
                        <input id="invoice_no" type="text" name="invoice_no" value="{{ old('invoice_no', $invoiceNo) }}" class="field" required>
                        @error('invoice_no') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="admin-label" for="purchase_date">Date</label>
                        <input id="purchase_date" type="date" name="purchase_date" value="{{ old('purchase_date', now()->toDateString()) }}" class="field" required>
                        @error('purchase_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="border-t border-ink/10 pt-4">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-muted">Product & Attributes</p>
                    <div id="product_attributes_grid" class="grid gap-3 sm:grid-cols-2 md:grid-cols-3">
                        <div>
                            <label class="admin-label" for="product_id">Product</label>
                            <select id="product_id" name="product_id" class="field" required onchange="onPurchaseProductChange(this.value)">
                                <option value="">--Select Product--</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>
                                        {{ $product->name }}@if ($product->sku) ({{ $product->sku }})@endif
                                    </option>
                                @endforeach
                            </select>
                            @error('product_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div id="variant_col" class="hidden">
                            <label class="admin-label" for="variant_id">Variant / Attribute</label>
                            <select id="variant_id" name="variant_id" class="field" onchange="onPurchaseVariantChange(this)">
                                <option value="">-- Select Variant --</option>
                            </select>
                            @error('variant_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="admin-label" for="color">Color</label>
                            <input id="color" type="text" name="color" list="colors_list" value="{{ old('color') }}" class="field" placeholder="e.g. Olive Green">
                            <datalist id="colors_list">
                                @foreach ($colors as $c)
                                    <option value="{{ $c->name }}"></option>
                                @endforeach
                            </datalist>
                            @error('color') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="admin-label" for="size">Size</label>
                            <input id="size" type="text" name="size" list="sizes_list" value="{{ old('size') }}" class="field" placeholder="e.g. M, L, XL, 32">
                            <datalist id="sizes_list">
                                @foreach ($sizes as $s)
                                    <option value="{{ $s->name }}"></option>
                                @endforeach
                            </datalist>
                            @error('size') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="mt-3 grid gap-3 md:grid-cols-2">
                        <div>
                            <label class="admin-label" for="quantity">Quantity</label>
                            <input id="quantity" type="number" name="quantity" min="1" value="{{ old('quantity') }}" class="field" required>
                            @error('quantity') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="admin-label" for="unit_cost">Unit Cost (৳)</label>
                            <input id="unit_cost" type="number" name="unit_cost" min="0" step="0.01" value="{{ old('unit_cost') }}" class="field" required>
                            @error('unit_cost') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="grid gap-3 md:grid-cols-4">
                    <div>
                        <label class="admin-label" for="discount">Discount</label>
                        <input id="discount" type="number" name="discount" min="0" step="0.01" value="{{ old('discount', 0) }}" class="field">
                    </div>
                    <div>
                        <label class="admin-label" for="shipping_cost">Shipping Cost</label>
                        <input id="shipping_cost" type="number" name="shipping_cost" min="0" step="0.01" value="{{ old('shipping_cost', 0) }}" class="field">
                    </div>
                    <div>
                        <label class="admin-label" for="paid_amount">Paid Amount</label>
                        <input id="paid_amount" type="number" name="paid_amount" min="0" step="0.01" value="{{ old('paid_amount', 0) }}" class="field">
                        <p class="mt-1 text-xs text-muted">Deducted from Total</p>
                    </div>
                    <div>
                        <label class="admin-label" for="note">Note</label>
                        <input id="note" type="text" name="note" value="{{ old('note') }}" class="field" placeholder="Optional">
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="admin-btn-primary">Save Purchase</button>
                </div>
            </form>
        </div>

        <div class="admin-card admin-card-body lg:col-span-4">
            <h2 class="mb-2.5 admin-section-title">Export Report</h2>
            <form method="GET" action="{{ route('admin.purchases.export') }}" class="space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="admin-label" for="month">Month</label>
                        <input id="month" type="number" name="month" min="1" max="12" value="{{ $exportMonth }}" class="field">
                    </div>
                    <div>
                        <label class="admin-label" for="year">Year</label>
                        <input id="year" type="number" name="year" min="2000" max="2100" value="{{ $exportYear }}" class="field">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="admin-label" for="from">From</label>
                        <input id="from" type="date" name="from" value="{{ $exportFrom }}" class="field">
                    </div>
                    <div>
                        <label class="admin-label" for="to">To</label>
                        <input id="to" type="date" name="to" value="{{ $exportTo }}" class="field">
                    </div>
                </div>
                <button type="submit" class="btn-primary w-full">Download CSV</button>
            </form>
        </div>
    </div>

    <div class="border border-ink/10 bg-white">
        <div class="border-b border-ink/10 px-3 py-2">
            <h2 class="admin-section-title">Recent Purchase History</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-mist text-muted">
                    <tr>
                        <th class="px-3 py-2 font-medium">#</th>
                        <th class="px-3 py-2 font-medium">Date</th>
                        <th class="px-3 py-2 font-medium">Invoice</th>
                        <th class="px-3 py-2 font-medium">Supplier</th>
                        <th class="px-3 py-2 font-medium">Items / Attributes</th>
                        <th class="px-3 py-2 font-medium">Total</th>
                        <th class="px-3 py-2 font-medium">Paid</th>
                        <th class="px-3 py-2 font-medium">Due</th>
                        <th class="px-3 py-2 font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($purchases as $index => $purchase)
                        <tr class="border-t border-ink/5">
                            <td class="px-3 py-2 text-muted">{{ $purchases->firstItem() + $index }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ $purchase->purchase_date?->format('d-M-Y') }}</td>
                            <td class="px-3 py-2 font-medium">{{ $purchase->invoice_no }}</td>
                            <td class="px-3 py-2">
                                <div class="font-medium">{{ $purchase->supplier?->name }}</div>
                                <div class="text-xs text-muted">{{ $purchase->supplier?->phone }}</div>
                            </td>
                            <td class="px-3 py-2">
                                @forelse ($purchase->items as $it)
                                    <div class="text-xs">
                                        <span class="font-medium text-ink">{{ $it->product?->name }}</span>
                                        @if ($it->attributeLabel())
                                            <span class="inline-flex items-center rounded bg-sage/10 px-1.5 py-0.5 text-[11px] font-semibold text-sage">
                                                {{ $it->attributeLabel() }}
                                            </span>
                                        @endif
                                        <span class="text-muted">× {{ $it->quantity }}</span>
                                    </div>
                                @empty
                                    <span class="text-xs text-muted">—</span>
                                @endforelse
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ number_format($purchase->total, 2) }} ৳</td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                @if ($purchase->isPaid())
                                    <span class="font-medium text-emerald-700">{{ number_format($purchase->paid_amount, 2) }} ৳</span>
                                @else
                                    {{ number_format($purchase->paid_amount, 2) }} ৳
                                @endif
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                @if ($purchase->isPaid())
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800">Paid</span>
                                @else
                                    <span class="font-medium text-red-600">{{ number_format($purchase->due, 2) }} ৳</span>
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    @if (! $purchase->isPaid())
                                        <form method="POST" action="{{ route('admin.purchases.pay-due', $purchase) }}" class="flex items-center gap-1">
                                            @csrf
                                            <input type="number" name="amount" min="0.01" max="{{ $purchase->due }}" step="0.01" value="{{ $purchase->due }}" class="field !w-24 !py-1 !text-xs" title="Pay amount">
                                            <button type="submit" class="rounded-md bg-amber-50 px-2 py-1 text-xs font-medium text-amber-800 hover:bg-amber-100">Pay Due</button>
                                        </form>
                                    @endif
                                    <a href="{{ route('admin.purchases.show', $purchase) }}" class="rounded-md bg-mist px-2 py-1 text-xs font-medium text-ink hover:bg-sage/15">View</a>
                                    <a href="{{ route('admin.purchases.edit', $purchase) }}" class="rounded-md bg-sage/15 px-2 py-1 text-xs font-medium text-sage hover:bg-sage/25">Edit</a>
                                    <form method="POST" action="{{ route('admin.purchases.destroy', $purchase) }}" onsubmit="return confirm('Delete this purchase?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 hover:bg-red-100">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-muted">No purchases recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($purchases->hasPages())
            <div class="border-t border-ink/10 px-3 py-2">{{ $purchases->links() }}</div>
        @endif
    </div>

    @push('scripts')
    <script>
        const purchaseProductsMap = @json($products->keyBy('id'));

        function onPurchaseProductChange(productId, selectedVariantId = null) {
            const variantCol = document.getElementById('variant_col');
            const gridContainer = document.getElementById('product_attributes_grid');
            const variantSelect = document.getElementById('variant_id');

            if (!variantSelect) return;

            if (!productId || !purchaseProductsMap[productId]) {
                if (variantCol) variantCol.classList.add('hidden');
                if (gridContainer) {
                    gridContainer.classList.add('md:grid-cols-3');
                    gridContainer.classList.remove('md:grid-cols-4');
                }
                variantSelect.innerHTML = '<option value="">-- Base Product (No Variant) --</option>';
                variantSelect.value = '';
                return;
            }

            const product = purchaseProductsMap[productId];
            const variants = product.variants || [];

            if (variants.length > 0) {
                if (variantCol) variantCol.classList.remove('hidden');
                if (gridContainer) {
                    gridContainer.classList.remove('md:grid-cols-3');
                    gridContainer.classList.add('md:grid-cols-4');
                }
                variantSelect.innerHTML = '<option value="">-- Select Variant --</option>';
                variants.forEach(v => {
                    const opt = document.createElement('option');
                    opt.value = v.id;
                    opt.textContent = v.name + (v.stock !== undefined ? ' (Stock: ' + v.stock + ')' : '');
                    opt.dataset.color = (v.attribute_values && v.attribute_values.Color) || '';
                    opt.dataset.size = (v.attribute_values && v.attribute_values.Size) || v.size_name || '';
                    if (selectedVariantId && String(v.id) === String(selectedVariantId)) {
                        opt.selected = true;
                    }
                    variantSelect.appendChild(opt);
                });
            } else {
                if (variantCol) variantCol.classList.add('hidden');
                if (gridContainer) {
                    gridContainer.classList.add('md:grid-cols-3');
                    gridContainer.classList.remove('md:grid-cols-4');
                }
                variantSelect.innerHTML = '<option value="">-- Base Product (No Variant) --</option>';
                variantSelect.value = '';
            }
        }

        function onPurchaseVariantChange(selectEl) {
            const selectedOption = selectEl.options[selectEl.selectedIndex];
            if (!selectedOption || !selectedOption.value) return;

            const colorInput = document.getElementById('color');
            const sizeInput = document.getElementById('size');

            if (selectedOption.dataset.color && colorInput) {
                colorInput.value = selectedOption.dataset.color;
            }
            if (selectedOption.dataset.size && sizeInput) {
                sizeInput.value = selectedOption.dataset.size;
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const productSelect = document.getElementById('product_id');
            if (productSelect && productSelect.value) {
                onPurchaseProductChange(productSelect.value, '{{ old('variant_id') }}');
            }
        });
    </script>
    @endpush
@endsection
