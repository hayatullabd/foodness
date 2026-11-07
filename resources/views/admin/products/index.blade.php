@extends('layouts.admin')

@php
    use App\Support\Money;
@endphp

@section('title', $pageTitle)
@section('heading', $pageTitle)
@section('subheading', $pageSubtitle)

@section('actions')
    <div class="flex items-center gap-2">
        <button type="button" id="export-products-btn" class="admin-btn-secondary" title="Export products (Selected or Filtered/All)">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
            </svg>
            <span id="export-products-label">Export</span>
        </button>
        <button type="button" onclick="document.getElementById('product-import-modal').classList.remove('hidden')" class="admin-btn-secondary" title="Import products from CSV">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
            </svg>
            Import
        </button>
        <a href="{{ route('admin.products.create') }}" class="admin-btn-primary">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            New Product
        </a>
    </div>
@endsection

@section('content')
    {{-- Compact Single-Line Toolbar --}}
    <div class="products-toolbar mb-2.5 flex items-center justify-between gap-2 overflow-x-auto pb-0.5">
        {{-- Left: Bulk Actions and Filters --}}
        <div class="flex shrink-0 items-center gap-1.5">
            {{-- Bulk Actions Form --}}
            <form method="POST" action="{{ route('admin.products.bulk') }}" id="bulk-products-form" class="flex shrink-0 items-center gap-1">
                @csrf
                <input type="hidden" name="action" id="bulk-action" value="">

                <select id="bulk-action-select" class="field w-32" aria-label="Bulk action">
                    <option value="">Bulk Actions…</option>
                    <option value="set_deal">Mark Hot Deal</option>
                    <option value="remove_deal">Remove Deal</option>
                    <option value="activate">Enable</option>
                    <option value="deactivate">Disable</option>
                    <option value="delete">Delete</option>
                </select>

                <button
                    type="button"
                    id="apply-bulk-btn"
                    class="admin-btn bg-emerald-600 text-white hover:bg-emerald-700"
                >
                    Apply
                </button>
                <span id="bulk-selected-badge" class="hidden rounded-full bg-sage/15 px-1.5 py-0.5 text-[10px] font-semibold text-sage">0 selected</span>
            </form>

            <span class="mx-0.5 h-4 w-px shrink-0 bg-ink/15" aria-hidden="true"></span>

            {{-- Filters (Category, Status, Deal, Stock) --}}
            <form method="GET" action="{{ route('admin.products.index') }}" id="products-filter-form" class="flex shrink-0 items-center gap-1">
                @if ($listFilter !== 'all')
                    <input type="hidden" name="filter" value="{{ $listFilter }}">
                @endif
                @if (request('q'))
                    <input type="hidden" name="q" value="{{ request('q') }}">
                @endif

                <select name="category_id" class="field w-32" aria-label="Category" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>

                <select name="status" class="field w-24" aria-label="Status" onchange="this.form.submit()">
                    <option value="">Status</option>
                    <option value="active" @selected(request('status') === 'active')>Enabled</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Disabled</option>
                </select>

                <select name="deal" class="field w-24" aria-label="Deals" onchange="this.form.submit()">
                    <option value="">Deals</option>
                    <option value="hot" @selected(request('deal') === 'hot')>Hot Deal</option>
                    <option value="regular" @selected(request('deal') === 'regular')>Regular</option>
                </select>

                <select name="stock" class="field w-24" aria-label="Stock" onchange="this.form.submit()">
                    <option value="">Stock</option>
                    <option value="in_stock" @selected(request('stock') === 'in_stock')>In Stock</option>
                    <option value="low_stock" @selected(request('stock') === 'low_stock')>Low (≤ 5)</option>
                    <option value="out_of_stock" @selected(request('stock') === 'out_of_stock')>Out of Stock</option>
                </select>

                @if(request()->anyFilled(['q', 'category_id', 'status', 'deal', 'stock']))
                    <a href="{{ route('admin.products.index', $listFilter !== 'all' ? ['filter' => $listFilter] : []) }}" class="admin-btn-secondary text-muted hover:text-ink" title="Clear filters">
                        Reset
                    </a>
                @endif
            </form>
        </div>

        {{-- Right: Search Box --}}
        <form method="GET" action="{{ route('admin.products.index') }}" class="flex shrink-0 items-center gap-1 ml-auto">
            @if ($listFilter !== 'all')
                <input type="hidden" name="filter" value="{{ $listFilter }}">
            @endif
            @if (request('category_id'))
                <input type="hidden" name="category_id" value="{{ request('category_id') }}">
            @endif
            @if (request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            @if (request('deal'))
                <input type="hidden" name="deal" value="{{ request('deal') }}">
            @endif
            @if (request('stock'))
                <input type="hidden" name="stock" value="{{ request('stock') }}">
            @endif

            <input
                type="text"
                name="q"
                value="{{ request('q') }}"
                placeholder="Search products…"
                class="field w-44"
            >
            <button type="submit" class="admin-btn bg-sage text-white hover:bg-sage/90">
                Search
            </button>
        </form>
    </div>

    <div class="admin-card overflow-hidden">
        <div class="admin-table-wrap">
            <table class="admin-table text-[13px]">
                <thead>
                    <tr>
                        <th class="w-8 px-2 py-2">
                            <input type="checkbox" id="select-all-products" class="rounded border-ink/20" aria-label="Select all">
                        </th>
                        <th class="px-2 py-2">Product</th>
                        <th class="px-2 py-2">Category</th>
                        <th class="px-2 py-2">Price</th>
                        <th class="px-2 py-2">Stock</th>
                        <th class="px-2 py-2">Feature</th>
                        <th class="px-2 py-2">Enable / Disable</th>
                        <th class="px-2 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr class="align-middle border-t border-ink/5">
                            <td class="px-2 py-2.5">
                                <input form="bulk-products-form" type="checkbox" name="product_ids[]" value="{{ $product->id }}" class="product-checkbox rounded border-ink/20">
                            </td>
                            <td class="px-2 py-2.5">
                                <div class="flex items-center gap-2.5">
                                    <div class="h-11 w-11 shrink-0 overflow-hidden rounded-md border border-ink/10 bg-mist">
                                        @if ($product->primaryImagePath())
                                            <img src="{{ asset('storage/'.$product->primaryImagePath()) }}" alt="" class="h-full w-full object-cover" onerror="this.style.opacity='0.3'">
                                        @else
                                            <div class="flex h-full w-full items-center justify-center text-[10px] text-muted">N/A</div>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <div class="truncate font-medium text-ink" title="{{ $product->name }}">{{ $product->name }}</div>
                                        <div class="mt-0.5 flex flex-wrap items-center gap-1.5">
                                            <span class="text-[11px] text-muted">#{{ $product->id }}</span>
                                            @if (! $product->is_visible_in_store)
                                                <span class="rounded bg-amber-500/10 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700 dark:text-amber-400" title="Hidden from main website, available on landing pages">
                                                    Landing Only
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-2 py-2.5 text-muted">{{ $product->category?->name ?: '—' }}</td>
                            <td class="px-2 py-2.5 whitespace-nowrap font-medium tabular-nums">{{ Money::format($product->price) }}</td>
                            <td class="px-2 py-2.5">
                                <span @class([
                                    'admin-badge-pill',
                                    'admin-badge-danger' => $product->stock <= 0,
                                    'admin-badge-warning' => $product->stock > 0 && $product->stock <= 5,
                                    'admin-badge-neutral' => $product->stock > 5,
                                ])>
                                    {{ $product->stock }}
                                </span>
                            </td>
                            <td class="px-2 py-2.5">
                                @if ($product->is_hot)
                                    <span class="admin-badge-pill admin-badge-warning">Hot Deal</span>
                                @else
                                    <span class="text-xs text-muted">—</span>
                                @endif
                            </td>
                            <td class="px-2 py-2.5">
                                <form method="POST" action="{{ route('admin.products.toggle', $product) }}" class="inline-flex items-center gap-2">
                                    @csrf
                                    <button
                                        type="submit"
                                        role="switch"
                                        aria-checked="{{ $product->is_active ? 'true' : 'false' }}"
                                        class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border transition {{ $product->is_active ? 'border-sage bg-sage' : 'border-ink/20 bg-fog' }}"
                                        title="{{ $product->is_active ? 'Disable product' : 'Enable product' }}"
                                    >
                                        <span class="sr-only">{{ $product->is_active ? 'Disable' : 'Enable' }} {{ $product->name }}</span>
                                        <span @class([
                                            'pointer-events-none inline-block h-4 w-4 translate-y-px rounded-full bg-white shadow transition',
                                            'translate-x-[18px]' => $product->is_active,
                                            'translate-x-0.5' => ! $product->is_active,
                                        ])></span>
                                    </button>
                                    <span @class([
                                        'text-[11px] font-medium',
                                        'text-sage' => $product->is_active,
                                        'text-muted' => ! $product->is_active,
                                    ])>
                                        {{ $product->is_active ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </form>
                            </td>
                            <td class="px-2 py-2.5 text-right whitespace-nowrap">
                                <a href="{{ route('admin.products.edit', $product) }}" class="admin-btn-secondary !px-2 !py-1 !text-[11px]">Edit</a>
                                <form method="POST" action="{{ route('admin.products.duplicate', $product) }}" class="ml-1 inline" onsubmit="return confirm('Duplicate this product as a draft?')">
                                    @csrf
                                    <button type="submit" class="admin-btn-secondary !px-2 !py-1 !text-[11px]" title="Clone product">Copy</button>
                                </form>
                                <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="ml-1 inline" onsubmit="return confirm('Delete this product?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="admin-btn-danger !px-2 !py-1 !text-[11px]">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-10 text-center text-muted">No products found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($products->hasPages() || $products->total())
            <div class="flex flex-wrap items-center justify-between gap-2 border-t border-ink/10 px-3 py-2 text-xs text-muted">
                <div>
                    @if ($products->total())
                        Showing {{ $products->firstItem() }}–{{ $products->lastItem() }} of {{ $products->total() }}
                    @endif
                </div>
                <div>{{ $products->links() }}</div>
            </div>
        @endif
    </div>

    {{-- Product Import Modal --}}
    <div id="product-import-modal" class="fixed inset-0 z-50 flex hidden items-center justify-center bg-black/50 p-4 backdrop-blur-xs">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl ring-1 ring-ink/10">
            <div class="mb-4 flex items-center justify-between border-b border-ink/10 pb-3">
                <div class="flex items-center gap-2">
                    <div class="rounded-lg bg-sage/10 p-2 text-sage">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-ink">Import Products</h3>
                        <p class="text-xs text-muted">Bulk create or update catalog items via CSV</p>
                    </div>
                </div>
                <button type="button" onclick="document.getElementById('product-import-modal').classList.add('hidden')" class="rounded-md p-1 text-muted hover:bg-mist hover:text-ink">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.products.import') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-ink mb-1">Select CSV File</label>
                    <input type="file" name="csv_file" accept=".csv,text/csv" required class="block w-full text-xs text-ink file:mr-3 file:rounded-md file:border-0 file:bg-sage/10 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-sage hover:file:bg-sage/20">
                    <p class="mt-1 text-[11px] text-muted">Supported columns: Name, SKU, Price, Compare Price, Stock, Category, Brand, Status, Unit, Image URL, Description</p>
                </div>

                <div class="rounded-lg bg-mist/60 p-3 text-xs flex items-center justify-between">
                    <div>
                        <span class="font-medium text-ink">Need the file format?</span>
                        <p class="text-[11px] text-muted">Download our ready-to-use spreadsheet</p>
                    </div>
                    <a href="{{ route('admin.products.sample-template') }}" class="admin-btn-secondary !px-2.5 !py-1 !text-xs font-medium inline-flex items-center gap-1">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        Sample CSV
                    </a>
                </div>

                <div class="mt-5 flex items-center justify-end gap-2 border-t border-ink/10 pt-3">
                    <button type="button" onclick="document.getElementById('product-import-modal').classList.add('hidden')" class="admin-btn-secondary">Cancel</button>
                    <button type="submit" class="admin-btn-primary">Upload & Import</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const productCheckboxes = () => document.querySelectorAll('.product-checkbox');
        const selectAllProducts = document.getElementById('select-all-products');
        const exportProductsBtn = document.getElementById('export-products-btn');
        const exportProductsLabel = document.getElementById('export-products-label');
        const bulkBadge = document.getElementById('bulk-selected-badge');
        const applyBulkBtn = document.getElementById('apply-bulk-btn');
        const bulkActionSelect = document.getElementById('bulk-action-select');
        const bulkForm = document.getElementById('bulk-products-form');
        const bulkActionHidden = document.getElementById('bulk-action');

        const updateSelectionStates = () => {
            const checked = document.querySelectorAll('.product-checkbox:checked');
            const count = checked.length;

            if (count > 0) {
                if (exportProductsLabel) exportProductsLabel.textContent = `Export Selected (${count})`;
                if (bulkBadge) {
                    bulkBadge.textContent = `${count} selected`;
                    bulkBadge.classList.remove('hidden');
                }
            } else {
                if (exportProductsLabel) exportProductsLabel.textContent = 'Export';
                if (bulkBadge) {
                    bulkBadge.classList.add('hidden');
                }
            }

            // Sync select-all checkbox indeterminate state
            if (selectAllProducts) {
                const total = productCheckboxes().length;
                selectAllProducts.checked = total > 0 && count === total;
                selectAllProducts.indeterminate = count > 0 && count < total;
            }
        };

        selectAllProducts?.addEventListener('change', function (e) {
            productCheckboxes().forEach((el) => { el.checked = e.target.checked; });
            updateSelectionStates();
        });

        productCheckboxes().forEach((el) => {
            el.addEventListener('change', updateSelectionStates);
        });

        applyBulkBtn?.addEventListener('click', function () {
            const selectedAction = bulkActionSelect?.value;
            const checked = document.querySelectorAll('.product-checkbox:checked');

            if (!selectedAction) {
                alert('Please select a bulk action from the dropdown.');
                bulkActionSelect?.focus();
                return;
            }

            if (checked.length === 0) {
                alert('Please select at least one product using the checkboxes.');
                return;
            }

            if (selectedAction === 'delete') {
                if (!confirm(`Are you sure you want to permanently delete the ${checked.length} selected product(s)?`)) {
                    return;
                }
            }

            bulkActionHidden.value = selectedAction;
            bulkForm?.submit();
        });

        exportProductsBtn?.addEventListener('click', function () {
            const checked = Array.from(document.querySelectorAll('.product-checkbox:checked')).map(cb => cb.value);
            const currentParams = new URLSearchParams(window.location.search);

            if (checked.length > 0) {
                currentParams.set('product_ids', checked.join(','));
            } else {
                currentParams.delete('product_ids');
            }

            const exportUrl = '{{ route("admin.products.export") }}?' + currentParams.toString();
            window.location.href = exportUrl;
        });
    </script>
@endsection
