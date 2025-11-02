<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Blog\Article;
use App\Models\Inventory\Product;
use App\Models\RawMaterial\RawMaterialCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HomeController extends Controller
{
    public function index()
    {
        // Banner/Hero (jika punya model Banner). Jika belum, ganti ke Collection manual dari DB setting.
        $heroSlides = collect(); // Banner::active()->orderBy('sort_order')->get(['title','subtitle','button_text','button_url','bg_class']);

        // Fitur (jika punya tabel features)
        $features = collect(); // Feature::active()->orderBy('sort_order')->get(['icon','title','desc']);

        // Kategori unggulan produk
        $featuredCategories = RawMaterialCategory::query()
            ->orderBy('id')
            ->take(4)
            ->get()
            ->map(function ($c) {
                // normalisasi field agar Blade bisa langsung pakai
                $c->banner_image = $c->banner_image ?? $c->image_url; // sesuaikan
                $c->banner_image_w = $c->banner_image_w ?? 540;
                $c->banner_image_h = $c->banner_image_h ?? 560;
                $c->url = url('/products?category='.$c->name);

                return $c;
            });

        // Produk terbaru
        $newProducts = Product::query()
            ->with(['imagesPrimary', 'variants'])
            ->where('is_active', true)
            ->latest('created_at')
            ->take(12)
            ->get();

        // Promo minggu ini (produk dengan deal_ends_at masih aktif)
        $deals = Product::query()
            ->with(['imagesPrimary', 'variants'])
            ->orderBy('created_at', 'desc')
            ->take(8)
            ->get();

        // Produk populer (berdasarkan view_count atau penjualan)
        $popularProducts = Product::query()
            ->with(['imagesPrimary', 'variants'])
            ->where('is_active', true)
            ->orderByDesc('created_at')
            ->take(12)
            ->get();

        // Produk terlaris (berdasarkan sold_count)
        $topSellingProducts = Product::query()
            ->with(['imagesPrimary', 'variants'])
            ->where('is_active', true)
            ->orderByDesc('created_at')
            ->take(10)
            ->get();

        // Artikel terbaru
        $blogPosts = Article::published()
            ->latest('published_at')
            ->with(['media'])
            ->take(8)
            ->get()
            ->each(function ($a) {
                $a->url = url('/articles/'.$a->slug);
            });

        // Instagram (jika punya tabel/endpoint). Bisa juga ambil dari setting statis.
        $instagramAccountUrl = config('services.instagram.url'); // optional
        $instagramImages = collect(); // InstagramMedia::latest()->take(10)->get(['src','width','height']);

        return view('ecommerce.pages.index', compact(
            'heroSlides',
            'features',
            'featuredCategories',
            'newProducts',
            'deals',
            'popularProducts',
            'topSellingProducts',
            'blogPosts',
            'instagramImages',
            'instagramAccountUrl',
        ));
    }

    public function about()
    {
        return view('ecommerce.pages.about');
    }

    public function articles(Request $request)
    {
        $perPage = 9;

        $q = Article::query()
            ->with([
                'author:id,name',
                'media' => fn ($m) => $m->orderBy('sort_order'),
            ])
            ->published()
            ->latest('published_at');

        // Optional filters
        if ($search = $request->query('q')) {
            $q->where(function ($qq) use ($search) {
                $qq->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            });
        }

        if ($cat = $request->query('category')) {
            $q->whereHas('categories', fn ($c) => $c->where('slug', $cat));
        }

        if ($tag = $request->query('tag')) {
            $q->whereHas('tags', fn ($t) => $t->where('slug', $tag));
        }

        // simple pagination (use LengthAwarePaginator so we can setCollection)
        $posts = $q->paginate($perPage)->withQueryString();

        // Transform ke struktur yang diharapkan Blade (array associative per item)
        $posts->setCollection(
            $posts->getCollection()->map(function (Article $a) {
                // Kumpulkan images:
                if ($a->type === 'gallery') {
                    $images = $a->media->where('type', 'image')->pluck('url')->values()->all();
                    if (empty($images) && $a->main_image) {
                        $images = [$a->main_image];
                    }
                } else {
                    // image/audio/video: pakai main_image atau fallback gambar pertama di media
                    $images = $a->main_image
                        ? [$a->main_image]
                        : $a->media->where('type', 'image')->pluck('url')->take(1)->values()->all();
                }

                return [
                    'id' => $a->id,
                    'title' => $a->title,
                    'slug' => $a->slug,
                    'author' => optional($a->author)->name ?? 'Admin',
                    'date' => $a->published_at ?? $a->created_at,
                    'excerpt' => $a->excerpt ?? Str::limit(strip_tags((string) $a->body), 220),
                    'type' => $a->type,                 // image | gallery | audio | video
                    'images' => $images,
                    'audio' => $a->audio_url,
                    'video' => $a->video_url,
                    'url' => route('ecommerce.articles.detail', ['slug' => $a->slug]),
                ];
            })
        );

        return view('ecommerce.pages.articles', compact('posts'));
    }

    public function articles_detail(string $slug)
    {
        // Ambil artikel utama beserta relasi yang dibutuhkan
        $article = Article::query()
            ->with([
                'author:id,name',
                'media' => fn ($q) => $q->orderBy('sort_order'),
            ])
            ->published()
            ->where('slug', $slug)
            ->first();

        if (! $article) {
            abort(404); // atau redirect()->to('/articles')->with('warning','Artikel tidak ditemukan.');
        }

        // Normalisasi URL file (kalau simpan di storage public)
        $normalizeUrl = function (?string $url) {
            if (blank($url)) {
                return null;
            }

            return Str::startsWith($url, ['http://', 'https://', '//'])
                ? $url
                : Storage::url($url);
        };

        // Kumpulkan images sesuai tipe
        if ($article->type === 'gallery') {
            $images = $article->media
                ->where('type', 'image')
                ->pluck('url')
                ->map($normalizeUrl)
                ->values()
                ->all();

            if (empty($images) && $article->main_image) {
                $images = [$normalizeUrl($article->main_image)];
            }
        } else {
            $images = $article->main_image
                ? [$normalizeUrl($article->main_image)]
                : $article->media->where('type', 'image')->pluck('url')->take(1)->map($normalizeUrl)->values()->all();
        }

        // Bentuk payload $post sesuai Blade
        $post = [
            'id' => $article->id,
            'title' => $article->title,
            'slug' => $article->slug,
            'author' => optional($article->author)->name ?? 'Admin',
            'date' => $article->published_at ?? $article->created_at,
            'excerpt' => $article->excerpt ?? Str::limit(strip_tags((string) $article->body), 220),
            'type' => $article->type,                 // image | gallery | audio | video
            'images' => $images,
            'audio' => $article->audio_url ? $normalizeUrl($article->audio_url) : null,
            'video' => $article->video_url ? $normalizeUrl($article->video_url) : null,
            'url' => route('ecommerce.articles.detail', ['slug' => $article->slug]),
        ];

        // Recent posts: artikel lain yang published, exclude current
        $recentModels = Article::query()
            ->with(['media' => fn ($q) => $q->orderBy('sort_order')])
            ->published()
            ->where('id', '!=', $article->id)
            ->latest('published_at')
            ->take(4)
            ->get();

        $recent = $recentModels->map(function (Article $a) use ($normalizeUrl) {
            $imgs = $a->main_image
                ? [$normalizeUrl($a->main_image)]
                : $a->media->where('type', 'image')->pluck('url')->take(1)->map($normalizeUrl)->values()->all();

            return [
                'id' => $a->id,
                'title' => $a->title,
                'slug' => $a->slug,
                'images' => $imgs,
                'date' => $a->published_at ?? $a->created_at,
                'url' => route('ecommerce.articles.detail', ['slug' => $a->slug]),
            ];
        })->values();

        return view('ecommerce.pages.articles_detail', compact('post', 'recent'));
    }
}
