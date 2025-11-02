@extends('ecommerce.layouts.app')
@section('title', 'Beranda')

@push('css')
@endpush

@section('content')
    {{-- ====== LETAKKAN TEPAT SETELAH @section('content') ====== --}}
    @php
        \Carbon\Carbon::setLocale('id');

        // Normalisasi semua dataset jadi Collection (aman jika null / tidak dikirim controller)
        $heroSlides = collect($heroSlides ?? []);
        $features = collect($features ?? []);

        // Normalisasi featuredCategories → objek seragam (bisa dari array statis / model Eloquent)
        $featuredCategories = collect($featuredCategories ?? [])
            ->map(function ($c) {
                $name = data_get($c, 'name');
                $slug = data_get($c, 'slug');
                $url = data_get($c, 'url'); // opsional, kalau ada field custom URL
                $img = data_get($c, 'image_url'); // simpan path/url gambar pada field ini (storage/.. atau url)

                // Jika $img bukan URL absolut, anggap itu path storage
                if ($img && !filter_var($img, FILTER_VALIDATE_URL)) {
                    $img = asset('storage/' . ltrim($img, '/'));
                }

                return (object) [
                    'name' => $name,
                    'slug' => $slug,
                    'url' => $url,
                    'banner_image' => $img ?: asset('ecommerce/images/category-banner/home1-banner1.webp'),
                    'banner_image_w' => data_get($c, 'banner_image_w', 540),
                    'banner_image_h' => data_get($c, 'banner_image_h', 560),
                ];
            })
            ->values();

        $newProducts = collect($newProducts ?? []);
        $deals = collect($deals ?? []);
        $popularProducts = collect($popularProducts ?? []);
        $topSellingProducts = collect($topSellingProducts ?? []);
        $blogPosts = collect($blogPosts ?? []);
        $instagramImages = collect($instagramImages ?? []);
    @endphp


    <!-- ====================== HERO ====================== -->
    <div class="hero-area pt-15 mb-80">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="slider-container">
                        <div class="hero-slider-one">
                            @forelse($heroSlides ?? [] as $slide)
                                <div class="hero-slider-item {{ $slide->bg_class ?? 'slider-bg-1' }}">
                                    <div
                                        class="slider-content d-flex flex-column justify-content-center align-items-start h-100">
                                        @if($slide->subtitle)
                                        <p>{{ $slide->subtitle }}</p>@endif
                                        <h1>{!! $slide->title !!}</h1>
                                        @if($slide->button_text)
                                            <a href="{{ $slide->button_url ?? '#' }}" class="pataku-btn slider-btn-1">
                                                {{ $slide->button_text }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                {{-- Fallback kosong agar tidak error jika belum ada data --}}
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- Features (keunggulan singkat) --}}
            <div class="row">
                <div class="col-lg-12 pt-40 pb-40">
                    <div class="feature-area">
                        @forelse($features ?? [] as $feature)
                            <div class="single-feature mb-md-20 mb-sm-20 mb-xxs-20">
                                <span class="icon"><i class="{{ $feature->icon ?? 'lnr lnr-star' }}"></i></span>
                                <p>{{ $feature->title }}
                                    @if($feature->desc) <span>{{ $feature->desc }}</span> @endif
                                </p>
                            </div>
                        @empty
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ====================== KATEGORI UNGGULAN ====================== -->
    <div class="featured-categories mb-80">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center mb-40">
                    <div class="section-title">
                        <h2>Kategori <span>Unggulan</span></h2>
                        <p>Tampilkan semua kategori unggulan beserta produk di halaman utama.</p>
                    </div>
                </div>
            </div>

            @php
                // ekspektasi data: $featuredCategories berisi koleksi model Category dengan field:
                // name, slug, banner_image (URL), banner_image_w, banner_image_h, url (opsional)
                $fc = ($featuredCategories ?? collect())->values();
            @endphp

            @if($fc->isNotEmpty())
                <div class="row">
                    {{-- Kartu 1 (besar kiri) --}}
                    @if($fc->get(0))
                        @php $c = $fc->get(0); @endphp
                        <div class="col-lg-6 col-md-6 mb-sm-30">
                            <div class="banner">
                                <a href="{{ $c->url ?? url('/products?category=' . $c->slug) }}">
                                    <img width="{{ $c->banner_image_w ?? 540 }}" height="{{ $c->banner_image_h ?? 560 }}"
                                        src="{{ asset($c->banner_image) }}" class="img-fluid" alt="{{ $c->name }}">
                                </a>
                                <span class="banner-category-title">
                                    <a href="{{ $c->url ?? url('/products?category=' . $c->slug) }}">{{ $c->name }}</a>
                                </span>
                            </div>
                        </div>
                    @endif

                    {{-- Kartu 2 (besar kanan atas) + 3/4 (kecil kanan bawah) --}}
                    <div class="col-lg-6 col-md-6">
                        <div class="row">
                            @if($fc->get(1))
                                @php $c = $fc->get(1); @endphp
                                <div class="col-lg-12 col-md-12 mb-30">
                                    <div class="banner">
                                        <a href="{{ $c->url ?? url('/products?category=' . $c->slug) }}">
                                            <img width="{{ $c->banner_image_w ?? 550 }}" height="{{ $c->banner_image_h ?? 270 }}"
                                                src="{{ $c->banner_image }}" class="img-fluid" alt="{{ $c->name }}">
                                        </a>
                                        <span class="banner-category-title">
                                            <a href="{{ $c->url ?? url('/products?category=' . $c->slug) }}">{{ $c->name }}</a>
                                        </span>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="row">
                            @foreach($fc->slice(2, 2) as $c)
                                <div class="col-lg-6 col-md-6 col-sm-6 col-6">
                                    <div class="banner">
                                        <a href="{{ $c->url ?? url('/products?category=' . $c->slug) }}">
                                            <img width="{{ $c->banner_image_w ?? 265 }}" height="{{ $c->banner_image_h ?? 270 }}"
                                                src="{{ $c->banner_image }}" class="img-fluid" alt="{{ $c->name }}">
                                        </a>
                                        <span class="banner-category-title">
                                            <a href="{{ $c->url ?? url('/products?category=' . $c->slug) }}">{{ $c->name }}</a>
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- ====================== KOLEKSI TERBARU (Produk baru) ====================== -->
    <div class="double-row-product-slider mb-80">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center mb-40">
                    <div class="section-title">
                        <h2>Koleksi <span>Terbaru</span> Produk</h2>
                        <p>Lihat koleksi produk terbaru kami, Anda pasti menemukan yang Anda cari.</p>
                    </div>
                </div>
            </div>

            @php
                $new = ($newProducts ?? collect());
            @endphp

            <div class="row">
                <div class="col-lg-12">
                    <div class="ptk-slider double-row-slider-container" data-row="2">
                        @forelse($new as $product)
                            @php
                                // ambil harga (final -> price -> regular_price)
                                $price = $product->variants->first()->price ?? 0;
                                $compare = $product->variants->first()->price ?? 0;
                                $img = data_get($product, 'imagesPrimary')['image_path'];
                                $badges = collect(data_get($product, 'badges', []));
                                $url = data_get($product, 'url') ?? route('ecommerce.products.show', $product->sku);
                            @endphp
                            <div class="col">
                                @livewire('ecommerce.components.product-card', [
                                    'product' => $product,
                                    'url' => $url,
                                    'img' => asset('storage/'.$img) ?? asset('ecommerce/images/placeholder/300x360.png'),
                                    'price' => $price,
                                    'compare' => $compare,
                                    'badges' => $badges,
                                    'isWishlisted' => false,
                                    'qty' => 1,
                                ])
                            </div>
                        @empty
                            {{-- kosong --}}
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ====================== FULLWIDTH BANNER ====================== -->
    @forelse(($fullwidthBanners ?? []) as $banner)
        <div
            class="fullwidth-banner-area {{ $banner->bg_class ?? 'fullwidth-banner-bg fullwidth-banner-bg-1' }} pt-120 pb-120 pt-xs-80 pb-xs-80 mb-80">
            <div class="container">
                <div class="row">
                    <div class="col-xl-6 col-lg-7 col-md-9 col-12">
                        <div class="fullwidth-banner-content">
                            <p class="fullwidth-banner-title">{{ $banner->title }}</p>
                            @if($banner->description)
                            <p>{{ $banner->description }}</p>@endif
                            @if($banner->button_text)
                                <a href="{{ $banner->button_url ?? '#' }}">{{ $banner->button_text }} <i
                                        class="fa fa-angle-right"></i></a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @empty
    @endforelse

    <!-- ====================== PROMO MINGGU INI + PRODUK POPULER ====================== -->
    <div class="deal-popular-product-area mb-80">
        <div class="container">
            <div class="row">
                {{-- Promo minggu ini --}}
                <div class="col-lg-6 mb-md-80 mb-sm-80">
                    <div class="section-title mb-40">
                        <h2>Promo <span>Minggu</span> Ini</h2>
                        <p>Pilihan promo terbaru yang diperbarui setiap minggu!</p>
                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="ptk-slider deal-slider-container">
                                @forelse(($deals ?? []) as $deal)
                                    @php
                                        $price = $deal->variants->first()->price ?? 0;
                                        $compare = $deal->variants->first()->price ?? 0;
                                        $img = asset('storage/'.$deal->imagesPrimary->image_path) ?? asset('ecommerce/images/placeholder/300x360.png');
                                        $badges = collect(data_get($deal, 'badges', []));
                                        $url = route('ecommerce.products.show', $deal->sku);
                                        $countdown = optional(data_get($deal, 'deal_ends_at')) instanceof \Carbon\Carbon
                                            ? data_get($deal, 'deal_ends_at')->format('Y/m/d')
                                            : (data_get($deal, 'deal_ends_at') ? \Carbon\Carbon::parse(data_get($deal, 'deal_ends_at'))->format('Y/m/d') : null);
                                    @endphp
                                    <div class="col">
                                        @if($countdown)
                                            <div class="product-countdown" data-countdown="{{ $countdown }}"></div>
                                        @endif
                                        @livewire('ecommerce.components.product-card', [
                                            'product' => $deal,
                                            'url' => $url,
                                            'img' => $img,
                                            'price' => $price,
                                            'compare' => $compare,
                                            'badges' => $badges,
                                            'isWishlisted' => false,
                                            'qty' => 1,
                                        ])
                                    </div>
                                @empty
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Produk populer --}}
                <div class="col-lg-6">
                    <div class="section-title mb-40">
                        <h2>Produk <span>Populer</span></h2>
                        <p>Kami menawarkan pilihan busana terbaik dengan harga yang Anda sukai!</p>
                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="ptk-slider popular-product-slider" data-row="3">
                                @forelse(($popularProducts ?? []) as $product)
                                    @php
                                        $price = $product->variants->first()->price ?? 0;
                                        $compare = $product->variants->first()->price ?? 0;
                                        $img = asset('storage/'.$product->imagesPrimary->image_path) ?? asset('ecommerce/images/placeholder/300x360.png');
                                        $url = data_get($product, 'url') ?? route('ecommerce.products.show', $product->sku);
                                    @endphp
                                    <div class="col">
                                        @livewire('ecommerce.components.product-card', [
                                            'product' => $product,
                                            'url' => $url,
                                            'img' => $img,
                                            'price' => $price,
                                            'compare' => $compare,
                                            'badges' => collect(data_get($product, 'badges', [])),
                                            'isWishlisted' => false,
                                            'qty' => 1,
                                        ])
                                    </div>
                                @empty
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

            </div> {{-- row --}}
        </div>
    </div>

    <!-- ====================== PRODUK TERLARIS ====================== -->
    <div class="top-selling-product-area mb-80">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center mb-40">
                    <div class="section-title">
                        <h2>Produk <span>Terlaris</span></h2>
                        <p>Lihat koleksi produk terlaris kami, Anda pasti menemukan yang Anda cari.</p>
                    </div>
                </div>
            </div>
            @php $tops = ($topSellingProducts ?? collect()); @endphp
            <div class="row">
                <div class="col-lg-12">
                    <div class="ptk-slider top-selling-product-slider-container">
                        @forelse($tops as $product)
                            @php
                                $price = $product->variants->first()->price ?? 0;
                                $compare = $product->variants->first()->compare_price ?? 0;
                                $img = asset('storage/'.$product->imagesPrimary->image_path) ?? asset('ecommerce/images/placeholder/300x360.png');
                                $badges = collect($product->badges ?? []);
                                $url = $product->url ?? route('ecommerce.products.show', $product->sku);
                            @endphp
                            <div class="col">
                                @livewire('ecommerce.components.product-card', [
                                    'product' => $product,
                                    'url' => $url,
                                    'img' => $img,
                                    'price' => $price,
                                    'compare' => $compare,
                                    'badges' => $badges,
                                    'isWishlisted' => false,
                                    'qty' => 1,
                                ])
                            </div>
                        @empty
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ====================== BLOG KAMI ====================== -->
    <div class="blog-slider-section mb-80">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center mb-40">
                    <div class="section-title">
                        <h2>Blog <span>Kami</span></h2>
                        <p>Menyoroti momen menarik dari blog Anda.</p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <div class="blog-post-slider-container ptk-slider">
                        @forelse(($blogPosts ?? []) as $post)
                            @php
                                $img = $post->main_image ?? optional($post->media->first())->url ?? asset('ecommerce/images/placeholder/800x517.png');
                                $url = $post->url ?? url('/articles/' . $post->slug);
                            @endphp
                            <div class="col">
                                <div class="single-slider-blog-post">
                                    <div class="image">
                                        <a href="{{ $url }}">
                                            <img width="800" height="517" src="{{ $img }}" class="img-fluid"
                                                alt="{{ $post->title }}">
                                        </a>
                                    </div>
                                    <div class="content">
                                        <p class="blog-title"><a href="{{ $url }}">{{ $post->title }}</a></p>
                                        <a href="{{ $url }}" class="readmore-btn">Baca Selengkapnya</a>
                                    </div>
                                </div>
                            </div>
                        @empty
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
@endpush
