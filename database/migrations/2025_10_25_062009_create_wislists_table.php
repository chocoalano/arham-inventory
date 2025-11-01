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
        // 1. Perbaikan Nama Tabel (Rekomendasi)
        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('wishlist_items', function (Blueprint $table) {
            $table->id();
            // Menggunakan nama FK yang disarankan 'wishlist_id'
            $table->foreignId('wishlist_id')->constrained('wishlists')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');

            // 2. Perbaikan Syntax Error: Menambahkan aksi 'cascade' pada onDelete()
            // Jika product_variant terhapus, item wishlist juga terhapus.
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->onDelete('cascade');

            // Tambahan: Tambahkan unique constraint untuk mencegah duplikasi item
            $table->unique(['wishlist_id', 'product_variant_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wishlist_items');
        Schema::dropIfExists('wishlists');
    }
};
