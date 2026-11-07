<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Childcategory;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Setting;
use App\Models\ShippingCharge;
use App\Models\Size;
use App\Models\Subcategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EcommerceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedSettings();
        $this->seedShippingCharges();
        $this->seedCatalogAttributes();
        $this->seedCatalog();
    }

    private function seedCatalogAttributes(): void
    {
        $brands = ['Foodness', 'Heritage Cotton', 'Urban Loom'];
        foreach ($brands as $index => $name) {
            Brand::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'is_active' => true, 'sort_order' => $index + 1],
            );
        }

        $colors = [
            ['name' => 'Black', 'hex' => '#111111'],
            ['name' => 'White', 'hex' => '#ffffff'],
            ['name' => 'Navy', 'hex' => '#1e3a5f'],
            ['name' => 'Sage', 'hex' => '#3d5a4c'],
            ['name' => 'Gray', 'hex' => '#6b7280'],
        ];
        foreach ($colors as $index => $color) {
            Color::query()->updateOrCreate(
                ['slug' => Str::slug($color['name'])],
                [
                    'name' => $color['name'],
                    'hex' => $color['hex'],
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ],
            );
        }

        $sizes = ['XS', 'S', 'M', 'L', 'XL', 'Free Size', '1 BOX'];
        foreach ($sizes as $index => $name) {
            Size::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'is_active' => true, 'sort_order' => $index + 1],
            );
        }
    }

    private function seedSettings(): void
    {
        $settings = [
            'store_name' => 'Foodness',
            'store_phone' => '01700000000',
            'store_email' => 'hello@foodness.com',
            'store_address' => 'Dhaka, Bangladesh',
            'store_logo' => null,
            'currency' => 'BDT',
            'currency_symbol' => '৳',
        ];

        foreach ($settings as $key => $value) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value],
            );
        }
    }

    private function seedShippingCharges(): void
    {
        $charges = [
            ['name' => 'Inside Dhaka', 'amount' => 70, 'is_active' => true],
            ['name' => 'Outside Dhaka', 'amount' => 130, 'is_active' => true],
            ['name' => 'Free Shipping', 'amount' => 0, 'is_active' => false],
        ];

        foreach ($charges as $charge) {
            ShippingCharge::query()->updateOrCreate(
                ['name' => $charge['name']],
                $charge,
            );
        }
    }

    private function seedCatalog(): void
    {
        $catalog = [
            'Men' => [
                [
                    'name' => 'Classic Cotton Panjabi',
                    'sku' => 'MEN-PNJ-001',
                    'price' => 1890,
                    'compare_at_price' => 2490,
                    'stock' => 40,
                    'is_featured' => true,
                    'description' => 'Lightweight cotton panjabi suited for Eid and everyday wear.',
                ],
                [
                    'name' => 'Slim Fit Casual Shirt',
                    'sku' => 'MEN-SHT-002',
                    'price' => 1290,
                    'compare_at_price' => 1590,
                    'stock' => 55,
                    'is_featured' => true,
                    'description' => 'Breathable casual shirt with a clean collar and soft hand feel.',
                ],
            ],
            'Women' => [
                [
                    'name' => 'Embroidered Kurti Set',
                    'sku' => 'WMN-KRT-001',
                    'price' => 2190,
                    'compare_at_price' => 2790,
                    'stock' => 35,
                    'is_featured' => true,
                    'description' => 'Soft two-piece kurti set with subtle embroidery for daily elegance.',
                ],
                [
                    'name' => 'Linen Blend Abaya',
                    'sku' => 'WMN-ABY-002',
                    'price' => 2590,
                    'compare_at_price' => null,
                    'stock' => 25,
                    'is_featured' => false,
                    'description' => 'Flowing linen-blend abaya with understated finishing.',
                ],
            ],
            'Kids' => [
                [
                    'name' => 'Kids Printed T-Shirt',
                    'sku' => 'KID-TSH-001',
                    'price' => 690,
                    'compare_at_price' => 890,
                    'stock' => 60,
                    'is_featured' => true,
                    'description' => 'Soft cotton tee with playful print, ideal for everyday play.',
                ],
                [
                    'name' => 'Kids Festive Panjabi',
                    'sku' => 'KID-PNJ-002',
                    'price' => 1190,
                    'compare_at_price' => 1490,
                    'stock' => 30,
                    'is_featured' => false,
                    'description' => 'Festive kids panjabi with comfortable fit and easy care fabric.',
                ],
            ],
            'Accessories' => [
                [
                    'name' => 'Leather Belt',
                    'sku' => 'ACC-BLT-001',
                    'price' => 790,
                    'compare_at_price' => null,
                    'stock' => 45,
                    'is_featured' => false,
                    'description' => 'Durable everyday belt with a brushed metal buckle.',
                ],
                [
                    'name' => 'Cotton Hijab',
                    'sku' => 'ACC-HJB-002',
                    'price' => 490,
                    'compare_at_price' => 650,
                    'stock' => 80,
                    'is_featured' => true,
                    'description' => 'Breathable cotton hijab available in staple seasonal tones.',
                ],
            ],
        ];

        $sortOrder = 0;

        foreach ($catalog as $categoryName => $products) {
            $sortOrder++;

            $category = Category::query()->updateOrCreate(
                ['slug' => Str::slug($categoryName)],
                [
                    'name' => $categoryName,
                    'image' => null,
                    'status' => true,
                    'is_front' => $sortOrder <= 2,
                    'sort_order' => $sortOrder,
                ],
            );

            $subcategory = Subcategory::query()->updateOrCreate(
                ['slug' => Str::slug($categoryName.'-essentials')],
                [
                    'category_id' => $category->id,
                    'name' => $categoryName.' Essentials',
                    'is_active' => true,
                    'sort_order' => 1,
                ],
            );

            $childcategory = Childcategory::query()->updateOrCreate(
                ['slug' => Str::slug($categoryName.'-bestsellers')],
                [
                    'subcategory_id' => $subcategory->id,
                    'name' => $categoryName.' Bestsellers',
                    'is_active' => true,
                    'sort_order' => 1,
                ],
            );

            $brand = Brand::query()->where('slug', 'foodness')->first();

            foreach ($products as $index => $productData) {
                $product = Product::query()->updateOrCreate(
                    ['sku' => $productData['sku']],
                    [
                        'name' => $productData['name'],
                        'slug' => Str::slug($productData['name']),
                        'category_id' => $category->id,
                        'subcategory_id' => $subcategory->id,
                        'childcategory_id' => $childcategory->id,
                        'brand_id' => $brand?->id,
                        'price' => $productData['price'],
                        'compare_at_price' => $productData['compare_at_price'],
                        'stock' => $productData['stock'],
                        'unit' => 'PCS',
                        'description' => $productData['description'],
                        'source' => 'inhouse',
                        'is_featured' => $productData['is_featured'],
                        'is_hot' => $productData['is_featured'],
                        'is_active' => true,
                    ],
                );

                ProductImage::query()->updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'path' => 'products/placeholders/'.$product->sku.'.jpg',
                    ],
                    [
                        'is_primary' => true,
                        'sort_order' => $index,
                    ],
                );
            }
        }
    }
}
