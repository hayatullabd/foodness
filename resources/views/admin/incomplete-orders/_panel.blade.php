@php
    use App\Support\Money;

    $ourRecordsByPhone = $ourRecordsByPhone ?? [];
@endphp

<div class="mb-2.5 flex flex-wrap items-center gap-2">
    <span class="inline-flex items-center gap-1.5 rounded-full bg-accent/10 px-2.5 py-1 text-[11px] font-medium text-accent">
        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M3 3a1 1 0 0 1 1-1h1.22a1 1 0 0 1 .97.76L6.8 5H16a1 1 0 0 1 .96 1.27l-1.5 5A1 1 0 0 1 14.5 12H8.12l.2.76A1 1 0 0 0 9.28 13H15a1 1 0 1 1 0 2H9.28a3 3 0 0 1-2.9-2.28L4.36 4H4a1 1 0 0 1-1-1Zm4.5 14a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Zm7 0a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z"/></svg>
        Accepting converts it to a regular order
    </span>
</div>

@if ($incompleteOrders->isNotEmpty())
    <form method="POST" action="{{ route('admin.incomplete-orders.bulk') }}" id="incomplete-bulk-form" class="orders-toolbar mb-2.5 flex items-center gap-1">
        @csrf
        <button type="submit" name="action" value="fraud_check" class="admin-btn bg-teal-600 text-white hover:bg-teal-700">
            Check Ratio
        </button>
        <button type="submit" name="action" value="delete" class="admin-btn-danger" onclick="return confirm('Delete selected incomplete orders?')">
            Delete
        </button>
    </form>
@endif

