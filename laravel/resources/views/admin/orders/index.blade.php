@extends('layouts.admin')

@php
    use App\Support\Money;

    $isIncompleteTab = ($activeTab ?? 'list') === 'incomplete';
@endphp

@section('title', $isIncompleteTab ? 'Incomplete Orders' : (($currentSection ?? 'orders') === 'courier' ? 'Courier Orders' : 'All Orders'))
@section('heading', $isIncompleteTab ? 'Incomplete Orders' : (($currentSection ?? 'orders') === 'courier' ? 'Courier Orders' : 'All Orders'))
@section('subheading', $isIncompleteTab
    ? 'Carts where checkout was not completed — tap a row for customer info and products'
    : ($currentStatus ? ucfirst(str_replace('_', ' ', $currentStatus)).' orders' : 'Manage all orders'))

@section('actions')
    <div class="orders-desktop-actions">
        <a href="{{ route('admin.orders.create') }}" class="admin-btn-primary orders-top-btn inline-flex items-center" title="Create a manual order">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            <span class="orders-top-label">Create</span>
        </a>
        <button type="button" id="export-orders-btn" class="admin-btn-secondary orders-top-btn" title="Export orders (Selected or Filtered/All)">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
            </svg>
            <span class="orders-top-label" id="export-orders-label">Export</span>
        </button>
        <button type="button" onclick="document.getElementById('order-import-modal').classList.remove('hidden')" class="admin-btn-secondary orders-top-btn" title="Import orders from CSV">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
            </svg>
            <span class="orders-top-label">Import</span>
        </button>
    </div>
@endsection

@push('styles')
<style>
/* ===== Desktop: normal layout (compact only on mobile) ===== */
.admin-app:has(.orders-index) .orders-desktop-actions {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
}
.admin-app:has(.orders-index) .admin-page-actions .orders-top-btn,
.admin-app:has(.orders-index) .admin-page-header .orders-top-btn {
    min-height: 2rem;
    padding: 0.35rem 0.7rem;
    font-size: 0.75rem;
    gap: 0.35rem;
}

