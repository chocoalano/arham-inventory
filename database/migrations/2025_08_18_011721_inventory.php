<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ===================== Warehouses & Suppliers =====================
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id()->comment('Primary key gudang');
            $table->string('code', 32)->unique()->comment('Kode unik/singkat gudang');
            $table->string('name', 150)->unique()->comment('Nama gudang (unik)');
            $table->string('address')->nullable()->comment('Alamat lengkap gudang');
            $table->string('district')->nullable()->comment('Kecamatan gudang');
            $table->string('city')->nullable()->comment('Kota gudang');
            $table->string('province')->nullable()->comment('Provinsi gudang');
            $table->string('postal_code', 16)->nullable()->comment('Kode pos gudang');
            $table->decimal('lat', 10, 7)->nullable()->comment('Koordinat latitude');
            $table->decimal('lng', 10, 7)->nullable()->comment('Koordinat longitude');
            $table->string('phone', 32)->nullable()->comment('Nomor telepon gudang');
            $table->boolean('is_active')->default(true)->index()->comment('Status aktif gudang');
            $table->timestamp('created_at')->nullable()->comment('Waktu data dibuat');
            $table->timestamp('updated_at')->nullable()->comment('Waktu terakhir diperbarui');
            $table->timestamp('deleted_at')->nullable()->comment('Waktu soft delete');
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id()->comment('Primary key supplier');
            $table->string('code', 32)->unique()->comment('Kode unik supplier');
            $table->string('name', 150)->unique()->comment('Nama supplier (unik)');
            $table->string('contact_name', 150)->nullable()->comment('Nama kontak utama supplier');
            $table->string('phone', 32)->nullable()->comment('Nomor telepon supplier');
            $table->string('email')->nullable()->comment('Email supplier');
            $table->string('address')->nullable()->comment('Alamat lengkap supplier');
            $table->string('district')->nullable()->comment('Kecamatan supplier');
            $table->string('city')->nullable()->comment('Kota supplier');
            $table->string('province')->nullable()->comment('Provinsi supplier');
            $table->string('postal_code', 16)->nullable()->comment('Kode pos supplier');
            $table->decimal('lat', 10, 7)->nullable()->comment('Koordinat latitude');
            $table->decimal('lng', 10, 7)->nullable()->comment('Koordinat longitude');
            $table->boolean('is_active')->default(true)->index()->comment('Status aktif supplier');
            $table->timestamp('created_at')->nullable()->comment('Waktu data dibuat');
            $table->timestamp('updated_at')->nullable()->comment('Waktu terakhir diperbarui');
            $table->timestamp('deleted_at')->nullable()->comment('Waktu soft delete');
        });

        // ===================== Products =====================
        Schema::create('products', function (Blueprint $table) {
            $table->id()->comment('Primary key produk');
            $table->foreignId('supplier_id')
                ->nullable()
                ->constrained('suppliers')
                ->cascadeOnUpdate()
                ->nullOnDelete()
                ->comment('Relasi ke supplier (nullable)');
            $table->string('sku', 64)->unique()->comment('SKU unik produk');
            $table->string('name', 200)->index()->comment('Nama produk');
            $table->string('model', 100)->nullable()->comment('Model/seri produk (opsional)');
            $table->string('brand', 100)->nullable()->comment('Merek/brand produk (opsional)');
            $table->text('description')->nullable()->comment('Deskripsi produk');
            $table->boolean('is_active')->default(true)->index()->comment('Status aktif produk');
            $table->timestamp('created_at')->nullable()->comment('Waktu data dibuat');
            $table->timestamp('updated_at')->nullable()->comment('Waktu terakhir diperbarui');
            $table->timestamp('deleted_at')->nullable()->comment('Waktu soft delete');
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id()->comment('Primary key gambar produk');
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Relasi ke produk');
            $table->string('image_path')->comment('Path/URL file gambar');
            $table->boolean('is_primary')->default(false)->comment('Menandai gambar utama');
            $table->unsignedSmallInteger('sort_order')->default(0)->comment('Urutan tampilan gambar');
            $table->timestamp('created_at')->nullable()->comment('Waktu data dibuat');
            $table->timestamp('updated_at')->nullable()->comment('Waktu terakhir diperbarui');
            $table->timestamp('deleted_at')->nullable()->comment('Waktu soft delete');
            $table->index(['product_id', 'is_primary', 'sort_order'], 'product_images_lookup_idx');
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id()->comment('Primary key varian produk');
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Relasi ke produk induk');
            $table->string('sku_variant', 64)->unique()->comment('SKU unik varian');
            $table->string('barcode', 64)->nullable()->unique()->comment('Barcode varian (opsional, unik bila diisi)');
            $table->string('color', 50)->index()->comment('Warna varian');
            $table->string('size', 50)->index()->comment('Ukuran varian');
            $table->decimal('cost_price', 20, 2)->default(0)->comment('Harga modal varian');
            $table->decimal('price', 20, 2)->default(0)->comment('Harga jual varian');
            $table->string('status', 20)->default('active')->index()->comment('Status varian: active/inactive/discontinued');
            $table->timestamp('created_at')->nullable()->comment('Waktu data dibuat');
            $table->timestamp('updated_at')->nullable()->comment('Waktu terakhir diperbarui');
            $table->timestamp('deleted_at')->nullable()->comment('Waktu soft delete');
            $table->unique(['product_id', 'color', 'size'], 'variants_product_color_size_unique');
        });

        // ===================== Stock per variant per warehouse =====================
        Schema::create('warehouse_variant_stocks', function (Blueprint $table) {
            $table->id()->comment('Primary key stok varian per gudang');
            $table->foreignId('warehouse_id')
                ->constrained('warehouses')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Relasi ke gudang');
            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Relasi ke varian produk');
            $table->unsignedBigInteger('qty')->default(0)->comment('Kuantitas tersedia saat ini');
            $table->unsignedBigInteger('reserved_qty')->default(0)->comment('Kuantitas ter‐reservasi (pesanan)');
            $table->timestamp('created_at')->nullable()->comment('Waktu data dibuat');
            $table->timestamp('updated_at')->nullable()->comment('Waktu terakhir diperbarui');
            $table->unique(['warehouse_id', 'product_variant_id'], 'stock_unique_wv');
            $table->index(['product_variant_id', 'warehouse_id'], 'stock_lookup_idx');
        });

        // ===================== Transactions (header) =====================
        Schema::create('transactions', function (Blueprint $table) {
            $table->id()->comment('Primary key transaksi');
            $table->string('reference_number', 64)->unique()->comment('Nomor referensi transaksi (unik)');
            $table->enum('type', ['penjualan','pemindahan','pengembalian'])->index()->comment('Jenis transaksi');

            $table->dateTime('transaction_date')->index()->comment('Tanggal & waktu transaksi');

            // relasi gudang: tergantung type, satu/both bisa terpakai
            $table->foreignId('source_warehouse_id')
                ->nullable()
                ->constrained('warehouses')
                ->cascadeOnUpdate()
                ->nullOnDelete()
                ->comment('Gudang sumber (nullable sesuai jenis transaksi)');
            $table->foreignId('destination_warehouse_id')
                ->nullable()
                ->constrained('warehouses')
                ->cascadeOnUpdate()
                ->nullOnDelete()
                ->comment('Gudang tujuan (nullable sesuai jenis transaksi)');

            // customer (inline)
            $table->string('customer_name', 150)->nullable()->comment('Nama pelanggan (jika ada)');
            $table->string('customer_phone', 32)->nullable()->comment('No. telepon pelanggan (jika ada)');
            $table->string('customer_full_address')->nullable()->comment('Alamat lengkap pelanggan (jika ada)');

            // ringkasan nilai
            $table->unsignedBigInteger('item_count')->default(0)->comment('Jumlah baris/produk dalam transaksi');
            $table->decimal('grand_total', 20, 2)->default(0)->comment('Total nilai transaksi');

            // status
            $table->enum('status', ['draft','posted','cancelled'])->default('draft')->index()->comment('Status transaksi');
            $table->dateTime('posted_at')->nullable()->comment('Waktu diposting (final)');
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('User pembuat transaksi (nullable)');

            $table->text('remarks')->nullable()->comment('Catatan/remarks transaksi');
            $table->timestamp('created_at')->nullable()->comment('Waktu data dibuat');
            $table->timestamp('updated_at')->nullable()->comment('Waktu terakhir diperbarui');
            $table->timestamp('deleted_at')->nullable()->comment('Waktu soft delete');
        });

        // ===================== Transaction details (lines) =====================
        Schema::create('transaction_details', function (Blueprint $table) {
            $table->id()->comment('Primary key detail transaksi');
            $table->foreignId('transaction_id')
                ->constrained('transactions')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Relasi ke header transaksi');
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Relasi ke produk');
            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Relasi ke varian produk');

            // override gudang per baris bila perlu
            $table->foreignId('warehouse_id')
                ->nullable()
                ->constrained('warehouses')
                ->cascadeOnUpdate()
                ->nullOnDelete()
                ->comment('Gudang spesifik untuk baris ini (override header, nullable)');

            $table->unsignedBigInteger('qty')->comment('Jumlah/kuantitas pada baris');
            $table->decimal('price', 20, 2)->default(0)->comment('Harga satuan pada baris');
            $table->decimal('discount_amount', 20, 2)->default(0)->comment('Nominal diskon baris');
            $table->decimal('line_total', 20, 2)->default(0)->comment('Total nilai baris (qty x (price - diskon))');
            $table->timestamp('created_at')->nullable()->comment('Waktu data dibuat');
            $table->timestamp('updated_at')->nullable()->comment('Waktu terakhir diperbarui');

            $table->index(['transaction_id'], 'transaction_details_tx_idx');
            $table->index(['product_variant_id', 'warehouse_id'], 'transaction_details_lookup_idx');
        });

        // ===================== Inventory movements (audit log) =====================
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id()->comment('Primary key pergerakan persediaan');
            $table->foreignId('transaction_id')
                ->nullable()
                ->constrained('transactions')
                ->cascadeOnUpdate()
                ->nullOnDelete()
                ->comment('Relasi ke transaksi (nullable jika penyesuaian manual)');
            $table->foreignId('from_warehouse_id')
                ->constrained('warehouses')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Gudang asal (stok berkurang jika type=out)');
            $table->foreignId('to_warehouse_id')
                ->nullable()
                ->constrained('warehouses')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Gudang tujuan (stok bertambah jika type=in)');
            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Varian produk yang bergerak');

            $table->bigInteger('qty_change')->comment('Perubahan kuantitas (boleh negatif/positif)');
            $table->enum('type', ['in','out'])->index()->comment('Arah pergerakan stok: in/out');

            $table->dateTime('occurred_at')->index()->comment('Waktu terjadinya pergerakan');
            $table->text('remarks')->nullable()->comment('Catatan tambahan pergerakan');
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('User pencatat pergerakan (nullable)');

            $table->timestamp('created_at')->nullable()->comment('Waktu data dibuat');
            $table->timestamp('updated_at')->nullable()->comment('Waktu terakhir diperbarui');
            $table->timestamp('deleted_at')->nullable()->comment('Waktu soft delete');
            $table->index(['product_variant_id', 'from_warehouse_id', 'to_warehouse_id', 'occurred_at'], 'movements_lookup_idx');
        });

        // ===================== Invoicing & Payments =====================
        Schema::create('invoices', function (Blueprint $table) {
            $table->id()->comment('Primary key invoice');
            $table->foreignId('transaction_id')
                ->unique()
                ->constrained('transactions')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Relasi 1–1 ke transaksi penjualan/dropship');

            $table->string('invoice_number', 64)->unique()->index()->comment('Nomor invoice (unik)');
            $table->dateTime('issued_at')->nullable()->comment('Tanggal terbit invoice');
            $table->dateTime('due_at')->nullable()->comment('Jatuh tempo pembayaran');

            $table->decimal('subtotal', 20, 2)->default(0)->comment('Subtotal sebelum diskon/pajak');
            $table->decimal('discount_total', 20, 2)->default(0)->comment('Total diskon invoice');
            $table->decimal('tax_total', 20, 2)->default(0)->comment('Total pajak invoice');
            $table->decimal('shipping_fee', 20, 2)->default(0)->comment('Biaya pengiriman');
            $table->decimal('total_amount', 20, 2)->default(0)->comment('Total tagihan akhir');
            $table->decimal('paid_amount', 20, 2)->default(0)->comment('Total pembayaran diterima');
            $table->boolean('is_paid')->default(false)->index()->comment('Status lunas (true jika total sudah terbayar)');

            $table->timestamp('created_at')->nullable()->comment('Waktu data dibuat');
            $table->timestamp('updated_at')->nullable()->comment('Waktu terakhir diperbarui');
            $table->timestamp('deleted_at')->nullable()->comment('Waktu soft delete');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id()->comment('Primary key pembayaran');
            $table->foreignId('invoice_id')
                ->constrained('invoices')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Relasi ke invoice yang dibayar');
            $table->decimal('amount', 20, 2)->comment('Nominal pembayaran');
            $table->string('method', 32)->comment('Metode bayar: transfer/cash/card/e-wallet');
            $table->string('reference_no', 64)->nullable()->comment('Nomor referensi eksternal (opsional)');
            $table->dateTime('paid_at')->index()->comment('Tanggal & waktu pembayaran');
            $table->string('notes')->nullable()->comment('Catatan pembayaran (opsional)');
            $table->foreignId('received_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('User yang menerima pembayaran (nullable)');
            $table->timestamp('created_at')->nullable()->comment('Waktu data dibuat');
            $table->timestamp('updated_at')->nullable()->comment('Waktu terakhir diperbarui');
            $table->timestamp('deleted_at')->nullable()->comment('Waktu soft delete');
            $table->index(['invoice_id', 'paid_at'], 'payments_invoice_paid_idx');
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
