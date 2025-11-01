<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Cart;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        // === UI labels → value ===
        $sortOptions = [
            ['value' => 'popular',    'label' => 'Urutkan Berdasarkan Popularitas'],
            ['value' => 'newest',     'label' => 'Urutkan Berdasarkan Produk Terbaru'],
            ['value' => 'price_asc',  'label' => 'Urutkan Berdasarkan Harga: Termurah'],
            ['value' => 'price_desc', 'label' => 'Urutkan Berdasarkan Harga: Termahal'],
        ];

        // === Params ===
        $search  = $request->input('search');             // ?search=...
        $brandId = $request->input('category.brand');     // ?category[brand]=...
        $modelId = $request->input('category.model');     // ?category[model]=...
        $sortKey = $request->input('sort-by', 'popular'); // popular|newest|price_asc|price_desc
        $perPage = (int) $request->input('per_page', 16);

        // Validasi sortKey
        $allowedSort = collect($sortOptions)->pluck('value')->all();
        if (! in_array($sortKey, $allowedSort, true)) {
            $sortKey = 'popular';
        }

        // === Query: Products ===
        // - relasi images (minimal kolom)
        // - subselect min_price & max_cost_price dari product_variants
        $products = Product::query()
            ->with(['imagesPrimary:id,product_id,image_path', 'images:id,product_id,image_path', 'variants' => function ($q) {
            $q->select('id','product_id','sku_variant','color','size','status','price','cost_price')
              ->withSum('stocks as total_stocks', 'qty');
        }, 'variantStocks'])
            ->select('products.*')
            ->selectSub(
                ProductVariant::selectRaw('MIN(price)')
                    ->whereColumn('product_variants.product_id', 'products.id'),
                'min_price'
            )
            ->selectSub(
                ProductVariant::selectRaw('MAX(cost_price)')
                    ->whereColumn('product_variants.product_id', 'products.id'),
                'max_cost_price'
            )
            // Search by name
            ->when($search, fn ($q) => $q->where('name', 'like', '%' . $search . '%'))
            // Filter brand/model (dukung brand vs brand_id, model vs model_id)
            ->when($brandId, fn ($q) => $q->where(function ($w) use ($brandId) {
                $w->where('brand', $brandId)->orWhere('brand_id', $brandId);
            }))
            ->when($modelId, fn ($q) => $q->where(function ($w) use ($modelId) {
                $w->where('model', $modelId)->orWhere('model_id', $modelId);
            }));

        // === Sorting ===
        switch ($sortKey) {
            case 'newest':
                $products->orderByDesc('created_at');
                break;
            case 'price_asc':
                // dorong NULL ke belakang lalu ASC
                $products->orderByRaw('min_price IS NULL, min_price ASC');
                break;
            case 'price_desc':
                // dorong NULL ke belakang lalu DESC
                $products->orderByRaw('min_price IS NULL, min_price DESC');
                break;
            case 'popular':
            default:
                // kamu bisa ganti ke sales_count desc jika ada
                $products->orderByDesc('id');
                break;
        }

        // === Pagination ===
        /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator $paginator */
        $paginator = $products->paginate($perPage)->withQueryString();

        // === Transform → payload untuk Blade & Quick View (termasuk gallery) ===
        $paginator = $paginator->through(function ($product) {
            // ambil gambar pertama sebagai cover
            $firstImage = $product->imagesPrimary;
            $imagePath  = $firstImage?->image_path ?? 'ecommerce/images/products/product01.webp';
            $imageUrl   = asset('storage/'.$imagePath);

            // build gallery dari relasi images (URL absolut)
            $gallery = collect($product->images ?? [])
                ->pluck('image_path')
                ->filter()
                ->map(fn ($p) => asset('storage/'.$p))
                ->values();

            $price     = $product->min_price !== null ? (float) $product->min_price : null;
            $costPrice = $product->max_cost_price !== null ? (float) $product->max_cost_price : null;

            $discountBadge = null;
            if ($price !== null && $costPrice !== null && $costPrice > 0 && $price < $costPrice) {
                $discountBadge = '-' . round((($costPrice - $price) / $costPrice) * 100) . '%';
            }
            return [
                // data utama
                'sku'       => $product->sku ?? 'sku',
                'title'       => $product->name ?? 'Produk',
                'description' => $product->description ?? '',
                // harga (string untuk tampilan)
                'price'       => $price !== null ? 'Rp ' . number_format($price, 0, ',', '.') : null,
                'cost_price'  => $costPrice !== null ? 'Rp ' . number_format($costPrice, 0, ',', '.') : null,
                // badge
                'badges'      => [
                    'new'      => optional($product->created_at)->diffInDays(now()) < 30,
                    'discount' => $discountBadge,
                ],
                // rating fallback jika tidak ada kolom rating
                'stock'      => $product->variantStocks->sum('qty') ?? 0 ?? 0,
                'rating'      => $product->rating ?? 4,
                'variants'    => $product->variants->toArray() ?? [],
                // gambar
                'image'       => $imageUrl,                 // cover utama
                'gallery'     => $gallery->all(),           // dipakai JS Quick View; kosong → JS fallback
            ];
        });

        // === Kirim ke view ===
        return view('ecommerce.pages.products.list', [
            'products'      => $paginator,   // foreach($products as $product)
            'paginator'     => $paginator,   // $paginator->links()
            'sortOptions'   => $sortOptions, // [{value,label}, ...]
            'selectedSort'  => $sortKey,     // set <option selected>
            'appliedFilter' => [
                'category' => ['brand' => $brandId, 'model' => $modelId],
                'search'   => $search,
                'sort'     => $sortKey,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function show(Request $request, Product $product)
    {
        // Muat relasi seperlunya
        $product->loadMissing([
            'imagesPrimary:id,product_id,image_path',
            'images:id,product_id,image_path',
            'variants:id,product_id,price,cost_price',
            'variantStocks'
        ]);

        // Ambil harga dari varian (fallback ke kolom langsung jika ada)
        $minPrice = optional($product->variants)->min('price') ?? $product->price ?? null;
        $maxCost  = optional($product->variants)->max('cost_price') ?? $product->cost_price ?? null;
        $harga    = Product::bangunHarga($minPrice, $maxCost);
        // ===== Data utama produk (ARRAY) =====
        $produk = [
            'judul'      => $product->name ?? 'Produk',
            'referensi'  => $product->sku ?? ('PRD-'.$product->id),
            'rating'     => (int) ($product->rating ?? 4),
            'harga'      => $harga,
            'stock'      => $product->variantStocks->sum('qty') ?? 0,
            'deskripsi'  => $product->description ?? '',
            'galeri'     => $product->images->toArray(),
            'kebijakan'  => Product::bangunKebijakan(),
        ];

        // ===== Fitur/Spesifikasi ringkas (ARRAY) =====
        $fitur = [
            ['label' => 'Nama',   'nilai' => $produk['judul']],
            ['label' => 'SKU',    'nilai' => $produk['referensi']],
            ['label' => 'Brand',  'nilai' => $product->brand ?? '-'],
            ['label' => 'Model',  'nilai' => $product->model ?? '-'],
        ];

        // ===== Ulasan (ARRAY) – isi dari tabel reviews jika sudah ada =====
        $ulasan = []; // contoh: ['nama'=>'Budi','rating'=>5,'komentar'=>'Mantap']

        // ===== Produk terkait (ARRAY) =====
        $produkTerkait = Product::query()
            ->with([
                'imagesPrimary:id,product_id,image_path', 'images:id,product_id,image_path', 'variants' => function ($q) {
            $q->select('id','product_id','sku_variant','color','size','status','price','cost_price')
              ->withSum('stocks as total_stocks', 'qty');
        }, 'variantStocks'
                ])
            ->when(
                ($product->brand_id ?? null) || ($product->brand ?? null),
                fn ($q) => $q->where(function ($w) use ($product) {
                    if (!empty($product->brand)) $w->where('brand', $product->brand);
                }),
                fn ($q) => $q->inRandomOrder()
            )
            ->where('id', '!=', $product->id)
            ->limit(10)
            ->get()
            ->map(function (Product $p) {
                $gallery = collect($p->images ?? [])
                ->pluck('image_path')
                ->filter()
                ->map(fn ($p) => asset('storage/'.$p))
                ->values();

                $price     = $p->variants->first()->price ?? null;
                $costPrice = $p->variants->first()->cost_price ?? null;

                $discountBadge = null;
                if ($price !== null && $costPrice !== null && $costPrice > 0 && $price < $costPrice) {
                    $discountBadge = '-' . round((($costPrice - $price) / $costPrice) * 100) . '%';
                }

                $firstImage = $p->imagesPrimary;
                $imagePath  = $firstImage?->image_path ?? 'ecommerce/images/products/product01.webp';
                $imageUrl   = asset('storage/'.$imagePath);

                return [
                    'sku'       => $p->sku ?? 'sku',
                    'title'       => $p->name ?? 'Produk',
                    'description' => $p->description ?? '',
                    // harga (string untuk tampilan)
                    'price'       => $price !== null ? 'Rp ' . number_format($price, 0, ',', '.') : null,
                    'cost_price'  => $costPrice !== null ? 'Rp ' . number_format($costPrice, 0, ',', '.') : null,
                    // badge
                    'badges'      => [
                        'new'      => optional($p->created_at)->diffInDays(now()) < 30,
                        'discount' => $discountBadge,
                    ],
                    // rating fallback jika tidak ada kolom rating
                    'stock'      => $p->variantStocks->sum('qty') ?? 0,
                    'rating'      => $p->rating ?? 4,
                    'variants'    => $p->variants->toArray() ?? [],
                    // gambar
                    'image'       => $imageUrl,                 // cover utama
                    'gallery'     => $gallery->all(),
                    'url'    => route('ecommerce.products.show', $p),
                ];
            })
            ->values()
            ->all();

        return view('ecommerce.pages.products.detail', [
            'produk'        => $produk,
            'fitur'         => $fitur,
            'ulasan'        => $ulasan,
            'produkTerkait' => $produkTerkait,
        ]);
    }
}