<div class="admin-card">
    @if ($incompleteOrders->isEmpty())
        <div class="admin-card-body text-sm text-muted">No incomplete orders right now.</div>
    @else
        <div class="admin-table-wrap">
            <table class="admin-table text-[13px]">
                <thead>
                    <tr>
                        <th class="w-8 px-2 py-2">
                            <input type="checkbox" id="select-all-incomplete" class="rounded border-ink/20" form="incomplete-bulk-form">
                        </th>
                        <th class="w-8 px-2 py-2"></th>
                        <th class="px-2 py-2">#</th>
                        <th class="px-2 py-2">Customer</th>
                        <th class="px-2 py-2">Phone</th>
                        <th class="px-2 py-2">Date</th>
                        <th class="px-2 py-2">Total</th>
                        <th class="px-2 py-2">Courier ratio</th>
                        <th class="px-2 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($incompleteOrders as $incomplete)
                        @php
                            $rowNumber = $incompleteOrders->firstItem() + $loop->index;
                            $detailsId = 'io-details-'.$incomplete->id;
                            $fraudReport = is_array($incomplete->fraud_report) ? $incomplete->fraud_report : [];
                            $fraudData = is_array($fraudReport['data'] ?? null) ? $fraudReport['data'] : [];
                            $fraudSummary = is_array($fraudData['summary'] ?? null) ? $fraudData['summary'] : [];
                        @endphp
                        <tr
                            class="cursor-pointer border-t border-ink/5 hover:bg-mist/60"
                            onclick="document.getElementById('{{ $detailsId }}').classList.toggle('hidden'); this.querySelector('[data-chevron]')?.classList.toggle('rotate-180')"
                        >
                            <td class="px-2 py-2.5" onclick="event.stopPropagation()">
                                <input
                                    type="checkbox"
                                    name="incomplete_order_ids[]"
                                    value="{{ $incomplete->id }}"
                                    form="incomplete-bulk-form"
                                    class="incomplete-checkbox rounded border-ink/20"
                                >
                            </td>
                            <td class="px-2 py-2.5 text-muted">
                                <svg data-chevron class="h-3.5 w-3.5 transition" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/></svg>
                            </td>
                            <td class="px-2 py-2.5">{{ $rowNumber }}</td>
                            <td class="px-2 py-2.5">
                                <div class="font-medium text-ink">{{ $incomplete->customer_name ?: 'Guest' }}</div>
                                <div class="text-[11px] text-muted">#{{ $incomplete->id }}</div>
                            </td>
                            <td class="px-2 py-2.5">{{ $incomplete->customer_phone ?: '—' }}</td>
                            <td class="px-2 py-2.5 whitespace-nowrap">
                                <div>{{ $incomplete->created_at?->format('d M, Y') }}</div>
                                <div class="text-[11px] text-muted">{{ $incomplete->created_at?->format('h:i A') }}</div>
                            </td>
                            <td class="px-2 py-2.5">
                                <span class="admin-badge-pill bg-accent/10 text-accent">{{ Money::format($incomplete->total) }}</span>
                            </td>
                            <td class="px-2 py-2.5" onclick="event.stopPropagation()">
                                @include('admin.partials.fraud-badge', ['score' => $incomplete->fraud_score])
                            </td>
                            <td class="px-2 py-2.5 text-right" onclick="event.stopPropagation()">
                                <div class="inline-flex flex-wrap items-center justify-end gap-1">
                                    <form method="POST" action="{{ route('admin.incomplete-orders.fraud-check', $incomplete) }}">
                                        @csrf
                                        <button type="submit" class="admin-btn bg-teal-600 text-white hover:bg-teal-700">Check Ratio</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.incomplete-orders.accept', $incomplete) }}" onsubmit="return confirm('Accept this cart and convert it to a regular order?')">
                                        @csrf
                                        <button type="submit" class="admin-btn bg-emerald-600 text-white hover:bg-emerald-700" title="Accept">Accept</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.incomplete-orders.destroy', $incomplete) }}" onsubmit="return confirm('Delete this incomplete order permanently?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="admin-btn-danger" title="Delete">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <tr id="{{ $detailsId }}" class="hidden border-t border-ink/5 bg-mist/40">
                            <td colspan="9" class="px-2 py-3">
                                <div class="space-y-3">
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div>
                                            <div class="admin-section-title mb-1.5">Delivery address</div>
                                            <p class="text-sm text-ink">{{ $incomplete->customer_address ?: 'No address provided' }}</p>
                                            <div class="mt-3">
                                                <div class="admin-section-title mb-1.5">Traffic</div>
                                                <div class="flex flex-wrap items-center gap-2 text-sm">
                                                    @include('admin.partials.platform-badge', [
                                                        'platform' => $incomplete->source_platform ?: 'direct',
                                                    ])
                                                    @if ($incomplete->utm_campaign)
                                                        <span class="text-muted">{{ $incomplete->utm_campaign }}</span>
                                                    @endif
                                                </div>
                                                @if ($incomplete->landing_url)
                                                    <a href="{{ $incomplete->landing_url }}" target="_blank" rel="noopener noreferrer" class="mt-1 block break-all text-xs text-sage hover:underline">
                                                        {{ \Illuminate\Support\Str::limit($incomplete->landing_url, 80) }}
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                        <div>
                                            <div class="admin-section-title mb-1.5">Order items</div>
                                            @php $items = $incomplete->lineItems(); @endphp
                                            @if ($items === [])
                                                <p class="text-sm text-muted">No product details.</p>
                                            @else
                                                <table class="w-full text-sm">
                                                    <thead>
                                                        <tr class="text-left text-[11px] uppercase tracking-wide text-muted">
                                                            <th class="pb-1.5 font-medium">Image</th>
                                                            <th class="pb-1.5 font-medium">Product</th>
                                                            <th class="pb-1.5 font-medium">Qty</th>
                                                            <th class="pb-1.5 text-right font-medium">Price</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($items as $item)
                                                            <tr class="border-t border-ink/5">
                                                                <td class="py-1.5 pr-2">
                                                                    @if (! empty($item['image']))
                                                                        <img src="{{ $item['image'] }}" alt="" class="h-10 w-10 rounded object-cover ring-1 ring-ink/10">
                                                                    @else
                                                                        <span class="inline-flex h-10 w-10 items-center justify-center rounded bg-fog text-[10px] text-muted">N/A</span>
                                                                    @endif
                                                                </td>
                                                                <td class="py-1.5 pr-2">{{ \Illuminate\Support\Str::limit($item['name'] ?? 'Product', 60) }}</td>
                                                                <td class="py-1.5">× {{ $item['qty'] ?? 1 }}</td>
                                                                <td class="py-1.5 text-right font-semibold">{{ Money::format($item['price'] ?? 0) }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            @endif
                                        </div>
                                    </div>

                                    <div>
                                        <div class="admin-section-title mb-1.5">Courier ratio summary</div>
                                        @if ($incomplete->fraud_score !== null || $fraudSummary !== [] || $fraudData !== [])
                                            <div class="mb-2 flex flex-wrap items-center gap-1.5">
                                                @include('admin.partials.fraud-badge', ['score' => $incomplete->fraud_score, 'size' => 'md'])
                                            </div>
                                            @include('admin.partials.courier-ratio-cards', [
                                                'fraudReport' => $fraudReport,
                                                'ourRecord' => $ourRecordsByPhone[trim((string) $incomplete->customer_phone)] ?? null,
                                                'phone' => $incomplete->customer_phone,
                                                'scrollKey' => 'incomplete-'.$incomplete->id,
                                            ])
                                        @else
                                            <p class="text-sm text-muted">Not checked yet. Use Check Ratio when a phone number is available.</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($incompleteOrders->hasPages())
            <div class="border-t border-ink/10 px-3 py-2 flex flex-col sm:flex-row items-center justify-between gap-2">
                <div class="text-xs text-muted">
                    Showing <span class="font-semibold text-ink">{{ $incompleteOrders->firstItem() ?? 0 }}</span> to <span class="font-semibold text-ink">{{ $incompleteOrders->lastItem() ?? 0 }}</span> of <span class="font-semibold text-ink">{{ $incompleteOrders->total() }}</span> incomplete orders
                    @if ($incompleteOrders->total() > 0)
                        (<span class="font-medium text-slate-600">{{ $incompleteOrders->perPage() }}</span> per page)
                    @endif
                </div>
                <div>{{ $incompleteOrders->links() }}</div>
            </div>
        @endif
    @endif
</div>

<script>
    document.getElementById('select-all-incomplete')?.addEventListener('change', function (e) {
        document.querySelectorAll('.incomplete-checkbox').forEach((el) => { el.checked = e.target.checked; });
    });
</script>
