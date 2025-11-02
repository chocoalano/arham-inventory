<?php

namespace Database\Seeders;

use App\Models\Blog\Article;
use App\Models\Blog\ArticleCategory;
use App\Models\Blog\ArticleMedia;
use App\Models\Blog\ArticleTag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $faker  = Faker::create('id_ID');
        $author = User::query()->first() ?? User::factory()->create();

        // Buat kategori & tag contoh
        $catIds = collect(['Tips', 'Interior', 'Produk'])->map(function ($name) {
            return ArticleCategory::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            )->id;
        });

        $tagIds = collect(['furniture', 'design', 'image', 'gallery', 'video', 'audio'])->map(function ($name) {
            return ArticleTag::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            )->id;
        });

        foreach (range(1, 20) as $i) {
            $type  = $faker->randomElement(['image', 'gallery', 'audio', 'video']);
            $title = $faker->sentence(5);
            $slug  = Str::slug($title) . '-' . $i;

            $article = Article::create([
                'author_id'        => $author->id,
                'title'            => $title,
                'slug'             => $slug,
                'type'             => $type,
                'main_image'       => "https://picsum.photos/seed/{$slug}/800/517",
                'video_url'        => $type === 'video' ? 'https://www.youtube.com/embed/dQw4w9WgXcQ' : null,
                'audio_url'        => $type === 'audio' ? 'https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/tracks/293' : null,
                'excerpt'          => $faker->paragraph(2),
                'body'             => '<p>' . implode('</p><p>', $faker->paragraphs(6)) . '</p>',
                'reading_time'     => $faker->numberBetween(2, 8),
                'status'           => 'published',
                'is_featured'      => $faker->boolean(20),
                'published_at'     => now()->subDays($faker->numberBetween(0, 365)),
                'meta_title'       => $title,
                'meta_description' => $faker->sentence(12),
                'meta'             => ['canonical' => url('/articles/' . $slug)],
            ]);

            // Sinkron kategori & tag (gunakan kolom pivot category_id / tag_id)
            $article->categories()->sync(
                collect($catIds)->shuffle()->take($faker->numberBetween(1, count($catIds)))->values()->all()
            );
            $article->tags()->sync(
                collect($tagIds)->shuffle()->take($faker->numberBetween(1, 3))->values()->all()
            );

            // Media galeri bila tipe "gallery"
            if ($type === 'gallery') {
                foreach (range(1, $faker->numberBetween(2, 4)) as $ord) {
                    ArticleMedia::create([
                        'article_id' => $article->id,
                        'type'       => 'image',
                        'url'        => "https://picsum.photos/seed/{$slug}-{$ord}/800/517",
                        'title'      => "Gambar {$ord}",
                        'alt'        => "Gambar {$ord} untuk {$title}",
                        'sort_order' => $ord,
                    ]);
                }
            }
        }
    }
}
