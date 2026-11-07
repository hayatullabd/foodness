<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductImportExportController extends Controller
{
    /**
     * Export products to CSV. Supports exporting selected IDs or filtered/all.
     */
    public function export(Request $request): StreamedResponse
    {
        $query = Product::query()->with(['category', 'brand', 'images'])->latest('id');

        // Check if specific product IDs were selected
        $selectedIds = $request->input('product_ids');
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
                $term = '%'.$request->string('q')->toString().'%';
                $query->where(function ($sub) use ($term): void {
                    $sub->where('name', 'like', $term)
                        ->orWhere('sku', 'like', $term);
                });
            }

            if ($request->filled('filter')) {
                $filter = $request->string('filter')->toString();
                if ($filter === 'pending') {
                    $query->pending();
                } else {
                    $query->inhouse();
                }
            }

            if ($request->filled('category_id')) {
                $query->where('category_id', $request->integer('category_id'));
            }
        }

        $filename = 'products_export_'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            // UTF-8 BOM for Microsoft Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Headers with Image URL included
            fputcsv($handle, [
                'ID',
                'Name',
                'SKU',
                'Price',
                'Compare Price',
                'Stock',
                'Category',
                'Brand',
                'Status',
                'Unit',
                'Image URL',
                'Short Description',
                'Description',
            ]);

            $query->chunk(200, function ($products) use ($handle): void {
                foreach ($products as $product) {
                    $imageUrl = '';
                    $primaryPath = $product->primaryImagePath();
                    if ($primaryPath) {
                        $imageUrl = asset('storage/'.$primaryPath);
                    }

                    fputcsv($handle, [
                        $product->id,
                        $product->name,
                        $product->sku,
                        $product->price,
                        $product->compare_at_price,
                        $product->stock,
                        $product->category?->name ?? '',
                        $product->brand?->name ?? '',
                        $product->is_active ? 'Active' : 'Inactive',
                        $product->unit ?? '',
                        $imageUrl,
                        $product->short_description ?? '',
                        $product->description ?? '',
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
     * Download sample CSV template for Product Import.
     */
    public function sampleTemplate(): StreamedResponse
    {
        $filename = 'products_sample_template.csv';

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            // UTF-8 BOM
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header row with Image URL
            fputcsv($handle, [
                'Name',
                'SKU',
                'Price',
                'Compare Price',
                'Stock',
                'Category',
                'Brand',
                'Status',
                'Unit',
                'Image URL',
                'Short Description',
                'Description',
            ]);

            // Sample rows with sample image URLs
            fputcsv($handle, [
                'Sample Premium Cotton T-Shirt',
                'TSHIRT-001',
                '650',
                '850',
                '50',
                'Clothing',
                'Foodness',
                'Active',
                'pcs',
                'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=800',
                '100% pure combed cotton casual t-shirt.',
                'Premium quality fabric, breathable, comfortable for summer.',
            ]);

            fputcsv($handle, [
                'Classic Leather Wallet',
                'WALLET-002',
                '1200',
                '1500',
                '25',
                'Accessories',
                'Foodness',
                'Active',
                'pcs',
                'https://images.unsplash.com/photo-1627123424574-724758594e93?w=800',
                'Genuine leather bifold wallet with multiple card slots.',
                'Made from top grain cowhide leather.',
            ]);

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Import products from CSV. Supports downloading and saving image from Image URL.
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

        // Check and remove BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $header = fgetcsv($handle);
        if (! $header) {
            fclose($handle);

            return back()->with('error', 'The uploaded CSV file is empty.');
        }

        // Normalize header keys
        $normalizedHeaders = array_map(function ($h) {
            $cleaned = strtolower(trim((string) $h));
            $cleaned = preg_replace('/[^a-z0-9]+/', '_', $cleaned);

            return trim($cleaned, '_');
        }, $header);

        $createdCount = 0;
        $updatedCount = 0;
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

                $name = $data['name'] ?? '';
                if (empty($name)) {
                    $skippedCount++;

                    continue;
                }

                $sku = $data['sku'] ?? '';
                $price = is_numeric($data['price'] ?? null) ? (float) $data['price'] : 0.00;
                $comparePrice = is_numeric($data['compare_price'] ?? ($data['compare_at_price'] ?? null)) ? (float) ($data['compare_price'] ?? $data['compare_at_price']) : null;
                $stock = is_numeric($data['stock'] ?? null) ? (int) $data['stock'] : 0;
                $unit = $data['unit'] ?? 'pcs';
                $imageUrl = $data['image_url'] ?? ($data['image'] ?? ($data['photo'] ?? ''));
                $shortDesc = $data['short_description'] ?? '';
                $desc = $data['description'] ?? '';
                $statusStr = strtolower($data['status'] ?? 'active');
                $isActive = ! in_array($statusStr, ['inactive', '0', 'disabled', 'draft', 'pending'], true);

                // Find or match Category
                $categoryId = null;
                if (! empty($data['category'])) {
                    $category = Category::query()->firstOrCreate(
                        ['name' => $data['category']],
                        ['slug' => Str::slug($data['category'])]
                    );
                    $categoryId = $category->id;
                }

                // Find or match Brand
                $brandId = null;
                if (! empty($data['brand'])) {
                    $brand = Brand::query()->firstOrCreate(
                        ['name' => $data['brand']],
                        ['slug' => Str::slug($data['brand'])]
                    );
                    $brandId = $brand->id;
                }

                // Find existing product by SKU if available, else by name
                $existingProduct = null;
                if (! empty($sku)) {
                    $existingProduct = Product::query()->where('sku', $sku)->first();
                }

                if (! $existingProduct) {
                    $existingProduct = Product::query()->where('name', $name)->first();
                }

                $targetProduct = null;

                if ($existingProduct) {
                    $updatePayload = [
                        'name' => $name,
                        'price' => $price,
                        'compare_at_price' => $comparePrice,
                        'stock' => $stock,
                        'unit' => $unit,
                        'short_description' => $shortDesc ?: $existingProduct->short_description,
                        'description' => $desc ?: $existingProduct->description,
                        'is_active' => $isActive,
                    ];
                    if ($categoryId) {
                        $updatePayload['category_id'] = $categoryId;
                    }
                    if ($brandId) {
                        $updatePayload['brand_id'] = $brandId;
                    }
                    if (! empty($sku)) {
                        $updatePayload['sku'] = $sku;
                    }

                    $existingProduct->update($updatePayload);
                    $targetProduct = $existingProduct;
                    $updatedCount++;
                } else {
                    $generatedSku = ! empty($sku) ? $sku : 'PRD-'.strtoupper(Str::random(6));

                    // Generate unique slug
                    $baseSlug = Str::slug($name);
                    $slug = $baseSlug;
                    $counter = 1;
                    while (Product::query()->where('slug', $slug)->exists()) {
                        $slug = $baseSlug.'-'.$counter++;
                    }

                    $targetProduct = Product::create([
                        'name' => $name,
                        'slug' => $slug,
                        'sku' => $generatedSku,
                        'category_id' => $categoryId,
                        'brand_id' => $brandId,
                        'price' => $price,
                        'compare_at_price' => $comparePrice,
                        'stock' => $stock,
                        'unit' => $unit,
                        'short_description' => $shortDesc,
                        'description' => $desc,
                        'is_active' => $isActive,
                    ]);
                    $createdCount++;
                }

                // Attach Image if provided and not already present
                if (! empty($imageUrl) && filter_var($imageUrl, FILTER_VALIDATE_URL) && $targetProduct) {
                    $this->attachImageFromUrl($targetProduct, $imageUrl);
                }
            }

            DB::commit();
            fclose($handle);

            return back()->with('success', "Import completed successfully! Created: {$createdCount}, Updated: {$updatedCount}".($skippedCount > 0 ? ", Skipped: {$skippedCount}" : ''));
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);

            return back()->with('error', 'Import failed due to error: '.$e->getMessage());
        }
    }

    /**
     * Download an image from an external URL and attach it to the product as primary image.
     */
    protected function attachImageFromUrl(Product $product, string $url): void
    {
        try {
            // Check if product already has primary image
            if ($product->images()->where('is_primary', true)->exists()) {
                return;
            }

            $response = Http::timeout(6)->get($url);
            if ($response->successful()) {
                $imageContent = $response->body();
                if (! empty($imageContent)) {
                    $extension = 'jpg';
                    $contentType = $response->header('Content-Type') ?? '';
                    if (str_contains($contentType, 'png')) {
                        $extension = 'png';
                    } elseif (str_contains($contentType, 'webp')) {
                        $extension = 'webp';
                    } elseif (str_contains($contentType, 'gif')) {
                        $extension = 'gif';
                    }

                    $fileName = 'products/'.Str::slug($product->name).'-'.now()->format('YmdHis').'-'.Str::lower(Str::random(6)).'.'.$extension;
                    Storage::disk('public')->put($fileName, $imageContent);

                    ProductImage::create([
                        'product_id' => $product->id,
                        'path' => $fileName,
                        'is_primary' => true,
                        'sort_order' => 0,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // Suppress network/download exceptions so import continues smoothly
        }
    }
}
