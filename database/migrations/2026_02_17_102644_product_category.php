<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();

            // Relasi Self-Referencing (Parent Category)
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('product_categories')
                ->nullOnDelete();

            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            // Kolom JSON untuk cast 'meta' => 'array'
            $table->json('meta')->nullable();

            $table->softDeletes(); // Untuk trait SoftDeletes
            $table->timestamps();
        });

        // Tabel Pivot untuk relasi BelongsToMany ke Product
        Schema::connection($this->connection)->create('product_category_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_category_id')
                ->constrained('product_categories')
                ->cascadeOnDelete();

            // Pastikan tabel 'products' sudah ada atau migrasinya dijalankan sebelumnya
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('product_category_product');
        Schema::connection($this->connection)->dropIfExists('product_categories');
    }
};