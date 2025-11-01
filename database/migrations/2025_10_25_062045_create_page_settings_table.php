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
        Schema::create('page_settings', function (Blueprint $table) {
            $table->id();
            $table->enum('page', ['index','product','cart','checkout', 'wishlist', 'privacy', 'terms', 'about', 'contact', 'faq'])->default('index');
            $table->string('jumbotron')->default('Hello world');

            // PERBAIKAN: Hapus ->default(...) karena kolom LONGTEXT tidak bisa memiliki nilai default eksplisit
            $table->longText('content');
            // Jika Anda ingin ini nullable, tambahkan ->nullable(), tapi ini defaultnya NOT NULL

            $table->string('banner_image')->nullable();
            $table->string('banner_image_alt')->nullable();
            $table->string('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->timestamps();
        });

        Schema::create('page_builder', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_setting_id')->constrained('page_settings')->onDelete('cascade');
            $table->enum('section', ['main', 'sidebar', 'footer', 'banner', 'hero', 'custom'])->default('main');
            $table->enum('content_type', ['text', 'image', 'video', 'html'])->default('text');
            $table->longText('content_value')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_builder');
        Schema::dropIfExists('page_settings');
    }
};
