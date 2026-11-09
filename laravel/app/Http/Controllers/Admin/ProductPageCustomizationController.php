<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Setting;
use App\Support\StoreSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductPageCustomizationController extends Controller
{
    /**
     * List of customizable keys for the single product page.
     *
     * @var list<string>
     */
    public const SETTING_KEYS = [
        // Action Buttons
        'product_add_to_cart_enabled',
        'product_add_to_cart_text',
        'product_add_to_cart_bg',
        'product_buy_now_enabled',
        'product_buy_now_text',
        'product_buy_now_bg',
        'product_whatsapp_btn_enabled',
        'product_whatsapp_btn_text',
        'product_whatsapp_number',
        'product_whatsapp_message',
        'product_whatsapp_btn_bg',
        'product_call_btn_enabled',
        'product_call_btn_text',
        'product_call_number',
        'product_call_btn_bg',

        // Gallery & Visuals
        'product_show_discount_badge',
        'product_discount_badge_text',
        'product_discount_badge_bg',
        'product_show_zoom',
        'product_show_gallery_arrows',
        'product_thumb_border_color',

        // Price & Info Elements
        'product_show_price_box',
        'product_price_box_bg',
        'product_price_color',
        'product_show_short_description',
        'product_show_sku',
        'product_show_stock',
        'product_show_category',
        'product_show_delivery_badge',
        'product_show_trust_badges',
        'product_trust_badge_1_icon',
        'product_trust_badge_1_title',
        'product_trust_badge_1_desc',
        'product_trust_badge_2_icon',
        'product_trust_badge_2_title',
        'product_trust_badge_2_desc',
        'product_trust_badge_3_icon',
        'product_trust_badge_3_title',
        'product_trust_badge_3_desc',

        // Tabs & Content
        'product_desc_tab_title',
        'product_delivery_payment_tab_title',
        'product_delivery_info_text',
        'product_payment_info_text',
        'product_show_related',
        'product_related_title',
    ];

    /**
     * Default settings dictionary.
     *
     * @return array<string, string>
     */
    public static function defaults(): array
    {
        return [
            // Action Buttons
            'product_add_to_cart_enabled' => '1',
            'product_add_to_cart_text' => 'কার্টে যোগ করুন',
            'product_add_to_cart_bg' => '#164f38',
            'product_buy_now_enabled' => '1',
            'product_buy_now_text' => 'ক্যাশ অন ডেলিভারিতে অর্ডার করুন',
            'product_buy_now_bg' => '#f6e8b7',
            'product_whatsapp_btn_enabled' => '1',
            'product_whatsapp_btn_text' => 'WhatsApp',
            'product_whatsapp_number' => '',
            'product_whatsapp_message' => 'হ্যালো, আমি এই পণ্যটি অর্ডার করতে চাই: {product_name} (মূল্য: {price})। প্রোডাক্ট লিংক: {url}',
            'product_whatsapp_btn_bg' => '#1d6245',
            'product_call_btn_enabled' => '1',
            'product_call_btn_text' => 'কল করুন',
            'product_call_number' => '',
            'product_call_btn_bg' => '#164f38',

            // Gallery & Visuals
            'product_show_discount_badge' => '1',
            'product_discount_badge_text' => '{discount}% ছাড়!',
            'product_discount_badge_bg' => '#A5000B',
            'product_show_zoom' => '1',
            'product_show_gallery_arrows' => '0',
            'product_thumb_border_color' => '#164f38',

            // Price & Info Elements
            'product_show_price_box' => '0',
            'product_price_box_bg' => '#f2f8f4',
            'product_price_color' => '#164f38',
            'product_show_short_description' => '1',
            'product_show_sku' => '1',
            'product_show_stock' => '0',
            'product_show_category' => '1',
            'product_show_delivery_badge' => '0',
            'product_show_trust_badges' => '1',
            'product_trust_badge_1_icon' => '✓',
            'product_trust_badge_1_title' => '১০০% প্রাকৃতিক',
            'product_trust_badge_1_desc' => 'কোনো কৃত্রিম রং নেই',
            'product_trust_badge_2_icon' => '✓',
            'product_trust_badge_2_title' => 'স্বাস্থ্যসম্মত',
            'product_trust_badge_2_desc' => 'পরিচ্ছন্ন পরিবেশে প্রস্তুত',
            'product_trust_badge_3_icon' => '✓',
            'product_trust_badge_3_title' => 'দেশি উপকরণ',
            'product_trust_badge_3_desc' => 'বিশ্বস্ত উৎস থেকে সংগৃহীত',

            // Tabs & Content
            'product_desc_tab_title' => 'পণ্যের বিবরণ',
            'product_delivery_payment_tab_title' => 'ডেলিভারি ও রিটার্ন',
            'product_delivery_info_text' => 'ঢাকার মধ্যে ১–২ এবং ঢাকার বাইরে ৩–৫ কর্মদিবসে ডেলিভারি। পণ্য ক্ষতিগ্রস্ত অবস্থায় পৌঁছালে ২৪ ঘণ্টার মধ্যে ছবিসহ জানান।',
            'product_payment_info_text' => '',
            'product_show_related' => '1',
            'product_related_title' => 'সম্পর্কিত পণ্য',
        ];
    }

    /**
     * Show the Single Product Page Customization Form.
     */
    public function edit(): View
    {
        $settings = StoreSettings::all();
        $sampleProduct = Product::query()
            ->active()
            ->with(['images', 'category', 'variants'])
            ->first();

        return view('admin.product-page.edit', [
            'settings' => $settings,
            'defaults' => self::defaults(),
            'sampleProduct' => $sampleProduct,
        ]);
    }

    /**
     * Update Single Product Page Customization Settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // Action Buttons
            'product_add_to_cart_enabled' => ['nullable', 'in:0,1'],
            'product_add_to_cart_text' => ['nullable', 'string', 'max:100'],
            'product_add_to_cart_bg' => ['nullable', 'string', 'max:25'],
            'product_buy_now_enabled' => ['nullable', 'in:0,1'],
            'product_buy_now_text' => ['nullable', 'string', 'max:100'],
            'product_buy_now_bg' => ['nullable', 'string', 'max:25'],
            'product_whatsapp_btn_enabled' => ['nullable', 'in:0,1'],
            'product_whatsapp_btn_text' => ['nullable', 'string', 'max:100'],
            'product_whatsapp_number' => ['nullable', 'string', 'max:50'],
            'product_whatsapp_message' => ['nullable', 'string', 'max:1000'],
            'product_whatsapp_btn_bg' => ['nullable', 'string', 'max:25'],
            'product_call_btn_enabled' => ['nullable', 'in:0,1'],
            'product_call_btn_text' => ['nullable', 'string', 'max:100'],
            'product_call_number' => ['nullable', 'string', 'max:50'],
            'product_call_btn_bg' => ['nullable', 'string', 'max:25'],

            // Gallery & Visuals
            'product_show_discount_badge' => ['nullable', 'in:0,1'],
            'product_discount_badge_text' => ['nullable', 'string', 'max:100'],
            'product_discount_badge_bg' => ['nullable', 'string', 'max:25'],
            'product_show_zoom' => ['nullable', 'in:0,1'],
            'product_show_gallery_arrows' => ['nullable', 'in:0,1'],
            'product_thumb_border_color' => ['nullable', 'string', 'max:25'],

            // Price & Info Elements
            'product_show_price_box' => ['nullable', 'in:0,1'],
            'product_price_box_bg' => ['nullable', 'string', 'max:25'],
            'product_price_color' => ['nullable', 'string', 'max:25'],
            'product_show_short_description' => ['nullable', 'in:0,1'],
            'product_show_sku' => ['nullable', 'in:0,1'],
            'product_show_stock' => ['nullable', 'in:0,1'],
            'product_show_category' => ['nullable', 'in:0,1'],
            'product_show_delivery_badge' => ['nullable', 'in:0,1'],
            'product_show_trust_badges' => ['nullable', 'in:0,1'],
            'product_trust_badge_1_icon' => ['nullable', 'string', 'max:50'],
            'product_trust_badge_1_title' => ['nullable', 'string', 'max:100'],
            'product_trust_badge_1_desc' => ['nullable', 'string', 'max:255'],
            'product_trust_badge_2_icon' => ['nullable', 'string', 'max:50'],
            'product_trust_badge_2_title' => ['nullable', 'string', 'max:100'],
            'product_trust_badge_2_desc' => ['nullable', 'string', 'max:255'],
            'product_trust_badge_3_icon' => ['nullable', 'string', 'max:50'],
            'product_trust_badge_3_title' => ['nullable', 'string', 'max:100'],
            'product_trust_badge_3_desc' => ['nullable', 'string', 'max:255'],

            // Tabs & Content
            'product_desc_tab_title' => ['nullable', 'string', 'max:100'],
            'product_delivery_payment_tab_title' => ['nullable', 'string', 'max:100'],
            'product_delivery_info_text' => ['nullable', 'string'],
            'product_payment_info_text' => ['nullable', 'string'],
            'product_reviews_tab_title' => ['nullable', 'string', 'max:100'],
            'product_reviews_enabled' => ['nullable', 'in:0,1'],
            'product_show_related' => ['nullable', 'in:0,1'],
            'product_related_title' => ['nullable', 'string', 'max:150'],
        ]);

        foreach (self::SETTING_KEYS as $key) {
            if ($request->has($key)) {
                Setting::query()->updateOrCreate(
                    ['key' => $key],
                    ['value' => $validated[$key] ?? null],
                );
            }
        }

        // Immediately purge settings cache so storefront renders new options
        StoreSettings::forget();

        return redirect()->route('admin.product-page.edit')
            ->with('status', 'Product page customization saved successfully! Changes are live on your storefront.');
    }

    /**
     * Reset Product Page Customization to Recommended Defaults.
     */
    public function resetDefaults(Request $request): RedirectResponse
    {
        $defaults = self::defaults();

        foreach ($defaults as $key => $value) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value],
            );
        }

        StoreSettings::forget();

        return redirect()->route('admin.product-page.edit')
            ->with('status', 'Product page customization settings reset to recommended defaults.');
    }
}
