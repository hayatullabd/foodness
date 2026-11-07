<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderStatusController extends Controller
{
    public function index(Request $request): View
    {
        $currentSection = $request->string('section')->toString();

        $statuses = OrderStatus::query()
            ->when(in_array($currentSection, OrderStatus::SECTIONS, true), fn ($query) => $query->where('section', $currentSection))
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.$request->string('q')->toString().'%';
                $query->where(function ($inner) use ($term): void {
                    $inner->where('name', 'like', $term)
                        ->orWhere('slug', 'like', $term);
                });
            })
            ->ordered()
            ->paginate(25)
            ->withQueryString();

        return view('admin.order-statuses.index', [
            'statuses' => $statuses,
            'currentSection' => $currentSection,
            'allCount' => OrderStatus::query()->count(),
            'ordersCount' => OrderStatus::query()->where('section', OrderStatus::SECTION_ORDERS)->count(),
            'courierCount' => OrderStatus::query()->where('section', OrderStatus::SECTION_COURIER)->count(),
        ]);
    }

    public function create(Request $request): View
    {
        $defaultSection = $request->string('section', OrderStatus::SECTION_ORDERS)->toString();
        if (! in_array($defaultSection, OrderStatus::SECTIONS, true)) {
            $defaultSection = OrderStatus::SECTION_ORDERS;
        }

        return view('admin.order-statuses.create', [
            'defaultSection' => $defaultSection,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $section = $request->input('section', OrderStatus::SECTION_ORDERS);
        if (! in_array($section, OrderStatus::SECTIONS, true)) {
            $section = OrderStatus::SECTION_ORDERS;
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'section' => ['nullable', 'string', Rule::in(OrderStatus::SECTIONS)],
            'slug' => [
                'nullable',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique('order_statuses', 'slug')->where('section', $section),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        OrderStatus::query()->create([
            'name' => $validated['name'],
            'section' => $section,
            'slug' => $validated['slug'] ?? Str::slug($validated['name'], '_'),
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('admin.order-statuses.index')
            ->with('success', 'Status created.');
    }

    public function edit(OrderStatus $orderStatus): View
    {
        return view('admin.order-statuses.edit', [
            'orderStatus' => $orderStatus,
        ]);
    }

    public function update(Request $request, OrderStatus $orderStatus): RedirectResponse
    {
        $section = $request->input('section', $orderStatus->section ?? OrderStatus::SECTION_ORDERS);
        if (! in_array($section, OrderStatus::SECTIONS, true)) {
            $section = OrderStatus::SECTION_ORDERS;
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'section' => ['nullable', 'string', Rule::in(OrderStatus::SECTIONS)],
            'slug' => [
                'required',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique('order_statuses', 'slug')
                    ->where('section', $section)
                    ->ignore($orderStatus->id),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $orderStatus->update([
            'name' => $validated['name'],
            'section' => $section,
            'slug' => $validated['slug'],
            'sort_order' => $validated['sort_order'] ?? $orderStatus->sort_order,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.order-statuses.index')
            ->with('success', 'Status updated.');
    }

    public function toggle(OrderStatus $orderStatus): RedirectResponse
    {
        $orderStatus->update([
            'is_active' => ! $orderStatus->is_active,
        ]);

        return back()->with('success', $orderStatus->is_active ? 'Status activated.' : 'Status deactivated.');
    }
}
