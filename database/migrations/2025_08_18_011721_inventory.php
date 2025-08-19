<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ===================== Warehouses & Suppliers =====================
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();            // kode singkat gudang
            $table->string('name', 150)->unique();
            $table->string('address')->nullable();
            $table->string('district')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code', 16)->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('phone', 32)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name', 150)->unique();
            $table->string('contact_name', 150)->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('district')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code', 16)->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        // ===================== Products =====================
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->nullable()
                ->constrained('suppliers')->cascadeOnUpdate()->nullOnDelete();
            $table->string('sku', 64)->unique();
            $table->string('name', 200)->index();
            $table->string('model', 100)->nullable();                 // model busana
            $table->string('brand', 100)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('image_path');             // path/URL
            $table->boolean('is_primary')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['product_id', 'is_primary', 'sort_order']);
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('sku_variant', 64)->unique();
            $table->string('barcode', 64)->nullable()->unique();
            $table->string('color', 50)->index();
            $table->string('size', 50)->index();
            $table->decimal('cost_price', 20, 2)->default(0);
            $table->decimal('price', 20, 2)->default(0);
            $table->string('status', 20)->default('active')->index(); // active/inactive/discontinued
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['product_id', 'color', 'size'], 'variants_product_color_size_unique');
        });

        // ===================== Stock per variant per warehouse =====================
        Schema::create('warehouse_variant_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnUpdate()->cascadeOnDelete();
            $table->unsignedBigInteger('qty')->default(0);
            $table->unsignedBigInteger('reserved_qty')->default(0); // untuk reservasi / pesanan
            $table->timestamps();
            $table->unique(['warehouse_id', 'product_variant_id'], 'stock_unique_wv');
            $table->index(['product_variant_id', 'warehouse_id']);
        });

        // ===================== Transactions (header) =====================
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number', 64)->unique();
            $table->enum('type', [
                'penjualan',
                'pemindahan',
                'pengembalian',
            ])->index();

            $table->dateTime('transaction_date')->index();

            // relasi gudang: tergantung type, satu/both bisa terpakai
            $table->foreignId('source_warehouse_id')->nullable()
                ->constrained('warehouses')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('destination_warehouse_id')->nullable()
                ->constrained('warehouses')->cascadeOnUpdate()->nullOnDelete();

            // customer / supplier (inline agar ringan; bisa juga FK ke tabel khusus)
            $table->string('customer_name', 150)->nullable();
            $table->string('customer_phone', 32)->nullable();
            $table->string('customer_full_address')->nullable();
            $table->foreignId('supplier_id')->nullable()
                ->constrained('suppliers')->cascadeOnUpdate()->nullOnDelete();

            // ringkasan nilai
            $table->unsignedBigInteger('item_count')->default(0);
            $table->decimal('grand_total', 20, 2)->default(0);

            // status transaksi
            $table->string('status', 20)->default('draft')->index(); // draft/posted/cancelled
            $table->dateTime('posted_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // ===================== Transaction details (lines) =====================
        Schema::create('transaction_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnUpdate()->cascadeOnDelete();

            // override gudang per baris bila perlu (kalau null, pakai header source/destination sesuai type)
            $table->foreignId('warehouse_id')->nullable()
                ->constrained('warehouses')->cascadeOnUpdate()->nullOnDelete();

            $table->unsignedBigInteger('qty');
            $table->decimal('price', 20, 2)->default(0);
            $table->decimal('discount_amount', 20, 2)->default(0);
            $table->decimal('line_total', 20, 2)->default(0);
            $table->timestamps();

            $table->index(['transaction_id']);
            $table->index(['product_variant_id', 'warehouse_id']);
        });

        // ===================== Inventory movements (audit log) =====================
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->nullable()
                ->constrained('transactions')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('from_warehouse_id')->constrained('warehouses')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('to_warehouse_id')->nullable()->constrained('warehouses')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnUpdate()->cascadeOnDelete();

            $table->bigInteger('qty_change'); // bisa negatif/positif
            $table->enum('type', ['in', 'out'])->index();

            $table->dateTime('occurred_at')->index();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->index(['product_variant_id', 'from_warehouse_id', 'to_warehouse_id', 'occurred_at'], 'movements_lookup_idx');
        });

        // ===================== Invoicing & Payments =====================
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            // Invoice untuk transaksi penjualan/dropship
            $table->foreignId('transaction_id')->unique()
                ->constrained('transactions')->cascadeOnUpdate()->cascadeOnDelete();

            $table->string('invoice_number', 64)->unique()->index();
            $table->dateTime('issued_at')->nullable();
            $table->dateTime('due_at')->nullable();

            $table->decimal('subtotal', 20, 2)->default(0);
            $table->decimal('discount_total', 20, 2)->default(0);
            $table->decimal('tax_total', 20, 2)->default(0);
            $table->decimal('shipping_fee', 20, 2)->default(0);
            $table->decimal('total_amount', 20, 2)->default(0);
            $table->decimal('paid_amount', 20, 2)->default(0);
            $table->boolean('is_paid')->default(false)->index();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnUpdate()->cascadeOnDelete();
            $table->decimal('amount', 20, 2);
            $table->string('method', 32); // transfer/cash/card/e-wallet
            $table->string('reference_no', 64)->nullable();
            $table->dateTime('paid_at')->index();
            $table->string('notes')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['invoice_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('transaction_details');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('warehouse_variant_stocks');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('warehouses');
    }
};
