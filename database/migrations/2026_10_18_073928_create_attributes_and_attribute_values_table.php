<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('attributes')) {
            Schema::create('attributes', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('type')->default('button'); // button, color, select
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('attribute_values')) {
            Schema::create('attribute_values', function (Blueprint $table) {
                $table->id();
                $table->foreignId('attribute_id')->constrained('attributes')->cascadeOnDelete();
                $table->string('name');
                $table->string('slug');
                $table->string('color_code')->nullable();
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                $table->index(['attribute_id', 'sort_order']);
            });
        }

        Schema::table('product_variants', function (Blueprint $table) {
            if (! Schema::hasColumn('product_variants', 'name')) {
                $table->string('name')->nullable()->after('product_id');
            }
            if (! Schema::hasColumn('product_variants', 'attribute_values')) {
                $table->json('attribute_values')->nullable()->after('name');
            }
        });

        if (! Schema::hasTable('product_variant_attribute_value')) {
            Schema::create('product_variant_attribute_value', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
                $table->foreignId('attribute_value_id')->constrained('attribute_values')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['product_variant_id', 'attribute_value_id'], 'variant_attr_val_unique');
            });
        }

        Schema::table('order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('order_items', 'variant_id')) {
                $table->foreignId('variant_id')->nullable()->after('product_id')->nullOnDelete();
            }
            if (! Schema::hasColumn('order_items', 'variation_name')) {
                $table->string('variation_name')->nullable()->after('product_name');
            }
            if (! Schema::hasColumn('order_items', 'attributes')) {
                $table->json('attributes')->nullable()->after('variation_name');
            }
        });

        // Migrate existing Colors to Attributes & Attribute Values
        if (Schema::hasTable('colors') && Schema::hasTable('attributes')) {
            $colorAttrId = DB::table('attributes')->insertGetId([
                'name' => 'Color',
                'slug' => 'color',
                'type' => 'color',
                'is_active' => true,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $existingColors = DB::table('colors')->get();
            $colorValueMap = [];
            foreach ($existingColors as $color) {
                $valId = DB::table('attribute_values')->insertGetId([
                    'attribute_id' => $colorAttrId,
                    'name' => $color->name,
                    'slug' => $color->slug ?: Str::slug($color->name),
                    'color_code' => $color->hex,
                    'sort_order' => (int) $color->sort_order,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $colorValueMap[$color->id] = ['id' => $valId, 'name' => $color->name];
            }
        }

        // Migrate existing Sizes to Attributes & Attribute Values
        if (Schema::hasTable('sizes') && Schema::hasTable('attributes')) {
            $sizeAttrId = DB::table('attributes')->insertGetId([
                'name' => 'Size',
                'slug' => 'size',
                'type' => 'button',
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $existingSizes = DB::table('sizes')->get();
            $sizeValueMap = [];
            foreach ($existingSizes as $size) {
                $valId = DB::table('attribute_values')->insertGetId([
                    'attribute_id' => $sizeAttrId,
                    'name' => $size->name,
                    'slug' => $size->slug ?: Str::slug($size->name),
                    'color_code' => null,
                    'sort_order' => (int) $size->sort_order,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $sizeValueMap[$size->id] = ['id' => $valId, 'name' => $size->name];
            }
        }

        // Populate existing product_variants with name and attribute_values JSON
        if (Schema::hasTable('product_variants')) {
            $variants = DB::table('product_variants')->get();
            foreach ($variants as $v) {
                $attrMap = [];
                $nameParts = [];

                if (! empty($v->color_id) && isset($colorValueMap[$v->color_id])) {
                    $attrMap['Color'] = $colorValueMap[$v->color_id]['name'];
                    $nameParts[] = $colorValueMap[$v->color_id]['name'];

                    DB::table('product_variant_attribute_value')->insertOrIgnore([
                        'product_variant_id' => $v->id,
                        'attribute_value_id' => $colorValueMap[$v->color_id]['id'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $sizeVal = null;
                if (! empty($v->size_id) && isset($sizeValueMap[$v->size_id])) {
                    $sizeVal = $sizeValueMap[$v->size_id]['name'];
                    DB::table('product_variant_attribute_value')->insertOrIgnore([
                        'product_variant_id' => $v->id,
                        'attribute_value_id' => $sizeValueMap[$v->size_id]['id'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } elseif (! empty($v->size_name)) {
                    $sizeVal = $v->size_name;
                }

                if ($sizeVal) {
                    $attrMap['Size'] = $sizeVal;
                    $nameParts[] = $sizeVal;
                }

                if ($attrMap !== []) {
                    DB::table('product_variants')
                        ->where('id', $v->id)
                        ->update([
                            'name' => implode(' / ', $nameParts),
                            'attribute_values' => json_encode($attrMap),
                        ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'attributes')) {
                $table->dropColumn('attributes');
            }
            if (Schema::hasColumn('order_items', 'variation_name')) {
                $table->dropColumn('variation_name');
            }
            if (Schema::hasColumn('order_items', 'variant_id')) {
                $table->dropConstrainedForeignId('variant_id');
            }
        });

        Schema::dropIfExists('product_variant_attribute_value');

        Schema::table('product_variants', function (Blueprint $table) {
            if (Schema::hasColumn('product_variants', 'attribute_values')) {
                $table->dropColumn('attribute_values');
            }
            if (Schema::hasColumn('product_variants', 'name')) {
                $table->dropColumn('name');
            }
        });

        Schema::dropIfExists('attribute_values');
        Schema::dropIfExists('attributes');
    }
};
