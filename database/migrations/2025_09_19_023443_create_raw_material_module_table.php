<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id()->comment('Primary key Unit of Measure');
            $table->string('code', 32)->unique()->comment('Kode unik UoM, mis. KG, G, L, ML');
            $table->string('name', 100)->comment('Nama UoM');
            $table->string('symbol', 16)->nullable()->comment('Simbol singkat');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });


        Schema::create('unit_conversions', function (Blueprint $table) {
            $table->id()->comment('Primary key konversi UoM');
            $table->foreignId('from_unit_id')->constrained('units')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('to_unit_id')->constrained('units')->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('factor', 24, 12)->comment('Faktor konversi: qty_to = qty_from * factor');
            $table->unique(['from_unit_id', 'to_unit_id']);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('raw_material_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });


        Schema::create('raw_materials', function (Blueprint $table) {
            $table->id()->comment('Primary key bahan baku');
            $table->foreignId('category_id')->nullable()->constrained('raw_material_categories')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('default_unit_id')->constrained('units')->cascadeOnUpdate()->restrictOnDelete()->comment('UoM dasar');
            $table->string('code', 64)->unique()->comment('Kode unik bahan baku');
            $table->string('name', 200)->index()->comment('Nama bahan baku');
            $table->text('spec')->nullable()->comment('Spesifikasi bahan baku');
            $table->decimal('min_stock', 24, 6)->default(0)->comment('Batas minimum stok');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });


        // optional images (paralel dg product_images)
        Schema::create('raw_material_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raw_material_id')->constrained('raw_materials')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('image_path');
            $table->boolean('is_primary')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->index(['raw_material_id', 'is_primary', 'sort_order'], 'rm_images_lookup_idx');
        });

        Schema::create('raw_material_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raw_material_id')->constrained('raw_materials')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('supplier_sku', 64)->nullable();
            $table->boolean('is_preferred')->default(false)->index();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique(['raw_material_id', 'supplier_id']);
        });


        Schema::create('raw_material_supplier_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raw_material_supplier_id')->constrained('raw_material_suppliers')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('price', 20, 2)->default(0);
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(
                ['raw_material_supplier_id', 'valid_from', 'valid_to'],
                'rm_sup_price_valid_idx'
            );
        });

        Schema::create('raw_material_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raw_material_id')->constrained('raw_materials')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('lot_no', 100)->index();
            $table->date('mfg_date')->nullable();
            $table->date('exp_date')->nullable();
            $table->string('quality_status', 30)->default('released')->index()->comment('released/on_hold/rejected');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->unique(['raw_material_id', 'lot_no']);
        });


        Schema::create('raw_material_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raw_material_id')->constrained('raw_materials')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('raw_material_batches')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('quantity', 24, 6)->default(0);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique(['raw_material_id', 'warehouse_id', 'batch_id', 'unit_id'], 'rm_stock_unique');
            $table->index(['raw_material_id', 'warehouse_id']);
        });


        Schema::create('raw_material_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raw_material_id')->constrained('raw_materials')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('raw_material_batches')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnUpdate()->restrictOnDelete();
            $table->enum('type', ['in', 'out', 'adjust', 'transfer'])->index();
            $table->decimal('qty', 24, 6);
            $table->decimal('unit_cost', 20, 6)->nullable()->comment('Opsional: untuk average cost');
            $table->morphs('reference'); // reference_type, reference_id (PO/Production/etc)
            $table->string('note')->nullable();
            $table->timestamp('moved_at')->index();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('product_boms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained('products')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->cascadeOnUpdate()->nullOnDelete();
            $table->string('version', 32)->default('v1');
            $table->boolean('is_active')->default(true)->index();
            $table->string('note')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique(['product_id', 'product_variant_id', 'version'], 'bom_unique_version');
            $table->index(['product_id', 'product_variant_id']);
        });


        Schema::create('product_bom_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_bom_id')->constrained('product_boms')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('raw_material_id')->constrained('raw_materials')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('qty', 24, 6);
            $table->decimal('waste_percent', 8, 4)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['product_bom_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop tables in correct order to avoid foreign key constraint errors
        // Drop child tables first, then parent tables

        Schema::dropIfExists('product_bom_items');
        Schema::dropIfExists('product_boms');

        Schema::dropIfExists('raw_material_stock_movements');
        Schema::dropIfExists('raw_material_stocks');
        Schema::dropIfExists('raw_material_batches');

        Schema::dropIfExists('raw_material_supplier_prices');
        Schema::dropIfExists('raw_material_suppliers');

        Schema::dropIfExists('raw_material_images');
        Schema::dropIfExists('raw_materials'); // Drop this before units since it references units
        Schema::dropIfExists('raw_material_categories');

        Schema::dropIfExists('unit_conversions'); // Drop this before units since it references units
        Schema::dropIfExists('units'); // Drop units last
    }
};
