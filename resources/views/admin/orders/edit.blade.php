@extends('layouts.admin')

@section('title', 'Order Edit')
@section('heading', 'Order Edit')
@section('subheading')
    Invoice #{{ $order->invoice_no }}
    ·
    <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">
        {{ $order->statusLabel() }}
    </span>
    ·
    @include('admin.partials.fraud-badge', ['score' => $order->fraud_score])
@endsection

@section('actions')
    <form method="POST" action="{{ route('admin.orders.processing', $order) }}" class="inline">
        @csrf
        <button type="submit" class="btn-secondary !py-2">← Process</button>
    </form>
    <a href="{{ route('admin.orders.invoice', $order) }}" target="_blank" class="btn-secondary !py-2">Invoice</a>
    <form method="POST" action="{{ route('admin.orders.clear-cart', $order) }}" class="inline" onsubmit="return confirm('Clear all line items from this order?')">
        @csrf
        <button type="submit" class="inline-flex items-center rounded-md border border-red-300 bg-white px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50">
            Clear Cart
        </button>
    </form>
@endsection

@section('content')
    @php
        $colorNames = $colors->pluck('name')->values();
        $sizeNames = $sizes->pluck('name')->values();
        $initialItems = $order->items->map(function ($item) {
            $imagePath = $item->product?->primaryImagePath();

            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'color' => $item->color,
                'size' => $item->size,
                'qty' => (int) $item->qty,
                'price' => (float) $item->price,
                'discount' => (float) $item->discount,
                'image_url' => $imagePath ? asset('storage/'.$imagePath) : null,
            ];
        })->values();
    @endphp

    <form id="order-edit-form" method="POST" action="{{ route('admin.orders.update', $order) }}" class="space-y-4">
        @csrf
        @method('PUT')

        @if ($errors->any())
            <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="space-y-4 lg:col-span-2">
                <div class="admin-card admin-card-body">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <h2 class="admin-section-title">
                            Order Items
                            <span class="ml-2 rounded bg-mist px-2 py-0.5 text-xs font-medium text-sage">#{{ $order->invoice_no }}</span>
                        </h2>
                    </div>

                    <div class="relative mb-4">
                        <label class="admin-label" for="product-search">Add Product</label>
                        <input
                            id="product-search"
                            type="search"
                            autocomplete="off"
                            placeholder="Search product..."
                            class="field"
                        >
                        <div id="product-search-results" class="absolute z-20 mt-1 hidden max-h-64 w-full overflow-auto border border-ink/10 bg-white shadow-lg"></div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead class="bg-mist text-muted">
                                <tr>
                                    <th class="px-2 py-2 font-medium">Product</th>
                                    <th class="px-2 py-2 font-medium">Color</th>
                                    <th class="px-2 py-2 font-medium">Size</th>
                                    <th class="px-2 py-2 font-medium">Qty</th>
                                    <th class="px-2 py-2 font-medium">Price</th>
                                    <th class="px-2 py-2 font-medium">Discount</th>
                                    <th class="px-2 py-2 font-medium">Subtotal</th>
                                    <th class="px-2 py-2 font-medium"></th>
                                </tr>
                            </thead>
                            <tbody id="order-items-body"></tbody>
                        </table>
                    </div>
                    <div id="items-hidden-inputs"></div>
                </div>

                <div class="admin-card admin-card-body">
                    <h2 class="mb-2.5 admin-section-title">Payment Info</h2>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="admin-label" for="payment_method">Payment Method</label>
                            <input id="payment_method" type="text" name="payment_method" value="{{ old('payment_method', $order->payment_method) }}" class="field" required>
                        </div>
                        <div>
                            <label class="admin-label" for="payment_status">Payment Status</label>
                            <select id="payment_status" name="payment_status" class="field" required>
                                @foreach ($paymentStatuses as $value => $label)
                                    <option value="{{ $value }}" @selected(old('payment_status', $order->payment_status ?? 'pending') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="admin-card admin-card-body">
                    <h2 class="mb-2.5 admin-section-title">Customer &amp; Delivery</h2>
                    <div class="space-y-3">
                        <div>
                            <label class="admin-label" for="customer_name">Name</label>
                            <input id="customer_name" type="text" name="customer_name" value="{{ old('customer_name', $order->customer_name) }}" class="field" required>
                        </div>
                        <div>
                            <label class="admin-label" for="customer_phone">Mobile</label>
                            <input id="customer_phone" type="text" name="customer_phone" value="{{ old('customer_phone', $order->customer_phone) }}" class="field" required>
                        </div>
                        <div>
                            <label class="admin-label" for="customer_address">Address</label>
                            <textarea id="customer_address" name="customer_address" rows="3" class="field" required>{{ old('customer_address', $order->customer_address) }}</textarea>
                        </div>
                        <div>
                            <label class="admin-label" for="delivery_division">Division</label>
                            <input id="delivery_division" type="text" name="delivery_division" value="{{ old('delivery_division', $order->delivery_division) }}" class="field" placeholder="e.g. Dhaka" list="division-suggestions">
                            <datalist id="division-suggestions">
                                <option value="Dhaka"></option>
                                <option value="Chattogram"></option>
                                <option value="Rajshahi"></option>
                                <option value="Khulna"></option>
                                <option value="Barishal"></option>
                                <option value="Sylhet"></option>
                                <option value="Rangpur"></option>
                                <option value="Mymensingh"></option>
                            </datalist>
                        </div>
                        <div>
                            <label class="admin-label" for="delivery_district">District</label>
                            <input id="delivery_district" type="text" name="delivery_district" value="{{ old('delivery_district', $order->delivery_district) }}" class="field" placeholder="e.g. Dhaka">
                        </div>
                        <div>
                            <label class="admin-label" for="delivery_upazila">Upazila</label>
                            <input id="delivery_upazila" type="text" name="delivery_upazila" value="{{ old('delivery_upazila', $order->delivery_upazila) }}" class="field" placeholder="e.g. Mirpur">
                        </div>
                    </div>
                </div>

                <div class="admin-card admin-card-body">
                    <h2 class="mb-2.5 admin-section-title">Order Summary</h2>
                    <dl class="space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-muted">Subtotal</dt>
                            <dd id="summary-subtotal" class="font-medium">৳0.00</dd>
                        </div>
                        <div>
                            <label class="mb-1 block text-muted" for="shipping_charge">Delivery Charge</label>
                            @if ($shippingCharges->isNotEmpty())
                                <select id="shipping_charge_select" class="field mb-2">
                                    <option value="">Custom amount</option>
                                    @foreach ($shippingCharges as $charge)
                                        <option value="{{ $charge->amount }}" @selected((float) old('shipping_charge', $order->shipping_charge) === (float) $charge->amount)>
                                            {{ $charge->name }} — ৳{{ number_format($charge->amount, 2) }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                            <input id="shipping_charge" type="number" name="shipping_charge" min="0" step="0.01" value="{{ old('shipping_charge', $order->shipping_charge) }}" class="field" required>
                        </div>
                        <div>
                            <label class="mb-1 block text-muted" for="discount">Total Discount</label>
                            <input id="discount" type="number" name="discount" min="0" step="0.01" value="{{ old('discount', $order->discount) }}" class="field" required>
                        </div>
                        <div class="flex items-center justify-between gap-3 border-t border-ink/10 pt-3">
                            <dt class="font-semibold text-sage">Total Payable</dt>
                            <dd id="summary-total" class="text-lg font-semibold text-sage">৳0.00</dd>
                        </div>
                    </dl>
                    <button type="submit" class="btn-primary mt-5 w-full">Update Order</button>
                </div>
            </div>
        </div>
    </form>

    <script>
        (() => {
            const searchUrl = @json(route('admin.orders.products.search'));
            const defaultColors = @json($colorNames);
            const defaultSizes = @json($sizeNames);
            let items = @json($initialItems);
            let discountTouched = false;

            const body = document.getElementById('order-items-body');
            const hidden = document.getElementById('items-hidden-inputs');
            const searchInput = document.getElementById('product-search');
            const resultsBox = document.getElementById('product-search-results');
            const shippingInput = document.getElementById('shipping_charge');
            const shippingSelect = document.getElementById('shipping_charge_select');
            const discountInput = document.getElementById('discount');
            const summarySubtotal = document.getElementById('summary-subtotal');
            const summaryTotal = document.getElementById('summary-total');

            const money = (n) => '৳' + Number(n).toFixed(2);

            const lineSubtotal = (item) => Math.max(0, (Number(item.price) * Number(item.qty)) - Number(item.discount || 0));

            const syncHidden = () => {
                hidden.innerHTML = '';
                items.forEach((item, index) => {
                    const fields = {
                        product_id: item.product_id || '',
                        product_name: item.product_name,
                        color: item.color || '',
                        size: item.size || '',
                        qty: item.qty,
                        price: Number(item.price).toFixed(2),
                        discount: Number(item.discount || 0).toFixed(2),
                    };

                    if (item.id) {
                        fields.id = item.id;
                    }

                    Object.entries(fields).forEach(([key, value]) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = `items[${index}][${key}]`;
                        input.value = value;
                        hidden.appendChild(input);
                    });
                });
            };

            const recalculate = () => {
                const subtotal = items.reduce((sum, item) => sum + (Number(item.price) * Number(item.qty)), 0);
                const itemDiscounts = items.reduce((sum, item) => sum + Number(item.discount || 0), 0);

                if (! discountTouched) {
                    discountInput.value = itemDiscounts.toFixed(2);
                }

                const shipping = Number(shippingInput.value || 0);
                const discount = Number(discountInput.value || 0);
                const total = Math.max(0, subtotal - discount + shipping);

                summarySubtotal.textContent = money(subtotal);
                summaryTotal.textContent = money(total);
                syncHidden();
            };

            const optionList = (values, selected) => {
                const opts = ['<option value="">—</option>'];
                (values || []).forEach((value) => {
                    const sel = value === selected ? ' selected' : '';
                    opts.push(`<option value="${String(value).replace(/"/g, '&quot;')}"${sel}>${value}</option>`);
                });
                if (selected && !(values || []).includes(selected)) {
                    opts.push(`<option value="${String(selected).replace(/"/g, '&quot;')}" selected>${selected}</option>`);
                }
                return opts.join('');
            };

            const render = () => {
                if (! items.length) {
                    body.innerHTML = '<tr><td colspan="8" class="px-3 py-6 text-center text-muted">No items. Search and add a product.</td></tr>';
                    recalculate();
                    return;
                }

                body.innerHTML = items.map((item, index) => `
                    <tr class="border-t border-ink/5" data-index="${index}">
                        <td class="px-2 py-2">
                            <div class="flex items-center gap-2">
                                <div class="h-10 w-10 shrink-0 overflow-hidden bg-mist">
                                    ${item.image_url
                                        ? `<img src="${item.image_url}" alt="" class="h-full w-full object-cover" onerror="this.parentElement.innerHTML=''">`
                                        : ''}
                                </div>
                                <div class="font-medium">${item.product_name}</div>
                            </div>
                        </td>
                        <td class="px-2 py-2">
                            <select data-field="color" class="field !py-1.5 min-w-[6rem]">${optionList(item.colors || defaultColors, item.color)}</select>
                        </td>
                        <td class="px-2 py-2">
                            <select data-field="size" class="field !py-1.5 min-w-[5rem]">${optionList(item.sizes || defaultSizes, item.size)}</select>
                        </td>
                        <td class="px-2 py-2">
                            <div class="inline-flex items-center gap-1">
                                <button type="button" data-qty="-1" class="rounded border border-ink/15 px-2 py-1 text-xs">−</button>
                                <input data-field="qty" type="number" min="1" value="${item.qty}" class="field !w-14 !py-1.5 text-center">
                                <button type="button" data-qty="1" class="rounded border border-ink/15 px-2 py-1 text-xs">+</button>
                            </div>
                        </td>
                        <td class="px-2 py-2">
                            <input data-field="price" type="number" min="0" step="0.01" value="${Number(item.price).toFixed(2)}" class="field !w-24 !py-1.5">
                        </td>
                        <td class="px-2 py-2">
                            <input data-field="discount" type="number" min="0" step="0.01" value="${Number(item.discount || 0).toFixed(2)}" class="field !w-20 !py-1.5">
                        </td>
                        <td class="px-2 py-2 whitespace-nowrap font-medium" data-line-subtotal>${money(lineSubtotal(item))}</td>
                        <td class="px-2 py-2">
                            <button type="button" data-remove class="rounded border border-red-200 px-2 py-1 text-xs font-semibold text-red-600 hover:bg-red-50">✕</button>
                        </td>
                    </tr>
                `).join('');

                recalculate();
            };

            body.addEventListener('click', (event) => {
                const row = event.target.closest('tr[data-index]');
                if (! row) return;
                const index = Number(row.dataset.index);

                if (event.target.matches('[data-remove]')) {
                    items.splice(index, 1);
                    render();
                    return;
                }

                if (event.target.matches('[data-qty]')) {
                    const delta = Number(event.target.dataset.qty);
                    items[index].qty = Math.max(1, Number(items[index].qty) + delta);
                    render();
                }
            });

            body.addEventListener('change', (event) => {
                const row = event.target.closest('tr[data-index]');
                if (! row || ! event.target.dataset.field) return;
                const index = Number(row.dataset.index);
                const field = event.target.dataset.field;
                let value = event.target.value;

                if (field === 'qty') value = Math.max(1, parseInt(value || '1', 10));
                if (field === 'price' || field === 'discount') value = Math.max(0, Number(value || 0));

                items[index][field] = value;
                if (field === 'discount') discountTouched = false;
                render();
            });

            body.addEventListener('input', (event) => {
                const row = event.target.closest('tr[data-index]');
                if (! row || ! event.target.dataset.field) return;
                const index = Number(row.dataset.index);
                const field = event.target.dataset.field;
                if (! ['price', 'discount', 'qty'].includes(field)) return;

                let value = event.target.value;
                if (field === 'qty') value = Math.max(1, parseInt(value || '1', 10));
                if (field === 'price' || field === 'discount') value = Math.max(0, Number(value || 0));
                items[index][field] = value;
                row.querySelector('[data-line-subtotal]').textContent = money(lineSubtotal(items[index]));
                if (field === 'discount') discountTouched = false;
                recalculate();
            });

            shippingInput.addEventListener('input', recalculate);
            discountInput.addEventListener('input', () => {
                discountTouched = true;
                recalculate();
            });

            if (shippingSelect) {
                shippingSelect.addEventListener('change', () => {
                    if (shippingSelect.value !== '') {
                        shippingInput.value = Number(shippingSelect.value).toFixed(2);
                        recalculate();
                    }
                });
            }

            let searchTimer = null;
            let searchResults = [];
            const hideResults = () => resultsBox.classList.add('hidden');

            const showResults = (products) => {
                searchResults = products;
                if (! products.length) {
                    resultsBox.innerHTML = '<div class="px-3 py-2 text-sm text-muted">No products found.</div>';
                    resultsBox.classList.remove('hidden');
                    return;
                }

                resultsBox.innerHTML = products.map((product, index) => `
                    <button type="button" class="flex w-full items-center gap-3 px-3 py-2 text-left text-sm hover:bg-mist" data-product-index="${index}">
                        <div class="h-9 w-9 shrink-0 overflow-hidden bg-mist">
                            ${product.image_url ? `<img src="${product.image_url}" alt="" class="h-full w-full object-cover">` : ''}
                        </div>
                        <div>
                            <div class="font-medium">${product.name}</div>
                            <div class="text-xs text-muted">৳${Number(product.price).toFixed(2)} · Stock ${product.stock}</div>
                        </div>
                    </button>
                `).join('');
                resultsBox.classList.remove('hidden');
            };

            resultsBox.addEventListener('click', (event) => {
                const button = event.target.closest('[data-product-index]');
                if (! button) return;
                const product = searchResults[Number(button.dataset.productIndex)];
                if (! product) return;
                items.push({
                    id: null,
                    product_id: product.id,
                    product_name: product.name,
                    color: '',
                    size: '',
                    qty: 1,
                    price: product.price,
                    discount: 0,
                    image_url: product.image_url,
                    colors: product.colors,
                    sizes: product.sizes,
                });
                searchInput.value = '';
                hideResults();
                discountTouched = false;
                render();
            });

            searchInput.addEventListener('input', () => {
                clearTimeout(searchTimer);
                const q = searchInput.value.trim();
                searchTimer = setTimeout(async () => {
                    const response = await fetch(`${searchUrl}?q=${encodeURIComponent(q)}`, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    const json = await response.json();
                    showResults(json.data || []);
                }, 250);
            });

            searchInput.addEventListener('blur', () => setTimeout(hideResults, 200));
            document.addEventListener('click', (event) => {
                if (! event.target.closest('#product-search') && ! event.target.closest('#product-search-results')) {
                    hideResults();
                }
            });

            render();
        })();
    </script>
@endsection
