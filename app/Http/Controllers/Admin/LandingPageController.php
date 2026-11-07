<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandingPage;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Services\ImageUploadService;
use App\Support\LandingUrl;
use App\Support\StoreSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LandingPageController extends Controller
{
    public function index(Request $request): View
    {
        $query = LandingPage::query()->with(['product.images', 'landingPageProducts'])->latest('id');

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($pq) use ($search): void {
                        $pq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === '1');
        }

        $landingPages = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => LandingPage::query()->count(),
            'active' => LandingPage::query()->where('is_active', true)->count(),
            'total_views' => (int) LandingPage::query()->sum('views_count'),
            'total_orders' => (int) LandingPage::query()->sum('orders_count'),
        ];

        return view('admin.landing-pages.index', compact('landingPages', 'stats'));
    }

    public function updateUrlPrefix(Request $request): Response
    {
        $validated = $request->validate([
            'landing_url_prefix' => [
                'required',
                'string',
                'max:40',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::notIn(LandingUrl::reserved()),
            ],
        ], [
            'landing_url_prefix.regex' => 'Use lowercase letters, numbers, and hyphens only.',
            'landing_url_prefix.not_in' => 'That word is already used by another store page.',
        ]);

        Setting::query()->updateOrCreate(
            ['key' => 'landing_url_prefix'],
            ['value' => $validated['landing_url_prefix']],
        );
        StoreSettings::forget();

        if (app()->routesAreCached()) {
            Artisan::call('route:clear');
        }

        $message = 'Landing page URL prefix updated.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }

    public function create(): View
    {
        $products = Product::query()
            ->active()
            ->with(['images', 'variants.color', 'variants.size'])
            ->orderBy('name')
            ->get();

        $defaultHtml = $this->getDefaultHtmlTemplate();
        $defaultCss = $this->getDefaultCssTemplate();
        $landingPage = new LandingPage;

        return view('admin.landing-pages.create', compact('products', 'defaultHtml', 'defaultCss', 'landingPage'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:landing_pages,slug'],
            'product_selection_type' => ['nullable', 'string', 'in:single,multiple'],
            'show_quantity' => ['nullable', 'boolean'],
            'product_id' => ['nullable', 'exists:products,id'],
            'products' => ['nullable', 'array'],
            'products.*.product_id' => ['required_with:products', 'exists:products,id'],
            'products.*.custom_price' => ['nullable', 'numeric', 'min:0'],
            'products.*.is_default' => ['nullable'],
            'custom_price' => ['nullable', 'numeric', 'min:0'],
            'custom_html' => ['nullable', 'string'],
            'custom_css' => ['nullable', 'string'],
            'custom_js' => ['nullable', 'string'],
            'checkout_heading' => ['nullable', 'string', 'max:255'],
            'checkout_subheading' => ['nullable', 'string', 'max:500'],
            'product_selection_label' => ['nullable', 'string', 'max:255'],
            'variant_label' => ['nullable', 'string', 'max:255'],
            'checkout_button_text' => ['nullable', 'string', 'max:255'],
            'checkout_fields_config' => ['nullable', 'array'],
            'checkout_color' => ['nullable', 'string', 'max:25'],
            'delivery_charge_type' => ['required', 'in:default,free,custom'],
            'custom_delivery_inside' => ['nullable', 'numeric', 'min:0'],
            'custom_delivery_outside' => ['nullable', 'numeric', 'min:0'],
            'variant_delivery_charges' => ['nullable', 'array'],
            'variant_delivery_charges.*.type' => ['nullable', 'string', 'in:default,free,custom'],
            'variant_delivery_charges.*.inside' => ['nullable', 'numeric', 'min:0'],
            'variant_delivery_charges.*.outside' => ['nullable', 'numeric', 'min:0'],
            'fb_pixel_id' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($request->has('variant_delivery_charges')) {
            $cleanVariantCharges = [];
            foreach ($request->input('variant_delivery_charges', []) as $vId => $vData) {
                $type = $vData['type'] ?? 'default';
                if (in_array($type, ['default', 'free', 'custom'], true)) {
                    $cleanVariantCharges[$vId] = [
                        'type' => $type,
                        'inside' => isset($vData['inside']) && $vData['inside'] !== '' ? (float) $vData['inside'] : null,
                        'outside' => isset($vData['outside']) && $vData['outside'] !== '' ? (float) $vData['outside'] : null,
                    ];
                    $pv = ProductVariant::query()->find($vId);
                    if ($pv) {
                        $pv->update([
                            'delivery_charge_type' => $type,
                            'custom_delivery_inside' => isset($vData['inside']) && $vData['inside'] !== '' ? (float) $vData['inside'] : null,
                            'custom_delivery_outside' => isset($vData['outside']) && $vData['outside'] !== '' ? (float) $vData['outside'] : null,
                        ]);
                    }
                }
            }
            $validated['variant_delivery_charges'] = $cleanVariantCharges;
        }

        $productsData = $request->input('products', []);
        if (empty($productsData) && ! empty($validated['product_id'])) {
            $productsData = [
                [
                    'product_id' => $validated['product_id'],
                    'custom_price' => $validated['custom_price'] ?? null,
                    'is_default' => true,
                ],
            ];
        }

        if (empty($productsData)) {
            return back()->withInput()->withErrors(['products' => 'At least one product must be selected.']);
        }

        $firstItem = reset($productsData);
        $validated['product_id'] = $validated['product_id'] ?? ($firstItem['product_id'] ?? null);
        $validated['custom_price'] = $validated['custom_price'] ?? ($firstItem['custom_price'] ?? null);
        $validated['product_selection_type'] = $validated['product_selection_type'] ?? 'single';
        $validated['show_quantity'] = $request->boolean('show_quantity');
        $validated['is_active'] = $request->boolean('is_active');
        unset($validated['products']);

        $landingPage = LandingPage::query()->create($validated);
        $this->syncLandingProducts($landingPage, $productsData);

        return redirect()
            ->route('admin.landing-pages.index')
            ->with('success', "Landing page '{$landingPage->title}' created successfully.");
    }

    public function edit(LandingPage $landingPage): View
    {
        $landingPage->loadMissing([
            'products.images',
            'products.variants.product',
            'products.variants.color',
            'products.variants.size',
            'landingPageProducts.product.images',
            'landingPageProducts.product.variants.product',
            'landingPageProducts.product.variants.color',
            'landingPageProducts.product.variants.size',
        ]);

        $products = Product::query()
            ->with(['images', 'variants.product', 'variants.color', 'variants.size'])
            ->orderBy('name')
            ->get();

        return view('admin.landing-pages.edit', compact('landingPage', 'products'));
    }

    public function update(Request $request, LandingPage $landingPage): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('landing_pages', 'slug')->ignore($landingPage->id)],
            'product_selection_type' => ['nullable', 'string', 'in:single,multiple'],
            'show_quantity' => ['nullable', 'boolean'],
            'product_id' => ['nullable', 'exists:products,id'],
            'products' => ['nullable', 'array'],
            'products.*.product_id' => ['required_with:products', 'exists:products,id'],
            'products.*.custom_price' => ['nullable', 'numeric', 'min:0'],
            'products.*.is_default' => ['nullable'],
            'custom_price' => ['nullable', 'numeric', 'min:0'],
            'custom_html' => ['nullable', 'string'],
            'custom_css' => ['nullable', 'string'],
            'custom_js' => ['nullable', 'string'],
            'checkout_heading' => ['nullable', 'string', 'max:255'],
            'checkout_subheading' => ['nullable', 'string', 'max:500'],
            'product_selection_label' => ['nullable', 'string', 'max:255'],
            'variant_label' => ['nullable', 'string', 'max:255'],
            'checkout_button_text' => ['nullable', 'string', 'max:255'],
            'checkout_fields_config' => ['nullable', 'array'],
            'checkout_color' => ['nullable', 'string', 'max:25'],
            'delivery_charge_type' => ['required', 'in:default,free,custom'],
            'custom_delivery_inside' => ['nullable', 'numeric', 'min:0'],
            'custom_delivery_outside' => ['nullable', 'numeric', 'min:0'],
            'variant_delivery_charges' => ['nullable', 'array'],
            'variant_delivery_charges.*.type' => ['nullable', 'string', 'in:default,free,custom'],
            'variant_delivery_charges.*.inside' => ['nullable', 'numeric', 'min:0'],
            'variant_delivery_charges.*.outside' => ['nullable', 'numeric', 'min:0'],
            'fb_pixel_id' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($request->has('variant_delivery_charges')) {
            $cleanVariantCharges = [];
            foreach ($request->input('variant_delivery_charges', []) as $vId => $vData) {
                $type = $vData['type'] ?? 'default';
                if (in_array($type, ['default', 'free', 'custom'], true)) {
                    $cleanVariantCharges[$vId] = [
                        'type' => $type,
                        'inside' => isset($vData['inside']) && $vData['inside'] !== '' ? (float) $vData['inside'] : null,
                        'outside' => isset($vData['outside']) && $vData['outside'] !== '' ? (float) $vData['outside'] : null,
                    ];
                    $pv = ProductVariant::query()->find($vId);
                    if ($pv) {
                        $pv->update([
                            'delivery_charge_type' => $type,
                            'custom_delivery_inside' => isset($vData['inside']) && $vData['inside'] !== '' ? (float) $vData['inside'] : null,
                            'custom_delivery_outside' => isset($vData['outside']) && $vData['outside'] !== '' ? (float) $vData['outside'] : null,
                        ]);
                    }
                }
            }
            $validated['variant_delivery_charges'] = $cleanVariantCharges;
        }

        $productsData = $request->input('products', []);
        if (empty($productsData) && ! empty($validated['product_id'])) {
            $productsData = [
                [
                    'product_id' => $validated['product_id'],
                    'custom_price' => $validated['custom_price'] ?? null,
                    'is_default' => true,
                ],
            ];
        }

        if (empty($productsData)) {
            return back()->withInput()->withErrors(['products' => 'At least one product must be selected.']);
        }

        $firstItem = reset($productsData);
        $validated['product_id'] = $validated['product_id'] ?? ($firstItem['product_id'] ?? null);
        $validated['custom_price'] = $validated['custom_price'] ?? ($firstItem['custom_price'] ?? null);
        $validated['product_selection_type'] = $validated['product_selection_type'] ?? 'single';
        $validated['show_quantity'] = $request->boolean('show_quantity');
        $validated['is_active'] = $request->boolean('is_active');
        unset($validated['products']);

        $landingPage->update($validated);
        $this->syncLandingProducts($landingPage, $productsData);

        return redirect()
            ->route('admin.landing-pages.index')
            ->with('success', "Landing page '{$landingPage->title}' updated successfully.");
    }

    private function syncLandingProducts(LandingPage $landingPage, array $productsData): void
    {
        $syncData = [];
        $hasDefault = false;

        foreach ($productsData as $index => $item) {
            $pId = (int) ($item['product_id'] ?? 0);
            if ($pId <= 0) {
                continue;
            }

            $isDefault = ! empty($item['is_default']);
            if ($isDefault) {
                $hasDefault = true;
            }

            $customPrice = isset($item['custom_price']) && $item['custom_price'] !== ''
                ? number_format((float) $item['custom_price'], 2, '.', '')
                : null;

            $syncData[$pId] = [
                'custom_price' => $customPrice,
                'is_default' => $isDefault,
                'sort_order' => $index,
            ];
        }

        if (! $hasDefault && ! empty($syncData)) {
            $firstKey = array_key_first($syncData);
            $syncData[$firstKey]['is_default'] = true;
        }

        $landingPage->products()->sync($syncData);
    }

    public function destroy(LandingPage $landingPage): RedirectResponse
    {
        $title = $landingPage->title;
        $landingPage->delete();

        return redirect()
            ->route('admin.landing-pages.index')
            ->with('success', "Landing page '{$title}' deleted successfully.");
    }

    public function toggle(LandingPage $landingPage): JsonResponse|RedirectResponse
    {
        $landingPage->is_active = ! $landingPage->is_active;
        $landingPage->save();

        if (request()->wantsJson()) {
            return response()->json([
                'ok' => true,
                'is_active' => $landingPage->is_active,
                'message' => $landingPage->is_active ? 'Landing page activated.' : 'Landing page deactivated.',
            ]);
        }

        return back()->with('success', $landingPage->is_active ? 'Landing page activated.' : 'Landing page deactivated.');
    }

    public function duplicate(LandingPage $landingPage): RedirectResponse
    {
        $landingPage->loadMissing('landingPageProducts');

        $clone = $landingPage->replicate();
        $clone->title = $landingPage->title.' (Copy)';
        $clone->slug = Str::slug($landingPage->slug.'-copy-'.Str::random(5));
        $clone->views_count = 0;
        $clone->orders_count = 0;
        $clone->save();

        foreach ($landingPage->landingPageProducts as $lpp) {
            $clone->landingPageProducts()->create([
                'product_id' => $lpp->product_id,
                'custom_price' => $lpp->custom_price,
                'is_default' => $lpp->is_default,
                'sort_order' => $lpp->sort_order,
            ]);
        }

        return redirect()
            ->route('admin.landing-pages.edit', $clone)
            ->with('success', "Landing page duplicated successfully as '{$clone->title}'.");
    }

    public function uploadMedia(Request $request): JsonResponse
    {
        $request->validate([
            'media' => ['required', 'file', 'max:51200', 'mimes:jpg,jpeg,png,webp,gif,mp4,webm,mov'],
        ]);

        $file = $request->file('media');

        $ext = strtolower((string) $file->guessExtension());

        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'mp4', 'webm', 'mov'], true)) {
            return response()->json(['ok' => false, 'message' => 'File type is not allowed.'], 422);
        }

        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            $path = ImageUploadService::upload($file, 'landing-pages', 'landing');
        } else {
            $filename = 'video-'.now()->format('YmdHis').'-'.Str::lower(Str::random(6)).'.'.$ext;
            $path = $file->storeAs('landing-pages/videos', $filename, 'public');
        }

        $url = asset('storage/'.$path);

        return response()->json([
            'ok' => true,
            'url' => $url,
            'path' => $path,
            'name' => $file->getClientOriginalName(),
        ]);
    }

    public function getProductImages(Product $product): JsonResponse
    {
        $product->loadMissing(['images', 'variants.color', 'variants.size']);

        $images = collect();

        foreach ($product->images as $img) {
            if (! $img->path) {
                continue;
            }
            $images->push([
                'url' => asset('storage/'.$img->path),
                'path' => $img->path,
                'label' => $img->is_primary ? 'Primary Image' : 'Gallery Image',
            ]);
        }

        foreach ($product->variants as $variant) {
            $vPath = $variant->primaryImagePath();
            if ($vPath) {
                $images->push([
                    'url' => asset('storage/'.$vPath),
                    'path' => $vPath,
                    'label' => 'Variant: '.($variant->label() ?? 'Variant'),
                ]);
            }
        }

        return response()->json([
            'ok' => true,
            'images' => $images->unique('path')->values(),
        ]);
    }

    private function getDefaultHtmlTemplate(): string
    {
        return <<<'HTML'
<!-- Hero Banner Section -->
<section class="py-10 px-4 max-w-4xl mx-auto text-center">
    <span class="inline-block px-4 py-1.5 rounded-full text-sm font-semibold bg-red-100 text-red-600 mb-4 animate-pulse">
        🔥 স্পেশাল সীমিত সময়ের ধামাকা অফার!
    </span>
    <h1 class="text-3xl md:text-5xl font-black text-slate-900 leading-tight mb-4">
        {{product_title}}
    </h1>
    <p class="text-lg md:text-xl text-slate-600 mb-8 max-w-2xl mx-auto">
        ১০০% প্রিমিয়াম কোয়ালিটি ও অরিজিনাল প্রোডাক্ট। সারাদেশে হোম ডেলিভারি ও পণ্য হাতে পেয়ে টাকা পরিশোধের সুবিধা!
    </p>

    <!-- Product Image or Video -->
    <div class="mb-8 rounded-2xl overflow-hidden shadow-2xl border-4 border-white inline-block max-w-lg w-full">
        <img src="{{product_image_url}}" alt="{{product_title}}" class="w-full h-auto object-cover">
    </div>

    <!-- CTA Button that scrolls to Checkout Form -->
    <div class="mb-12">
        <a href="#checkout-section" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-emerald-600 hover:bg-emerald-700 text-white text-xl font-bold rounded-xl shadow-lg hover:shadow-emerald-500/25 transition transform hover:-translate-y-0.5">
            <span>👉 এখনই অর্ডার করুন</span>
        </a>
    </div>
</section>

<!-- Highlights / Features Section -->
<section class="py-12 bg-slate-100/70 border-y border-slate-200 px-4">
    <div class="max-w-4xl mx-auto">
        <h2 class="text-2xl md:text-3xl font-extrabold text-center text-slate-900 mb-8">
            কেন আমাদের প্রোডাক্টটি সেরা?
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200/80 text-center">
                <div class="w-12 h-12 mx-auto mb-4 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center text-2xl font-bold">✨</div>
                <h3 class="font-bold text-lg text-slate-800 mb-2">১০০% প্রিমিয়াম মান</h3>
                <p class="text-sm text-slate-600">উচ্চমানের উপাদানে তৈরি যা আপনাকে দেবে দীর্ঘস্থায়ী ব্যবহারের নিশ্চিয়তা।</p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200/80 text-center">
                <div class="w-12 h-12 mx-auto mb-4 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center text-2xl font-bold">🚚</div>
                <h3 class="font-bold text-lg text-slate-800 mb-2">দ্রুত ডেলিভারি</h3>
                <p class="text-sm text-slate-600">অর্ডার কনফার্মের পর ৪৮ থেকে ৭২ ঘণ্টার মধ্যে সারাদেশে ডেলিভারি।</p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200/80 text-center">
                <div class="w-12 h-12 mx-auto mb-4 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-2xl font-bold">🛡️</div>
                <h3 class="font-bold text-lg text-slate-800 mb-2">ক্যাশ অন ডেলিভারি</h3>
                <p class="text-sm text-slate-600">পণ্য হাতে পেয়ে চেক করে সম্পূর্ণ মূল্য পরিশোধ করার সুবিধা।</p>
            </div>
        </div>
    </div>
</section>

<!-- Video Review Section (Optional YouTube Embed) -->
<section class="py-12 px-4 max-w-3xl mx-auto text-center">
    <h2 class="text-2xl md:text-3xl font-extrabold text-slate-900 mb-6">
        ভিডিওতে এক নজরে প্রোডাক্টটি দেখে নিন
    </h2>
    <div class="relative w-full aspect-video rounded-2xl overflow-hidden shadow-xl border-2 border-slate-200">
        <!-- Replace YOUR_VIDEO_ID with your YouTube video ID -->
        <iframe class="w-full h-full" src="https://www.youtube.com/embed/dQw4w9WgXcQ" title="Product Video" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
    </div>
</section>

<!-- Embedded Checkout Form Placeholder -->
[checkout_form]

<!-- Customer Reviews / Trust Footer Section -->
<section class="py-10 px-4 max-w-4xl mx-auto text-center border-t border-slate-200 mt-12 text-slate-500 text-sm">
    <p class="font-medium">কোনো জিজ্ঞাসা বা সহায়তার জন্য কল করুন: <span class="font-bold text-slate-800">01XXXXXXXXX</span></p>
    <p class="mt-2 text-xs">© All Rights Reserved.</p>
</section>
HTML;
    }

    private function getDefaultCssTemplate(): string
    {
        return <<<'CSS'
/*
 * SAFE CSS RULES
 * 1. Style your own sections with class names (.hero-section, .feature-card, etc.)
 * 2. Do NOT target #checkout-section / #landing-order-form — Checkout tab controls that
 * 3. Avoid blanketing resets like: * { margin:0; padding:0; }  (can fight layout utilities)
 * 4. Prefer Tailwind classes in HTML; use CSS only for custom look & animations
 */
html {
    scroll-behavior: smooth;
}

body {
    background-color: #f8fafc;
    color: #1e293b;
}
CSS;
    }
}
