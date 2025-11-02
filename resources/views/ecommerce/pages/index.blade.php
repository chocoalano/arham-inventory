@extends('ecommerce.layouts.app')
@section('title', 'Beranda')

@push('css')
@endpush

@section('content')
    @php
        \Carbon\Carbon::setLocale('id');

        // ===================== NORMALISASI DATA =====================
        // Hero slides -> pakai object uniform
        $heroSlides = collect($heroSlides ?? [])->map(function ($s) {
            return (object) [
                'bg_class'    => data_get($s, 'bg_class', 'slider-bg-1'),
                'subtitle'    => data_get($s, 'subtitle'),
                'title'       => data_get($s, 'title', ''),
                'button_text' => data_get($s, 'button_text'),
                'button_url'  => data_get($s, 'button_url', '#'),
            ];
        })->values();

        // Features -> object uniform
        $features = collect($features ?? [])->map(function ($f) {
            return (object) [
                'icon' => data_get($f, 'icon', 'lnr lnr-star'),
                'title'=> data_get($f, 'title', ''),
                'desc' => data_get($f, 'desc'),
            ];
        })->values();

        // Featured categories -> object uniform (image_url boleh path storage atau URL)
        $featuredCategories = collect($featuredCategories ?? [])
            ->map(function ($c) {
                $img = data_get($c, 'image_url');
                if ($img && !filter_var($img, FILTER_VALIDATE_URL)) {
                    $img = asset('storage/' . ltrim($img, '/'));
                }
                return (object) [
                    'name'           => data_get($c, 'name', 'Kategori'),
                    'slug'           => data_get($c, 'slug', ''),
                    'url'            => data_get($c, 'url'),
                    'banner_image'   => $img ?: asset('ecommerce/images/category-banner/home1-banner1.webp'),
                    'banner_image_w' => data_get($c, 'banner_image_w', 540),
                    'banner_image_h' => data_get($c, 'banner_image_h', 560),
                ];
            })->values();

        // Koleksi produk & blog (biarkan apa adanya, tapi aksesnya pakai data_get saat dipakai)
        $newProducts       = collect($newProducts ?? []);
        $deals             = collect($deals ?? []);
        $popularProducts   = collect($popularProducts ?? []);
        $topSellingProducts= collect($topSellingProducts ?? []);
        $blogPosts         = collect($blogPosts ?? []);
        $instagramImages   = collect($instagramImages ?? []);
    @endphp

    <!-- ====================== HERO ====================== -->
    <div class="hero-area pt-15 mb-80">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="slider-container">
                        <div class="hero-slider-one">
                            @forelse($heroSlides as $slide)
                                <div class="hero-slider-item {{ $slide->bg_class }}">
                                    <div class="slider-content d-flex flex-column justify-content-center align-items-start h-100">
                                        @if(!empty($slide->subtitle))
                                            <p>{{ $slide->subtitle }}</p>
                                        @endif
                                        <h1>{!! $slide->title !!}</h1>
                                        @if(!empty($slide->button_text))
                                            <a href="{{ $slide->button_url }}" class="pataku-btn slider-btn-1">
                                                {{ $slide->button_text }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                {{-- kosong --}}
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- Features --}}
            <div class="row">
                <div class="col-lg-12 pt-40 pb-40">
                    <div class="feature-area">
                        @forelse($features as $feature)
                            <div class="single-feature mb-md-20 mb-sm-20 mb-xxs-20">
                                <span class="icon"><i class="{{ $feature->icon }}"></i></span>
                                <p>{{ $feature->title }} @if(!empty($feature->desc)) <span>{{ $feature->desc }}</span> @endif</p>
                            </div>
                        @empty
                            {{-- kosong --}}
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

            @php $fc = $featuredCategories; @endphp

            @if($fc->isNotEmpty())
                <div class="row">
                    {{-- Besar kiri --}}
                    @if($fc->get(0))
                        @php $c = $fc->get(0); @endphp
                        <div class="col-lg-6 col-md-6 mb-sm-30">
                            <div class="banner">
                                <a href="{{ $c->url ?? url('/products?category=' . $c->slug) }}">
                                    {{-- JANGAN dibungkus asset() lagi karena sudah absolut --}}
                                    <img width="{{ $c->banner_image_w }}" height="{{ $c->banner_image_h }}"
                                         src="{{ $c->banner_image }}" class="img-fluid" alt="{{ $c->name }}">
                                </a>
                                <span class="banner-category-title">
                                    <a href="{{ $c->url ?? url('/products?category=' . $c->slug) }}">{{ $c->name }}</a>
                                </span>
                            </div>
                        </div>
                    @endif

                    {{-- Kanan (besar atas + 2 kecil bawah) --}}
                    <div class="col-lg-6 col-md-6">
                        <div class="row">
                            @if($fc->get(1))
                                @php $c = $fc->get(1); @endphp
                                <div class="col-lg-12 col-md-12 mb-30">
                                    <div class="banner">
                                        <a href="{{ $c->url ?? url('/products?category=' . $c->slug) }}">
                                            <img width="{{ $c->banner_image_w }}" height="{{ $c->banner_image_h }}"
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
                                            <img width="{{ $c->banner_image_w }}" height="{{ $c->banner_image_h }}"
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

    <!-- ====================== KOLEKSI TERBARU ====================== -->
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

            @php $new = $newProducts; @endphp

            <div class="row">
                <div class="col-lg-12">
                    <div class="ptk-slider double-row-slider-container" data-row="2">
                        @forelse($new as $product)
                            @php
                                $price   = data_get($product, 'variants.0.price', data_get($product, 'price', 0));
                                $compare = data_get($product, 'variants.0.compare_price', data_get($product, 'regular_price'));
                                $imgPath = data_get($product, 'imagesPrimary.image_path'); // <- SAFE
                                $imgUrl  = $imgPath ? asset('storage/' . $imgPath) : asset('ecommerce/images/placeholder/300x360.png');
                                $badges  = (array) data_get($product, 'badges', []);
                                $url     = data_get($product, 'url') ?? (data_get($product, 'sku') ? route('ecommerce.products.show', data_get($product, 'sku')) : '#');
                            @endphp
                            <div class="col">
                                @livewire('ecommerce.components.product-card', [
                                    'product'       => $product,
                                    'url'           => $url,
                                    'img'           => $imgUrl,
                                    'price'         => $price,
                                    'compare'       => $compare,
                                    'badges'        => $badges,
                                    'isWishlisted'  => false,
                                    'qty'           => 1,
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
        @php
            $bgClass = data_get($banner, 'bg_class', 'fullwidth-banner-bg fullwidth-banner-bg-1');
            $title   = data_get($banner, 'title', '');
            $desc    = data_get($banner, 'description');
            $btnText = data_get($banner, 'button_text');
            $btnUrl  = data_get($banner, 'button_url', '#');
        @endphp
        <div class="fullwidth-banner-area {{ $bgClass }} pt-120 pb-120 pt-xs-80 pb-xs-80 mb-80">
            <div class="container">
                <div class="row">
                    <div class="col-xl-6 col-lg-7 col-md-9 col-12">
                        <div class="fullwidth-banner-content">
                            <p class="fullwidth-banner-title">{{ $title }}</p>
                            @if(!empty($desc)) <p>{{ $desc }}</p> @endif
                            @if(!empty($btnText))
                                <a href="{{ $btnUrl }}">{{ $btnText }} <i class="fa fa-angle-right"></i></a>
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
                                @forelse($deals as $deal)
                                    @php
                                        $price    = data_get($deal, 'variants.0.price', data_get($deal, 'price', 0));
                                        $compare  = data_get($deal, 'variants.0.compare_price', data_get($deal, 'regular_price'));
                                        $imgPath  = data_get($deal, 'imagesPrimary.image_path');
                                        $imgUrl   = $imgPath ? asset('storage/' . $imgPath) : asset('ecommerce/images/placeholder/300x360.png');
                                        $badges   = (array) data_get($deal, 'badges', []);
                                        $url      = route('ecommerce.products.show', data_get($deal, 'sku'));
                                        $endsAt   = data_get($deal, 'deal_ends_at');
                                        $countdown= $endsAt ? \Carbon\Carbon::parse($endsAt)->format('Y/m/d') : null;
                                    @endphp
                                    <div class="col">
                                        @if($countdown)
                                            <div class="product-countdown" data-countdown="{{ $countdown }}"></div>
                                        @endif
                                        @livewire('ecommerce.components.product-card', [
                                            'product'       => $deal,
                                            'url'           => $url,
                                            'img'           => $imgUrl,
                                            'price'         => $price,
                                            'compare'       => $compare,
                                            'badges'        => $badges,
                                            'isWishlisted'  => false,
                                            'qty'           => 1,
                                        ])
                                    </div>
                                @empty
                                    {{-- kosong --}}
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
                                @forelse($popularProducts as $product)
                                    @php
                                        $price   = data_get($product, 'variants.0.price', data_get($product, 'price', 0));
                                        $compare = data_get($product, 'variants.0.compare_price', data_get($product, 'regular_price'));
                                        $imgPath = data_get($product, 'imagesPrimary.image_path');
                                        $imgUrl  = $imgPath ? asset('storage/' . $imgPath) : asset('ecommerce/images/placeholder/300x360.png');
                                        $badges  = (array) data_get($product, 'badges', []);
                                        $url     = data_get($product, 'url') ?? (data_get($product, 'sku') ? route('ecommerce.products.show', data_get($product, 'sku')) : '#');
                                    @endphp
                                    <div class="col">
                                        @livewire('ecommerce.components.product-card', [
                                            'product'       => $product,
                                            'url'           => $url,
                                            'img'           => $imgUrl,
                                            'price'         => $price,
                                            'compare'       => $compare,
                                            'badges'        => $badges,
                                            'isWishlisted'  => false,
                                            'qty'           => 1,
                                        ])
                                    </div>
                                @empty
                                    {{-- kosong --}}
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
            @php $tops = $topSellingProducts; @endphp
            <div class="row">
                <div class="col-lg-12">
                    <div class="ptk-slider top-selling-product-slider-container">
                        @forelse($tops as $product)
                            @php
                                $price   = data_get($product, 'variants.0.price', data_get($product, 'price', 0));
                                $compare = data_get($product, 'variants.0.compare_price', data_get($product, 'regular_price'));
                                $imgPath = data_get($product, 'imagesPrimary.image_path');
                                $imgUrl  = $imgPath ? asset('storage/' . $imgPath) : asset('ecommerce/images/placeholder/300x360.png');
                                $badges  = (array) data_get($product, 'badges', []);
                                $url     = data_get($product, 'url') ?? (data_get($product, 'sku') ? route('ecommerce.products.show', data_get($product, 'sku')) : '#');
                            @endphp
                            <div class="col">
                                @livewire('ecommerce.components.product-card', [
                                    'product'       => $product,
                                    'url'           => $url,
                                    'img'           => $imgUrl,
                                    'price'         => $price,
                                    'compare'       => $compare,
                                    'badges'        => $badges,
                                    'isWishlisted'  => false,
                                    'qty'           => 1,
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
                        @forelse($blogPosts as $post)
                            @php
                                $img = data_get($post, 'main_image')
                                    ?: data_get($post, 'media.0.url')
                                    ?: asset('ecommerce/images/placeholder/800x517.png');
                                $url = data_get($post, 'url') ?? url('/articles/' . data_get($post, 'slug'));
                            @endphp
                            <div class="col">
                                <div class="single-slider-blog-post">
                                    <div class="image">
                                        <a href="{{ $url }}">
                                            <img width="800" height="517" src="{{ $img }}" class="img-fluid" alt="{{ data_get($post, 'title', 'Artikel') }}">
                                        </a>
                                    </div>
                                    <div class="content">
                                        <p class="blog-title"><a href="{{ $url }}">{{ data_get($post, 'title', 'Artikel') }}</a></p>
                                        <a href="{{ $url }}" class="readmore-btn">Baca Selengkapnya</a>
                                    </div>
                                </div>
                            </div>
                        @empty
                            {{-- kosong --}}
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
@endpush
