<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Childcategory;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Size;
use App\Models\Subcategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command?->info('Seeding demo attributes and brands...');
        $brand = Brand::query()->firstOrCreate(
            ['slug' => 'foodness'],
            ['name' => 'Foodness', 'is_active' => true, 'sort_order' => 1]
        );

        $colorsData = [
            ['name' => 'Olive Green', 'slug' => 'olive-green', 'hex' => '#4a5d4e'],
            ['name' => 'Royal White', 'slug' => 'royal-white', 'hex' => '#ffffff'],
            ['name' => 'Navy Blue', 'slug' => 'navy-blue', 'hex' => '#1b2a4a'],
            ['name' => 'Crimson Red', 'slug' => 'crimson-red', 'hex' => '#9b111e'],
            ['name' => 'Mustard Gold', 'slug' => 'mustard-gold', 'hex' => '#d4af37'],
            ['name' => 'Charcoal Black', 'slug' => 'charcoal-black', 'hex' => '#222222'],
            ['name' => 'Sky Blue', 'slug' => 'sky-blue', 'hex' => '#87ceeb'],
            ['name' => 'Antique Brown', 'slug' => 'antique-brown', 'hex' => '#6e473b'],
        ];

        $colors = [];
        foreach ($colorsData as $index => $c) {
            $colors[$c['slug']] = Color::query()->updateOrCreate(
                ['slug' => $c['slug']],
                [
                    'name' => $c['name'],
                    'hex' => $c['hex'],
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }

        $sizesData = ['M', 'L', 'XL', 'XXL', '30', '32', '34', 'Free Size'];
        $sizes = [];
        foreach ($sizesData as $index => $s) {
            $sizes[$s] = Size::query()->updateOrCreate(
                ['slug' => Str::slug($s)],
                [
                    'name' => $s,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }

        // Ensure directories exist
        Storage::disk('public')->makeDirectory('products');
        Storage::disk('public')->makeDirectory('categories');

        $catalog = $this->getCatalogData();

        $categoryOrder = 0;
        foreach ($catalog as $categoryName => $catData) {
            $categoryOrder++;
            $this->command?->info("Seeding category: {$categoryName}");

            $catImageRelPath = $this->downloadImage(
                $catData['category_image'],
                'categories/'.Str::slug($categoryName).'.jpg'
            );

            $category = Category::query()->updateOrCreate(
                ['slug' => Str::slug($categoryName)],
                [
                    'name' => $categoryName,
                    'image' => $catImageRelPath,
                    'status' => true,
                    'is_front' => true,
                    'sort_order' => $categoryOrder,
                ]
            );

            foreach ($catData['subcategories'] as $subName => $products) {
                $subcategory = Subcategory::query()->updateOrCreate(
                    ['slug' => Str::slug($categoryName.'-'.$subName)],
                    [
                        'category_id' => $category->id,
                        'name' => $subName,
                        'is_active' => true,
                        'sort_order' => 1,
                    ]
                );

                $childcategory = Childcategory::query()->updateOrCreate(
                    ['slug' => Str::slug($categoryName.'-'.$subName.'-trending')],
                    [
                        'subcategory_id' => $subcategory->id,
                        'name' => 'Trending '.$subName,
                        'is_active' => true,
                        'sort_order' => 1,
                    ]
                );

                foreach ($products as $item) {
                    $this->command?->info("  -> Product: {$item['name']}");

                    $product = Product::query()->updateOrCreate(
                        ['sku' => $item['sku']],
                        [
                            'name' => $item['name'],
                            'slug' => Str::slug($item['name']).'-'.Str::lower($item['sku']),
                            'category_id' => $category->id,
                            'subcategory_id' => $subcategory->id,
                            'childcategory_id' => $childcategory->id,
                            'brand_id' => $brand->id,
                            'price' => $item['price'],
                            'compare_at_price' => $item['compare_at_price'],
                            'stock' => $item['stock'] ?? 50,
                            'sold' => rand(15, 120),
                            'unit' => 'PCS',
                            'short_description' => $item['short_desc'],
                            'description' => $item['description'],
                            'source' => 'inhouse',
                            'is_featured' => $item['is_featured'] ?? true,
                            'is_hot' => $item['is_hot'] ?? true,
                            'is_active' => true,
                            'meta_title' => $item['name'].' | Foodness Fashion',
                            'meta_description' => Str::limit(strip_tags($item['short_desc']), 150),
                        ]
                    );

                    // Download primary image
                    $primaryPath = $this->downloadImage(
                        $item['image'],
                        'products/'.Str::lower($item['sku']).'.jpg'
                    );

                    ProductImage::query()->updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'path' => $primaryPath,
                        ],
                        [
                            'is_primary' => true,
                            'sort_order' => 1,
                        ]
                    );

                    // Gallery image if available
                    if (! empty($item['gallery'])) {
                        $galleryPath = $this->downloadImage(
                            $item['gallery'],
                            'products/'.Str::lower($item['sku']).'-gallery.jpg'
                        );

                        ProductImage::query()->updateOrCreate(
                            [
                                'product_id' => $product->id,
                                'path' => $galleryPath,
                            ],
                            [
                                'is_primary' => false,
                                'sort_order' => 2,
                            ]
                        );
                    }

                    // Variants setup
                    if (! empty($item['variants'])) {
                        foreach ($item['variants'] as $vIndex => $varData) {
                            $colorModel = isset($varData['color']) && isset($colors[$varData['color']]) ? $colors[$varData['color']] : null;
                            $sizeModel = isset($varData['size']) && isset($sizes[$varData['size']]) ? $sizes[$varData['size']] : null;

                            ProductVariant::query()->updateOrCreate(
                                [
                                    'product_id' => $product->id,
                                    'sku' => $item['sku'].'-'.($sizeModel?->name ?? 'DEF').($colorModel ? '-'.Str::upper(substr($colorModel->slug, 0, 3)) : ''),
                                ],
                                [
                                    'name' => trim(($colorModel?->name ?? '').' '.($sizeModel?->name ?? '')),
                                    'color_id' => $colorModel?->id,
                                    'size_id' => $sizeModel?->id,
                                    'size_name' => $sizeModel?->name,
                                    'price' => $item['price'],
                                    'compare_at_price' => $item['compare_at_price'],
                                    'stock' => rand(15, 45),
                                    'sort_order' => $vIndex + 1,
                                    'attribute_values' => array_filter([
                                        'Color' => $colorModel?->name,
                                        'Size' => $sizeModel?->name,
                                    ]),
                                ]
                            );
                        }
                    }
                }
            }
        }

        $this->command?->info('Demo products seeded successfully!');
    }

    /**
     * Download image from free source or generate high quality SVG placeholder if offline.
     */
    private function downloadImage(string $url, string $targetRelativePath): string
    {
        $storagePath = storage_path('app/public/'.$targetRelativePath);
        $publicPath = public_path('storage/'.$targetRelativePath);

        // If already downloaded and exists, reuse it
        if (file_exists($storagePath) && filesize($storagePath) > 5000) {
            return $targetRelativePath;
        }

        @mkdir(dirname($storagePath), 0755, true);
        @mkdir(dirname($publicPath), 0755, true);

        try {
            $response = Http::timeout(10)->get($url);
            if ($response->successful() && strlen($response->body()) > 2000) {
                file_put_contents($storagePath, $response->body());
                @file_put_contents($publicPath, $response->body());

                return $targetRelativePath;
            }
        } catch (\Throwable $e) {
            // Fall through to placeholder generator
        }

        // Generate fallback stylish canvas placeholder if remote download fails
        $this->createFallbackImage($storagePath, basename($targetRelativePath));
        @copy($storagePath, $publicPath);

        return $targetRelativePath;
    }

    private function createFallbackImage(string $filePath, string $title): void
    {
        if (function_exists('imagecreatetruecolor')) {
            $im = imagecreatetruecolor(600, 600);
            $bg = imagecolorallocate($im, 45, 75, 60);
            $fg = imagecolorallocate($im, 255, 255, 255);
            imagefill($im, 0, 0, $bg);
            imagestring($im, 5, 200, 290, 'Foodness - '.substr($title, 0, 15), $fg);
            imagejpeg($im, $filePath, 85);
            imagedestroy($im);
        } else {
            file_put_contents($filePath, '');
        }
    }

    /**
     * Curated catalog data with free high quality Unsplash photos.
     */
    public function getCatalogData(): array
    {
        return [
            'Men' => [
                'category_image' => 'https://images.unsplash.com/photo-1617137984095-74e4e5e3613f?auto=format&fit=crop&w=800&q=80',
                'subcategories' => [
                    'Panjabi' => [
                        [
                            'name' => 'Exclusive Olive Green Semi-Fit Cotton Panjabi',
                            'sku' => 'MEN-PNJ-01',
                            'price' => 1850,
                            'compare_at_price' => 2450,
                            'stock' => 50,
                            'short_desc' => 'Modern crafted semi-fit panjabi made with premium quality breathable cotton fabric.',
                            'description' => '<p><strong>Foodness Exclusive Collection:</strong> Crafted from 100% combed pure cotton, this panjabi offers ultimate comfort and elegance for festive occasions, celebrations, or Friday prayers.</p><ul><li>Fabric: 100% Combed Pure Cotton</li><li>Fit: Semi-fit stylish pattern</li><li>Collar & Buttons: High-quality snap buttons with intricate thread work</li><li>Care: Easy wash & long-lasting color</li></ul>',
                            'image' => 'https://images.unsplash.com/photo-1583391733956-3750e0ff4e8b?auto=format&fit=crop&w=800&q=80',
                            'gallery' => 'https://images.unsplash.com/photo-1597983073493-88cd35cf93b0?auto=format&fit=crop&w=800&q=80',
                            'is_featured' => true,
                            'is_hot' => true,
                            'variants' => [
                                ['color' => 'olive-green', 'size' => 'M'],
                                ['color' => 'olive-green', 'size' => 'L'],
                                ['color' => 'olive-green', 'size' => 'XL'],
                                ['color' => 'royal-white', 'size' => 'M'],
                                ['color' => 'royal-white', 'size' => 'L'],
                            ],
                        ],
                        [
                            'name' => 'Royal White Embroidered Festive Panjabi',
                            'sku' => 'MEN-PNJ-02',
                            'price' => 1990,
                            'compare_at_price' => 2650,
                            'stock' => 45,
                            'short_desc' => 'Graceful pure white festive panjabi featuring delicate embroidered collar and cuffs.',
                            'description' => '<p>A timeless statement of royalty and sophistication for Eid and special occasions. Handcrafted embroidery along the placket and collar gives it a distinguished royal touch.</p><ul><li>Fabric: Premium Jacquard Cotton</li><li>Color: Pure Snow White</li><li>Fit: Regular and Slim fit available</li></ul>',
                            'image' => 'https://images.unsplash.com/photo-1597983073493-88cd35cf93b0?auto=format&fit=crop&w=800&q=80',
                            'is_featured' => true,
                            'is_hot' => false,
                            'variants' => [
                                ['color' => 'royal-white', 'size' => 'M'],
                                ['color' => 'royal-white', 'size' => 'L'],
                                ['color' => 'royal-white', 'size' => 'XL'],
                            ],
                        ],
                    ],
                    'Shirts & Polos' => [
                        [
                            'name' => 'Slim-Fit Navy Blue Casual Cotton Shirt',
                            'sku' => 'MEN-SHT-03',
                            'price' => 1290,
                            'compare_at_price' => 1690,
                            'stock' => 60,
                            'short_desc' => 'Soft-washed 100% cotton casual navy shirt perfect for daily work or evening outings.',
                            'description' => '<p>Stay effortlessly sharp whether at the office or catching up with friends. Designed with breathable cotton and precision tailoring.</p><ul><li>Material: 100% Pre-washed Cotton</li><li>Fit: Smart Slim Fit</li><li>Collar: Button-down collar</li></ul>',
                            'image' => 'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?auto=format&fit=crop&w=800&q=80',
                            'is_featured' => true,
                            'is_hot' => true,
                            'variants' => [
                                ['color' => 'navy-blue', 'size' => 'M'],
                                ['color' => 'navy-blue', 'size' => 'L'],
                                ['color' => 'navy-blue', 'size' => 'XL'],
                            ],
                        ],
                        [
                            'name' => 'Classic White Formal Oxford Shirt',
                            'sku' => 'MEN-SHT-04',
                            'price' => 1390,
                            'compare_at_price' => 1850,
                            'stock' => 40,
                            'short_desc' => 'Crisp and timeless premium Oxford weave formal shirt.',
                            'description' => '<p>The essential white Oxford shirt for boardroom meetings, presentations, and formal events. Wrinkle-resistant premium finish.</p>',
                            'image' => 'https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?auto=format&fit=crop&w=800&q=80',
                            'is_featured' => false,
                            'is_hot' => false,
                            'variants' => [
                                ['color' => 'royal-white', 'size' => 'M'],
                                ['color' => 'royal-white', 'size' => 'L'],
                                ['color' => 'royal-white', 'size' => 'XL'],
                            ],
                        ],
                        [
                            'name' => 'Premium Pique Cotton Polo Shirt - Forest Green',
                            'sku' => 'MEN-POL-05',
                            'price' => 850,
                            'compare_at_price' => 1150,
                            'stock' => 70,
                            'short_desc' => 'Breathable pique cotton polo shirt with stylish two-tone ribbed collar.',
                            'description' => '<p>Crafted with heavy 220+ GSM premium pique cotton. Designed for all-day breathability, softness, and durability.</p>',
                            'image' => 'https://images.unsplash.com/photo-1586363104862-3a5e2ab60d99?auto=format&fit=crop&w=800&q=80',
                            'is_featured' => true,
                            'is_hot' => true,
                            'variants' => [
                                ['color' => 'olive-green', 'size' => 'M'],
                                ['color' => 'olive-green', 'size' => 'L'],
                                ['color' => 'navy-blue', 'size' => 'L'],
                            ],
                        ],
                    ],
                    'Pants & Denims' => [
                        [
                            'name' => 'Vintage Fade Slim Stretch Denim Jeans',
                            'sku' => 'MEN-JNS-06',
                            'price' => 1650,
                            'compare_at_price' => 2200,
                            'stock' => 50,
                            'short_desc' => 'Comfortable slim-fit stretch denim with authentic vintage wash finishing.',
                            'description' => '<p>Modern tapered cut with a vintage wash. Crafted with 98% cotton and 2% spandex for unrestricted flexibility and comfort.</p>',
                            'image' => 'https://images.unsplash.com/photo-1541099649105-f69ad21f3246?auto=format&fit=crop&w=800&q=80',
                            'is_featured' => true,
                            'is_hot' => false,
                            'variants' => [
                                ['size' => '30'],
                                ['size' => '32'],
                                ['size' => '34'],
                            ],
                        ],
                    ],
                ],
            ],

            'Women' => [
                'category_image' => 'https://images.unsplash.com/photo-1610030469983-98e550d6193c?auto=format&fit=crop&w=800&q=80',
                'subcategories' => [
                    'Saree' => [
                        [
                            'name' => 'Handcrafted Traditional Dhakai Jamdani Saree',
                            'sku' => 'WMN-SAR-01',
                            'price' => 3850,
                            'compare_at_price' => 4950,
                            'stock' => 25,
                            'short_desc' => 'Mesmerizing crimson red saree woven with traditional Jamdani motifs.',
                            'description' => '<p>Authentic heritage Dhakai Jamdani saree. Intricate artisan weaving and featherlight drape make you the centerpiece of every festive celebration.</p><ul><li>Fabric: Premium Half Silk Jamdani</li><li>Length: 12 Haat (with blouse piece)</li><li>Design: Traditional floral & jaal motifs</li></ul>',
                            'image' => 'https://images.unsplash.com/photo-1617627143750-d86bc21e42bb?auto=format&fit=crop&w=800&q=80',
                            'gallery' => 'https://images.unsplash.com/photo-1596462502278-27bfdc403348?auto=format&fit=crop&w=800&q=80',
                            'is_featured' => true,
                            'is_hot' => true,
                            'variants' => [
                                ['color' => 'crimson-red', 'size' => 'Free Size'],
                            ],
                        ],
                        [
                            'name' => 'Elegant Pastel Silk Festive Saree',
                            'sku' => 'WMN-SAR-02',
                            'price' => 2750,
                            'compare_at_price' => 3500,
                            'stock' => 30,
                            'short_desc' => 'Classic festive silk saree with royal pallu and shimmering border.',
                            'description' => '<p>Exude grace at weddings and family gatherings with this opulent pastel silk saree. Features an exquisite woven border.</p>',
                            'image' => 'https://images.unsplash.com/photo-1609357605129-26f69add5d6e?auto=format&fit=crop&w=800&q=80',
                            'is_featured' => true,
                            'is_hot' => false,
                            'variants' => [
                                ['color' => 'mustard-gold', 'size' => 'Free Size'],
                            ],
                        ],
                    ],
                    'Kurti & Dresses' => [
                        [
                            'name' => 'Designer Embroidered Kurti Set - Mustard Gold',
                            'sku' => 'WMN-KRT-03',
                            'price' => 2150,
                            'compare_at_price' => 2850,
                            'stock' => 40,
                            'short_desc' => 'Stylish embroidered kurti and trouser set for work and casual elegance.',
                            'description' => '<p>Delicate embroidery on premium breathable cotton-viscose fabric. Combining supreme comfort with contemporary elegance.</p>',
                            'image' => 'https://images.unsplash.com/photo-1610030469983-98e550d6193c?auto=format&fit=crop&w=800&q=80',
                            'is_featured' => true,
                            'is_hot' => true,
                            'variants' => [
                                ['color' => 'mustard-gold', 'size' => 'M'],
                                ['color' => 'mustard-gold', 'size' => 'L'],
                                ['color' => 'mustard-gold', 'size' => 'XL'],
                            ],
                        ],
                        [
                            'name' => 'Bohemian Floral Summer Maxi Dress',
                            'sku' => 'WMN-DRS-04',
                            'price' => 1850,
                            'compare_at_price' => 2450,
                            'stock' => 35,
                            'short_desc' => 'Trendy long floral summer maxi dress with breezy relaxed fit.',
                            'description' => '<p>Crafted in lightweight soft georgette with a romantic floral pattern. Your go-to companion for brunch and vacation days.</p>',
                            'image' => 'https://images.unsplash.com/photo-1572804013309-59a88b7e92f1?auto=format&fit=crop&w=800&q=80',
                            'is_featured' => true,
                            'is_hot' => false,
                            'variants' => [
                                ['color' => 'crimson-red', 'size' => 'M'],
                                ['color' => 'crimson-red', 'size' => 'L'],
                            ],
                        ],
                        [
                            'name' => 'Double Layer Georgette Modest Abaya - Charcoal',
                            'sku' => 'WMN-ABY-05',
                            'price' => 2950,
                            'compare_at_price' => 3850,
                            'stock' => 20,
                            'short_desc' => 'Double-layered modest abaya crafted from premium cherry georgette.',
                            'description' => '<p>Timeless modest fashion with graceful draping and elegant tailored finishing suitable for everyday and occasion wear.</p>',
                            'image' => 'https://images.unsplash.com/photo-1584917865442-de89df76afd3?auto=format&fit=crop&w=800&q=80',
                            'is_featured' => false,
                            'is_hot' => true,
                            'variants' => [
                                ['color' => 'charcoal-black', 'size' => 'Free Size'],
                            ],
                        ],
                    ],
                ],
            ],

            'Kids' => [
                'category_image' => 'https://images.unsplash.com/photo-1503919545889-aef636e10ad4?auto=format&fit=crop&w=800&q=80',
                'subcategories' => [
                    'Boys Clothing' => [
                        [
                            'name' => 'Festive Embroidered Cotton Panjabi for Boys',
                            'sku' => 'KID-PNJ-01',
                            'price' => 1150,
                            'compare_at_price' => 1550,
                            'stock' => 45,
                            'short_desc' => 'Gentle pure cotton festive panjabi tailored for kids delicate skin.',
                            'description' => '<p>Soft, breathable festive panjabi crafted especially for young gentlemen. Non-irritating pure cotton ensures all-day smiles.</p>',
                            'image' => 'https://images.unsplash.com/photo-1503919545889-aef636e10ad4?auto=format&fit=crop&w=800&q=80',
                            'is_featured' => true,
                            'is_hot' => true,
                            'variants' => [
                                ['color' => 'sky-blue', 'size' => 'M'],
                                ['color' => 'sky-blue', 'size' => 'L'],
                            ],
                        ],
                        [
                            'name' => 'Boys Cool Street Graphic Pure Cotton T-Shirt',
                            'sku' => 'KID-TSH-02',
                            'price' => 490,
                            'compare_at_price' => 690,
                            'stock' => 60,
                            'short_desc' => 'Fun and trendy graphic print pure cotton casual t-shirt.',
                            'description' => '<p>Play-ready, super-soft pure cotton t-shirt with vibrant colors designed to withstand repeat washing.</p>',
                            'image' => 'https://images.unsplash.com/photo-1519238263530-99bdd11df2ea?auto=format&fit=crop&w=800&q=80',
                            'is_featured' => false,
                            'is_hot' => false,
                            'variants' => [
                                ['color' => 'royal-white', 'size' => 'M'],
                                ['color' => 'navy-blue', 'size' => 'L'],
                            ],
                        ],
                    ],
                    'Girls Clothing' => [
                        [
                            'name' => 'Girls Floral Ruffle Party Frock Dress',
                            'sku' => 'KID-DRS-03',
                            'price' => 1290,
                            'compare_at_price' => 1750,
                            'stock' => 30,
                            'short_desc' => 'Charming party frock dress with flutter ruffles and soft cotton lining.',
                            'description' => '<p>The perfect birthday or party dress for little princesses. Breathable inner cotton lining keeps them cool and comfy.</p>',
                            'image' => 'https://images.unsplash.com/photo-1622290291468-a28f7a7dc6a8?auto=format&fit=crop&w=800&q=80',
                            'is_featured' => true,
                            'is_hot' => true,
                            'variants' => [
                                ['color' => 'royal-white', 'size' => 'M'],
                                ['color' => 'royal-white', 'size' => 'L'],
                            ],
                        ],
                    ],
                ],
            ],

            'Accessories' => [
                'category_image' => 'https://images.unsplash.com/photo-1624222247344-550fb60583dc?auto=format&fit=crop&w=800&q=80',
                'subcategories' => [
                    'Belts & Wallets' => [
                        [
                            'name' => 'Full Grain Genuine Leather Belt - Antique Brown',
                            'sku' => 'ACC-BLT-01',
                            'price' => 890,
                            'compare_at_price' => 1250,
                            'stock' => 50,
                            'short_desc' => 'Original full-grain cowhide leather belt with solid brushed metal buckle.',
                            'description' => '<p>100% genuine full-grain leather crafted to last for years without cracking. Seamlessly pairs with both trousers and denim.</p>',
                            'image' => 'https://images.unsplash.com/photo-1624222247344-550fb60583dc?auto=format&fit=crop&w=800&q=80',
                            'is_featured' => true,
                            'is_hot' => false,
                            'variants' => [
                                ['color' => 'antique-brown', 'size' => '32'],
                                ['color' => 'antique-brown', 'size' => '34'],
                                ['color' => 'charcoal-black', 'size' => '34'],
                            ],
                        ],
                        [
                            'name' => 'Handcrafted Bifold Genuine Leather Wallet',
                            'sku' => 'ACC-WLT-02',
                            'price' => 950,
                            'compare_at_price' => 1350,
                            'stock' => 40,
                            'short_desc' => 'Slim bifold genuine leather wallet with 8 card slots and gift box packaging.',
                            'description' => '<p>Handcrafted genuine leather wallet with reinforced stitching. Includes dedicated bill compartments, 8 card slots, and an elegant gift box.</p>',
                            'image' => 'https://images.unsplash.com/photo-1627123424574-724758594e93?auto=format&fit=crop&w=800&q=80',
                            'is_featured' => true,
                            'is_hot' => true,
                            'variants' => [
                                ['color' => 'antique-brown', 'size' => 'Free Size'],
                                ['color' => 'charcoal-black', 'size' => 'Free Size'],
                            ],
                        ],
                    ],
                    'Watches & Hijabs' => [
                        [
                            'name' => 'Minimalist Classic Wristwatch with Leather Strap',
                            'sku' => 'ACC-WCH-03',
                            'price' => 2250,
                            'compare_at_price' => 3200,
                            'stock' => 25,
                            'short_desc' => 'Quartz movement water-resistant watch with genuine leather strap.',
                            'description' => '<p>Minimalist dial encased in sleek stainless steel casing. Elevates any formal or casual look. Includes 1-year movement warranty.</p>',
                            'image' => 'https://images.unsplash.com/photo-1524805444758-089113d48a6d?auto=format&fit=crop&w=800&q=80',
                            'is_featured' => true,
                            'is_hot' => true,
                            'variants' => [
                                ['color' => 'antique-brown', 'size' => 'Free Size'],
                            ],
                        ],
                        [
                            'name' => 'Premium Soft Chiffon Georgette Hijab',
                            'sku' => 'ACC-HJB-04',
                            'price' => 450,
                            'compare_at_price' => 650,
                            'stock' => 70,
                            'short_desc' => 'Breathable, non-slip bubble chiffon hijab with elegant drape.',
                            'description' => '<p>Premium lightweight bubble chiffon hijab designed for all-day comfort. Stays pinned without slipping and drapes gracefully.</p>',
                            'image' => 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?auto=format&fit=crop&w=800&q=80',
                            'is_featured' => false,
                            'is_hot' => false,
                            'variants' => [
                                ['color' => 'charcoal-black', 'size' => 'Free Size'],
                                ['color' => 'olive-green', 'size' => 'Free Size'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
