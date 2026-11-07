@extends('layouts.admin')

@section('title', 'Edit Purchase')
@section('heading', 'Edit Purchase')
@section('subheading', $purchase->invoice_no)

@section('actions')
    <a href="{{ route('admin.purchases.show', $purchase) }}" class="btn-secondary !py-2">View</a>
    <a href="{{ route('admin.purchases.index') }}" class="btn-secondary !py-2">Back</a>
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.purchases.update', $purchase) }}" class="max-w-4xl space-y-4 admin-card admin-card-body">
        @csrf
        @method('PUT')

        <div class="grid gap-3 md:grid-cols-3">
            <div>
                <label class="admin-label" for="supplier_id">Supplier</label>
                <select id="supplier_id" name="supplier_id" class="field" required>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected(old('supplier_id', $purchase->supplier_id) == $supplier->id)>{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="admin-label" for="invoice_no">Invoice No</label>
                <input id="invoice_no" type="text" name="invoice_no" value="{{ old('invoice_no', $purchase->invoice_no) }}" class="field" required>
            </div>
            <div>
                <label class="admin-label" for="purchase_date">Date</label>
                <input id="purchase_date" type="date" name="purchase_date" value="{{ old('purchase_date', $purchase->purchase_date?->toDateString()) }}" class="field" required>
            </div>
        </div>

        <div class="border-t border-ink/10 pt-4">
            <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-muted">Product & Attributes</p>
            <div id="product_attributes_grid" class="grid gap-3 sm:grid-cols-2 md:grid-cols-3">
                <div>
                    <label class="admin-label" for="product_id">Product</label>
                    <select id="product_id" name="product_id" class="field" required onchange="onPurchaseProductChange(this.value)">
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected(old('product_id', $item?->product_id) == $product->id)>{{ $product->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="variant_col" class="hidden">
                    <label class="admin-label" for="variant_id">Variant / Attribute</label>
                    <select id="variant_id" name="variant_id" class="field" onchange="onPurchaseVariantChange(this)">
                        <option value="">-- Select Variant --</option>
                    </select>
                </div>
                <div>
                    <label class="admin-label" for="color">Color</label>
                    <input id="color" type="text" name="color" list="colors_list" value="{{ old('color', $item?->color) }}" class="field" placeholder="e.g. Olive Green">
                    <datalist id="colors_list">
                        @foreach ($colors as $c)
                            <option value="{{ $c->name }}"></option>
                        @endforeach
                    </datalist>
                </div>
                <div>
                    <label class="admin-label" for="size">Size</label>
                    <input id="size" type="text" name="size" list="sizes_list" value="{{ old('size', $item?->size) }}" class="field" placeholder="e.g. M, L, XL, 32">
                    <datalist id="sizes_list">
                        @foreach ($sizes as $s)
                            <option value="{{ $s->name }}"></option>
                        @endforeach
                    </datalist>
                </div>
            </div>

            <div class="mt-3 grid gap-3 md:grid-cols-2">
                <div>
                    <label class="admin-label" for="quantity">Quantity</label>
                    <input id="quantity" type="number" name="quantity" min="1" value="{{ old('quantity', $item?->quantity) }}" class="field" required>
                </div>
                <div>
                    <label class="admin-label" for="unit_cost">Unit Cost (৳)</label>
                    <input id="unit_cost" type="number" name="unit_cost" min="0" step="0.01" value="{{ old('unit_cost', $item?->unit_cost) }}" class="field" required>
                </div>
            </div>
        </div>

        <div class="grid gap-3 md:grid-cols-4">
            <div>
                <label class="admin-label" for="discount">Discount</label>
                <input id="discount" type="number" name="discount" min="0" step="0.01" value="{{ old('discount', $purchase->discount) }}" class="field">
            </div>
            <div>
                <label class="admin-label" for="shipping_cost">Shipping Cost</label>
                <input id="shipping_cost" type="number" name="shipping_cost" min="0" step="0.01" value="{{ old('shipping_cost', $purchase->shipping_cost) }}" class="field">
            </div>
            <div>
                <label class="admin-label" for="paid_amount">Paid Amount</label>
                <input id="paid_amount" type="number" name="paid_amount" min="0" step="0.01" value="{{ old('paid_amount', $purchase->paid_amount) }}" class="field">
            </div>
            <div>
                <label class="admin-label" for="note">Note</label>
                <input id="note" type="text" name="note" value="{{ old('note', $purchase->note) }}" class="field">
            </div>
        </div>

        <button type="submit" class="btn-primary">Update Purchase</button>
    </form>

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
                onPurchaseProductChange(productSelect.value, '{{ old('variant_id', $item?->variant_id) }}');
            }
        });
    </script>
    @endpush
@endsection
