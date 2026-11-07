<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Color;
use App\Models\Size;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Color Attribute
        $colorAttr = Attribute::query()->updateOrCreate(
            ['slug' => 'color'],
            [
                'name' => 'Color',
                'type' => 'color',
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        $colors = Color::query()->orderBy('sort_order')->orderBy('id')->get();
        if ($colors->isEmpty()) {
            $defaultColors = [
                ['name' => 'Black', 'hex' => '#111111'],
                ['name' => 'White', 'hex' => '#ffffff'],
                ['name' => 'Navy', 'hex' => '#1e3a5f'],
                ['name' => 'Sage', 'hex' => '#3d5a4c'],
                ['name' => 'Gray', 'hex' => '#6b7280'],
                ['name' => 'Olive Green', 'hex' => '#4a5d4e'],
                ['name' => 'Crimson Red', 'hex' => '#9b111e'],
                ['name' => 'Mustard Gold', 'hex' => '#d4af37'],
                ['name' => 'Sky Blue', 'hex' => '#87ceeb'],
                ['name' => 'Antique Brown', 'hex' => '#6e473b'],
            ];
            foreach ($defaultColors as $index => $c) {
                Color::query()->updateOrCreate(
                    ['slug' => Str::slug($c['name'])],
                    ['name' => $c['name'], 'hex' => $c['hex'], 'is_active' => true, 'sort_order' => $index + 1]
                );
            }
            $colors = Color::query()->orderBy('sort_order')->orderBy('id')->get();
        }

        foreach ($colors as $index => $color) {
            AttributeValue::query()->updateOrCreate(
                [
                    'attribute_id' => $colorAttr->id,
                    'slug' => $color->slug ?: Str::slug($color->name),
                ],
                [
                    'name' => $color->name,
                    'color_code' => $color->hex,
                    'sort_order' => $color->sort_order ?: $index + 1,
                ]
            );
        }

        // 2. Size Attribute
        $sizeAttr = Attribute::query()->updateOrCreate(
            ['slug' => 'size'],
            [
                'name' => 'Size',
                'type' => 'button',
                'is_active' => true,
                'sort_order' => 2,
            ]
        );

        $sizes = Size::query()->orderBy('sort_order')->orderBy('id')->get();
        if ($sizes->isEmpty()) {
            $defaultSizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'Free Size'];
            foreach ($defaultSizes as $index => $name) {
                Size::query()->updateOrCreate(
                    ['slug' => Str::slug($name)],
                    ['name' => $name, 'is_active' => true, 'sort_order' => $index + 1]
                );
            }
            $sizes = Size::query()->orderBy('sort_order')->orderBy('id')->get();
        }

        foreach ($sizes as $index => $size) {
            AttributeValue::query()->updateOrCreate(
                [
                    'attribute_id' => $sizeAttr->id,
                    'slug' => $size->slug ?: Str::slug($size->name),
                ],
                [
                    'name' => $size->name,
                    'color_code' => null,
                    'sort_order' => $size->sort_order ?: $index + 1,
                ]
            );
        }

        // 3. Storage Attribute
        $storageAttr = Attribute::query()->updateOrCreate(
            ['slug' => 'storage'],
            [
                'name' => 'Storage',
                'type' => 'button',
                'is_active' => true,
                'sort_order' => 3,
            ]
        );
        $storageValues = ['64GB', '128GB', '256GB', '512GB', '1TB'];
        foreach ($storageValues as $index => $val) {
            AttributeValue::query()->updateOrCreate(
                [
                    'attribute_id' => $storageAttr->id,
                    'slug' => Str::slug($val),
                ],
                [
                    'name' => $val,
                    'color_code' => null,
                    'sort_order' => $index + 1,
                ]
            );
        }

        // 4. RAM Attribute
        $ramAttr = Attribute::query()->updateOrCreate(
            ['slug' => 'ram'],
            [
                'name' => 'RAM',
                'type' => 'button',
                'is_active' => true,
                'sort_order' => 4,
            ]
        );
        $ramValues = ['4GB', '6GB', '8GB', '12GB', '16GB'];
        foreach ($ramValues as $index => $val) {
            AttributeValue::query()->updateOrCreate(
                [
                    'attribute_id' => $ramAttr->id,
                    'slug' => Str::slug($val),
                ],
                [
                    'name' => $val,
                    'color_code' => null,
                    'sort_order' => $index + 1,
                ]
            );
        }

        // 5. Material Attribute
        $materialAttr = Attribute::query()->updateOrCreate(
            ['slug' => 'material'],
            [
                'name' => 'Material',
                'type' => 'button',
                'is_active' => true,
                'sort_order' => 5,
            ]
        );
        $materialValues = ['Cotton', 'Pure Silk', 'Denim', 'Leather', 'Linen', 'Polyester'];
        foreach ($materialValues as $index => $val) {
            AttributeValue::query()->updateOrCreate(
                [
                    'attribute_id' => $materialAttr->id,
                    'slug' => Str::slug($val),
                ],
                [
                    'name' => $val,
                    'color_code' => null,
                    'sort_order' => $index + 1,
                ]
            );
        }

        // 6. Weight Attribute
        $weightAttr = Attribute::query()->updateOrCreate(
            ['slug' => 'weight'],
            [
                'name' => 'Weight',
                'type' => 'button',
                'is_active' => true,
                'sort_order' => 6,
            ]
        );
        $weightValues = ['250g', '500g', '1kg', '2kg', '5kg'];
        foreach ($weightValues as $index => $val) {
            AttributeValue::query()->updateOrCreate(
                [
                    'attribute_id' => $weightAttr->id,
                    'slug' => Str::slug($val),
                ],
                [
                    'name' => $val,
                    'color_code' => null,
                    'sort_order' => $index + 1,
                ]
            );
        }
    }
}
