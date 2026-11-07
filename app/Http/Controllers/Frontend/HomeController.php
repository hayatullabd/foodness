<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Story;
use App\Support\StoreSettings;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $productLimit = (int) StoreSettings::get('home_product_limit', '8');
        if ($productLimit <= 0) {
            $productLimit = 8;
        }

        $featuredProducts = Product::query()
            ->storeVisible()
            ->with(['images', 'category'])
            ->orderByDesc('is_featured')
            ->latest()
            ->take($productLimit)
            ->get();

        $categoryLimit = (int) StoreSettings::get('category_limit', '8');
        $categoryQuery = Category::query()
            ->active()
            ->withCount(['products' => fn ($q) => $q->storeVisible()])
            ->with(['products' => fn ($q) => $q->storeVisible()->with('images')->latest()->take(3)])
            ->orderBy('sort_order');

        if ($categoryLimit > 0) {
            $categoryQuery->take($categoryLimit);
        }

        $categories = $categoryQuery->get();

        $stories = Story::query()
            ->active()
            ->get();

        $bestSellingProducts = Product::query()
            ->storeVisible()
            ->with(['images', 'category'])
            ->where(function ($q) {
                $q->where('is_featured', true)
                    ->orWhere('sold', '>', 0);
            })
            ->latest()
            ->take(8)
            ->get();

        if ($bestSellingProducts->isEmpty()) {
            $bestSellingProducts = $featuredProducts;
        }

        $trendingProducts = Product::query()
            ->storeVisible()
            ->with(['images', 'category', 'variants'])
            ->where(function ($q) {
                $q->whereIn('slug', [
                    'talbina-half-combo',
                    'beetroot-powder',
                    'ashwagandha-powder',
                    'himalayan-pink-salt',
                    'diabetic-tea',
                    'foil-patali-gur',
                    'gol-patali-gur',
                    'chocolate-beej-gur',
                    'danadar-gur',
                    'khati-gawa-ghee',
                    'sundarban-kholisa-modhu',
                ])
                    ->orWhere('is_featured', true)
                    ->orWhere('id', '>=', 27);
            })
            ->latest()
            ->take(20)
            ->get();

        if ($trendingProducts->isEmpty()) {
            $trendingProducts = Product::query()
                ->storeVisible()
                ->with(['images', 'category', 'variants'])
                ->latest()
                ->take(15)
                ->get();
        }

        $allProducts = Product::query()
            ->storeVisible()
            ->with(['images', 'category', 'variants'])
            ->latest()
            ->take(40)
            ->get();

        // Dynamic Product Showcase Sections Configured via Admin Customizer
        $rawSections = StoreSettings::get('home_product_sections');
        $configuredSections = ! empty($rawSections) ? json_decode((string) $rawSections, true) : null;

        if (empty($configuredSections) || ! is_array($configuredSections)) {
            $gurCat = $categories->firstWhere('slug', 'khejur-gur') ?? $categories->firstWhere('slug', 'gur');
            $mangoCat = $categories->firstWhere('slug', 'fresh-mango') ?? $categories->firstWhere('slug', 'mango');
            $homemadeCat = $categories->firstWhere('slug', 'homemade');

            $defaultTabs = [
                ['id' => 'tab_all', 'label' => 'সব', 'type' => 'all', 'target_id' => ''],
            ];
            if ($mangoCat) {
                $defaultTabs[] = [
                    'id' => 'tab_cat_'.$mangoCat->id,
                    'label' => $mangoCat->name,
                    'type' => 'category',
                    'target_id' => (string) $mangoCat->id,
                ];
            }
            if ($gurCat) {
                $defaultTabs[] = [
                    'id' => 'tab_cat_'.$gurCat->id,
                    'label' => $gurCat->name,
                    'type' => 'category',
                    'target_id' => (string) $gurCat->id,
                ];
            }
            if ($homemadeCat) {
                $defaultTabs[] = [
                    'id' => 'tab_cat_'.$homemadeCat->id,
                    'label' => $homemadeCat->name,
                    'type' => 'category',
                    'target_id' => (string) $homemadeCat->id,
                ];
            }

            $configuredSections = [
                [
                    'id' => 'sec_default_1',
                    'is_active' => true,
                    'tag' => 'বাছাই করা সেরা পণ্য',
                    'title' => 'এ সপ্তাহের জনপ্রিয়',
                    'view_all_text' => 'সব পণ্য',
                    'view_all_url' => route('shop.index'),
                    'product_limit' => 8,
                    'product_source' => 'all',
                    'tabs' => $defaultTabs,
                ],
            ];
        }

        $dynamicSections = [];
        foreach ($configuredSections as $sec) {
            if (empty($sec['is_active'])) {
                continue;
            }

            $source = $sec['product_source'] ?? 'all';
            $sourceCatId = ! empty($sec['source_category_id']) ? (int) $sec['source_category_id'] : null;
            $limit = max(4, min(36, (int) ($sec['product_limit'] ?? 8)));
            $manualIds = collect($sec['product_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values();

            $secQuery = Product::query()
                ->storeVisible()
                ->with(['images', 'category', 'variants']);

            // Custom order: only the picked products, in the saved sequence.
            if ($source === 'manual') {
                if ($manualIds->isEmpty()) {
                    $sec['products'] = collect();
                    $dynamicSections[] = $sec;

                    continue;
                }

                $secQuery->whereIn('id', $manualIds->all());
            } elseif ($source === 'category' && $sourceCatId) {
                // Strictly only products belonging to this selected category
                $secQuery->where('category_id', $sourceCatId);

                // Auto-resolve view all url if default /shop
                $targetCategory = $categories->firstWhere('id', $sourceCatId);
                if ($targetCategory && (empty($sec['view_all_url']) || $sec['view_all_url'] === '/shop')) {
                    $sec['view_all_url'] = route('shop.category', $targetCategory->slug);
                }
            } elseif ($source === 'featured') {
                $secQuery->where('is_featured', true);
            } elseif ($source === 'best_selling') {
                $secQuery->where(function ($q) {
                    $q->where('sold', '>', 0)
                        ->orWhere('is_featured', true);
                })->orderByDesc('sold');
            }

            // 2. Tab-level category and subcategory IDs
            $tabCatIds = collect($sec['tabs'] ?? [])
                ->where('type', 'category')
                ->pluck('target_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->all();

            $tabSubcatIds = collect($sec['tabs'] ?? [])
                ->where('type', 'subcategory')
                ->pluck('target_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->all();

            // When source is not category-scoped and tabs specify targets, filter strictly to those targets
            if ($source !== 'category' && $source !== 'manual' && (! empty($tabCatIds) || ! empty($tabSubcatIds))) {
                $secQuery->where(function ($q) use ($tabCatIds, $tabSubcatIds) {
                    if (! empty($tabCatIds)) {
                        $q->whereIn('category_id', $tabCatIds);
                    }
                    if (! empty($tabSubcatIds)) {
                        $q->orWhereIn('subcategory_id', $tabSubcatIds);
                    }
                });
            }

            if ($source === 'manual') {
                // Order is applied after fetch so the saved sequence is kept.
            } elseif ($source !== 'best_selling') {
                $secQuery->latest();
            } else {
                $secQuery->latest('id');
            }

            // Fetch products without padding unrelated categories
            $hasMultipleTabs = ! empty($sec['tabs']) && count($sec['tabs']) > 1;
            $fetchLimit = $source === 'manual'
                ? $manualIds->count()
                : ($hasMultipleTabs ? max(32, $limit * 3) : $limit);
            $secProducts = $secQuery->take($fetchLimit)->get();

            if ($source === 'manual') {
                $rank = $manualIds->flip();
                $secProducts = $secProducts
                    ->sortBy(fn ($product) => $rank[$product->id] ?? 9999)
                    ->values();
            }

            $sec['products'] = $secProducts;
            $dynamicSections[] = $sec;
        }

        return view('store.home', [
            'featuredProducts' => $featuredProducts,
            'bestSellingProducts' => $bestSellingProducts,
            'trendingProducts' => $trendingProducts,
            'allProducts' => $allProducts,
            'categories' => $categories,
            'stories' => $stories,
            'dynamicSections' => $dynamicSections,
        ]);
    }
}
