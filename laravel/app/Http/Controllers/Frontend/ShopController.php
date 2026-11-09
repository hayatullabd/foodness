<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Product;
use App\Services\TrackingService;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function index(Request $request): View
    {
        $categories = Category::query()
            ->active()
            ->orderBy('sort_order')
            ->get();

        $selectedCategory = null;

        if ($request->filled('category')) {
            $selectedCategory = Category::query()
                ->active()
                ->where('slug', $request->string('category')->toString())
                ->first();
        }

        $perPage = 16;

        $products = Product::query()
            ->storeVisible()
            ->with(['images', 'category'])
            ->when($selectedCategory, fn ($query) => $query->where('category_id', $selectedCategory->id))
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.$request->string('q')->toString().'%';
                $query->where(function ($inner) use ($term): void {
                    $inner->where('name', 'like', $term)
                        ->orWhere('description', 'like', $term)
                        ->orWhere('sku', 'like', $term);
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('store.shop', [
            'products' => $products,
            'categories' => $categories,
            'selectedCategory' => $selectedCategory,
        ]);
    }

    public function category(Category $category): View
    {
        abort_unless($category->status, 404);

        $perPage = 16;

        $categories = Category::query()
            ->active()
            ->orderBy('sort_order')
            ->get();

        $products = Product::query()
            ->storeVisible()
            ->with(['images', 'category'])
            ->where('category_id', $category->id)
            ->latest()
            ->paginate($perPage);

        return view('store.shop', [
            'products' => $products,
            'categories' => $categories,
            'selectedCategory' => $category,
        ]);
    }

    public function show(Request $request, Product $product, TrackingService $tracking): View
    {
        abort_unless($product->is_active, 404);

        $product->load([
            'images',
            'videos',
            'category',
            'variants' => fn ($query) => $query->with(['color', 'size', 'product.images'])->orderBy('sort_order'),
        ]);

        $trackedEvent = $tracking->trackViewContent($product, $request);

        $relatedProducts = Product::query()
            ->storeVisible()
            ->with(['images', 'category'])
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->take(4)
            ->get();

        return view('store.product', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
            'trackedEvent' => $trackedEvent,
        ]);
    }

    public function liveSearch(Request $request): JsonResponse
    {
        $term = trim($request->string('q')->toString());

        if (mb_strlen($term) < 1) {
            $recommendations = [
                'Panjabi',
                'Shirt',
                'T-Shirt',
                'Polo Shirt',
                'New Arrivals',
                'Premium Collection',
            ];

            $popularCategories = Category::query()
                ->active()
                ->orderBy('sort_order')
                ->take(6)
                ->get(['id', 'name', 'slug']);

            $trendingProducts = Product::query()
                ->storeVisible()
                ->with(['images', 'category'])
                ->orderByDesc('is_featured')
                ->latest()
                ->take(5)
                ->get()
                ->map(fn ($p) => $this->transformProductForSearch($p));

            return response()->json([
                'products' => $trendingProducts,
                'total_count' => 0,
                'is_trending' => true,
            ]);
        }

        $query = Product::query()
            ->storeVisible()
            ->with(['images', 'category'])
            ->where(function ($inner) use ($term): void {
                $searchWildcard = '%'.$term.'%';
                $inner->where('name', 'like', $searchWildcard)
                    ->orWhere('sku', 'like', $searchWildcard)
                    ->orWhereHas('category', function ($catQuery) use ($searchWildcard): void {
                        $catQuery->where('name', 'like', $searchWildcard);
                    });
            });

        $totalCount = $query->count();
        $products = $query->latest()
            ->take(8)
            ->get()
            ->map(fn ($p) => $this->transformProductForSearch($p));

        return response()->json([
            'products' => $products,
            'total_count' => $totalCount,
            'is_trending' => false,
            'term' => $term,
        ]);
    }

    /**
     * Return JSON data for variation / quick-order modal
     */
    public function productModalData(Product $product): JsonResponse
    {
        abort_unless($product->is_active, 404);

        $product->load([
            'images',
            'category',
            'variants' => fn ($query) => $query->with(['color', 'size', 'product.images'])->orderBy('sort_order'),
        ]);

        $imagePath = $product->primaryImagePath();
        $imageUrl = $imagePath ? asset('storage/'.$imagePath) : null;

        $hasDiscount = $product->compare_at_price && (float) $product->compare_at_price > (float) $product->price;

        $colorCodes = AttributeValue::query()
            ->whereNotNull('color_code')
            ->pluck('color_code', 'name')
            ->all();

        $variants = $product->variants->map(function ($v) use ($product) {
            if (! $v->relationLoaded('product')) {
                $v->setRelation('product', $product);
            }
            $imgPath = $v->primaryImagePath();

            return [
                'id' => $v->id,
                'name' => $v->label(),
                'attributes' => $v->attributeMap(),
                'color_id' => $v->color_id,
                'color_name' => $v->color?->name,
                'size_id' => $v->size_id,
                'size_name' => $v->size?->name ?? $v->size_name,
                'price' => (float) $v->effectivePrice(),
                'price_formatted' => Money::formatBn($v->effectivePrice()),
                'compare_at_price' => $v->effectiveCompareAtPrice() ? (float) $v->effectiveCompareAtPrice() : null,
                'compare_at_price_formatted' => $v->effectiveCompareAtPrice() ? Money::formatBn($v->effectiveCompareAtPrice()) : null,
                'stock' => $v->is_unlimited_stock ? 99999 : (int) $v->stock,
                'is_unlimited_stock' => (bool) $v->is_unlimited_stock,
                'sku' => $v->sku,
                'delivery_charge_type' => $v->delivery_charge_type ?? 'default',
                'custom_delivery_inside' => $v->custom_delivery_inside !== null ? (float) $v->custom_delivery_inside : null,
                'custom_delivery_outside' => $v->custom_delivery_outside !== null ? (float) $v->custom_delivery_outside : null,
                'image_url' => $imgPath ? asset('storage/'.$imgPath) : null,
            ];
        })->values();

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'price' => (float) $product->price,
            'price_formatted' => Money::formatBn($product->price),
            'compare_at_price' => $hasDiscount ? (float) $product->compare_at_price : null,
            'compare_at_price_formatted' => $hasDiscount ? Money::formatBn($product->compare_at_price) : null,
            'free_delivery' => (bool) $product->free_delivery,
            'delivery_charge' => $product->delivery_charge !== null ? (float) $product->delivery_charge : null,
            'category_name' => $product->category?->name ?? 'Foodness',
            'image_url' => $imageUrl,
            'has_variants' => $product->variants->isNotEmpty(),
            'total_stock' => $product->totalStock(),
            'attributes' => $product->variationAttributes(),
            'color_codes' => $colorCodes,
            'variants' => $variants,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function transformProductForSearch(Product $p): array
    {
        $imagePath = $p->primaryImagePath();
        $imageUrl = $imagePath && file_exists(public_path('storage/'.$imagePath))
            ? asset('storage/'.$imagePath)
            : null;

        $hasDiscount = $p->compare_at_price && (float) $p->compare_at_price > (float) $p->price;
        $discountPct = $hasDiscount
            ? (int) round((((float) $p->compare_at_price - (float) $p->price) / (float) $p->compare_at_price) * 100)
            : 0;

        return [
            'id' => $p->id,
            'name' => $p->name,
            'slug' => $p->slug,
            'url' => route('product.show', $p),
            'price_formatted' => Money::formatBn($p->price),
            'compare_at_price_formatted' => $hasDiscount ? Money::formatBn($p->compare_at_price) : null,
            'has_discount' => $hasDiscount,
            'discount_pct' => $discountPct,
            'category_name' => $p->category?->name ?? 'Foodness',
            'image_url' => $imageUrl,
            'free_delivery' => (bool) $p->free_delivery,
        ];
    }
}
