<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * 1) Categories
         */
        Schema::create('article_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->unique();
            $table->string('slug', 170)->unique();
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()
                  ->constrained('article_categories')->nullOnDelete();
            $table->timestamps();
        });

        /**
         * 2) Tags
         */
        Schema::create('article_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->string('slug', 150)->unique();
            $table->timestamps();
        });

        /**
         * 3) Articles (utama)
         *    NOTE: asumsikan tabel 'users' sudah ada
         */
        Schema::create('articles', function (Blueprint $table) {
            $table->id();

            // Penulis
            $table->foreignId('author_id')
                  ->constrained('users')
                  ->cascadeOnUpdate()
                  ->restrictOnDelete();

            // Konten utama
            $table->string('title', 200);
            $table->string('slug', 220)->unique();

            // Tipe media (selaras dgn Blade)
            $table->enum('type', ['image','gallery','audio','video'])->default('image');

            // Media utama (opsional)
            $table->string('main_image')->nullable(); // untuk image/thumbnail
            $table->string('video_url')->nullable();  // URL embed YouTube
            $table->string('audio_url')->nullable();  // URL embed SoundCloud

            $table->text('excerpt')->nullable();
            $table->longText('body')->nullable();     // HTML/Markdown

            // Meta & publikasi
            $table->unsignedSmallInteger('reading_time')->nullable(); // menit
            $table->enum('status', ['draft','scheduled','published','archived'])->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable()->index();

            // SEO & statistik
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('meta')->nullable(); // og:image, canonical, dsb

            $table->unsignedBigInteger('view_count')->default(0);
            $table->unsignedBigInteger('like_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
            $table->index('author_id');
        });

        /**
         * 4) Article Media (galeri / media tambahan)
         */
        Schema::create('article_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();

            $table->enum('type', ['image','video','audio'])->default('image');
            $table->string('url');                 // path storage / URL embed
            $table->string('title')->nullable();
            $table->string('alt')->nullable();
            $table->text('caption')->nullable();
            $table->json('meta')->nullable();      // provider, duration, dll

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['article_id','type','sort_order']);
        });

        /**
         * 5) Pivot: article_category (many-to-many)
         */
        Schema::create('article_category', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('article_categories_id')->constrained()->cascadeOnDelete();
            $table->primary(['article_id','article_categories_id']);
        });

        /**
         * 6) Pivot: article_tag (many-to-many)
         */
        Schema::create('article_tag', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('article_tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['article_id','article_tag_id']);
        });
    }

    public function down(): void
    {
        // Urutan drop: pivot -> anak -> induk
        Schema::dropIfExists('article_tag');
        Schema::dropIfExists('article_category');
        Schema::dropIfExists('article_media');
        Schema::dropIfExists('articles');
        Schema::dropIfExists('article_tags');
        Schema::dropIfExists('article_categories');
    }
};
