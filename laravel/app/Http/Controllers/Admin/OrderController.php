<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOrderRequest;
use App\Http\Requests\Admin\UpdateOrderRequest;
use App\Jobs\SendOrdersToCourier;
use App\Models\Admin;
use App\Models\Color;
use App\Models\CourierApi;
use App\Models\FraudSetting;
use App\Models\IncompleteOrder;
use App\Models\IpBlock;
use App\Models\Order;
use App\Models\OrderActivityLog;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\ShippingCharge;
use App\Models\Size;
use App\Services\BdCourierFraudService;
use App\Services\BulkSmsBdService;
use App\Services\Courier\CourierShipmentService;
use App\Services\GeminiService;
use App\Services\StoreEmailService;
use App\Support\CourierRatioCards;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class OrderController extends Controller
{
    public function index(Request $request, CourierShipmentService $shipments): View
    {
        $activeTab = $request->string('tab', 'list')->toString();
        $isIncompleteTab = $activeTab === 'incomplete';

        $currentSection = $request->string('section')->toString();
        $courierStatusSlugs = ['rts', 'shipped', 'pending_return', 'partial', 'lost'];
        if (! in_array($currentSection, ['orders', 'courier'], true)) {
            if ($request->filled('courier') || in_array($request->string('status')->toString(), $courierStatusSlugs, true)) {
                $currentSection = 'courier';
            } else {
                $currentSection = 'orders';
            }
        }

        $statuses = OrderStatus::query()
            ->forSection($currentSection)
            ->active()
            ->ordered()
            ->get();

        // Default list view always opens on Pending. Use status=all for the All tab.
        if (! $isIncompleteTab && ! $request->filled('status')) {
            $defaultStatus = $statuses->firstWhere('slug', 'pending')?->slug
                ?? $statuses->first(fn (OrderStatus $status): bool => $status->slug !== 'incomplete')?->slug;

            if ($defaultStatus) {
                $request->merge(['status' => $defaultStatus]);
            }
        }

        $isAllStatusTab = ! $isIncompleteTab && $request->string('status')->toString() === 'all';

        $currentCourier = $isIncompleteTab ? '' : $request->string('courier')->toString();

        $courierStatusIds = OrderStatus::query()->courierSection()->pluck('id')->all();

        $isCourierCondition = function ($query) use ($courierStatusIds): void {
            $query->where(function ($q) use ($courierStatusIds): void {
                if (! empty($courierStatusIds)) {
                    $q->whereIn('order_status_id', $courierStatusIds);
                }
                $q->orWhere(function ($t): void {
                    $t->whereNotNull('tracking_number')->where('tracking_number', '!=', '');
                });
            });
        };

        $isNotCourierCondition = function ($query) use ($courierStatusIds): void {
            $query->where(function ($q) use ($courierStatusIds): void {
                $q->whereNull('order_status_id');
                if (! empty($courierStatusIds)) {
                    $q->orWhereNotIn('order_status_id', $courierStatusIds);
                }
            })->where(function ($t): void {
                $t->whereNull('tracking_number')->orWhere('tracking_number', '');
            });
        };

        $courierRawCounts = Order::query()
            ->where($isCourierCondition)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $courierTotalCount = (int) $courierRawCounts->sum();

        $allOrdersRawCounts = Order::query()
            ->where($isNotCourierCondition)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $allOrdersTotalCount = (int) $allOrdersRawCounts->sum();

        if ($currentSection === 'courier') {
            $sectionTabs = [];
            foreach ($statuses as $status) {
                $count = (int) ($courierRawCounts[$status->slug] ?? 0);
                if ($status->slug === 'processing') {
                    $count += (int) ($courierRawCounts['in_courier'] ?? 0);
                } elseif ($status->slug === 'shipped') {
                    $count += (int) ($courierRawCounts['on_the_way'] ?? 0);
                } elseif ($status->slug === 'delivered') {
                    $count += (int) ($courierRawCounts['completed'] ?? 0);
                } elseif ($status->slug === 'returned') {
                    $count += (int) ($courierRawCounts['courier_returned'] ?? 0);
                }

                $sectionTabs[] = [
                    'label' => $status->name,
                    'slug' => $status->slug,
                    'count' => $count,
                ];
            }

            $sectionTabs[] = [
                'label' => 'All Courier',
                'slug' => 'all',
                'count' => $courierTotalCount,
            ];
        } else {
            $sectionTabs = [];
            foreach ($statuses as $status) {
                if ($status->slug === 'incomplete') {
                    continue;
                }

                $sectionTabs[] = [
                    'label' => $status->name,
                    'slug' => $status->slug,
                    'count' => (int) ($allOrdersRawCounts[$status->slug] ?? 0),
                ];
            }

            $sectionTabs[] = [
                'label' => 'All',
                'slug' => 'all',
                'count' => $allOrdersTotalCount,
            ];
        }

        $incompleteOrders = null;
        $ourRecordsByPhone = [];
        $orders = null;
        $courierProviders = [];
        $courierSections = [];
        $courierBookedTotal = 0;
        $notBookedCount = 0;

        $allowedPerPage = [20, 50, 100, 200, 500, 1000];
        $perPageInput = (int) $request->input('per_page', 50);
        $perPage = in_array($perPageInput, $allowedPerPage, true) ? $perPageInput : 50;

        if ($isIncompleteTab) {
            $incompleteOrders = IncompleteOrder::query()
                ->latest()
                ->paginate($perPage)
                ->withQueryString();

            $ourRecordsByPhone = CourierRatioCards::ourRecordsForPhones(
                $incompleteOrders->pluck('customer_phone')->all()
            );
        } else {
            $ordersQuery = Order::query()
                ->with([
                    'orderStatus:id,name,slug,section',
                    'courierApi:id,name,provider',
                    'items:id,order_id,product_id,product_name,qty,price',
                    'items.product:id',
                    'items.product.images:id,product_id,path,is_primary,sort_order',
                    'activityLogs:id,order_id,causer_name,description,created_at',
                ]);

            if ($currentSection === 'courier') {
                $ordersQuery->where($isCourierCondition);

                if ($request->filled('status') && ! $isAllStatusTab) {
                    $statusParam = $request->string('status')->toString();
                    if ($statusParam === 'processing') {
                        $ordersQuery->whereIn('status', ['processing', 'in_courier']);
                    } elseif ($statusParam === 'shipped') {
                        $ordersQuery->whereIn('status', ['shipped', 'on_the_way']);
                    } elseif ($statusParam === 'delivered') {
                        $ordersQuery->whereIn('status', ['delivered', 'completed']);
                    } elseif ($statusParam === 'returned') {
                        $ordersQuery->whereIn('status', ['returned', 'courier_returned']);
                    } else {
                        $ordersQuery->where('status', $statusParam);
                    }
                }

                if ($currentCourier !== '') {
                    $ordersQuery->where('courier_api_id', (int) $currentCourier);
                }
            } else {
                $ordersQuery->where($isNotCourierCondition);

                if ($request->filled('status') && ! $isAllStatusTab) {
                    $ordersQuery->where('status', $request->string('status')->toString());
                }
            }

            $orders = $ordersQuery
                ->when($request->filled('q'), function ($query) use ($request): void {
                    $term = '%'.$request->string('q')->toString().'%';
                    $query->where(function ($inner) use ($term): void {
                        $inner->where('invoice_no', 'like', $term)
                            ->orWhere('customer_name', 'like', $term)
                            ->orWhere('customer_phone', 'like', $term)
                            ->orWhere('tracking_number', 'like', $term);
                    });
                })
                ->when($request->filled('tracking'), function ($query) use ($request): void {
                    $tracking = $request->string('tracking')->toString();

                    if ($tracking === 'with') {
                        $query->whereNotNull('tracking_number')->where('tracking_number', '!=', '');
                    }

                    if ($tracking === 'without') {
                        $query->where(function ($inner): void {
                            $inner->whereNull('tracking_number')->orWhere('tracking_number', '');
                        });
                    }
                })
                ->when($request->filled('platform'), function ($query) use ($request): void {
                    $platform = strtolower($request->string('platform')->toString());
                    $query->where('source_platform', $platform);
                })
                ->when($activeTab === 'fraud', fn ($query) => $query->whereNotNull('fraud_score'))
                ->latest()
                ->paginate($perPage)
                ->withQueryString();

            $courierProviders = $shipments->enabledProviders();
            $courierBookedCounts = Order::query()
                ->whereNotNull('courier_api_id')
                ->whereNotNull('tracking_number')
                ->where('tracking_number', '!=', '')
                ->selectRaw('courier_api_id, COUNT(*) as aggregate')
                ->groupBy('courier_api_id')
                ->pluck('aggregate', 'courier_api_id');

            $courierSections = collect($courierProviders)->map(function (array $courier) use ($courierBookedCounts): array {
                return [
                    'id' => (int) $courier['id'],
                    'label' => (string) $courier['label'],
                    'provider' => (string) ($courier['provider'] ?? ''),
                    'booked_count' => (int) ($courierBookedCounts[$courier['id']] ?? 0),
                ];
            })->values()->all();

            $courierBookedTotal = (int) $courierBookedCounts->sum();
        }

        return view('admin.orders.index', [
            'orders' => $orders,
            'incompleteOrders' => $incompleteOrders,
            'ourRecordsByPhone' => $ourRecordsByPhone,
            'statuses' => $statuses,
            'currentSection' => $currentSection,
            'sectionTabs' => $sectionTabs,
            'statusCounts' => $currentSection === 'courier' ? $courierRawCounts : $allOrdersRawCounts,
            'allOrdersTotalCount' => $allOrdersTotalCount,
            'courierTotalCount' => $courierTotalCount,
            'totalOrderCount' => $currentSection === 'courier' ? $courierTotalCount : $allOrdersTotalCount,
            'activeTab' => $activeTab,
            'currentStatus' => $isIncompleteTab ? '' : $request->string('status')->toString(),
            'currentPlatform' => $request->string('platform')->toString(),
            'currentCourier' => $currentCourier,
            'courierProviders' => $courierProviders,
            'courierSections' => $courierSections,
            'courierBookedTotal' => $courierBookedTotal,
            'perPage' => $perPage,
        ]);
    }

    public function show(Order $order, BdCourierFraudService $fraudService, CourierShipmentService $shipments): View
    {
        $this->autoCheckMissingFraud($order, $fraudService);
        $order->refresh();
        $order->load(['items.product.images', 'user', 'orderStatus', 'courierApi', 'activityLogs']);

        $orderSection = $order->isCourier() ? OrderStatus::SECTION_COURIER : OrderStatus::SECTION_ORDERS;

        return view('admin.orders.show', [
            'order' => $order,
            'orderSection' => $orderSection,
            'statuses' => OrderStatus::query()->forSection($orderSection)->active()->ordered()->get(),
            'ourRecord' => CourierRatioCards::ourRecordForPhone($order->customer_phone),
            'courierProviders' => $shipments->enabledProviders(),
            'colors' => Color::query()->active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'sizes' => Size::query()->active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'shippingCharges' => ShippingCharge::query()->active()->orderBy('amount')->get(['id', 'name', 'amount']),
            'paymentStatuses' => [
                'pending' => 'Pending',
                'paid' => 'Paid',
                'failed' => 'Failed',
                'refunded' => 'Refunded',
            ],
        ]);
    }

    public function create(CourierShipmentService $shipments): View
    {
        return view('admin.orders.create', [
            'colors' => Color::query()->active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'sizes' => Size::query()->active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'shippingCharges' => ShippingCharge::query()->active()->orderBy('amount')->get(['id', 'name', 'amount']),
            'statuses' => OrderStatus::query()->forSection(OrderStatus::SECTION_ORDERS)->active()->ordered()->get(),
            'courierProviders' => $shipments->enabledProviders(),
            'paymentStatuses' => [
                'pending' => 'Pending',
                'paid' => 'Paid',
                'failed' => 'Failed',
                'refunded' => 'Refunded',
            ],
            'sourcePlatforms' => [
                'phone_call' => 'Phone Call',
                'whatsapp' => 'WhatsApp',
                'facebook' => 'Facebook / Messenger',
                'message' => 'SMS / Message',
                'manual' => 'Direct / Walk-in / Other',
            ],
        ]);
    }

    public function checkPhoneRatio(Request $request, BdCourierFraudService $fraudService): JsonResponse
    {
        $phone = trim($request->string('phone')->toString());

        if ($phone === '') {
            return response()->json([
                'ok' => false,
                'message' => 'Please provide a phone number.',
            ], 422);
        }

        $fraudReport = [];
        $fraudScore = null;
        $fraudReports = [];

        if (FraudSetting::current()->isConfigured()) {
            $checkResult = $fraudService->check($phone);
            if ($checkResult['ok'] ?? false) {
                $fraudReport = $checkResult['report'] ?? [];
                $fraudScore = $checkResult['score'] ?? null;
                $fraudReports = is_array($fraudReport['reports'] ?? null) ? $fraudReport['reports'] : [];
            }
        }

        $ourRecord = CourierRatioCards::ourRecordForPhone($phone);

        $html = view('admin.partials.courier-ratio-cards', [
            'fraudReport' => $fraudReport,
            'ourRecord' => $ourRecord,
            'scrollKey' => 'manual-order-'.md5($phone),
            'showOurRecord' => true,
        ])->render();

        return response()->json([
            'ok' => true,
            'phone' => $phone,
            'score' => $fraudScore,
            'reports_count' => count($fraudReports),
            'html' => $html,
        ]);
    }

    public function aiParseOrder(Request $request, GeminiService $gemini): JsonResponse
    {
        $text = $request->input('text');
        $imageBase64 = null;
        $imageMime = null;

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $imageMime = $file->getMimeType();
            $imageBase64 = base64_encode(file_get_contents($file->getRealPath()));
        } elseif ($request->filled('image_base64')) {
            $raw = (string) $request->input('image_base64');
            if (preg_match('/^data:([^;]+);base64,(.+)$/', $raw, $matches)) {
                $imageMime = $matches[1];
                $imageBase64 = $matches[2];
            } else {
                $imageMime = 'image/png';
                $imageBase64 = $raw;
            }
        }

        if (blank($text) && blank($imageBase64)) {
            return response()->json([
                'ok' => false,
                'message' => 'Please provide customer message text or an image/screenshot.',
            ], 422);
        }

        $extracted = $gemini->parseOrderFromTextOrImage($text, $imageBase64, $imageMime);

        // Product catalog matching
        $enrichedItems = [];
        if (! empty($extracted['items'])) {
            foreach ($extracted['items'] as $item) {
                $queryName = trim((string) ($item['product_name'] ?? ''));
                $matchedProduct = null;

                if ($queryName !== '') {
                    $matchedProduct = Product::query()
                        ->with(['images', 'variants.color', 'variants.size'])
                        ->where(function ($q) use ($queryName) {
                            $q->where('name', 'like', "%{$queryName}%")
                                ->orWhere('sku', 'like', "%{$queryName}%");
                        })
                        ->active()
                        ->first();
                }

                if ($matchedProduct) {
                    $variantColors = $matchedProduct->variants
                        ->pluck('color.name')
                        ->filter()
                        ->unique()
                        ->values()
                        ->all();
                    $variantSizes = $matchedProduct->variants
                        ->map(fn ($variant) => $variant->size?->name ?? $variant->size_name)
                        ->filter()
                        ->unique()
                        ->values()
                        ->all();

                    $imagePath = $matchedProduct->primaryImagePath();

                    $enrichedItems[] = [
                        'product_id' => $matchedProduct->id,
                        'product_name' => $matchedProduct->name,
                        'sku' => $matchedProduct->sku,
                        'price' => (float) $matchedProduct->price,
                        'stock' => (int) $matchedProduct->stock,
                        'image_url' => $imagePath ? asset('storage/'.$imagePath) : null,
                        'color' => $item['color'] ?? '',
                        'size' => $item['size'] ?? '',
                        'qty' => max(1, (int) ($item['qty'] ?? 1)),
                        'discount' => 0,
                        'colors' => $variantColors,
                        'sizes' => $variantSizes,
                    ];
                } else {
                    $enrichedItems[] = [
                        'product_id' => null,
                        'product_name' => $queryName ?: 'Product',
                        'sku' => '',
                        'price' => 0,
                        'stock' => 0,
                        'image_url' => null,
                        'color' => $item['color'] ?? '',
                        'size' => $item['size'] ?? '',
                        'qty' => max(1, (int) ($item['qty'] ?? 1)),
                        'discount' => 0,
                    ];
                }
            }
        }

        $extracted['items'] = $enrichedItems;

        return response()->json([
            'ok' => true,
            'data' => $extracted,
        ]);
    }

    public function store(StoreOrderRequest $request, BdCourierFraudService $fraudService): RedirectResponse
    {
        $validated = $request->validated();
        $incomingItems = collect($validated['items'] ?? []);
        $shouldConfirm = ($validated['action'] ?? 'save') === 'confirm';

        /** @var Order $order */
        $order = DB::transaction(function () use ($validated, $incomingItems): Order {
            $subtotal = 0.0;
            $itemsData = [];
            $stockDeltas = [];

            foreach ($incomingItems as $row) {
                $qty = (int) $row['qty'];
                $price = round((float) $row['price'], 2);
                $lineDiscount = round((float) ($row['discount'] ?? 0), 2);
                $subtotal += $price * $qty;

                $itemsData[] = [
                    'product_id' => $row['product_id'] ?? null,
                    'product_name' => $row['product_name'],
                    'variant_id' => $row['variant_id'] ?? null,
                    'variation_name' => $row['variation_name'] ?? null,
                    'attributes' => $row['attributes'] ?? null,
                    'color' => $row['color'] ?? null,
                    'size' => $row['size'] ?? null,
                    'price' => number_format($price, 2, '.', ''),
                    'discount' => number_format($lineDiscount, 2, '.', ''),
                    'qty' => $qty,
                    'source' => 'admin',
                ];

                if (! empty($row['product_id'])) {
                    $productId = (int) $row['product_id'];
                    $stockDeltas[$productId] = ($stockDeltas[$productId] ?? 0) + $qty;
                }
            }

            foreach ($stockDeltas as $productId => $qtySold) {
                $product = Product::query()->whereKey($productId)->lockForUpdate()->first();
                if ($product) {
                    $product->update([
                        'stock' => max(0, (int) $product->stock - $qtySold),
                    ]);
                }
            }

            $orderStatusId = $validated['order_status_id'] ?? null;
            $orderStatus = null;
            if ($orderStatusId) {
                $orderStatus = OrderStatus::query()->find($orderStatusId);
            }
            if (! $orderStatus) {
                $orderStatus = OrderStatus::query()->where('section', OrderStatus::SECTION_ORDERS)->where('slug', 'pending')->first()
                    ?? OrderStatus::query()->where('slug', 'pending')->first();
            }

            $shipping = round((float) $validated['shipping_charge'], 2);
            $orderDiscount = round((float) ($validated['discount'] ?? 0), 2);
            $total = max(0, round($subtotal - $orderDiscount + $shipping, 2));

            $order = Order::query()->create([
                'invoice_no' => Order::generateInvoiceNumber(),
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'customer_address' => $validated['customer_address'],
                'delivery_division' => $validated['delivery_division'] ?? null,
                'delivery_district' => $validated['delivery_district'] ?? null,
                'delivery_upazila' => $validated['delivery_upazila'] ?? null,
                'payment_method' => $validated['payment_method'],
                'payment_status' => $validated['payment_status'] ?? 'pending',
                'shipping_charge' => number_format($shipping, 2, '.', ''),
                'discount' => number_format($orderDiscount, 2, '.', ''),
                'subtotal' => number_format($subtotal, 2, '.', ''),
                'total' => number_format($total, 2, '.', ''),
                'status' => $orderStatus?->slug ?? 'pending',
                'order_status_id' => $orderStatus?->id,
                'courier_api_id' => $validated['courier_api_id'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'admin_notes' => $validated['admin_notes'] ?? null,
                'source' => 'manual',
                'source_platform' => $validated['source_platform'] ?? 'manual',
                'ip_address' => request()->ip(),
            ]);

            foreach ($itemsData as $item) {
                $order->items()->create($item);
            }

            $sourceLabel = match ($order->source_platform) {
                'phone_call' => 'Phone Call',
                'whatsapp' => 'WhatsApp',
                'facebook' => 'Facebook / Messenger',
                'message' => 'SMS / Message',
                default => 'Manual Entry',
            };

            OrderActivityLog::record(
                $order,
                'order_created',
                "Order manually created via {$sourceLabel} with ".count($itemsData).' product(s).'
            );

            return $order;
        });

        if (FraudSetting::current()->isConfigured()) {
            $fraudService->applyToOrder($order, softFail: true);
        }

        if ($shouldConfirm) {
            $courierPending = OrderStatus::query()
                ->where('section', OrderStatus::SECTION_COURIER)
                ->where('slug', 'pending')
                ->first();

            if ($courierPending) {
                $order->applyStatus($courierPending);
            }

            return redirect()
                ->route('admin.orders.index')
                ->with('success', 'Order #'.$order->invoice_no.' created and confirmed to Courier Pending.');
        }

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Order #'.$order->invoice_no.' created successfully.');
    }

    public function edit(Order $order): View|RedirectResponse
    {
        if ($order->isCourierBooked()) {
            return redirect()
                ->route('admin.orders.show', $order)
                ->with('error', 'This order is already booked in courier (Tracking #'.$order->tracking_number.') and cannot be edited.');
        }

        $order->load(['items.product.images', 'orderStatus']);

        return view('admin.orders.edit', [
            'order' => $order,
            'colors' => Color::query()->active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'sizes' => Size::query()->active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'shippingCharges' => ShippingCharge::query()->active()->orderBy('amount')->get(['id', 'name', 'amount']),
            'paymentStatuses' => [
                'pending' => 'Pending',
                'paid' => 'Paid',
                'failed' => 'Failed',
                'refunded' => 'Refunded',
            ],
        ]);
    }

    public function update(UpdateOrderRequest $request, Order $order, CourierShipmentService $shipments): RedirectResponse
    {
        if ($order->isCourierBooked()) {
            return redirect()
                ->route('admin.orders.show', $order)
                ->with('error', 'This order is already booked in courier (Tracking #'.$order->tracking_number.') and cannot be edited.');
        }

        $validated = $request->validated();
        $incomingItems = collect($validated['items'] ?? []);
        $phoneChanged = trim((string) $order->customer_phone) !== trim((string) $validated['customer_phone']);
        $shouldConfirm = ($validated['action'] ?? 'save') === 'confirm';

        DB::transaction(function () use ($order, $validated, $incomingItems, $phoneChanged): void {
            $order->load('items');
            $existingItems = $order->items->keyBy('id');

            /** @var array<int, int> $stockDeltas product_id => qty delta (positive = more sold / less stock) */
            $stockDeltas = [];

            foreach ($existingItems as $existing) {
                if ($existing->product_id) {
                    $stockDeltas[(int) $existing->product_id] = ($stockDeltas[(int) $existing->product_id] ?? 0) - (int) $existing->qty;
                }
            }

            $keptIds = [];
            $subtotal = 0.0;

            foreach ($incomingItems as $row) {
                $qty = (int) $row['qty'];
                $price = round((float) $row['price'], 2);
                $lineDiscount = round((float) ($row['discount'] ?? 0), 2);
                $subtotal += $price * $qty;

                $payload = [
                    'product_id' => $row['product_id'] ?? null,
                    'product_name' => $row['product_name'],
                    'variant_id' => $row['variant_id'] ?? null,
                    'variation_name' => $row['variation_name'] ?? null,
                    'attributes' => $row['attributes'] ?? null,
                    'color' => $row['color'] ?? null,
                    'size' => $row['size'] ?? null,
                    'price' => number_format($price, 2, '.', ''),
                    'discount' => number_format($lineDiscount, 2, '.', ''),
                    'qty' => $qty,
                    'source' => 'admin',
                ];

                $itemId = isset($row['id']) ? (int) $row['id'] : null;

                if ($itemId && $existingItems->has($itemId)) {
                    $existingItems[$itemId]->update($payload);
                    $keptIds[] = $itemId;
                } else {
                    $created = $order->items()->create($payload);
                    $keptIds[] = $created->id;
                }

                if (! empty($row['product_id'])) {
                    $productId = (int) $row['product_id'];
                    $stockDeltas[$productId] = ($stockDeltas[$productId] ?? 0) + $qty;
                }
            }

            $order->items()
                ->whereNotIn('id', $keptIds)
                ->delete();

            foreach ($stockDeltas as $productId => $delta) {
                if ($delta === 0) {
                    continue;
                }

                $product = Product::query()->whereKey($productId)->lockForUpdate()->first();

                if (! $product) {
                    continue;
                }

                $product->update([
                    'stock' => max(0, (int) $product->stock - $delta),
                ]);
            }

            $shipping = round((float) $validated['shipping_charge'], 2);
            $discount = round((float) $validated['discount'], 2);
            $total = max(0, round($subtotal - $discount + $shipping, 2));

            $payload = [
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'customer_address' => $validated['customer_address'],
                'delivery_division' => $validated['delivery_division'] ?? null,
                'delivery_district' => $validated['delivery_district'] ?? null,
                'delivery_upazila' => $validated['delivery_upazila'] ?? null,
                'payment_method' => $validated['payment_method'],
                'payment_status' => $validated['payment_status'] ?? $order->payment_status ?? 'pending',
                'shipping_charge' => number_format($shipping, 2, '.', ''),
                'discount' => number_format($discount, 2, '.', ''),
                'subtotal' => number_format($subtotal, 2, '.', ''),
                'total' => number_format($total, 2, '.', ''),
                'courier_api_id' => $validated['courier_api_id'] ?? null,
                'notes' => $validated['notes'] ?? $order->notes,
            ];

            if ($phoneChanged) {
                $payload['fraud_score'] = null;
                $payload['fraud_report'] = null;
            }

            // Calculate diff for activity log
            $diff = [];
            $fieldLabels = [
                'customer_name' => 'Customer Name',
                'customer_phone' => 'Customer Phone',
                'customer_address' => 'Customer Address',
                'delivery_division' => 'Division',
                'delivery_district' => 'District',
                'delivery_upazila' => 'Upazila',
                'payment_method' => 'Payment Method',
                'payment_status' => 'Payment Status',
                'shipping_charge' => 'Delivery Charge',
                'discount' => 'Discount Amount',
                'subtotal' => 'Subtotal',
                'total' => 'Total Amount',
                'courier_api_id' => 'Courier Service',
                'notes' => 'Customer Note',
            ];

            $courierMap = CourierApi::query()->pluck('name', 'id')->all();

            $updatedDetails = [];

            foreach ($payload as $key => $newVal) {
                $oldVal = $order->{$key};
                if ((string) $oldVal !== (string) $newVal) {
                    $oldDisplay = $oldVal;
                    $newDisplay = $newVal;

                    if ($key === 'courier_api_id') {
                        $oldDisplay = filled($oldVal) ? ($courierMap[$oldVal] ?? "Courier #{$oldVal}") : null;
                        $newDisplay = filled($newVal) ? ($courierMap[$newVal] ?? "Courier #{$newVal}") : null;
                        $diff['courier_service'] = ['old' => $oldDisplay, 'new' => $newDisplay];
                    } else {
                        $diff[$key] = ['old' => $oldVal, 'new' => $newVal];
                    }

                    $label = $fieldLabels[$key] ?? ucfirst(str_replace('_', ' ', $key));
                    $updatedDetails[] = "{$label} (".($oldDisplay ?: 'None').' → '.($newDisplay ?: 'None').')';
                }
            }

            // Check item additions/removals
            if (count($incomingItems) !== $existingItems->count()) {
                $itemDelta = count($incomingItems) - $existingItems->count();
                $updatedDetails[] = $itemDelta > 0 ? "+{$itemDelta} product(s) added" : abs($itemDelta).' product(s) removed';
            }

            $order->update($payload);

            if (! empty($updatedDetails)) {
                $summaryDescription = 'Updated: '.implode(', ', array_slice($updatedDetails, 0, 3));
                if (count($updatedDetails) > 3) {
                    $summaryDescription .= ' and '.(count($updatedDetails) - 3).' more';
                }

                OrderActivityLog::record(
                    $order,
                    'order_updated',
                    $summaryDescription,
                    $diff
                );
            }
        });

        if ($phoneChanged && FraudSetting::current()->isConfigured()) {
            app(BdCourierFraudService::class)->applyToOrder($order->fresh(), softFail: true);
        }

        $order = $order->fresh();

        if ($shouldConfirm) {
            $courierPending = OrderStatus::query()
                ->where('section', OrderStatus::SECTION_COURIER)
                ->where('slug', 'pending')
                ->firstOrFail();

            $order->applyStatus($courierPending);

            return redirect()
                ->route('admin.orders.index')
                ->with('success', 'Order #'.$order->invoice_no.' confirmed and moved to Courier Pending.');
        }

        if (($validated['action'] ?? 'save') === 'send_to_courier') {
            $result = $shipments->sendOrder(
                $order,
                (int) $validated['courier_api_id'],
                force: false,
            );

            if (! $result['ok']) {
                return redirect()
                    ->route('admin.orders.show', $order)
                    ->with('error', 'Order saved, but courier booking failed: '.$result['message']);
            }

            return redirect()
                ->route('admin.orders.index', ['section' => 'courier', 'status' => 'processing'])
                ->with('success', 'Order sent to courier. Status will update automatically from courier webhooks/cron.');
        }

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', $phoneChanged
                ? 'Order updated. Courier ratio check refreshed for the new phone number.'
                : 'Order updated.');
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $orderSection = $order->isCourier() ? OrderStatus::SECTION_COURIER : OrderStatus::SECTION_ORDERS;

        $validated = $request->validate([
            'status' => [
                'required',
                'string',
                Rule::exists('order_statuses', 'slug')->where('section', $orderSection),
            ],
            'cancel_reason' => [
                Rule::requiredIf(fn () => $orderSection === OrderStatus::SECTION_ORDERS && $request->string('status')->toString() === 'cancelled'),
                'nullable',
                'string',
                'max:255',
            ],
            'notes' => ['nullable', 'string'],
            'admin_notes' => ['nullable', 'string'],
            'tracking_number' => ['nullable', 'string', 'max:255'],
            'tracking_url' => ['nullable', 'url', 'max:255'],
        ], [
            'cancel_reason.required' => 'A cancellation reason is required to cancel an order.',
        ]);

        $status = OrderStatus::query()
            ->where('section', $orderSection)
            ->where('slug', $validated['status'])
            ->firstOrFail();

        $oldStatusName = $order->statusLabel();
        $oldAdminNotes = $order->admin_notes;
        $newAdminNotes = $validated['admin_notes'] ?? $order->admin_notes;

        $order->fill([
            'notes' => $validated['notes'] ?? $order->notes,
            'admin_notes' => $newAdminNotes,
            'cancel_reason' => ($orderSection === OrderStatus::SECTION_ORDERS && $status->slug === 'cancelled')
                ? ($validated['cancel_reason'] ?? $order->cancel_reason)
                : null,
            'tracking_number' => $validated['tracking_number'] ?? $order->tracking_number,
            'tracking_url' => $validated['tracking_url'] ?? $order->tracking_url,
            'status' => $status->slug,
            'order_status_id' => $status->id,
        ])->save();

        if ($oldStatusName !== $status->name) {
            OrderActivityLog::record(
                $order,
                'status_changed',
                "Status changed from '{$oldStatusName}' to '{$status->name}'",
                ['status' => ['old' => $oldStatusName, 'new' => $status->name]]
            );
        }

        if ((string) $oldAdminNotes !== (string) $newAdminNotes && filled($newAdminNotes)) {
            OrderActivityLog::record(
                $order,
                'admin_note_updated',
                'Admin note was updated',
                ['admin_notes' => ['old' => $oldAdminNotes, 'new' => $newAdminNotes]]
            );
        }

        try {
            app(BulkSmsBdService::class)->notifyOrderStatusUpdated($order, $status->name);
        } catch (Throwable $e) {
            Log::warning('Order status SMS notification failed softly.', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            app(StoreEmailService::class)->notifyOrderStatusUpdated($order, $status->name);
        } catch (Throwable $e) {
            Log::warning('Order status Email notification failed softly.', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Order status updated to '.$status->name.'.');
    }

    public function searchProducts(Request $request): JsonResponse
    {
        $term = trim($request->string('q')->toString());

        $products = Product::query()
            ->with(['images', 'variants.color', 'variants.size'])
            ->when($term !== '', function ($query) use ($term): void {
                $like = '%'.$term.'%';
                $query->where(function ($inner) use ($like): void {
                    $inner->where('name', 'like', $like)
                        ->orWhere('sku', 'like', $like);
                });
            })
            ->active()
            ->orderBy('name')
            ->limit(20)
            ->get();

        $colors = Color::query()->active()->orderBy('sort_order')->orderBy('name')->pluck('name');
        $sizes = Size::query()->active()->orderBy('sort_order')->orderBy('name')->pluck('name');

        return response()->json([
            'data' => $products->map(function (Product $product) use ($colors, $sizes): array {
                $variantColors = $product->variants
                    ->pluck('color.name')
                    ->filter()
                    ->unique()
                    ->values();
                $variantSizes = $product->variants
                    ->map(fn ($variant) => $variant->size?->name ?? $variant->size_name)
                    ->filter()
                    ->unique()
                    ->values();

                $imagePath = $product->primaryImagePath();

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'price' => (float) $product->price,
                    'stock' => (int) $product->stock,
                    'image_url' => $imagePath ? asset('storage/'.$imagePath) : null,
                    'colors' => ($variantColors->isNotEmpty() ? $variantColors : $colors)->values()->all(),
                    'sizes' => ($variantSizes->isNotEmpty() ? $variantSizes : $sizes)->values()->all(),
                ];
            })->values(),
        ]);
    }

    public function clearCart(Order $order): RedirectResponse
    {
        DB::transaction(function () use ($order): void {
            $order->load('items');

            foreach ($order->items as $item) {
                if (! $item->product_id) {
                    continue;
                }

                $product = Product::query()->whereKey($item->product_id)->lockForUpdate()->first();

                if ($product) {
                    $product->increment('stock', (int) $item->qty);
                }
            }

            $order->items()->delete();

            $shipping = (float) $order->shipping_charge;

            $order->update([
                'subtotal' => '0.00',
                'discount' => '0.00',
                'total' => number_format(max(0, $shipping), 2, '.', ''),
            ]);
        });

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Cart cleared.');
    }

    public function destroy(Order $order): RedirectResponse
    {
        /** @var Admin|null $admin */
        $admin = auth('admin')->user();

        if (! $admin || ! $admin->canDeleteOrders()) {
            return back()->with('error', 'You do not have permission to delete orders.');
        }

        if ($order->isCourier() && ! ($admin->isSuperAdmin())) {
            return back()->with('error', 'Courier booked orders can only be deleted by a Super Admin.');
        }

        if (! $order->isDeletable($admin)) {
            return back()->with('error', 'Only pending orders can be deleted.');
        }

        $params = $order->isCourier() ? ['section' => 'courier'] : [];
        $order->delete();

        return redirect()
            ->route('admin.orders.index', $params)
            ->with('success', 'Order deleted.');
    }

    public function bulkUpdate(Request $request, BdCourierFraudService $fraudService, CourierShipmentService $shipments): View|RedirectResponse
    {
        $bulkSection = $request->string('section', 'orders')->toString();
        if ($bulkSection !== 'courier') {
            $bulkSection = 'orders';
        }

        $validated = $request->validate([
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['integer', 'exists:orders,id'],
            'action' => ['required', 'string', Rule::in(['status', 'delete', 'fraud_check', 'send_to_courier', 'print', 'print_invoice', 'print_sticker'])],
            'status' => [
                'required_if:action,status',
                'nullable',
                'string',
                Rule::exists('order_statuses', 'slug')->where('section', $bulkSection),
            ],
            'cancel_reason' => [
                Rule::requiredIf(fn () => $request->input('action') === 'status' && $bulkSection === 'orders' && $request->input('status') === 'cancelled'),
                'nullable',
                'string',
                'max:255',
            ],
            'courier_api_id' => [
                'required_if:action,send_to_courier',
                'nullable',
                'integer',
                Rule::exists('courier_apis', 'id')->where('is_active', true),
            ],
            'force' => ['nullable', 'boolean'],
        ], [
            'cancel_reason.required' => 'A cancellation reason is required when cancelling orders.',
        ]);

        if ($validated['action'] === 'print' || $validated['action'] === 'print_invoice') {
            $orders = Order::query()
                ->whereIn('id', $validated['order_ids'])
                ->with(['items.variant', 'orderStatus', 'courierApi'])
                ->get();

            return view('admin.orders.invoice', [
                'orders' => $orders,
            ]);
        }

        if ($validated['action'] === 'print_sticker') {
            if ($bulkSection !== 'courier') {
                return back()->with('error', 'Sticker print is only available from the Courier section.');
            }

            $orders = Order::query()
                ->whereIn('id', $validated['order_ids'])
                ->whereNotNull('tracking_number')
                ->where('tracking_number', '!=', '')
                ->with(['items.variant', 'orderStatus', 'courierApi'])
                ->get();

            if ($orders->isEmpty()) {
                return back()->with('error', 'Selected orders must be booked in courier (tracking/consignment required) before printing stickers.');
            }

            return view('admin.orders.sticker', [
                'orders' => $orders,
            ]);
        }

        $orders = Order::query()->with('orderStatus')->whereIn('id', $validated['order_ids'])->get();

        if ($validated['action'] === 'delete') {
            /** @var Admin|null $admin */
            $admin = auth('admin')->user();

            if (! $admin || ! $admin->canDeleteOrders()) {
                return back()->with('error', 'You do not have permission to delete orders.');
            }

            $isSuperAdmin = $admin->isSuperAdmin();

            $courierBlocked = 0;
            $statusBlocked = 0;
            $deletable = $orders->filter(function (Order $order) use ($admin, $isSuperAdmin, &$courierBlocked, &$statusBlocked): bool {
                if ($order->isCourier() && ! $isSuperAdmin) {
                    $courierBlocked++;

                    return false;
                }

                if (! $order->isDeletable($admin)) {
                    $statusBlocked++;

                    return false;
                }

                return true;
            });

            if ($deletable->isEmpty()) {
                if ($courierBlocked > 0) {
                    return back()->with('error', 'Courier booked orders can only be deleted by a Super Admin.');
                }

                return back()->with('error', 'Only pending orders can be deleted. Selected order(s) are no longer pending.');
            }

            Order::query()->whereIn('id', $deletable->modelKeys())->delete();

            $message = $deletable->count().' order(s) deleted.';

            if ($courierBlocked > 0) {
                $message .= ' '.$courierBlocked.' courier-booked order(s) were skipped (Super Admin required).';
            }
            if ($statusBlocked > 0) {
                $message .= ' '.$statusBlocked.' non-pending order(s) were skipped.';
            }

            return back()->with('success', $message);
        }

        if ($validated['action'] === 'fraud_check') {
            if (! FraudSetting::current()->isConfigured()) {
                return back()->with('error', 'Courier Ratio API is disabled or missing a key. Configure it under Manage Courier Ratio API.');
            }

            $checked = 0;
            $failed = 0;

            foreach ($orders as $order) {
                $result = $fraudService->applyToOrder($order, softFail: true);

                if ($result['ok']) {
                    $checked++;
                } else {
                    $failed++;
                }
            }

            $message = $checked.' order(s) ratio-checked.';

            if ($failed > 0) {
                $message .= ' '.$failed.' could not be checked.';
            }

            return back()->with($checked > 0 ? 'success' : 'error', $message);
        }

        if ($validated['action'] === 'send_to_courier') {
            if ($bulkSection !== 'courier') {
                return back()->with('error', 'Courier booking can only be done from the Courier section.');
            }

            $courierApiId = (int) $validated['courier_api_id'];
            $force = $request->boolean('force');
            $method = CourierApi::query()->find($courierApiId);
            $methodLabel = $method?->displayName() ?? 'courier';

            if ($orders->count() > CourierShipmentService::QUEUE_THRESHOLD) {
                SendOrdersToCourier::dispatch(
                    $orders->pluck('id')->all(),
                    $courierApiId,
                    $force,
                );

                return back()->with(
                    'success',
                    $orders->count().' order(s) queued for '.$methodLabel.'. Check tracking shortly.',
                );
            }

            $summary = $shipments->sendMany($orders, $courierApiId, $force);

            $flashKey = $summary['success'] > 0 ? 'success' : 'error';

            return back()->with($flashKey, $summary['message']);
        }

        $status = OrderStatus::query()
            ->where('section', $bulkSection)
            ->where('slug', $validated['status'])
            ->firstOrFail();

        foreach ($orders as $order) {
            $order->forceFill([
                'cancel_reason' => ($bulkSection === 'orders' && $status->slug === 'cancelled') ? ($validated['cancel_reason'] ?? null) : null,
            ]);
            $order->applyStatus($status);
        }

        return back()->with('success', $orders->count().' order(s) status updated to '.$status->name.'.');
    }

    public function sendToCourier(Request $request, Order $order, CourierShipmentService $shipments): RedirectResponse
    {
        $validated = $request->validate([
            'courier_api_id' => [
                'required',
                'integer',
                Rule::exists('courier_apis', 'id')->where('is_active', true),
            ],
            'force' => ['nullable', 'boolean'],
        ]);

        $result = $shipments->sendOrder(
            $order,
            (int) $validated['courier_api_id'],
            $request->boolean('force'),
        );

        if ($result['ok']) {
            $courier = CourierApi::query()->find($validated['courier_api_id']);
            OrderActivityLog::record(
                $order,
                'sent_to_courier',
                'Sent to courier: '.($courier?->name ?? 'Courier'),
                ['courier' => $courier?->name, 'tracking' => $order->fresh()->tracking_number]
            );
        }

        return redirect()
            ->route('admin.orders.show', $order)
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function markConfirmed(Order $order): RedirectResponse
    {
        $courierPending = OrderStatus::query()
            ->where('section', OrderStatus::SECTION_COURIER)
            ->where('slug', 'pending')
            ->firstOrFail();

        $order->applyStatus($courierPending);

        return redirect()
            ->route('admin.orders.index')
            ->with('success', 'Order #'.$order->invoice_no.' confirmed and moved to Courier Pending.');
    }

    public function markProcessing(Order $order): RedirectResponse
    {
        $order->applyStatus('processing');

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Order set to Processing.');
    }

    public function markHold(Order $order): RedirectResponse
    {
        $order->applyStatus('on_hold');

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Order set to On Hold.');
    }

    public function markNoResponse(Order $order): RedirectResponse
    {
        $order->applyStatus('no_response');

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Order set to No Response.');
    }

    public function fraudCheck(Request $request, Order $order, BdCourierFraudService $fraudService): RedirectResponse|JsonResponse
    {
        $result = $fraudService->applyToOrder($order);

        if ($request->expectsJson()) {
            if (! $result['ok']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], 422);
            }

            $order->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Courier ratio updated.',
                'html' => view('admin.partials.order-success-rate', [
                    'score' => $order->fraud_score,
                    'fraudReport' => $order->fraud_report,
                ])->render(),
            ]);
        }

        if (! $result['ok']) {
            return back()->with('error', $result['message']);
        }

        $scoreLabel = $result['score'] !== null
            ? number_format($result['score'], 2).'% ratio'
            : 'no summary score';

        return back()->with('success', 'Courier ratio check complete: '.$scoreLabel.'.');
    }

    private function autoCheckMissingFraud(Order $order, BdCourierFraudService $fraudService): void
    {
        if ($order->fraud_score !== null || ! FraudSetting::current()->isConfigured()) {
            return;
        }

        $lockKey = 'order-fraud-auto:'.$order->id;

        if (! Cache::add($lockKey, true, now()->addMinutes(5))) {
            return;
        }

        $fraudService->applyToOrder($order, softFail: true);
    }

    public function invoice(Order $order): View
    {
        $order->load(['items.variant', 'orderStatus', 'courierApi']);

        return view('admin.orders.invoice', [
            'orders' => collect([$order]),
            'order' => $order,
        ]);
    }

    public function sticker(Order $order): View|RedirectResponse
    {
        if (blank($order->tracking_number)) {
            return redirect()
                ->route('admin.orders.show', $order)
                ->with('error', 'Sticker print is only available after courier booking (tracking/consignment number required).');
        }

        $order->load(['items.variant', 'orderStatus', 'courierApi']);

        return view('admin.orders.sticker', [
            'orders' => collect([$order]),
            'order' => $order,
        ]);
    }

    public function bulkInvoice(Request $request): View|RedirectResponse
    {
        $ids = array_filter(array_map('intval', explode(',', (string) $request->input('ids', ''))));
        if (empty($ids)) {
            return redirect()->back()->with('error', 'No orders selected for invoice print.');
        }

        $orders = Order::query()
            ->whereIn('id', $ids)
            ->with(['items.variant', 'orderStatus', 'courierApi'])
            ->get();

        return view('admin.orders.invoice', [
            'orders' => $orders,
        ]);
    }

    public function bulkSticker(Request $request): View|RedirectResponse
    {
        $ids = array_filter(array_map('intval', explode(',', (string) $request->input('ids', ''))));
        if (empty($ids)) {
            return redirect()->back()->with('error', 'No orders selected for sticker print.');
        }

        $orders = Order::query()
            ->whereIn('id', $ids)
            ->whereNotNull('tracking_number')
            ->where('tracking_number', '!=', '')
            ->with(['items.variant', 'orderStatus', 'courierApi'])
            ->get();

        if ($orders->isEmpty()) {
            return redirect()->back()->with('error', 'Selected orders must be booked in courier before printing stickers.');
        }

        return view('admin.orders.sticker', [
            'orders' => $orders,
        ]);
    }

    public function blockIp(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        if (blank($order->ip_address)) {
            return back()->with('error', 'This order has no IP address.');
        }

        IpBlock::query()->updateOrCreate(
            ['ip' => $order->ip_address],
            ['reason' => $validated['reason']],
        );

        return back()->with('success', 'IP blocked: '.$order->ip_address);
    }
}
