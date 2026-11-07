<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderImportExportController extends Controller
{
    /**
     * Export orders to CSV based on selection or current filters.
     */
    public function export(Request $request): StreamedResponse
    {
        $query = Order::query()->with(['items.product.images', 'orderStatus'])->latest('id');

        // Check if specific order IDs were selected
        $selectedIds = $request->input('order_ids');
        if (is_string($selectedIds) && trim($selectedIds) !== '') {
            $selectedIds = explode(',', $selectedIds);
        }

        if (is_array($selectedIds) && ! empty($selectedIds)) {
            $ids = array_filter(array_map('intval', $selectedIds));
            if (! empty($ids)) {
                $query->whereIn('id', $ids);
            }
        } else {
            // Apply current filters if not explicitly selecting IDs
            if ($request->filled('q')) {
                $q = trim((string) $request->input('q'));
                $query->where(function ($sub) use ($q): void {
                    $sub->where('invoice_no', 'like', "%{$q}%")
                        ->orWhere('customer_name', 'like', "%{$q}%")
                        ->orWhere('customer_phone', 'like', "%{$q}%")
                        ->orWhere('customer_address', 'like', "%{$q}%")
                        ->orWhere('tracking_number', 'like', "%{$q}%");
                });
            }

            if ($request->filled('section')) {
                $section = $request->string('section')->toString();
                if ($section === 'courier') {
                    $courierStatusIds = OrderStatus::query()->courierSection()->pluck('id')->all();
                    $query->where(function ($sub) use ($courierStatusIds): void {
                        $sub->whereNotNull('tracking_number')
                            ->orWhereIn('order_status_id', $courierStatusIds);
                    });
                }
            }

            if ($request->filled('status')) {
                $statusSlug = $request->string('status')->toString();
                $statusModel = OrderStatus::query()->where('slug', $statusSlug)->first();
                if ($statusModel) {
                    $query->where('order_status_id', $statusModel->id);
                }
            }

            if ($request->filled('courier')) {
                $courierId = (int) $request->input('courier');
                $query->where('courier_api_id', $courierId);
            }

            if ($request->filled('tracking')) {
                $tracking = $request->string('tracking')->toString();
                if ($tracking === 'with') {
                    $query->whereNotNull('tracking_number')->where('tracking_number', '!=', '');
                } elseif ($tracking === 'without') {
                    $query->where(function ($sub): void {
                        $sub->whereNull('tracking_number')->orWhere('tracking_number', '');
                    });
                }
            }

            if ($request->filled('platform')) {
                $platform = $request->string('platform')->toString();
                $query->where('source_platform', $platform);
            }
        }

        $filename = 'orders_export_'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            // UTF-8 BOM
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Headers with Product Image URL
            fputcsv($handle, [
                'Invoice No',
                'Date',
                'Customer Name',
                'Customer Phone',
                'Delivery Address',
                'Products Ordered',
                'Product Image URL',
                'Subtotal',
                'Shipping Charge',
                'Discount',
                'Total Amount',
                'Status',
                'Payment Method',
                'Payment Status',
                'Courier Provider',
                'Tracking Number',
                'Platform',
                'Customer Notes',
            ]);

            $query->chunk(200, function ($orders) use ($handle): void {
                foreach ($orders as $order) {
                    $productsSummary = $order->items->map(function ($item): string {
                        $variant = $item->variantLabel();

                        return ($item->product_name ?? 'Product').($variant ? ' ('.$variant.')' : '').' [Qty: '.$item->qty.' x '.$item->price.']';
                    })->implode('; ');

                    // Collect image URLs from order items
                    $imageUrls = $order->items->map(function ($item): string {
                        $img = $item->product?->primaryImagePath();

                        return $img ? asset('storage/'.$img) : '';
                    })->filter()->unique()->implode(' ; ');

                    $statusName = $order->orderStatus?->name ?? ucfirst(str_replace('_', ' ', $order->status ?? ''));

                    fputcsv($handle, [
                        $order->invoice_no,
                        $order->created_at?->format('Y-m-d H:i:s') ?? '',
                        $order->customer_name,
                        $order->customer_phone,
                        $order->customer_address,
                        $productsSummary,
                        $imageUrls,
                        $order->subtotal,
                        $order->shipping_charge,
                        $order->discount,
                        $order->total,
                        $statusName,
                        $order->payment_method ?? 'COD',
                        $order->payment_status ?? 'unpaid',
                        $order->courier_provider ?? '',
                        $order->tracking_number ?? '',
                        $order->source_platform ?? '',
                        $order->notes ?? '',
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Download sample CSV template for Order Import.
     */
    public function sampleTemplate(): StreamedResponse
    {
        $filename = 'orders_sample_template.csv';

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            // UTF-8 BOM
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header row
            fputcsv($handle, [
                'Customer Name',
                'Customer Phone',
                'Customer Address',
                'Product SKU / Name',
                'Quantity',
                'Unit Price',
                'Shipping Charge',
                'Discount',
                'Payment Method',
                'Customer Notes',
            ]);

            // Sample rows
            fputcsv($handle, [
                'Rahim Uddin',
                '01712345678',
                'House 12, Road 5, Dhanmondi, Dhaka',
                'TSHIRT-001',
                '1',
                '650',
                '60',
                '0',
                'COD',
                'Please deliver in afternoon',
            ]);

            fputcsv($handle, [
                'Karim Chowdhury',
                '01812345678',
                'GEC Circle, Chittagong',
                'WALLET-002',
                '2',
                '1200',
                '120',
                '50',
                'COD',
                'Call before delivery',
            ]);

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Import orders from CSV.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $file = $request->file('csv_file');
        if (! $file || ! $file->isValid()) {
            return back()->with('error', 'Please upload a valid CSV file.');
        }

        $path = $file->getRealPath();
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return back()->with('error', 'Unable to read the uploaded CSV file.');
        }

        // BOM removal if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $header = fgetcsv($handle);
        if (! $header) {
            fclose($handle);

            return back()->with('error', 'The uploaded CSV file is empty.');
        }

        $normalizedHeaders = array_map(function ($h) {
            $cleaned = strtolower(trim((string) $h));
            $cleaned = preg_replace('/[^a-z0-9]+/', '_', $cleaned);

            return trim($cleaned, '_');
        }, $header);

        // Find default initial pending status
        $defaultStatus = OrderStatus::query()->where('slug', 'pending')->first()
            ?? OrderStatus::query()->ordered()->first();

        $createdCount = 0;
        $skippedCount = 0;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                if (empty(array_filter($row, fn ($val) => trim((string) $val) !== ''))) {
                    continue; // Skip empty rows
                }

                $data = [];
                foreach ($normalizedHeaders as $index => $colName) {
                    $data[$colName] = isset($row[$index]) ? trim((string) $row[$index]) : '';
                }

                $customerName = $data['customer_name'] ?? ($data['name'] ?? '');
                $customerPhone = $data['customer_phone'] ?? ($data['phone'] ?? '');
                $customerAddress = $data['customer_address'] ?? ($data['address'] ?? '');

                if (empty($customerName) || empty($customerPhone) || empty($customerAddress)) {
                    $skippedCount++;

                    continue;
                }

                $productRef = $data['product_sku_name'] ?? ($data['product_sku'] ?? ($data['product_name'] ?? ($data['product'] ?? '')));
                $qty = max(1, (int) ($data['quantity'] ?? ($data['qty'] ?? 1)));
                $shippingCharge = is_numeric($data['shipping_charge'] ?? null) ? (float) $data['shipping_charge'] : 60.00;
                $discount = is_numeric($data['discount'] ?? null) ? (float) $data['discount'] : 0.00;
                $paymentMethod = ! empty($data['payment_method']) ? $data['payment_method'] : 'COD';
                $notes = $data['customer_notes'] ?? ($data['notes'] ?? '');

                // Try to resolve product
                $product = null;
                if (! empty($productRef)) {
                    $product = Product::query()->where('sku', $productRef)->first()
                        ?? Product::query()->where('name', 'like', "%{$productRef}%")->first();
                }

                $productName = $product ? $product->name : (! empty($productRef) ? $productRef : 'General Item');
                $unitPrice = 0.00;
                if (isset($data['unit_price']) && is_numeric($data['unit_price'])) {
                    $unitPrice = (float) $data['unit_price'];
                } elseif ($product) {
                    $unitPrice = (float) $product->price;
                }

                $subtotal = round($unitPrice * $qty, 2);
                $total = max(0, round($subtotal + $shippingCharge - $discount, 2));

                $order = Order::create([
                    'invoice_no' => Order::generateInvoiceNumber(),
                    'customer_name' => $customerName,
                    'customer_phone' => $customerPhone,
                    'customer_address' => $customerAddress,
                    'subtotal' => $subtotal,
                    'shipping_charge' => $shippingCharge,
                    'discount' => $discount,
                    'total' => $total,
                    'status' => $defaultStatus?->slug ?? 'pending',
                    'order_status_id' => $defaultStatus?->id,
                    'payment_method' => $paymentMethod,
                    'payment_status' => 'unpaid',
                    'notes' => $notes,
                    'source_platform' => 'manual_import',
                ]);

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product?->id,
                    'product_name' => $productName,
                    'price' => $unitPrice,
                    'qty' => $qty,
                    'discount' => 0.00,
                ]);

                $createdCount++;
            }

            DB::commit();
            fclose($handle);

            return back()->with('success', "Orders imported successfully! Total imported: {$createdCount}".($skippedCount > 0 ? ", Skipped: {$skippedCount}" : ''));
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);

            return back()->with('error', 'Order import failed: '.$e->getMessage());
        }
    }
}
