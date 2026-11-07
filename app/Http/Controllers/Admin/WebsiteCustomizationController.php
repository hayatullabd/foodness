<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Subcategory;
use App\Services\ImageUploadService;
use App\Support\CheckoutVoices;
use App\Support\CheckoutVoiceSynthesizer;
use App\Support\StoreSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class WebsiteCustomizationController extends Controller
{
    /**
     * @var list<string>
     */
    private const CUSTOMIZATION_KEYS = [
        // Branding & Header
        'store_name',
        'store_phone',
        'store_email',
        'store_address',
        'store_tagline',
        'top_announcement_enabled',
        'top_announcement_text',
        'top_announcement_link_text',
        'top_announcement_link_url',
        'top_announcement_point_1',
        'top_announcement_point_2',
        'top_announcement_point_3',
        'header_sticky',
        'header_search_enabled',
        'header_search_text',
        'header_cart_text',
        'header_menu_links',
        'header_logo_height',

        // Hero Section & Stories
        'home_show_hero',
        'hero_slides',
        'hero_slider_autoplay',
        'hero_slider_width',
        'home_show_promo_banner',
        'promo_banner_title',
        'promo_banner_link',
        'promo_banner_width',
        'home_show_stories',
        'stories_section_title',
        'hero_badge',
        'hero_title',
        'hero_subtitle',
        'hero_tag',
        'hero_headline',
        'hero_offer_text',
        'hero_cta_text',
        'hero_cta_url',
        'hero_perk_1',
        'hero_perk_2',

        // Homepage Section Toggles & Headings
        'home_show_search_bar',
        'trending_search_keywords',
        'home_show_categories',
        'category_section_tag',
        'category_section_title',
        'category_section_subtitle',
        'category_card_style',
        'category_limit',
        'category_cols_mobile',
        'category_cols_desktop',
        'home_show_featured',
        'featured_section_tag',
        'featured_section_title',
        'featured_section_subtitle',
        'home_product_sections',
        'home_product_limit',
        'product_cols_mobile',
        'product_cols_tablet',
        'product_cols_desktop',
        'product_card_aspect',
        'home_sections_order',

        // Our Story & Video Reel
        'home_show_story_section',
        'story_section_tag',
        'story_section_title',
        'story_section_desc',
        'story_step_1_title',
        'story_step_1_desc',
        'story_step_2_title',
        'story_step_2_desc',
        'story_step_3_title',
        'story_step_3_desc',
        'story_btn_text',
        'story_btn_url',
        'story_reel_video_url',
        'story_reel_badge',
        'story_reel_tag',
        'story_reel_title',
        'story_reel_subtitle',

        // Interactive Gur Showcase (Timeline & Story Accordion)
        'home_show_gur_showcase',
        'gur_badge_text',
        'gur_title',
        'gur_title_highlight',
        'gur_subtitle',
        'gur_btn_text',
        'gur_btn_url',
        'gur_autoplay',
        'gur_card_duration',
        'gur_card_1_time',
        'gur_card_1_title',
        'gur_card_1_desc',
        'gur_card_1_video_url',
        'gur_card_2_product_id',
        'gur_card_2_time',
        'gur_card_2_title',
        'gur_card_2_desc',
        'gur_card_2_price',
        'gur_card_2_btn_text',
        'gur_card_2_btn_url',
        'gur_card_3_product_id',
        'gur_card_3_time',
        'gur_card_3_title',
        'gur_card_3_desc',
        'gur_card_3_price',
        'gur_card_3_btn_text',
        'gur_card_3_btn_url',
        'gur_card_4_product_id',
        'gur_card_4_time',
        'gur_card_4_title',
        'gur_card_4_desc',
        'gur_card_4_price',
        'gur_card_4_btn_text',
        'gur_card_4_btn_url',

        'home_show_promises',
        'promises_tag',
        'promises_title',
        'promises_subtitle',
        'home_show_faq',
        'faq_show_whatsapp',
        'faq_whatsapp_prefix',
        'faq_whatsapp_placeholder',
        'faq_tag',
        'faq_title',

        // Brand Promises 4 Cards
        'promise_1_title',
        'promise_1_desc',
        'promise_2_title',
        'promise_2_desc',
        'promise_3_title',
        'promise_3_desc',
        'promise_4_title',
        'promise_4_desc',

        // FAQ 6 Items
        'faq_q1',
        'faq_a1',
        'faq_q2',
        'faq_a2',
        'faq_q3',
        'faq_a3',
        'faq_q4',
        'faq_a4',
        'faq_q5',
        'faq_a5',
        'faq_q6',
        'faq_a6',
        'chat_avatar',

        // Footer & Social
        'footer_about_text',
        'footer_copyright',
        'footer_bottom_note',
        'footer_cod_badge',
        'footer_col2_title',
        'footer_show_categories',
        'footer_col3_title',
        'footer_delivery_info',
        'footer_payment_info',
        'footer_col4_title',
        'footer_delivery_partners_title',
        'footer_delivery_partners_text',
        'mobile_bottom_nav_enabled',
        'mobile_bottom_nav_items',
        'mobile_bottom_nav_show_labels',
        'mobile_bottom_nav_bg',
        'mobile_bottom_nav_active_color',
        'mobile_bottom_nav_inactive_color',
        'mobile_nav_home_label',
        'mobile_nav_shop_label',
        'mobile_nav_search_label',
        'mobile_nav_cart_label',

        // Mobile 3-Dash Menu (Drawer)
        'mobile_drawer_enabled',
        'mobile_drawer_header_title',
        'mobile_drawer_show_logo',
        'mobile_drawer_show_search',
        'mobile_drawer_search_placeholder',
        'mobile_drawer_menu_links',
        'mobile_drawer_show_categories',
        'mobile_drawer_categories_title',
        'mobile_drawer_show_account',
        'mobile_drawer_account_title',
        'mobile_drawer_show_promo',
        'mobile_drawer_promo_badge',
        'mobile_drawer_promo_text',
        'mobile_drawer_promo_url',
        'social_facebook',
        'social_instagram',
        'social_whatsapp',
        'social_youtube',
        'social_tiktok',

        // Floating Widgets
        'floating_whatsapp_enabled',
        'floating_whatsapp_number',
        'floating_whatsapp_message',
        'floating_call_enabled',
        'floating_call_number',

        // Customer Auth (Login & Register)
        'login_title',
        'login_subtitle',
        'register_title',
        'register_subtitle',

        // Colors & Custom Styling
        'theme_canvas_color',
        'theme_card_color',
        'theme_primary_color',
        'theme_accent_color',
        'theme_gold_color',
        'theme_ink_color',
        'theme_custom_css',
        'theme_custom_header_html',
        'theme_custom_footer_html',

        // Checkout page and order modal
        'checkout_terms_enabled',
        'checkout_voice_enabled',
        'checkout_voice_id',
        'checkout_voice_name_text',
        'checkout_voice_phone_text',
        'checkout_voice_address_text',
    ];

    public function edit(): View
    {
        $settings = StoreSettings::all();
        $categories = Category::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'slug']);
        $subcategories = Subcategory::query()->with('category:id,name')->orderBy('sort_order')->orderBy('name')->get(['id', 'category_id', 'name', 'slug']);
        $products = Product::query()->active()->orderBy('name')->get(['id', 'name', 'price', 'slug']);

        return view('admin.customization.edit', [
            'settings' => $settings,
            'categories' => $categories,
            'subcategories' => $subcategories,
            'products' => $products,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // Branding & Header
            'store_name' => ['nullable', 'string', 'max:255'],
            'store_phone' => ['nullable', 'string', 'max:50'],
            'store_email' => ['nullable', 'email', 'max:255'],
            'store_address' => ['nullable', 'string', 'max:1000'],
            'store_tagline' => ['nullable', 'string', 'max:255'],
            'store_logo' => ['nullable', 'image', 'max:4096'],
            'site_favicon' => ['nullable', 'image', 'max:2048'],
            'top_announcement_enabled' => ['nullable', 'in:0,1'],
            'top_announcement_text' => ['nullable', 'string', 'max:500'],
            'top_announcement_link_text' => ['nullable', 'string', 'max:100'],
            'top_announcement_link_url' => ['nullable', 'string', 'max:500'],
            'top_announcement_point_1' => ['nullable', 'string', 'max:500'],
            'top_announcement_point_2' => ['nullable', 'string', 'max:500'],
            'top_announcement_point_3' => ['nullable', 'string', 'max:500'],
            'header_sticky' => ['nullable', 'in:0,1'],
            'header_search_enabled' => ['nullable', 'in:0,1'],
            'header_search_text' => ['nullable', 'string', 'max:100'],
            'header_cart_text' => ['nullable', 'string', 'max:100'],
            'header_menu_links' => ['nullable', 'string'],
            'header_logo_height' => ['nullable', 'string', 'max:10'],

            // Hero Section & Stories
            'home_show_hero' => ['nullable', 'in:0,1'],
            'hero_slides' => ['nullable', 'string'],
            'hero_slider_autoplay' => ['nullable', 'string', 'max:20'],
            'hero_slider_width' => ['nullable', 'string', 'in:full,wide,boxed'],
            'hero_new_slides.*' => ['nullable', 'image', 'max:8192'],
            'home_show_promo_banner' => ['nullable', 'in:0,1'],
            'promo_banner_image' => ['nullable', 'image', 'max:8192'],
            'promo_banner_image_media_path' => ['nullable', 'string', 'max:500'],
            'remove_promo_banner_image' => ['nullable', 'in:0,1'],
            'promo_banner_title' => ['nullable', 'string', 'max:255'],
            'promo_banner_link' => ['nullable', 'string', 'max:500'],
            'promo_banner_width' => ['nullable', 'string', 'in:full,wide,boxed'],
            'home_show_stories' => ['nullable', 'in:0,1'],
            'stories_section_title' => ['nullable', 'string', 'max:255'],
            'hero_badge' => ['nullable', 'string', 'max:255'],
            'hero_title' => ['nullable', 'string', 'max:255'],
            'hero_subtitle' => ['nullable', 'string', 'max:500'],
            'hero_tag' => ['nullable', 'string', 'max:255'],
            'hero_headline' => ['nullable', 'string', 'max:255'],
            'hero_offer_text' => ['nullable', 'string', 'max:255'],
            'hero_custom_image' => ['nullable', 'image', 'max:8192'],
            'hero_cta_text' => ['nullable', 'string', 'max:100'],
            'hero_cta_url' => ['nullable', 'string', 'max:500'],
            'hero_perk_1' => ['nullable', 'string', 'max:255'],
            'hero_perk_2' => ['nullable', 'string', 'max:255'],

            // Section Toggles, Limits & Grid Layouts
            'home_show_categories' => ['nullable', 'in:0,1'],
            'category_section_tag' => ['nullable', 'string', 'max:255'],
            'category_section_title' => ['nullable', 'string', 'max:255'],
            'category_section_subtitle' => ['nullable', 'string', 'max:500'],
            'category_card_style' => ['nullable', 'string', 'in:card,circle'],
            'category_limit' => ['nullable', 'string', 'max:10'],
            'category_cols_mobile' => ['nullable', 'in:1,2'],
            'category_cols_desktop' => ['nullable', 'in:2,3,4,5,6'],
            'home_show_featured' => ['nullable', 'in:0,1'],
            'featured_section_tag' => ['nullable', 'string', 'max:255'],
            'featured_section_title' => ['nullable', 'string', 'max:255'],
            'featured_section_subtitle' => ['nullable', 'string', 'max:500'],
            'home_product_sections' => ['nullable', 'string'],
            'home_product_limit' => ['nullable', 'string', 'max:10'],
            'product_cols_mobile' => ['nullable', 'in:1,2'],
            'product_cols_tablet' => ['nullable', 'in:2,3,4'],
            'product_cols_desktop' => ['nullable', 'in:2,3,4,5,6'],
            'product_card_aspect' => ['nullable', 'in:3/4,1/1,4/5,auto'],
            'home_sections_order' => ['nullable', 'string', 'max:2000'],

            // Our Story & Video Reel
            'home_show_story_section' => ['nullable', 'in:0,1'],
            'story_section_tag' => ['nullable', 'string', 'max:255'],
            'story_section_title' => ['nullable', 'string', 'max:255'],
            'story_section_desc' => ['nullable', 'string', 'max:1000'],
            'story_step_1_title' => ['nullable', 'string', 'max:255'],
            'story_step_1_desc' => ['nullable', 'string', 'max:500'],
            'story_step_2_title' => ['nullable', 'string', 'max:255'],
            'story_step_2_desc' => ['nullable', 'string', 'max:500'],
            'story_step_3_title' => ['nullable', 'string', 'max:255'],
            'story_step_3_desc' => ['nullable', 'string', 'max:500'],
            'story_btn_text' => ['nullable', 'string', 'max:100'],
            'story_btn_url' => ['nullable', 'string', 'max:500'],
            'story_reel_video_url' => ['nullable', 'string', 'max:1000'],
            'story_reel_badge' => ['nullable', 'string', 'max:100'],
            'story_reel_tag' => ['nullable', 'string', 'max:100'],
            'story_reel_title' => ['nullable', 'string', 'max:255'],
            'story_reel_subtitle' => ['nullable', 'string', 'max:500'],
            'story_reel_video' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/quicktime,video/x-matroska', 'max:102400'],
            'story_reel_image' => ['nullable', 'image', 'max:8192'],
            'auto_story_reel_image_data' => ['nullable', 'string'],

            // Interactive Gur Showcase
            'home_show_gur_showcase' => ['nullable', 'in:0,1'],
            'gur_badge_text' => ['nullable', 'string', 'max:255'],
            'gur_title' => ['nullable', 'string', 'max:255'],
            'gur_title_highlight' => ['nullable', 'string', 'max:255'],
            'gur_subtitle' => ['nullable', 'string', 'max:500'],
            'gur_btn_text' => ['nullable', 'string', 'max:100'],
            'gur_btn_url' => ['nullable', 'string', 'max:500'],
            'gur_autoplay' => ['nullable', 'in:0,1'],
            'gur_card_duration' => ['nullable', 'string', 'max:10'],

            'gur_card_1_time' => ['nullable', 'string', 'max:100'],
            'gur_card_1_title' => ['nullable', 'string', 'max:255'],
            'gur_card_1_desc' => ['nullable', 'string', 'max:1000'],
            'gur_card_1_video_url' => ['nullable', 'string', 'max:1000'],
            'gur_card_1_video' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/quicktime,video/x-matroska', 'max:102400'],
            'remove_gur_card_1_video' => ['nullable', 'in:0,1'],
            'gur_card_1_image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg,gif', 'max:16384'],
            'gur_card_1_image_media_path' => ['nullable', 'string', 'max:500'],
            'remove_gur_card_1_image' => ['nullable', 'in:0,1'],

            'gur_card_2_product_id' => ['nullable', 'exists:products,id'],
            'gur_card_2_time' => ['nullable', 'string', 'max:100'],
            'gur_card_2_title' => ['nullable', 'string', 'max:255'],
            'gur_card_2_desc' => ['nullable', 'string', 'max:1000'],
            'gur_card_2_price' => ['nullable', 'string', 'max:50'],
            'gur_card_2_btn_text' => ['nullable', 'string', 'max:100'],
            'gur_card_2_btn_url' => ['nullable', 'string', 'max:500'],
            'gur_card_2_image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg,gif', 'max:16384'],
            'gur_card_2_image_media_path' => ['nullable', 'string', 'max:500'],
            'remove_gur_card_2_image' => ['nullable', 'in:0,1'],

            'gur_card_3_product_id' => ['nullable', 'exists:products,id'],
            'gur_card_3_time' => ['nullable', 'string', 'max:100'],
            'gur_card_3_title' => ['nullable', 'string', 'max:255'],
            'gur_card_3_desc' => ['nullable', 'string', 'max:1000'],
            'gur_card_3_price' => ['nullable', 'string', 'max:50'],
            'gur_card_3_btn_text' => ['nullable', 'string', 'max:100'],
            'gur_card_3_btn_url' => ['nullable', 'string', 'max:500'],
            'gur_card_3_image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg,gif', 'max:16384'],
            'gur_card_3_image_media_path' => ['nullable', 'string', 'max:500'],
            'remove_gur_card_3_image' => ['nullable', 'in:0,1'],

            'gur_card_4_product_id' => ['nullable', 'exists:products,id'],
            'gur_card_4_time' => ['nullable', 'string', 'max:100'],
            'gur_card_4_title' => ['nullable', 'string', 'max:255'],
            'gur_card_4_desc' => ['nullable', 'string', 'max:1000'],
            'gur_card_4_price' => ['nullable', 'string', 'max:50'],
            'gur_card_4_btn_text' => ['nullable', 'string', 'max:100'],
            'gur_card_4_btn_url' => ['nullable', 'string', 'max:500'],
            'gur_card_4_image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg,gif', 'max:16384'],
            'gur_card_4_image_media_path' => ['nullable', 'string', 'max:500'],
            'remove_gur_card_4_image' => ['nullable', 'in:0,1'],

            'home_show_promises' => ['nullable', 'in:0,1'],
            'promises_tag' => ['nullable', 'string', 'max:255'],
            'promises_title' => ['nullable', 'string', 'max:255'],
            'promises_subtitle' => ['nullable', 'string', 'max:500'],
            'home_show_faq' => ['nullable', 'in:0,1'],
            'faq_show_whatsapp' => ['nullable', 'in:0,1'],
            'faq_whatsapp_prefix' => ['nullable', 'string', 'max:255'],
            'faq_whatsapp_placeholder' => ['nullable', 'string', 'max:255'],
            'faq_tag' => ['nullable', 'string', 'max:255'],
            'faq_title' => ['nullable', 'string', 'max:255'],

            // Brand Promises 4 Cards
            'promise_1_title' => ['nullable', 'string', 'max:255'],
            'promise_1_desc' => ['nullable', 'string', 'max:500'],
            'promise_2_title' => ['nullable', 'string', 'max:255'],
            'promise_2_desc' => ['nullable', 'string', 'max:500'],
            'promise_3_title' => ['nullable', 'string', 'max:255'],
            'promise_3_desc' => ['nullable', 'string', 'max:500'],
            'promise_4_title' => ['nullable', 'string', 'max:255'],
            'promise_4_desc' => ['nullable', 'string', 'max:500'],

            // FAQ 6 Items
            'faq_q1' => ['nullable', 'string', 'max:255'],
            'faq_a1' => ['nullable', 'string', 'max:1000'],
            'faq_q2' => ['nullable', 'string', 'max:255'],
            'faq_a2' => ['nullable', 'string', 'max:1000'],
            'faq_q3' => ['nullable', 'string', 'max:255'],
            'faq_a3' => ['nullable', 'string', 'max:1000'],
            'faq_q4' => ['nullable', 'string', 'max:255'],
            'faq_a4' => ['nullable', 'string', 'max:1000'],
            'faq_q5' => ['nullable', 'string', 'max:255'],
            'faq_a5' => ['nullable', 'string', 'max:1000'],
            'faq_q6' => ['nullable', 'string', 'max:255'],
            'faq_a6' => ['nullable', 'string', 'max:1000'],
            'chat_avatar' => ['nullable', 'image', 'max:4096'],
            'chat_avatar_media_path' => ['nullable', 'string', 'max:255'],
            'remove_chat_avatar' => ['nullable', 'boolean'],

            // Footer & Social
            'footer_about_text' => ['nullable', 'string', 'max:1000'],
            'footer_copyright' => ['nullable', 'string', 'max:255'],
            'footer_bottom_note' => ['nullable', 'string', 'max:500'],
            'footer_cod_badge' => ['nullable', 'string', 'max:500'],
            'footer_col2_title' => ['nullable', 'string', 'max:255'],
            'footer_show_categories' => ['nullable', 'in:0,1'],
            'footer_col3_title' => ['nullable', 'string', 'max:255'],
            'footer_delivery_info' => ['nullable', 'string', 'max:255'],
            'footer_payment_info' => ['nullable', 'string', 'max:255'],
            'footer_col4_title' => ['nullable', 'string', 'max:255'],
            'footer_delivery_partners_title' => ['nullable', 'string', 'max:255'],
            'footer_delivery_partners_text' => ['nullable', 'string', 'max:500'],
            'mobile_bottom_nav_enabled' => ['nullable', 'in:0,1'],
            'mobile_bottom_nav_items' => ['nullable', 'string', 'max:50000'],
            'mobile_bottom_nav_show_labels' => ['nullable', 'in:0,1'],
            'mobile_bottom_nav_bg' => ['nullable', 'string', 'max:30'],
            'mobile_bottom_nav_active_color' => ['nullable', 'string', 'max:30'],
            'mobile_bottom_nav_inactive_color' => ['nullable', 'string', 'max:30'],
            'mobile_nav_home_label' => ['nullable', 'string', 'max:50'],
            'mobile_nav_shop_label' => ['nullable', 'string', 'max:50'],
            'mobile_nav_search_label' => ['nullable', 'string', 'max:50'],
            'mobile_nav_cart_label' => ['nullable', 'string', 'max:50'],

            // Mobile 3-Dash Menu (Drawer)
            'mobile_drawer_enabled' => ['nullable', 'in:0,1'],
            'mobile_drawer_header_title' => ['nullable', 'string', 'max:100'],
            'mobile_drawer_show_logo' => ['nullable', 'in:0,1'],
            'mobile_drawer_show_search' => ['nullable', 'in:0,1'],
            'mobile_drawer_search_placeholder' => ['nullable', 'string', 'max:150'],
            'mobile_drawer_menu_links' => ['nullable', 'string', 'max:50000'],
            'mobile_drawer_show_categories' => ['nullable', 'in:0,1'],
            'mobile_drawer_categories_title' => ['nullable', 'string', 'max:100'],
            'mobile_drawer_show_account' => ['nullable', 'in:0,1'],
            'mobile_drawer_account_title' => ['nullable', 'string', 'max:100'],
            'mobile_drawer_show_promo' => ['nullable', 'in:0,1'],
            'mobile_drawer_promo_badge' => ['nullable', 'string', 'max:50'],
            'mobile_drawer_promo_text' => ['nullable', 'string', 'max:255'],
            'mobile_drawer_promo_url' => ['nullable', 'string', 'max:500'],
            'social_facebook' => ['nullable', 'string', 'max:500'],
            'social_instagram' => ['nullable', 'string', 'max:500'],
            'social_whatsapp' => ['nullable', 'string', 'max:50'],
            'social_youtube' => ['nullable', 'string', 'max:500'],
            'social_tiktok' => ['nullable', 'string', 'max:500'],

            // Floating Widgets
            'floating_whatsapp_enabled' => ['nullable', 'in:0,1'],
            'floating_whatsapp_number' => ['nullable', 'string', 'max:50'],
            'floating_whatsapp_message' => ['nullable', 'string', 'max:500'],
            'floating_call_enabled' => ['nullable', 'in:0,1'],
            'floating_call_number' => ['nullable', 'string', 'max:50'],

            // Customer Auth (Login & Register)
            'login_title' => ['nullable', 'string', 'max:255'],
            'login_subtitle' => ['nullable', 'string', 'max:500'],
            'register_title' => ['nullable', 'string', 'max:255'],
            'register_subtitle' => ['nullable', 'string', 'max:500'],

            // Colors & Custom Styling
            'theme_canvas_color' => ['nullable', 'string', 'max:30'],
            'theme_card_color' => ['nullable', 'string', 'max:30'],
            'theme_primary_color' => ['nullable', 'string', 'max:30'],
            'theme_accent_color' => ['nullable', 'string', 'max:30'],
            'theme_gold_color' => ['nullable', 'string', 'max:30'],
            'theme_ink_color' => ['nullable', 'string', 'max:30'],
            'theme_custom_css' => ['nullable', 'string', 'max:50000'],
            'theme_custom_header_html' => ['nullable', 'string', 'max:20000'],
            'theme_custom_footer_html' => ['nullable', 'string', 'max:20000'],

            // Checkout page and order modal
            'checkout_terms_enabled' => ['nullable', 'in:0,1'],
            'checkout_voice_enabled' => ['nullable', 'in:0,1'],
            'checkout_voice_id' => ['nullable', 'string', 'in:'.implode(',', array_keys(CheckoutVoices::options()))],
            'checkout_voice_name_text' => ['nullable', 'string', 'max:180'],
            'checkout_voice_phone_text' => ['nullable', 'string', 'max:180'],
            'checkout_voice_address_text' => ['nullable', 'string', 'max:180'],
        ]);

        // Save textual settings
        foreach (self::CUSTOMIZATION_KEYS as $key) {
            if ($request->has($key)) {
                Setting::query()->updateOrCreate(
                    ['key' => $key],
                    ['value' => $validated[$key] ?? null],
                );
            }
        }

        // Handle Store Logo Upload
        if ($request->hasFile('store_logo')) {
            $existing = Setting::query()->where('key', 'store_logo')->value('value');
            if ($existing) {
                Storage::disk('public')->delete($existing);
            }
            $path = ImageUploadService::upload(
                $request->file('store_logo'),
                'settings',
                'store-logo'
            );
            Setting::query()->updateOrCreate(
                ['key' => 'store_logo'],
                ['value' => $path],
            );
        } elseif ($request->filled('store_logo_media_path') && ImageUploadService::exists($request->input('store_logo_media_path'))) {
            $path = ImageUploadService::sanitizePath($request->input('store_logo_media_path'));
            Setting::query()->updateOrCreate(
                ['key' => 'store_logo'],
                ['value' => $path],
            );
        }

        // Handle Favicon Upload
        if ($request->hasFile('site_favicon')) {
            $existing = Setting::query()->where('key', 'site_favicon')->value('value');
            if ($existing) {
                Storage::disk('public')->delete($existing);
            }
            $path = ImageUploadService::upload(
                $request->file('site_favicon'),
                'customization',
                'site-favicon'
            );
            Setting::query()->updateOrCreate(
                ['key' => 'site_favicon'],
                ['value' => $path],
            );

            try {
                @copy(public_path('storage/'.$path), public_path('favicon.ico'));
            } catch (\Throwable) {
            }
        } elseif ($request->filled('site_favicon_media_path') && ImageUploadService::exists($request->input('site_favicon_media_path'))) {
            $path = ImageUploadService::sanitizePath($request->input('site_favicon_media_path'));
            Setting::query()->updateOrCreate(
                ['key' => 'site_favicon'],
                ['value' => $path],
            );

            try {
                @copy(public_path('storage/'.$path), public_path('favicon.ico'));
            } catch (\Throwable) {
            }
        }

        // Handle Chat Avatar Upload (Customer Care & AI Chat)
        if ($request->hasFile('chat_avatar')) {
            $existing = Setting::query()->where('key', 'chat_avatar')->value('value');
            if ($existing) {
                Storage::disk('public')->delete($existing);
            }
            $path = ImageUploadService::upload(
                $request->file('chat_avatar'),
                'customization',
                'chat-avatar'
            );
            Setting::query()->updateOrCreate(
                ['key' => 'chat_avatar'],
                ['value' => $path],
            );
        } elseif ($request->filled('chat_avatar_media_path') && ImageUploadService::exists($request->input('chat_avatar_media_path'))) {
            $path = ImageUploadService::sanitizePath($request->input('chat_avatar_media_path'));
            Setting::query()->updateOrCreate(
                ['key' => 'chat_avatar'],
                ['value' => $path],
            );
        }

        if ($request->boolean('remove_chat_avatar')) {
            $existing = Setting::query()->where('key', 'chat_avatar')->value('value');
            if ($existing) {
                Storage::disk('public')->delete($existing);
            }
            Setting::query()->updateOrCreate(
                ['key' => 'chat_avatar'],
                ['value' => null],
            );
        }

        // Handle Custom Hero Image Upload
        if ($request->hasFile('hero_custom_image')) {
            $existing = Setting::query()->where('key', 'hero_custom_image')->value('value');
            if ($existing) {
                Storage::disk('public')->delete($existing);
            }
            $path = ImageUploadService::upload(
                $request->file('hero_custom_image'),
                'customization',
                'hero-custom-image'
            );
            Setting::query()->updateOrCreate(
                ['key' => 'hero_custom_image'],
                ['value' => $path],
            );
        } elseif ($request->filled('hero_custom_image_media_path') && ImageUploadService::exists($request->input('hero_custom_image_media_path'))) {
            $path = ImageUploadService::sanitizePath($request->input('hero_custom_image_media_path'));
            Setting::query()->updateOrCreate(
                ['key' => 'hero_custom_image'],
                ['value' => $path],
            );
        }

        // Handle Hero Image Removal if requested
        if ($request->boolean('remove_hero_custom_image')) {
            $existing = Setting::query()->where('key', 'hero_custom_image')->value('value');
            if ($existing) {
                Storage::disk('public')->delete($existing);
            }
            Setting::query()->updateOrCreate(
                ['key' => 'hero_custom_image'],
                ['value' => null],
            );
        }

        // Handle direct file uploads for new hero slides if submitted
        if ($request->hasFile('hero_new_slides')) {
            $rawHeroSlides = (string) $request->input('hero_slides', '[]');
            $existingSlides = json_decode($rawHeroSlides, true);
            if (! is_array($existingSlides)) {
                $existingSlides = [];
            }

            foreach ($request->file('hero_new_slides') as $newSlideFile) {
                if ($newSlideFile && $newSlideFile->isValid()) {
                    $slidePath = ImageUploadService::upload(
                        $newSlideFile,
                        'customization',
                        'hero-slide'
                    );
                    $existingSlides[] = [
                        'id' => 'slide_'.uniqid(),
                        'image' => $slidePath,
                        'title' => pathinfo($newSlideFile->getClientOriginalName(), PATHINFO_FILENAME),
                        'link_url' => '',
                        'is_active' => true,
                    ];
                }
            }

            Setting::query()->updateOrCreate(
                ['key' => 'hero_slides'],
                ['value' => json_encode($existingSlides)],
            );
        }

        // Handle Promotional Banner Image Upload or Media Picker Selection
        if ($request->hasFile('promo_banner_image')) {
            $existing = Setting::query()->where('key', 'promo_banner_image')->value('value');
            if ($existing && $existing !== 'none') {
                Storage::disk('public')->delete($existing);
            }
            $path = ImageUploadService::upload(
                $request->file('promo_banner_image'),
                'customization',
                'promo-banner'
            );
            Setting::query()->updateOrCreate(
                ['key' => 'promo_banner_image'],
                ['value' => $path],
            );
        } elseif ($request->filled('promo_banner_image_media_path')) {
            $rawPath = (string) $request->input('promo_banner_image_media_path');
            $path = ImageUploadService::sanitizePath($rawPath);
            if ($path && (ImageUploadService::exists($path) || file_exists(public_path('storage/'.$path)))) {
                Setting::query()->updateOrCreate(
                    ['key' => 'promo_banner_image'],
                    ['value' => $path],
                );
            }
        }

        // Handle Promotional Banner Image Removal if requested
        if ($request->boolean('remove_promo_banner_image')) {
            $existing = Setting::query()->where('key', 'promo_banner_image')->value('value');
            if ($existing && $existing !== 'none') {
                Storage::disk('public')->delete($existing);
            }
            Setting::query()->updateOrCreate(
                ['key' => 'promo_banner_image'],
                ['value' => 'none'],
            );
        }

        // Handle Story Reel Video Upload
        if ($request->hasFile('story_reel_video')) {
            $existing = Setting::query()->where('key', 'story_reel_video')->value('value');
            if ($existing) {
                Storage::disk('public')->delete($existing);
            }
            $path = $request->file('story_reel_video')->store('customization/videos', 'public');
            Setting::query()->updateOrCreate(
                ['key' => 'story_reel_video'],
                ['value' => $path],
            );
        }

        if ($request->boolean('remove_story_reel_video')) {
            $existing = Setting::query()->where('key', 'story_reel_video')->value('value');
            if ($existing) {
                Storage::disk('public')->delete($existing);
            }
            Setting::query()->updateOrCreate(
                ['key' => 'story_reel_video'],
                ['value' => null],
            );
        }

        // Handle Story Reel Poster Image Upload
        if ($request->hasFile('story_reel_image')) {
            $existing = Setting::query()->where('key', 'story_reel_image')->value('value');
            if ($existing) {
                Storage::disk('public')->delete($existing);
            }
            $path = ImageUploadService::upload(
                $request->file('story_reel_image'),
                'customization',
                'story-reel-poster'
            );
            Setting::query()->updateOrCreate(
                ['key' => 'story_reel_image'],
                ['value' => $path],
            );
        } elseif ($request->filled('story_reel_image_media_path') && ImageUploadService::exists($request->input('story_reel_image_media_path'))) {
            $path = ImageUploadService::sanitizePath($request->input('story_reel_image_media_path'));
            Setting::query()->updateOrCreate(
                ['key' => 'story_reel_image'],
                ['value' => $path],
            );
        } elseif ($request->filled('auto_story_reel_image_data')) {
            $existing = Setting::query()->where('key', 'story_reel_image')->value('value');
            if ($existing) {
                Storage::disk('public')->delete($existing);
            }
            $path = ImageUploadService::storeBase64(
                (string) $request->input('auto_story_reel_image_data'),
                'customization',
                'story-reel-auto-poster'
            );
            if ($path) {
                Setting::query()->updateOrCreate(
                    ['key' => 'story_reel_image'],
                    ['value' => $path],
                );
            }
        }

        if ($request->boolean('remove_story_reel_image')) {
            $existing = Setting::query()->where('key', 'story_reel_image')->value('value');
            if ($existing) {
                Storage::disk('public')->delete($existing);
            }
            Setting::query()->updateOrCreate(
                ['key' => 'story_reel_image'],
                ['value' => null],
            );
        }

        // Handle Gur Showcase Card 1 Video Upload
        if ($request->boolean('remove_gur_card_1_video')) {
            $existing = Setting::query()->where('key', 'gur_card_1_video')->value('value');
            if ($existing) {
                Storage::disk('public')->delete($existing);
            }
            Setting::query()->where('key', 'gur_card_1_video')->delete();
        } elseif ($request->hasFile('gur_card_1_video')) {
            $existing = Setting::query()->where('key', 'gur_card_1_video')->value('value');
            if ($existing) {
                Storage::disk('public')->delete($existing);
            }
            $path = $request->file('gur_card_1_video')->store('customization', 'public');
            Setting::query()->updateOrCreate(
                ['key' => 'gur_card_1_video'],
                ['value' => $path],
            );
        }

        // Handle Gur Showcase Card Images (1, 2, 3, 4)
        foreach ([1, 2, 3, 4] as $idx) {
            $key = "gur_card_{$idx}_image";
            $removeKey = "remove_{$key}";

            if ($request->boolean($removeKey)) {
                $existing = Setting::query()->where('key', $key)->value('value');
                if ($existing) {
                    Storage::disk('public')->delete($existing);
                }
                Setting::query()->where('key', $key)->delete();

                continue;
            }

            if ($request->hasFile($key)) {
                $existing = Setting::query()->where('key', $key)->value('value');
                if ($existing) {
                    Storage::disk('public')->delete($existing);
                }
                $path = ImageUploadService::upload(
                    $request->file($key),
                    'customization',
                    "gur-card-{$idx}"
                );
                Setting::query()->updateOrCreate(
                    ['key' => $key],
                    ['value' => $path],
                );
            } elseif ($request->filled("{$key}_media_path") && ImageUploadService::exists($request->input("{$key}_media_path"))) {
                $path = ImageUploadService::sanitizePath($request->input("{$key}_media_path"));
                Setting::query()->updateOrCreate(
                    ['key' => $key],
                    ['value' => $path],
                );
            }
        }

        $voice = CheckoutVoiceSynthesizer::sync(CheckoutVoices::phrasesFrom($validated));

        // Clear settings cache immediately
        StoreSettings::forget();

        $status = 'Website customization saved successfully! Changes are live on storefront.';
        if ($voice['regenerated']) {
            $status .= ' Checkout voice lines were recorded again.';
        }

        $redirect = redirect()->route('admin.customization.edit', ['tab' => $request->input('active_tab', 'branding')])
            ->with('status', $status);

        return $voice['error'] ? $redirect->with('error', $voice['error']) : $redirect;
    }

    /**
     * Upload a hero slide image via AJAX.
     */
    public function uploadHeroSlide(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'max:8192'],
        ]);

        $path = ImageUploadService::upload(
            $request->file('image'),
            'customization',
            'hero-slide'
        );

        return response()->json([
            'ok' => true,
            'path' => $path,
            'url' => asset('storage/'.$path),
        ]);
    }

    public function resetDefaults(Request $request): RedirectResponse
    {
        $defaults = [
            'home_show_hero' => '1',
            'home_show_stories' => '1',
            'stories_section_title' => 'BEAUTY STORIES',
            'hero_badge' => '★ FOODNESS · EXCLUSIVE FASHION ★',
            'hero_title' => 'New Exclusive Collection',
            'hero_subtitle' => 'Premium quality fabric & modern precision tailoring. Cash on delivery nationwide.',
            'hero_tag' => 'Original Quality',
            'hero_offer_text' => 'Special Offer Price',
            'hero_cta_text' => 'Explore Collection',
            'hero_perk_1' => '100% Original Quality Guaranteed',
            'hero_perk_2' => 'Nationwide Home Delivery · 7-Day Easy Replacement',

            'home_show_categories' => '1',
            'category_section_tag' => 'বাছাই করা কালেকশন',
            'category_section_title' => 'আমাদের ক্যাটাগরিসমূহ',
            'category_section_subtitle' => 'আমাদের প্রতিটি বিভাগের শতভাগ প্রাকৃতিক ও খাঁটি খাবার',
            'category_card_style' => 'card',
            'category_limit' => '8',
            'category_cols_mobile' => '2',
            'category_cols_desktop' => '4',

            'home_show_featured' => '1',
            'featured_section_tag' => 'Curated Selection',
            'featured_section_title' => 'Trending Collection',
            'featured_section_subtitle' => 'Tap on any product to order with Cash on Delivery.',
            'home_product_limit' => '8',
            'product_cols_mobile' => '2',
            'product_cols_tablet' => '3',
            'product_cols_desktop' => '4',
            'product_card_aspect' => '3/4',
            'home_show_promo_banner' => '1',
            'promo_banner_title' => 'Special Promotion',
            'promo_banner_link' => '/shop',
            'home_sections_order' => json_encode(['hero', 'stories', 'categories', 'gur_showcase', 'dynamic_sections', 'promo_banner', 'featured', 'story_reel', 'promises', 'faq']),

            // Interactive Gur Showcase Defaults
            'home_show_gur_showcase' => '1',
            'gur_badge_text' => 'Foodness™ অরিজিনাল',
            'gur_title' => 'খেজুরের',
            'gur_title_highlight' => 'খাঁটি গুড়',
            'gur_subtitle' => 'শীতের ভোরের রস, মাটির চুলায় জ্বাল।',
            'gur_btn_text' => 'অর্ডার করুন',
            'gur_btn_url' => '/shop',
            'gur_autoplay' => '1',
            'gur_card_duration' => '4.5',

            'gur_card_1_time' => 'ভোর ৬টা',
            'gur_card_1_title' => 'রস নামানো',
            'gur_card_1_desc' => 'শীতের কুয়াশায়, গাছের ডগা থেকে রস নামানো হয় সূর্য ওঠার আগেই। ভোরের হিমেল হাওয়ায় সংগৃহীত এই কাঁচা রসই খাঁটি গুড়ের মূল রহস্য।',
            'gur_card_1_video_url' => 'https://assets.mixkit.co/videos/preview/mixkit-morning-fog-in-a-forest-of-pines-42742-large.mp4',

            'gur_card_2_time' => 'সকাল ৮টা',
            'gur_card_2_title' => 'ঝোলা গুড়',
            'gur_card_2_desc' => 'রুটি, মুড়ি আর পায়েসে এক চামচ।',
            'gur_card_2_price' => '৳৩৮০',
            'gur_card_2_btn_text' => 'খেজুরের ঝোলা গুড় দেখুন',
            'gur_card_2_btn_url' => '/shop',

            'gur_card_3_time' => 'সকাল ১০টা',
            'gur_card_3_title' => 'বীজ গুড়',
            'gur_card_3_desc' => 'দানা দানা, চকলেটের মতো ঘন।',
            'gur_card_3_price' => '৳৫৫০',
            'gur_card_3_btn_text' => 'চকলেট বীজ গুড় দেখুন',
            'gur_card_3_btn_url' => '/shop',

            'gur_card_4_time' => 'দুপুর ১২টা',
            'gur_card_4_title' => 'পাটালি',
            'gur_card_4_desc' => 'জমে শক্ত, ভাঙলে ঘ্রাণে ভরপুর।',
            'gur_card_4_price' => '৳৩৫০',
            'gur_card_4_btn_text' => 'খেজুরের পাটালি গুড় দেখুন',
            'gur_card_4_btn_url' => '/shop',

            'home_show_promises' => '1',
            'promises_tag' => 'Our Promise',
            'promises_title' => 'What Matters Most To Us',
            'promises_subtitle' => 'At Foodness, we ensure international standards and maximum customer satisfaction in every garment.',

            'promise_1_title' => '100% Premium Fabric',
            'promise_1_desc' => 'No coarse or cheap materials — long-lasting colors and maximum comfort texture guaranteed.',
            'promise_2_title' => 'Master Tailoring',
            'promise_2_desc' => 'Modern trendy patterns and immaculate stitching designed for a confident, flattering fit.',
            'promise_3_title' => '7-Day Easy Exchange',
            'promise_3_desc' => 'Any size or fitting issue? Quick and hassle-free exchange within 7 days.',
            'promise_4_title' => 'Fast Express Delivery',
            'promise_4_desc' => 'Fast and reliable doorstep delivery nationwide through trusted courier partners.',

            'home_show_faq' => '1',
            'faq_show_whatsapp' => '1',
            'faq_whatsapp_prefix' => 'আমি Foodness ওয়েবসাইট থেকে জানতে চাইছি:',
            'faq_whatsapp_placeholder' => 'অন্য কোনো প্রশ্ন আছে? হোয়াটসঅ্যাপে মেসেজ দিন...',
            'faq_tag' => 'Got Questions?',
            'faq_title' => 'Frequently Asked Questions',

            'faq_q1' => 'How many days does delivery take?',
            'faq_a1' => 'Within Dhaka in 1–2 days and outside Dhaka within 2–3 days. Courier tracking number will be sent upon order confirmation.',
            'faq_q2' => 'How much is the delivery charge and how do I pay?',
            'faq_a2' => 'Payment is 100% Cash on Delivery (COD). No advance payment needed. Inspect the parcel and pay cash to the delivery agent.',
            'faq_q3' => 'What if there is a size or fitting issue?',
            'faq_a3' => 'No worries! Inform us within 7 days of receiving your item, and we will arrange an exchange smoothly.',
            'faq_q4' => 'Can I check the product before paying?',
            'faq_a4' => 'Yes, absolutely! Open the parcel in front of the delivery agent to check quality and fit before paying.',
            'faq_q5' => 'How is the fabric and stitching quality?',
            'faq_a5' => 'We use 100% original premium combed cotton and breathable fabrics. Master-tailored for durability and colorfastness.',
            'faq_q6' => 'Can I place bulk or wholesale orders?',
            'faq_a6' => 'Yes, for showroom or wholesale orders, call us directly or send a message on WhatsApp below.',
            'chat_avatar' => null,

            'top_announcement_enabled' => '1',
            'top_announcement_text' => 'New Exclusive Fashion Collection · Nationwide Cash on Delivery',
            'top_announcement_link_text' => 'Shop Now',
            'top_announcement_point_1' => '৳২,৫০০+ অর্ডারে <strong class="text-[#A5000B] font-bold">ফ্রি ডেলিভারি</strong>',
            'top_announcement_point_2' => 'সারা দেশে ক্যাশ অন ডেলিভারি',
            'top_announcement_point_3' => 'ঢাকায় ১-২ দিনে, বাইরে ২-৩ দিনে',
            'header_sticky' => '1',
            'header_search_enabled' => '1',
            'header_search_text' => 'খুঁজুন',
            'header_cart_text' => 'ব্যাগ',
            'header_logo_height' => '48',

            'footer_about_text' => 'Foodness — খাঁটি, প্রাকৃতিক ও নিরাপদ খাদ্যের বিশ্বস্ত ঠিকানা। শতভাগ অর্গানিক উপায়ে প্রস্তুতকৃত খাঁটি খেজুরের গুড় ও দেশীয় পণ্য নিয়ে আমরা আপনার পাশে।',
            'footer_bottom_note' => '১০০% ক্যাশ অন ডেলিভারি · নিরাপদ অনলাইন শপিং',
            'footer_cod_badge' => '● সারা দেশে ক্যাশ অন ডেলিভারি সুবিধা',
            'footer_col2_title' => 'কালেকশন',
            'footer_show_categories' => '1',
            'footer_col3_title' => 'সহায়তা ও ডেলিভারি',
            'footer_delivery_info' => 'ডেলিভারি: ২৪ থেকে ৭২ ঘণ্টা',
            'footer_payment_info' => 'পেমেন্ট: ক্যাশ অন ডেলিভারি',
            'footer_col4_title' => 'যোগাযোগ',
            'footer_delivery_partners_title' => 'ডেলিভারি পার্টনার্স',
            'footer_delivery_partners_text' => 'Cash on Delivery, Steadfast, Pathao',
            'mobile_bottom_nav_enabled' => '1',
            'mobile_bottom_nav_show_labels' => '1',
            'mobile_bottom_nav_bg' => '#FFFFFF',
            'mobile_bottom_nav_active_color' => '#013F1E',
            'mobile_bottom_nav_inactive_color' => '#6B7280',
            'mobile_nav_home_label' => 'হোম',
            'mobile_nav_shop_label' => 'শপ',
            'mobile_nav_search_label' => 'সার্চ',
            'mobile_nav_cart_label' => 'ব্যাগ',

            'mobile_drawer_enabled' => '1',
            'mobile_drawer_show_logo' => '1',
            'mobile_drawer_show_search' => '1',
            'mobile_drawer_search_placeholder' => 'Search products or collections...',
            'mobile_drawer_show_categories' => '1',
            'mobile_drawer_categories_title' => 'ক্যাটাগরি ও কালেকশন',
            'mobile_drawer_show_account' => '1',
            'mobile_drawer_account_title' => 'মাই অ্যাকাউন্ট',
            'mobile_drawer_show_promo' => '0',
            'mobile_drawer_promo_badge' => 'স্পেশাল অফার',
            'mobile_drawer_promo_text' => 'আজকের অর্ডারে বিশেষ ছাড়!',
            'mobile_drawer_promo_url' => '/shop',

            'floating_whatsapp_enabled' => '1',
            'floating_whatsapp_message' => 'Hello Foodness, I would like to know more about a product.',
            'floating_call_enabled' => '1',

            'login_title' => 'স্বাগতম',
            'login_subtitle' => 'আপনার অর্ডার ট্র্যাক করতে এবং অ্যাকাউন্ট পরিচালনা করতে লগইন করুন।',
            'register_title' => 'নতুন অ্যাকাউন্ট তৈরি করুন',
            'register_subtitle' => 'অ্যাকাউন্ট তৈরি করে আপনার ক্যাশ অন ডেলিভারি (COD) অর্ডার সহজে ট্র্যাক করুন ও দ্রুত কেনাকাটা সম্পন্ন করুন।',

            'checkout_voice_enabled' => '1',
            'checkout_voice_id' => 'nabanita',
            'checkout_voice_name_text' => 'আপনার সুন্দর নামটি লিখুন',
            'checkout_voice_phone_text' => 'আপনার মোবাইল নম্বরটি লিখুন',
            'checkout_voice_address_text' => 'আপনার ঠিকানা লিখুন',

            'theme_primary_color' => '#0B733E',
            'theme_accent_color' => '#D91C2B',
            'theme_gold_color' => '#E6A11D',
            'theme_ink_color' => '#13251B',
        ];

        foreach ($defaults as $k => $v) {
            Setting::query()->updateOrCreate(['key' => $k], ['value' => $v]);
        }

        $voice = CheckoutVoiceSynthesizer::sync(CheckoutVoices::phrasesFrom([]));

        StoreSettings::forget();

        $redirect = redirect()->route('admin.customization.edit')
            ->with('status', 'Default website customization restored successfully!');

        return $voice['error'] ? $redirect->with('error', $voice['error']) : $redirect;
    }
}