.admin-app .orders-index .orders-top-nav {
    margin-bottom: 0.65rem;
}
.admin-app .orders-index .orders-seg {
    width: auto;
    max-width: 100%;
    display: inline-flex;
    flex-wrap: wrap;
}
.admin-app .orders-index .orders-seg .admin-seg-item {
    flex: 0 0 auto;
}
.admin-app .orders-index .orders-seg-short { display: none; }
.admin-app .orders-index .admin-tabs {
    position: relative;
    margin-bottom: 0.75rem;
    mask-image: linear-gradient(90deg, #000 0%, #000 calc(100% - 1.5rem), transparent);
    -webkit-mask-image: linear-gradient(90deg, #000 0%, #000 calc(100% - 1.5rem), transparent);
}
.admin-app .orders-index .admin-tabs-item.is-active {
    background: color-mix(in oklab, var(--color-sage) 12%, white);
    border-radius: 0.45rem 0.45rem 0 0;
}
/* Toolbar card */
.admin-app .orders-index .orders-toolbar-card {
    display: block;
    overflow: visible;
    margin-bottom: 0.75rem;
    padding: 0.65rem 0.75rem;
}
.admin-app .orders-index .orders-toolbar-main {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.65rem 0.85rem;
}
.admin-app .orders-index .orders-toolbar-filters,
.admin-app .orders-index .orders-toolbar-bulk {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.4rem;
}
.admin-app .orders-index .orders-toolbar-divider {
    width: 1px;
    align-self: stretch;
    min-height: 1.75rem;
    background: #e2e8f0;
}
.admin-app .orders-index .orders-filter-q {
    width: 13.5rem;
    min-width: 10rem;
}
.admin-app .orders-index .orders-selected-chip {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    background: #ecf8ea;
    border: 1px solid #b7dfb2;
    color: #128200;
    padding: 0.2rem 0.55rem;
    font-size: 0.65rem;
    font-weight: 700;
}

/* Table */
.admin-app .orders-index .admin-orders-table.is-col-resizable {
    table-layout: fixed;
    width: max-content;
    min-width: 100%;
}
.admin-app .orders-index .admin-orders-table.is-col-resizable th,
.admin-app .orders-index .admin-orders-table.is-col-resizable td {
    overflow: hidden;
    text-overflow: ellipsis;
}
.admin-app .orders-index .admin-orders-table.is-col-resizable td:has(.order-actions),
.admin-app .orders-index .admin-orders-table.is-col-resizable th[data-col="actions"] {
    overflow: visible;
}
.admin-app .orders-index .admin-orders-table thead th {
    position: relative;
    user-select: none;
    background: linear-gradient(180deg, #f8fafc, #f1f5f9);
    color: #64748b;
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    padding: 0.65rem 0.6rem;
    text-align: center;
    vertical-align: middle;
}
.admin-app .orders-index .admin-orders-table tbody td {
    padding: 0.7rem 0.6rem;
    border-top: 1px solid #eef2f7;
    vertical-align: middle;
    text-align: center;
}
.admin-app .orders-index .admin-orders-table tbody td > * {
    max-width: 100%;
}
.admin-app .orders-index .cell-clamp {
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
    overflow: hidden;
    word-break: break-word;
    max-width: 100%;
    margin-inline: auto;
}
.admin-app .orders-index .order-customer,
.admin-app .orders-index .order-cell,
.admin-app .orders-index .order-item-row {
    max-width: 100%;
    margin-inline: auto;
    text-align: left;
}
.admin-app .orders-index .order-actions {
    margin-inline: auto;
}
.admin-app .orders-index .admin-orders-table tbody tr:nth-child(even) {
    background: #fcfcfd;
}
.admin-app .orders-index .admin-orders-table tbody tr:hover {
    background: #f1f5f9;
}
.admin-app .orders-index .admin-orders-table .col-resize-handle {
    position: absolute;
    top: 0;
    right: -3px;
    z-index: 5;
    width: 7px;
    height: 100%;
    cursor: col-resize;
    touch-action: none;
}
.admin-app .orders-index .admin-orders-table .col-resize-handle::after {
    content: '';
    position: absolute;
    top: 22%;
    bottom: 22%;
    left: 3px;
    width: 1px;
    background: #cbd5e1;
    opacity: 0;
    transition: opacity .12s ease, background-color .12s ease;
}
.admin-app .orders-index .admin-orders-table thead th:hover .col-resize-handle::after,
.admin-app .orders-index .admin-orders-table thead th.is-resizing .col-resize-handle::after {
    opacity: 1;
}
.admin-app .orders-index .admin-orders-table thead th.is-resizing .col-resize-handle::after {
    background: var(--color-sage, #128200);
    width: 2px;
    left: 2.5px;
}
body.is-col-resizing,
body.is-col-resizing * {
    cursor: col-resize !important;
    user-select: none !important;
}

/* Sticky only Actions (right). Left sticky offsets caused header/body gaps. */
.admin-app .orders-index .admin-table-wrap {
    position: relative;
}
.admin-app .orders-index .orders-col-sticky-right {
    position: sticky;
    right: 0;
    z-index: 4;
    background: #fff;
    box-shadow: -6px 0 8px -8px rgb(15 23 42 / 0.25);
}
.admin-app .orders-index thead .orders-col-sticky-right {
    background: #f5f7fa;
}
.admin-app .orders-index tbody tr:nth-child(even) .orders-col-sticky-right {
    background: #fcfcfd;
}
.admin-app .orders-index tbody tr:hover .orders-col-sticky-right {
    background: #f1f5f9;
}

.admin-app .orders-index .order-customer .inline-flex {
    padding: 0.1rem 0.4rem !important;
    font-size: 0.6rem !important;
    line-height: 1.1;
}

/* Actions */
.admin-app .orders-index .order-actions {
    display: inline-flex !important;
    flex-wrap: nowrap !important;
    align-items: center !important;
    gap: 0.3rem !important;
    width: max-content !important;
}
.admin-app .orders-index a.order-action,
.admin-app .orders-index button.order-action {
    display: inline-flex !important;
    flex: 0 0 auto !important;
    width: auto !important;
    min-width: 0 !important;
    height: 1.85rem !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 0.28rem !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 0.45rem !important;
    background: #fff !important;
    padding: 0 0.5rem !important;
    font-size: 0.68rem !important;
    font-weight: 700 !important;
    color: #475569 !important;
    text-decoration: none !important;
    cursor: pointer;
    white-space: nowrap !important;
}
.admin-app .orders-index a.order-action.is-primary,
.admin-app .orders-index button.order-action.is-primary {
    background: #ecf8ea !important;
    border-color: #b7dfb2 !important;
    color: #128200 !important;
}
.admin-app .orders-index a.order-action.is-print,
.admin-app .orders-index button.order-action.is-print {
    background: #eff6ff !important;
    border-color: #bfdbfe !important;
    color: #1d4ed8 !important;
}
.admin-app .orders-index .order-action svg {
    width: 0.9rem !important;
    height: 0.9rem !important;
    flex-shrink: 0 !important;
}
.admin-app .orders-index .order-more {
    position: relative;
    display: inline-flex;
}
.admin-app .orders-index .order-more-toggle {
    width: 1.85rem !important;
    min-width: 1.85rem !important;
    padding: 0 !important;
}
.admin-app .orders-index .order-more-tip {
    position: absolute;
    bottom: calc(100% + 0.4rem);
    right: 0;
    z-index: 40;
    display: none;
    align-items: center;
    gap: 0.25rem;
    min-width: max-content;
    padding: 0.3rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.55rem;
    background: #fff;
    box-shadow: 0 8px 20px rgb(15 23 42 / 0.14);
}
.admin-app .orders-index .order-more-tip::after {
    content: '';
    position: absolute;
    top: 100%;
    right: 0.7rem;
    border: 5px solid transparent;
    border-top-color: #fff;
    filter: drop-shadow(0 1px 0 #e2e8f0);
}
.admin-app .orders-index .order-more:hover .order-more-tip,
.admin-app .orders-index .order-more:focus-within .order-more-tip {
    display: inline-flex;
}
.admin-app .orders-index .order-more-item {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.4rem;
    background: #fff;
    padding: 0.3rem 0.5rem;
    font-size: 0.68rem;
    font-weight: 700;
    color: #0f766e;
    cursor: pointer;
    white-space: nowrap;
}
.admin-app .orders-index .order-more-item:hover {
    background: #f0fdfa;
    border-color: #99f6e4;
}
.admin-app .orders-index .order-more-item.is-danger {
    color: #b91c1c;
    border-color: #fecaca;
}
.admin-app .orders-index .order-more-item.is-danger:hover {
    background: #fef2f2;
}
.admin-app .orders-index .order-more-item svg {
    width: 0.85rem;
    height: 0.85rem;
    flex-shrink: 0;
}
.admin-app .orders-index .order-update {
    position: relative;
    max-width: 100%;
}
.admin-app .orders-index .order-update-text {
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
    overflow: hidden;
    font-size: 0.68rem;
    line-height: 1.25;
    font-weight: 600;
    color: #0f766e;
    cursor: default;
    text-align: left;
}
.admin-app .orders-index .order-update-tip {
    display: none;
}
.order-update-tip-float {
    position: fixed;
    z-index: 9999;
    display: none;
    width: max-content;
    max-width: 18rem;
    padding: 0.45rem 0.55rem;
    border: 1px solid #334155;
    border-radius: 0.45rem;
    background: #0f172a;
    color: #f8fafc;
    font-size: 0.68rem;
    font-weight: 500;
    line-height: 1.35;
    white-space: pre-wrap;
    box-shadow: 0 10px 24px rgb(15 23 42 / 0.28);
    pointer-events: none;
}

@media (max-width: 1100px) {
    .admin-app .orders-index .orders-toolbar-divider { display: none; }
}

/* Mobile helpers (desktop defaults) */
.admin-app .orders-index .orders-mobile-actions { display: none; }
.admin-app .orders-index .orders-filter-more-btn { display: none; }
.admin-app .orders-index .orders-filter-primary,
.admin-app .orders-index .orders-more-filters { display: contents; }
.admin-app .orders-index .orders-filter-q { order: 1; }
.admin-app .orders-index .orders-more-filters > .field { order: 2; }
.admin-app .orders-index .orders-filter-go { order: 3; }

/* Mobile: readable compact chrome (not tiny) */
@media (max-width: 767px) {
    .admin-app:has(.orders-index) .admin-page-header {
        display: none !important;
    }
    .admin-app:has(.orders-index) .admin-content {
        padding-inline: 0.5rem !important;
        padding-top: 0.15rem !important;
        padding-bottom: 5rem !important;
    }
    .admin-app:has(.orders-index) .admin-flash-success,
    .admin-app:has(.orders-index) .admin-flash-error {
        margin-bottom: 0.35rem !important;
        padding: 0.45rem 0.6rem !important;
        font-size: 0.75rem !important;
    }

    .admin-app .orders-index .orders-top-nav {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        margin: 0 0 0.4rem !important;
        position: sticky;
        top: 3.5rem;
        z-index: 25;
        background: #f8fafc;
        padding: 0.15rem 0 0.25rem;
    }
    .admin-app .orders-index .orders-seg {
        flex: 1 1 auto;
        min-width: 0;
        padding: 0.2rem !important;
        gap: 0.2rem !important;
        border-radius: 0.65rem !important;
    }
    .admin-app .orders-index .orders-seg-full { display: none !important; }
    .admin-app .orders-index .orders-seg-short { display: inline !important; }
    .admin-app .orders-index .orders-seg .admin-seg-item {
        flex: 1 1 0;
        justify-content: center;
        min-height: 2.35rem !important;
        padding: 0.4rem 0.35rem !important;
        font-size: 0.78rem !important;
        line-height: 1.15 !important;
        gap: 0 !important;
        border-radius: 0.5rem !important;
        white-space: nowrap;
    }
    .admin-app .orders-index .orders-seg .admin-seg-item svg {
        display: none !important;
    }
    .admin-app .orders-index .orders-mobile-actions {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        flex-shrink: 0;
    }
    .admin-app .orders-index .orders-mobile-actions .orders-top-btn {
        width: 2.35rem !important;
        height: 2.35rem !important;
        min-height: 2.35rem !important;
        padding: 0 !important;
        border-radius: 0.55rem !important;
        justify-content: center !important;
    }
    .admin-app .orders-index .orders-mobile-actions .orders-top-btn svg {
        width: 1.05rem !important;
        height: 1.05rem !important;
    }
    .admin-app .orders-index .orders-mobile-actions .orders-top-label {
        display: none !important;
    }

    .admin-app .orders-index .admin-tabs {
        display: flex !important;
        flex-wrap: nowrap !important;
        overflow-x: auto !important;
        overflow-y: visible !important;
        -webkit-overflow-scrolling: touch;
        margin-bottom: 0.45rem !important;
        border-bottom: none !important;
        gap: 0.35rem !important;
        padding: 0.1rem 0 0.2rem !important;
        mask-image: none !important;
        -webkit-mask-image: none !important;
    }
    .admin-app .orders-index .admin-tabs-item {
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.3rem !important;
        flex: 0 0 auto !important;
        min-height: 2.2rem !important;
        height: auto !important;
        max-height: none !important;
        padding: 0.45rem 0.7rem !important;
        font-size: 0.78rem !important;
        font-weight: 600 !important;
        line-height: 1.2 !important;
        white-space: nowrap !important;
        overflow: visible !important;
        border: 1px solid #e2e8f0 !important;
        border-bottom: 1px solid #e2e8f0 !important;
        border-radius: 999px !important;
        background: #fff !important;
        color: #475569 !important;
        box-shadow: none !important;
    }
    .admin-app .orders-index .admin-tabs-item .orders-tab-label {
        display: inline !important;
        white-space: nowrap !important;
        overflow: visible !important;
        text-overflow: clip !important;
        max-width: none !important;
        line-height: 1.2 !important;
    }
    .admin-app .orders-index .admin-tabs-item.is-active {
        background: var(--color-sage) !important;
        border-color: var(--color-sage) !important;
        color: #fff !important;
        border-radius: 999px !important;
        border-bottom: 1px solid var(--color-sage) !important;
    }
    .admin-app .orders-index .admin-tabs-item.is-active .admin-tabs-count {
        background: rgb(255 255 255 / 0.22) !important;
        color: #fff !important;
    }
    .admin-app .orders-index .admin-tabs-count {
        min-width: 1.1rem !important;
        min-height: 1.1rem !important;
        padding: 0.05rem 0.35rem !important;
        font-size: 0.65rem !important;
        line-height: 1.2 !important;
    }

    .admin-app .orders-index .orders-toolbar {
        margin-bottom: 0.45rem !important;
        padding: 0.45rem !important;
        border-radius: 0.75rem;
        background: #fff;
        border: 1px solid #e2e8f0;
    }
    .admin-app .orders-index .orders-toolbar-main {
        gap: 0.4rem !important;
    }
    .admin-app .orders-index .orders-toolbar-filters {
        width: 100%;
        display: flex !important;
        flex-direction: column;
        gap: 0.35rem !important;
        align-items: stretch;
    }
    .admin-app .orders-index .orders-filter-primary {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) auto auto;
        gap: 0.35rem;
        align-items: center;
    }
    .admin-app .orders-index .orders-filter-q,
    .admin-app .orders-index .orders-filter-more-btn,
    .admin-app .orders-index .orders-filter-go {
        order: 0 !important;
    }
    .admin-app .orders-index .orders-filter-q {
        width: 100% !important;
        min-width: 0 !important;
        min-height: 2.4rem !important;
        height: 2.4rem !important;
        font-size: 0.85rem !important;
        padding: 0 0.75rem !important;
        border-radius: 0.6rem !important;
    }
    .admin-app .orders-index .orders-filter-more-btn {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        width: 2.4rem;
        height: 2.4rem;
        min-height: 2.4rem !important;
        padding: 0 !important;
        border-radius: 0.6rem !important;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #475569;
    }
    .admin-app .orders-index .orders-filter-more-btn.is-open,
    .admin-app .orders-index .orders-filter-more-btn.has-active {
        background: color-mix(in oklab, var(--color-sage) 14%, white);
        border-color: color-mix(in oklab, var(--color-sage) 35%, white);
        color: var(--color-sage);
    }
    .admin-app .orders-index .orders-filter-go {
        width: 2.75rem;
        min-height: 2.4rem !important;
        height: 2.4rem !important;
        padding: 0 !important;
        font-size: 0.8rem !important;
        border-radius: 0.6rem !important;
    }
    .admin-app .orders-index .orders-more-filters {
        display: none;
        grid-template-columns: 1fr 1fr;
        gap: 0.35rem;
    }
    .admin-app .orders-index .orders-more-filters.is-open {
        display: grid;
    }
    .admin-app .orders-index .orders-more-filters .field {
        width: 100% !important;
        min-width: 0 !important;
        min-height: 2.4rem !important;
        height: 2.4rem !important;
        font-size: 0.8rem !important;
        padding: 0 0.65rem !important;
    }
    .admin-app .orders-index .orders-toolbar-divider { display: none !important; }

    .admin-app .orders-index .orders-toolbar-bulk {
        display: none !important;
    }
    .admin-app .orders-index.has-selection .orders-toolbar-bulk {
        display: flex !important;
        position: fixed;
        left: 0.5rem;
        right: 0.5rem;
        bottom: calc(3.6rem + env(safe-area-inset-bottom));
        z-index: 45;
        width: auto !important;
        gap: 0.35rem !important;
        padding: 0.55rem;
        border-radius: 0.85rem;
        background: #0f172a;
        box-shadow: 0 12px 28px rgb(15 23 42 / 0.28);
        flex-wrap: wrap;
        align-items: center;
    }
    .admin-app .orders-index.has-selection .orders-toolbar-bulk .field,
    .admin-app .orders-index.has-selection .orders-toolbar-bulk .admin-btn,
    .admin-app .orders-index.has-selection .orders-toolbar-bulk .admin-btn-primary,
    .admin-app .orders-index.has-selection .orders-toolbar-bulk .admin-btn-secondary,
    .admin-app .orders-index.has-selection .orders-toolbar-bulk .admin-btn-danger {
        min-height: 2.25rem !important;
        height: 2.25rem !important;
        font-size: 0.75rem !important;
        padding: 0 0.6rem !important;
        border-radius: 0.5rem !important;
        flex: 1 1 auto;
    }
    .admin-app .orders-index.has-selection .orders-toolbar-bulk .field {
        background: #1e293b;
        border-color: #334155;
        color: #f8fafc;
        min-width: 6.5rem !important;
        width: auto !important;
        flex: 1 1 7rem;
    }
    .admin-app .orders-index.has-selection .orders-selected-chip {
        color: #bbf7d0;
        background: transparent;
        border: 0;
        padding: 0;
        font-size: 0.75rem;
        width: 100%;
    }
    .admin-app .orders-index.has-selection .orders-toolbar-bulk label {
        color: #cbd5e1;
    }
    .admin-app .orders-index .admin-orders-card .admin-card-header {
        padding: 0.45rem 0.55rem;
        gap: 0.3rem;
    }
    .admin-app .orders-index .admin-orders-card .admin-card-header h2 {
        font-size: 0.78rem;
    }
    .admin-app .orders-index .admin-orders-card .admin-card-header p {
        display: none;
    }
    .admin-app .orders-index .admin-orders-table {
        font-size: 0.72rem;
    }
    .admin-app .orders-index .admin-orders-table thead th {
        padding: 0.4rem 0.3rem;
        font-size: 0.58rem;
    }
    .admin-app .orders-index .admin-orders-table tbody td {
        padding: 0.4rem 0.3rem;
    }
    .admin-app .orders-index a.order-action,
    .admin-app .orders-index button.order-action {
        height: 1.7rem !important;
        padding: 0 0.35rem !important;
        font-size: 0.62rem !important;
    }
    .admin-app .orders-index .order-action span { display: none; }
    .admin-app .orders-index .order-action svg {
        width: 0.9rem !important;
        height: 0.9rem !important;
    }
    .admin-app .orders-index .order-more-toggle {
        width: 1.7rem !important;
        min-width: 1.7rem !important;
    }
    .admin-app .orders-index .orders-footer {
        padding: 0.55rem 0.65rem;
        gap: 0.45rem;
    }
}
</style>
@endpush

@section('content')
<div class="orders-index">
    @php
        $queryBase = array_filter([
            'section' => ($currentSection ?? 'orders') !== 'orders' ? $currentSection : null,
            'q' => request('q'),
            'tracking' => request('tracking'),
            'platform' => ($currentPlatform ?? request('platform')) ?: null,
            'courier' => ($currentCourier ?? request('courier')) ?: null,
            'tab' => in_array($activeTab, ['list', 'incomplete'], true) ? null : $activeTab,
        ], fn ($value) => $value !== null && $value !== '');
        $isOrderStatusTab = ! $isIncompleteTab && $activeTab !== 'fraud';
    @endphp

    {{-- Section Toggle + mobile action icons --}}
    <div class="orders-top-nav">
        <div class="admin-seg orders-seg">
            <a
                href="{{ route('admin.orders.index', ['section' => 'orders', 'status' => 'pending']) }}"
                @class(['admin-seg-item', 'is-active' => ! $isIncompleteTab && ($currentSection ?? 'orders') === 'orders'])
            >
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                </svg>
                <span class="orders-seg-full">All Orders</span>
                <span class="orders-seg-short">Orders</span>
            </a>
            <a
                href="{{ route('admin.orders.index', ['section' => 'courier', 'status' => 'pending']) }}"
                @class(['admin-seg-item', 'is-active' => ! $isIncompleteTab && ($currentSection ?? 'orders') === 'courier'])
            >
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.25V3.75m0 3.75a1.125 1.125 0 01-1.125-1.125H5.625A1.125 1.125 0 004.5 3.75v10.5" />
                </svg>
                <span class="orders-seg-full">Courier</span>
                <span class="orders-seg-short">Courier</span>
            </a>
            <a
                href="{{ route('admin.orders.index', ['tab' => 'incomplete']) }}"
                @class(['admin-seg-item', 'is-active' => $isIncompleteTab])
            >
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="orders-seg-full">Incomplete</span>
                <span class="orders-seg-short">Incomplete</span>
            </a>
        </div>
        <div class="orders-mobile-actions">
            <a href="{{ route('admin.orders.create') }}" class="admin-btn-primary orders-top-btn inline-flex items-center" title="Create order" aria-label="Create order">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
            </a>
            <button type="button" class="admin-btn-secondary orders-top-btn export-orders-btn" title="Export orders" aria-label="Export orders">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
            </button>
            <button type="button" onclick="document.getElementById('order-import-modal').classList.remove('hidden')" class="admin-btn-secondary orders-top-btn" title="Import orders" aria-label="Import orders">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                </svg>
            </button>
        </div>
    </div>

    {{-- Sub-tabs with counts for active section --}}
    @unless ($isIncompleteTab)
    <div class="admin-tabs">
        @foreach ($sectionTabs as $tab)
            @php
                $isTabActive = ! $isIncompleteTab && request('status') === $tab['slug'];
                $tabUrl = route('admin.orders.index', array_filter(array_merge($queryBase, [
                    'section' => $currentSection,
                    'status' => $tab['slug'],
                    'tab' => null,
                ])));
            @endphp
            <a href="{{ $tabUrl }}" @class(['admin-tabs-item', 'is-active' => $isTabActive]) title="{{ $tab['label'] }}">
                <span class="orders-tab-label">{{ $tab['label'] }}</span>
                <span class="admin-tabs-count">{{ $tab['count'] }}</span>
            </a>
        @endforeach
    </div>
    @endunless

    @if ($isIncompleteTab)
        @include('admin.incomplete-orders._panel', [
            'incompleteOrders' => $incompleteOrders,
            'ourRecordsByPhone' => $ourRecordsByPhone ?? [],
        ])
    @else
        @if (($currentSection ?? '') === 'courier' && ! empty($courierSections))
            {{-- Per-courier booking sections --}}
            <div class="mb-3 overflow-x-auto">
                <div class="admin-seg">
                    <a
                        href="{{ route('admin.orders.index', array_filter(array_merge($queryBase, ['courier' => null]))) }}"
                        @class(['admin-seg-item', 'is-active' => ($currentCourier ?? '') === ''])
                    >
                        All couriers
                        <span class="admin-tabs-count">{{ $courierBookedTotal }}</span>
                    </a>

                    @foreach ($courierSections as $courierSection)
                        <a
                            href="{{ route('admin.orders.index', array_filter(array_merge($queryBase, ['courier' => $courierSection['id'], 'tab' => null]))) }}"
                            @class(['admin-seg-item', 'is-active' => (string) ($currentCourier ?? '') === (string) $courierSection['id']])
                        >
                            {{ $courierSection['label'] }}
                            <span class="admin-tabs-count">{{ $courierSection['booked_count'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Filter + bulk toolbar --}}
        <div class="orders-toolbar orders-toolbar-card">
            <div class="orders-toolbar-main">
                @php
                    $hasAdvancedFilters = filled(request('tracking'))
                        || filled($currentPlatform ?? request('platform'))
                        || ((int) ($perPage ?? 50) !== 50);
                @endphp
                <form method="GET" id="orders-filter-form" class="orders-toolbar-filters">
                    <input type="hidden" name="section" value="{{ $currentSection ?? 'orders' }}">
                    @if ($currentStatus)
                        <input type="hidden" name="status" value="{{ $currentStatus }}">
                    @endif
                    @if ($activeTab !== 'list')
                        <input type="hidden" name="tab" value="{{ $activeTab }}">
                    @endif
                    @if (! empty($currentCourier))
                        <input type="hidden" name="courier" value="{{ $currentCourier }}">
                    @endif
                    <div class="orders-filter-primary">
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search invoice, phone…" class="field orders-filter-q">
                        <button
                            type="button"
                            id="orders-filter-more-btn"
                            class="orders-filter-more-btn {{ $hasAdvancedFilters ? 'has-active is-open' : '' }}"
                            aria-expanded="{{ $hasAdvancedFilters ? 'true' : 'false' }}"
                            aria-controls="orders-more-filters"
                            title="More filters"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                            </svg>
                        </button>
                        <button type="submit" class="admin-btn-primary orders-filter-go">Go</button>
                    </div>
                    <div id="orders-more-filters" class="orders-more-filters {{ $hasAdvancedFilters ? 'is-open' : '' }}">
                        <select name="tracking" class="field w-[7rem]" aria-label="Tracking">
                            <option value="">Tracking</option>
                            <option value="with" @selected(request('tracking') === 'with')>Has tracking</option>
                            <option value="without" @selected(request('tracking') === 'without')>No tracking</option>
                        </select>
                        <select name="platform" class="field w-[7rem]" aria-label="Platform">
                            <option value="">Platform</option>
                            @foreach (['facebook' => 'Facebook', 'instagram' => 'Instagram', 'google' => 'Google', 'tiktok' => 'TikTok', 'youtube' => 'YouTube', 'direct' => 'Direct', 'referral' => 'Referral'] as $value => $label)
                                <option value="{{ $value }}" @selected(($currentPlatform ?? request('platform')) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <select name="per_page" class="field w-[5.5rem] font-medium text-slate-700" aria-label="Rows per page" onchange="this.form.submit()">
                            @foreach ([50, 100, 200, 500, 1000] as $size)
                                <option value="{{ $size }}" @selected((int) ($perPage ?? 50) === $size)>{{ $size }}/p</option>
                            @endforeach
                        </select>
                    </div>
                </form>

                <div class="orders-toolbar-divider" aria-hidden="true"></div>

                <form method="POST" action="{{ route('admin.orders.bulk') }}" id="orders-bulk-form" class="orders-toolbar-bulk">
                    @csrf
                    <input type="hidden" name="section" value="{{ $currentSection ?? 'orders' }}">
                    <input type="hidden" name="cancel_reason" id="bulk-cancel-reason-input" value="">
                    <span id="orders-selected-count" class="orders-selected-chip hidden" aria-live="polite">0 selected</span>
                    <select name="status" id="bulk-status-select" class="field w-[8rem]" aria-label="Bulk status">
                        <option value="">{{ ($currentSection ?? '') === 'courier' ? 'Courier…' : 'Status…' }}</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->slug }}">{{ $status->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" name="action" value="status" id="bulk-status-btn" class="admin-btn-primary">Assign</button>
                    @if (auth('admin')->user()?->canDeleteOrders())
                        <button type="submit" name="action" value="delete" class="admin-btn-danger" onclick="return confirm('Delete selected orders?')">Delete</button>
                    @endif

                    @if (($currentSection ?? 'orders') === 'courier' && ! empty($courierProviders))
                        <select name="courier_api_id" class="field w-28" aria-label="Courier">
                            <option value="">Courier…</option>
                            @foreach ($courierProviders as $courier)
                                <option value="{{ $courier['id'] }}">{{ $courier['label'] }}</option>
                            @endforeach
                        </select>
                        <label class="inline-flex items-center gap-0.5 text-[10px] text-muted" title="Resend even if tracking exists">
                            <input type="checkbox" name="force" value="1" class="h-3 w-3 rounded border-ink/20">
                            Force
                        </label>
                        <button type="submit" name="action" value="send_to_courier" class="admin-btn-secondary">Courier Entry</button>
                    @endif

                    <button type="submit" name="action" value="print" formtarget="_blank" class="admin-btn-secondary" title="Print selected orders">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Print
                    </button>
                </form>
            </div>
        </div>

        <div class="admin-card admin-orders-card">
            <div class="admin-card-header">
                <div>
                    <h2 class="text-sm font-semibold text-ink">
                        {{ ($currentSection ?? 'orders') === 'courier' ? 'Courier orders' : 'All orders' }}
                    </h2>
                    <p class="text-xs text-muted mt-0.5">
                        {{ number_format($orders->total()) }} result{{ $orders->total() === 1 ? '' : 's' }}
                        @if ($currentStatus && $currentStatus !== 'all')
                            · {{ ucfirst(str_replace('_', ' ', $currentStatus)) }}
                        @elseif ($currentStatus === 'all')
                            · All
                        @endif
                    </p>
                </div>
                <button type="button" id="orders-reset-col-widths" class="admin-btn-secondary !px-2.5 !py-1 !text-[11px]" title="Reset column widths">
                    Reset widths
                </button>
            </div>
            <div class="admin-table-wrap">
                <table class="admin-table admin-orders-table" id="orders-resizable-table">
                    <colgroup>
                        <col data-col="select">
                        <col data-col="order">
                        <col data-col="customer">
                        <col data-col="updates">
                        <col data-col="items">
                        <col data-col="ratio">
                        <col data-col="amount">
                        <col data-col="tracking">
                        <col data-col="status">
                        <col data-col="actions">
                    </colgroup>
                    <thead>
                        <tr>
                            <th data-col="select">
                                <input type="checkbox" id="select-all-orders" class="rounded border-ink/20">
                            </th>
                            <th data-col="order">Order</th>
                            <th data-col="customer">Customer</th>
                            <th data-col="updates">Updates</th>
                            <th data-col="items">Items</th>
                            <th data-col="ratio">Ratio</th>
                            <th data-col="amount">Amount</th>
                            <th data-col="tracking">Tracking</th>
                            <th data-col="status">Status</th>
                            <th data-col="actions" class="orders-col-sticky-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            @php
                                $noteText = trim((string) ($order->admin_notes ?: $order->notes ?: ''));
                                $badge = match ($order->status) {
                                    'pending', 'unpaid', 'advance_payment', 'preorder' => 'admin-badge-warning',
                                    'approved', 'confirmed', 'incomplete_to_confirmed' => 'admin-badge-success',
                                    'processing' => 'admin-badge-info',
                                    'rts' => 'admin-badge-info',
                                    'shipped', 'on_the_way', 'in_courier' => 'admin-badge-info',
                                    'delivered', 'completed' => 'admin-badge-success',
                                    'partial' => 'admin-badge-warning',
                                    'on_hold' => 'admin-badge-warning',
                                    'no_response' => 'admin-badge-neutral',
                                    'pending_return' => 'admin-badge-warning',
                                    'refunded', 'returned', 'courier_returned' => 'admin-badge-warning',
                                    'cancelled' => 'admin-badge-danger',
                                    'lost' => 'admin-badge-danger',
                                    default => 'admin-badge-neutral',
                                };
                                $customerInitials = collect(preg_split('/\s+/', trim((string) $order->customer_name) ?: 'Guest'))
                                    ->filter()
                                    ->take(2)
                                    ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                                    ->implode('');
                                $latestLog = $order->activityLogs->first();
                            @endphp
                            <tr>
                                <td>
                                    <input type="checkbox" name="order_ids[]" value="{{ $order->id }}" form="orders-bulk-form" class="order-checkbox rounded border-ink/20">
                                </td>
                                @php
                                    $orderTip = '#'.$order->invoice_no.' · '.$order->created_at?->format('d M, h:i a').' · #'.$order->id;
                                    $customerTip = trim(($order->customer_phone ?: '—').' · '.($order->customer_name ?: '').' · '.($order->customer_address ?: ''));
                                    $updateShort = $latestLog
                                        ? trim(($latestLog->causer_name ? $latestLog->causer_name.': ' : '').$latestLog->description)
                                        : ('Updated '.$order->updated_at?->diffForHumans());
                                    $updateFull = $updateShort;
                                    if ($noteText !== '') {
                                        $updateFull = trim($updateFull.($updateFull !== '' ? "\n" : '').'Note: '.$noteText);
                                    }
                                    if ($latestLog) {
                                        $updateFull = trim($updateFull."\n".$order->updated_at?->diffForHumans());
                                    }
                                    $itemsTip = $order->items->map(fn ($item) => $item->product_name.' ('.$item->qty.'× '.Money::format($item->price).')')->implode("\n") ?: 'No items';
                                    $amountTip = Money::format($order->total).' · '.($order->payment_status === 'paid' ? 'Paid' : 'Unpaid').' · '.($order->payment_method === 'sslcommerz' ? 'SSL' : strtoupper($order->payment_method ?? 'COD'));
                                    $trackingTip = $order->tracking_number
                                        ? (($order->courierApi?->displayName() ?: strtoupper((string) ($order->courier_provider ?: 'Courier'))).' · '.$order->tracking_number)
                                        : 'Not booked';
                                    $statusTip = $order->statusLabel().($order->status === 'cancelled' && $order->cancel_reason ? ' · '.$order->cancel_reason : '');
                                @endphp
                                <td>
                                    <div class="order-cell cell-tip" data-tip="{{ $orderTip }}">
                                        <a href="{{ route('admin.orders.show', $order) }}" class="order-invoice cell-clamp">
                                            #{{ $order->invoice_no }}
                                        </a>
                                        <div class="mt-0.5 text-[11px] text-muted cell-clamp">
                                            {{ $order->created_at?->format('d M, h:i a') }} · #{{ $order->id }}
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="order-customer cell-tip" data-tip="{{ $customerTip }}">
                                        <span class="order-customer-avatar">{{ $customerInitials ?: 'G' }}</span>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5 min-w-0">
                                                <div class="font-semibold tabular-nums text-ink truncate">{{ $order->customer_phone ?: '—' }}</div>
                                                @php
                                                    $sourcePlatform = strtolower((string) ($order->source_platform ?: ''));
                                                    $knownPlatforms = ['facebook', 'instagram', 'google', 'tiktok', 'youtube', 'referral', 'direct'];
                                                    $looksLikeIp = (bool) preg_match('/^\d{1,3}([._-]\d{1,3}){3}$/', $sourcePlatform)
                                                        || filter_var(str_replace('_', '.', $sourcePlatform), FILTER_VALIDATE_IP);
                                                    $showPlatformBadge = $sourcePlatform !== ''
                                                        && in_array($sourcePlatform, $knownPlatforms, true)
                                                        && ! $looksLikeIp;
                                                @endphp
                                                @if ($showPlatformBadge)
                                                    @include('admin.partials.platform-badge', [
                                                        'platform' => $sourcePlatform,
                                                    ])
                                                @endif
                                            </div>
                                            <div class="mt-0.5 truncate font-medium text-slate-700">{{ $order->customer_name }}</div>
                                            <div class="mt-0.5 truncate text-[11px] text-muted">
                                                {{ $order->customer_address ?: '—' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="order-update cell-tip" tabindex="0" data-tip="{{ $updateFull }}">
                                        <div class="order-update-text">{{ $updateShort }}</div>
                                    </div>
                                </td>
                                <td>
                                    <div class="space-y-1.5 cell-tip" data-tip="{{ $itemsTip }}">
                                        @forelse ($order->items->take(2) as $item)
                                            @php
                                                $imagePath = $item->product?->primaryImagePath();
                                            @endphp
                                            <div class="order-item-row">
                                                <div class="order-item-thumb">
                                                    @if ($imagePath)
                                                        <img src="{{ asset('storage/'.$imagePath) }}" alt="" onerror="this.style.opacity='0.3'">
                                                    @endif
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="truncate font-medium text-ink">{{ $item->product_name }}</div>
                                                    <div class="text-[11px] text-muted">{{ $item->qty }}× {{ Money::format($item->price) }}</div>
                                                </div>
                                            </div>
                                        @empty
                                            <span class="text-xs text-muted">No items</span>
                                        @endforelse
                                        @if ($order->items->count() > 2)
                                            <div class="text-[10px] font-semibold text-muted">+{{ $order->items->count() - 2 }} more</div>
                                        @endif
                                    </div>
                                </td>
                                <td
                                    data-ratio-cell
                                    class="cell-tip"
                                    @if ($order->fraud_score === null && blank($order->fraud_report) && filled($order->customer_phone))
                                        data-ratio-pending
                                        data-ratio-url="{{ route('admin.orders.fraud-check', $order) }}"
                                    @endif
                                >
                                    <div class="cell-clamp">
                                        @include('admin.partials.order-success-rate', [
                                            'score' => $order->fraud_score,
                                            'fraudReport' => $order->fraud_report,
                                        ])
                                    </div>
                                </td>
                                <td>
                                    <div class="cell-tip" data-tip="{{ $amountTip }}">
                                        <div class="font-bold text-ink tabular-nums cell-clamp">{{ Money::format($order->total) }}</div>
                                        <div class="mt-1 flex flex-wrap items-center justify-center gap-1">
                                            @if ($order->payment_status === 'paid')
                                                <span class="admin-badge admin-badge-success">Paid</span>
                                            @else
                                                <span class="admin-badge admin-badge-warning">Unpaid</span>
                                            @endif
                                            <span class="text-[10px] font-semibold uppercase text-muted">
                                                {{ $order->payment_method === 'sslcommerz' ? 'SSL' : strtoupper($order->payment_method ?? 'COD') }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="cell-tip" data-tip="{{ $trackingTip }}">
                                        @if ($order->tracking_number)
                                            <div class="text-[11px] font-semibold text-ink cell-clamp">
                                                {{ $order->courierApi?->displayName() ?: strtoupper((string) ($order->courier_provider ?: 'Courier')) }}
                                            </div>
                                            <div class="mt-0.5 text-[11px] text-muted cell-clamp">
                                                {{ $order->tracking_number }}
                                            </div>
                                        @else
                                            <span class="admin-badge admin-badge-neutral">Not booked</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="cell-tip" data-tip="{{ $statusTip }}">
                                        <span class="admin-badge-pill {{ $badge }}">
                                            {{ $order->statusLabel() }}
                                        </span>
                                        @if ($order->status === 'cancelled' && $order->cancel_reason)
                                            <div class="mt-0.5 cell-clamp text-[10px] font-medium text-red-600">
                                                {{ $order->cancel_reason }}
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="orders-col-sticky-right">
                                    <div class="order-actions" role="group" aria-label="Order actions">
                                        <a href="{{ route('admin.orders.show', $order) }}" class="order-action is-primary" title="Open order">
                                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                            <span>View</span>
                                        </a>
                                        <a href="{{ route('admin.orders.print', $order) }}" target="_blank" class="order-action is-print" title="Print invoice / sticker">
                                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18.75 6.75h.008v.008h-.008V6.75zm1.5 1.5h.008v.008h-.008V8.25z" /></svg>
                                            <span>Print</span>
                                        </a>
                                        <div class="order-more">
                                            <button type="button" class="order-action order-more-toggle" aria-label="More actions" tabindex="0">
                                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM12.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM18.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" /></svg>
                                            </button>
                                            <div class="order-more-tip" role="tooltip">
                                                <form method="POST" action="{{ route('admin.orders.fraud-check', $order) }}">
                                                    @csrf
                                                    <button type="submit" class="order-more-item">
                                                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
                                                        Ratio
                                                    </button>
                                                </form>
                                                @if ($order->isDeletable(auth('admin')->user()))
                                                    <form method="POST" action="{{ route('admin.orders.destroy', $order) }}" onsubmit="return confirm('Delete this order?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="order-more-item is-danger">
                                                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                                            Delete
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="admin-empty-cell">
                                    <div class="admin-empty">
                                        <p class="admin-empty-title">No orders found</p>
                                        <p class="admin-empty-hint">Try clearing filters, or create a manual order to get started.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="orders-footer">
                <div class="text-xs text-muted">
                    Showing <span class="font-semibold text-ink">{{ $orders->firstItem() ?? 0 }}</span>–<span class="font-semibold text-ink">{{ $orders->lastItem() ?? 0 }}</span>
                    of <span class="font-semibold text-ink">{{ $orders->total() }}</span>
                    @if ($orders->total() > 0)
                        · {{ $orders->perPage() }}/page
                    @endif
                </div>
                <div>{{ $orders->links() }}</div>
            </div>
        </div>

        {{-- Bulk Cancel Reason Modal --}}
        <div id="bulk-cancel-modal" class="fixed inset-0 z-50 flex hidden items-center justify-center bg-ink/50 p-4">
            <div class="w-full max-w-md rounded-lg bg-white p-5 shadow-xl">
                <div class="mb-3 flex items-center justify-between border-b border-ink/10 pb-2">
                    <h3 class="font-semibold text-ink">Select Cancellation Reason</h3>
                    <button type="button" onclick="closeBulkCancelModal()" class="text-lg font-bold leading-none text-muted hover:text-ink">&times;</button>
                </div>
                <p class="mb-3 text-xs text-muted">
                    A cancellation reason is required to cancel selected orders in All Orders.
                </p>
                <div class="space-y-3">
                    <div>
                        <label class="admin-label" for="modal-bulk-cancel-reason">Reason <span class="text-red-500">*</span></label>
                        <select id="modal-bulk-cancel-reason" class="field w-full text-xs">
                            <option value="">Select reason…</option>
                            @foreach (\App\Models\Order::CANCEL_REASONS as $reasonKey => $reasonLabel)
                                <option value="{{ $reasonKey }}">{{ $reasonLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="modal-bulk-custom-reason-box" class="hidden">
                        <label class="admin-label" for="modal-bulk-custom-reason">Custom Reason Note</label>
                        <input type="text" id="modal-bulk-custom-reason" class="field w-full text-xs" placeholder="Specify custom reason…">
                    </div>
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" onclick="closeBulkCancelModal()" class="admin-btn bg-mist text-ink hover:bg-ink/10">Back</button>
                    <button type="button" id="modal-bulk-cancel-confirm-btn" class="admin-btn bg-red-600 text-white hover:bg-red-700">Cancel Orders</button>
                </div>
            </div>
        </div>

        <script>
            (() => {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const queue = Array.from(document.querySelectorAll('[data-ratio-pending]')).slice(0, 50);
                let active = 0;

                const paint = (cell, html, fallback) => {
                    cell.innerHTML = html || `<span class="text-xs text-muted">${fallback}</span>`;
                    cell.removeAttribute('data-ratio-pending');
                };

                const pump = () => {
                    while (active < 2 && queue.length > 0) {
                        const cell = queue.shift();
                        if (!cell?.dataset.ratioUrl) {
                            continue;
                        }
                        active += 1;
                        cell.innerHTML = '<span class="text-xs font-medium text-teal-700">Checking...</span>';
                        fetch(cell.dataset.ratioUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                            },
                        })
                            .then(async (response) => ({
                                ok: response.ok,
                                data: await response.json().catch(() => null),
                            }))
                            .then(({ ok, data }) => {
                                paint(cell, ok ? data?.html : null, 'Not checked');
                            })
                            .catch(() => paint(cell, null, 'Not checked'))
                            .finally(() => {
                                active -= 1;
                                pump();
                            });
                    }
                };

                pump();

                document.addEventListener('admin:form-saved', (event) => {
                    const form = event.detail?.form;
                    if (!form?.action?.includes('/fraud-check')) {
                        return;
                    }
                    const cell = form.closest('tr')?.querySelector('[data-ratio-cell]');
                    if (cell && event.detail.data?.html) {
                        paint(cell, event.detail.data.html, 'Not checked');
                    }
                });
            })();

            // Floating cell tooltip (equal columns + overflow text)
            (() => {
                let floatTip = document.querySelector('.order-update-tip-float');
                if (!floatTip) {
                    floatTip = document.createElement('div');
                    floatTip.className = 'order-update-tip-float';
                    document.body.appendChild(floatTip);
                }

                const tipText = (el) => (el.getAttribute('data-tip') || '').trim();

                const placeTip = (anchor) => {
                    const text = tipText(anchor);
                    if (!text) return;
                    floatTip.textContent = text;
                    floatTip.style.display = 'block';
                    const rect = anchor.getBoundingClientRect();
                    const tipRect = floatTip.getBoundingClientRect();
                    let left = rect.left + (rect.width / 2) - (tipRect.width / 2);
                    let top = rect.bottom + 6;
                    if (top + tipRect.height > window.innerHeight - 8) {
                        top = Math.max(8, rect.top - tipRect.height - 6);
                    }
                    left = Math.min(Math.max(8, left), window.innerWidth - tipRect.width - 8);
                    floatTip.style.left = `${left}px`;
                    floatTip.style.top = `${top}px`;
                };

                document.querySelectorAll('.cell-tip').forEach((el) => {
                    el.addEventListener('mouseenter', () => placeTip(el));
                    el.addEventListener('focus', () => placeTip(el));
                    el.addEventListener('mouseleave', () => { floatTip.style.display = 'none'; });
                    el.addEventListener('blur', () => { floatTip.style.display = 'none'; });
                });

                window.addEventListener('scroll', () => { floatTip.style.display = 'none'; }, true);
            })();

            const ordersIndexRoot = document.querySelector('.orders-index');
            const orderCheckboxes = () => document.querySelectorAll('.order-checkbox');
            const selectAllOrders = document.getElementById('select-all-orders');
            const exportOrdersLabel = document.getElementById('export-orders-label');
            const selectedCountChip = document.getElementById('orders-selected-count');
            const filterMoreBtn = document.getElementById('orders-filter-more-btn');
            const moreFilters = document.getElementById('orders-more-filters');

            filterMoreBtn?.addEventListener('click', () => {
                const open = moreFilters?.classList.toggle('is-open');
                filterMoreBtn.classList.toggle('is-open', !!open);
                filterMoreBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
            });

            const updateExportBtnState = () => {
                const checked = document.querySelectorAll('.order-checkbox:checked');
                if (exportOrdersLabel) {
                    exportOrdersLabel.textContent = checked.length > 0
                        ? `Export Selected (${checked.length})`
                        : 'Export';
                }
                if (selectedCountChip) {
                    if (checked.length > 0) {
                        selectedCountChip.textContent = `${checked.length} selected`;
                        selectedCountChip.classList.remove('hidden');
                    } else {
                        selectedCountChip.classList.add('hidden');
                    }
                }
                ordersIndexRoot?.classList.toggle('has-selection', checked.length > 0);
            };

            selectAllOrders?.addEventListener('change', function (e) {
                orderCheckboxes().forEach((el) => { el.checked = e.target.checked; });
                updateExportBtnState();
            });

            orderCheckboxes().forEach((el) => {
                el.addEventListener('change', updateExportBtnState);
            });

            const runOrdersExport = () => {
                const checked = Array.from(document.querySelectorAll('.order-checkbox:checked')).map(cb => cb.value);
                const currentParams = new URLSearchParams(window.location.search);

                if (checked.length > 0) {
                    currentParams.set('order_ids', checked.join(','));
                } else {
                    currentParams.delete('order_ids');
                }

                window.location.href = '{{ route("admin.orders.export") }}?' + currentParams.toString();
            };

            document.getElementById('export-orders-btn')?.addEventListener('click', runOrdersExport);
            document.querySelectorAll('.export-orders-btn').forEach((btn) => {
                btn.addEventListener('click', runOrdersExport);
            });
            updateExportBtnState();

            document.querySelectorAll('[data-copy]').forEach((button) => {
                button.addEventListener('click', async () => {
                    const value = button.getAttribute('data-copy') || '';
                    try {
                        await navigator.clipboard.writeText(value);
                        button.title = 'Copied';
                        setTimeout(() => { button.title = 'Copy phone'; }, 1200);
                    } catch (error) {
                        // ignore clipboard failures
                    }
                });
            });

            const bulkForm = document.getElementById('orders-bulk-form');
            const bulkStatusSelect = document.getElementById('bulk-status-select');
            const bulkCancelReasonInput = document.getElementById('bulk-cancel-reason-input');
            const bulkCancelModal = document.getElementById('bulk-cancel-modal');
            const modalReasonSelect = document.getElementById('modal-bulk-cancel-reason');
            const modalCustomReasonBox = document.getElementById('modal-bulk-custom-reason-box');
            const modalCustomReasonInput = document.getElementById('modal-bulk-custom-reason');
            const modalConfirmBtn = document.getElementById('modal-bulk-cancel-confirm-btn');

            window.closeBulkCancelModal = function () {
                bulkCancelModal?.classList.add('hidden');
            };

            modalReasonSelect?.addEventListener('change', function () {
                if (modalReasonSelect.value === 'Other') {
                    modalCustomReasonBox?.classList.remove('hidden');
                } else {
                    modalCustomReasonBox?.classList.add('hidden');
                }
            });

            modalConfirmBtn?.addEventListener('click', function () {
                let reason = modalReasonSelect.value;
                if (!reason) {
                    alert('Please select a cancellation reason.');
                    return;
                }
                if (reason === 'Other' && modalCustomReasonInput && modalCustomReasonInput.value.trim() !== '') {
                    reason = modalCustomReasonInput.value.trim();
                }
                if (bulkCancelReasonInput) {
                    bulkCancelReasonInput.value = reason;
                }
                bulkCancelModal?.classList.add('hidden');
                bulkForm?.submit();
            });

            bulkForm?.addEventListener('submit', function (e) {
                const submitter = e.submitter;
                if (submitter && (submitter.value === 'print' || submitter.value === 'print_invoice' || submitter.value === 'print_sticker')) {
                    const checkedCount = document.querySelectorAll('.order-checkbox:checked').length;
                    if (checkedCount === 0) {
                        alert('Please select at least one order to print.');
                        e.preventDefault();
                        return;
                    }
                }
                if (submitter && submitter.name === 'action' && submitter.value === 'status') {
                    const isOrdersSection = '{{ $currentSection ?? "orders" }}' === 'orders';
                    if (isOrdersSection && bulkStatusSelect && bulkStatusSelect.value === 'cancelled' && (!bulkCancelReasonInput || !bulkCancelReasonInput.value)) {
                        const checkedCount = document.querySelectorAll('.order-checkbox:checked').length;
                        if (checkedCount === 0) {
                            alert('Please select at least one order.');
                            e.preventDefault();
                            return;
                        }
                        e.preventDefault();
                        bulkCancelModal?.classList.remove('hidden');
                    }
                }
            });

            // Excel-like resizable columns (persisted in localStorage)
            (() => {
                const table = document.getElementById('orders-resizable-table');
                if (!table) return;

                const storageKey = 'admin.orders.columnWidths.v5';
                const equalWidth = 132;
                const minWidths = {
                    select: 44,
                    order: equalWidth,
                    customer: equalWidth,
                    updates: equalWidth,
                    items: equalWidth,
                    ratio: equalWidth,
                    amount: equalWidth,
                    tracking: equalWidth,
                    status: equalWidth,
                    actions: equalWidth,
                };
                const cols = Array.from(table.querySelectorAll('colgroup col[data-col]'));
                const headers = Array.from(table.querySelectorAll('thead th[data-col]'));

                const readStore = () => {
                    try {
                        return JSON.parse(localStorage.getItem(storageKey) || '{}') || {};
                    } catch (_) {
                        return {};
                    }
                };

                const writeStore = (map) => {
                    localStorage.setItem(storageKey, JSON.stringify(map));
                };

                const applyWidth = (key, px) => {
                    const width = Math.max(minWidths[key] || 60, Math.round(px));
                    const col = cols.find((c) => c.dataset.col === key);
                    const th = headers.find((h) => h.dataset.col === key);
                    if (col) col.style.width = `${width}px`;
                    if (th) th.style.width = `${width}px`;
                    return width;
                };

                const snapshotNatural = () => {
                    const map = {};
                    headers.forEach((th) => {
                        const key = th.dataset.col;
                        if (!key) return;
                        map[key] = Math.max(minWidths[key] || 60, Math.round(th.getBoundingClientRect().width));
                    });
                    return map;
                };

                table.classList.add('is-col-resizable');
                table.style.tableLayout = 'fixed';
                table.style.width = 'max-content';
                table.style.minWidth = '100%';

                // Force equal content-column sizes (ignore stale unequal saves)
                let widths = {
                    select: 44,
                    order: equalWidth,
                    customer: equalWidth,
                    updates: equalWidth,
                    items: equalWidth,
                    ratio: equalWidth,
                    amount: equalWidth,
                    tracking: equalWidth,
                    status: equalWidth,
                    actions: equalWidth,
                };
                Object.entries(widths).forEach(([key, px]) => applyWidth(key, px));
                writeStore(widths);

                headers.forEach((th, index) => {
                    if (index >= headers.length - 1) return;
                    const key = th.dataset.col;
                    if (!key) return;

                    const handle = document.createElement('span');
                    handle.className = 'col-resize-handle';
                    handle.title = 'Drag to resize · double-click to reset';
                    th.appendChild(handle);

                    handle.addEventListener('pointerdown', (event) => {
                        event.preventDefault();
                        event.stopPropagation();

                        const startX = event.clientX;
                        const startWidth = th.getBoundingClientRect().width;
                        th.classList.add('is-resizing');
                        document.body.classList.add('is-col-resizing');
                        handle.setPointerCapture(event.pointerId);

                        const onMove = (moveEvent) => {
                            const next = applyWidth(key, startWidth + (moveEvent.clientX - startX));
                            widths[key] = next;
                        };

                        const onUp = () => {
                            th.classList.remove('is-resizing');
                            document.body.classList.remove('is-col-resizing');
                            handle.releasePointerCapture(event.pointerId);
                            handle.removeEventListener('pointermove', onMove);
                            handle.removeEventListener('pointerup', onUp);
                            writeStore(widths);
                        };

                        handle.addEventListener('pointermove', onMove);
                        handle.addEventListener('pointerup', onUp);
                    });

                    handle.addEventListener('dblclick', (event) => {
                        event.preventDefault();
                        event.stopPropagation();
                        th.style.width = '';
                        const col = cols.find((c) => c.dataset.col === key);
                        if (col) col.style.width = '';
                        delete widths[key];
                        writeStore(widths);
                        requestAnimationFrame(() => {
                            widths[key] = applyWidth(key, th.getBoundingClientRect().width);
                            writeStore(widths);
                        });
                    });
                });

                document.getElementById('orders-reset-col-widths')?.addEventListener('click', () => {
                    widths = {
                        select: 44,
                        order: equalWidth,
                        customer: equalWidth,
                        updates: equalWidth,
                        items: equalWidth,
                        ratio: equalWidth,
                        amount: equalWidth,
                        tracking: equalWidth,
                        status: equalWidth,
                        actions: equalWidth,
                    };
                    Object.entries(widths).forEach(([key, px]) => applyWidth(key, px));
                    writeStore(widths);
                });
            })();
        </script>
    @endif

    {{-- Order Import Modal --}}
    <div id="order-import-modal" class="fixed inset-0 z-50 flex hidden items-center justify-center bg-black/50 p-4 backdrop-blur-xs">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl ring-1 ring-ink/10">
            <div class="mb-4 flex items-center justify-between border-b border-ink/10 pb-3">
                <div class="flex items-center gap-2">
                    <div class="rounded-lg bg-sage/10 p-2 text-sage">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-ink">Import Orders</h3>
                        <p class="text-xs text-muted">Batch import customer orders via CSV</p>
                    </div>
                </div>
                <button type="button" onclick="document.getElementById('order-import-modal').classList.add('hidden')" class="rounded-md p-1 text-muted hover:bg-mist hover:text-ink">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.orders.import') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-ink mb-1">Select CSV File</label>
                    <input type="file" name="csv_file" accept=".csv,text/csv" required class="block w-full text-xs text-ink file:mr-3 file:rounded-md file:border-0 file:bg-sage/10 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-sage hover:file:bg-sage/20">
                    <p class="mt-1 text-[11px] text-muted">Required columns: Customer Name, Customer Phone, Customer Address. Optional: Product SKU / Name, Quantity, Unit Price, Shipping Charge, Discount, Payment Method, Notes.</p>
                </div>

                <div class="rounded-lg bg-mist/60 p-3 text-xs flex items-center justify-between">
                    <div>
                        <span class="font-medium text-ink">Need the file format?</span>
                        <p class="text-[11px] text-muted">Download sample orders template</p>
                    </div>
                    <a href="{{ route('admin.orders.sample-template') }}" class="admin-btn-secondary !px-2.5 !py-1 !text-xs font-medium inline-flex items-center gap-1">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        Sample CSV
                    </a>
                </div>

                <div class="mt-5 flex items-center justify-end gap-2 border-t border-ink/10 pt-3">
                    <button type="button" onclick="document.getElementById('order-import-modal').classList.add('hidden')" class="admin-btn-secondary">Cancel</button>
                    <button type="submit" class="admin-btn-primary">Upload & Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
