@extends('ecommerce.layouts.app')
@section('title', 'Beranda')
@push('css')

@endpush
@section('content')
    <!--=============================================
        =            Hero Area One         =
        =============================================-->

    @php
        $heroSlides = [
            [
                'bg_class' => 'slider-bg-1',
                'subtitle' => 'Dekorasi indah dan mewah dengan harga terjangkau',
                'title' => 'KURSI <span>AKSEN</span>',
                'button_text' => 'BELI SEKARANG',
                'button_url' => 'shop-left-sidebar.html',
            ],
            [
                'bg_class' => 'slider-bg-2',
                'subtitle' => 'Lampu meja LED ultra terang, cocok untuk pencocokan warna',
                'title' => 'LAMPU <span>MEJA</span>',
                'button_text' => 'BELI SEKARANG',
                'button_url' => 'shop-left-sidebar.html',
            ],
        ];

        $features = [
            [
                'icon' => 'lnr lnr-rocket',
                'title' => 'Gratis Ongkir',
                'desc' => 'Gratis ongkir untuk semua pesanan di Indonesia',
            ],
            [
                'icon' => 'lnr lnr-phone',
                'title' => 'Layanan 24/7',
                'desc' => 'Hubungi kami kapan saja',
            ],
            [
                'icon' => 'lnr lnr-undo',
                'title' => 'Uang Kembali 100%',
                'desc' => 'Pengembalian dalam 30 hari',
            ],
            [
                'icon' => 'lnr lnr-gift',
                'title' => 'Pembayaran Aman',
                'desc' => 'Kami menjamin pembayaran aman',
            ],
        ];
    @endphp

    <div class="hero-area pt-15 mb-80">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <!--=======  slider container  =======-->
                    <div class="slider-container">
                        <!--=======  hero slider one  =======-->
                        <div class="hero-slider-one">
                            @foreach($heroSlides as $slide)
                                <div class="hero-slider-item {{ $slide['bg_class'] }}">
                                    <div class="slider-content d-flex flex-column justify-content-center align-items-start h-100">
                                        <p>{{ $slide['subtitle'] }}</p>
                                        <h1>{!! $slide['title'] !!}</h1>
                                        <a href="{{ $slide['button_url'] }}" class="pataku-btn slider-btn-1">{{ $slide['button_text'] }}</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <!--=======  End of hero slider one  =======-->
                    </div>
                    <!--=======  End of slider container  =======-->
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12 pt-40 pb-40">
                    <!--=======  feature area  =======-->
                    <div class="feature-area">
                        @foreach($features as $feature)
                            <div class="single-feature mb-md-20 mb-sm-20 mb-xxs-20">
                                <span class="icon"><i class="{{ $feature['icon'] }}"></i></span>
                                <p>{{ $feature['title'] }} <span>{{ $feature['desc'] }}</span></p>
                            </div>
                        @endforeach
                    </div>
                    <!--=======  End of feature area  =======-->
                </div>
            </div>
        </div>
    </div>

    <!--=====  End of Hero Area One  ======-->


    <!--=============================================
        =            featured categories         =
        =============================================-->

    @php
        $featuredCategories = [
            [
                'title' => 'Furnitur',
                'image' => asset('ecommerce/images/category-banner/home1-banner1.webp'),
                'url' => 'shop-left-sidebar.html',
                'col_class' => 'col-lg-6 col-md-6 mb-sm-30',
                'img_width' => 540,
                'img_height' => 560,
            ],
            [
                'title' => 'Ruang',
                'image' => asset('ecommerce/images/category-banner/home1-banner2.webp'),
                'url' => 'shop-left-sidebar.html',
                'col_class' => 'col-lg-12 col-md-12 mb-30',
                'img_width' => 550,
                'img_height' => 270,
            ],
            [
                'title' => 'Pencahayaan',
                'image' => asset('ecommerce/images/category-banner/home1-banner3.webp'),
                'url' => 'shop-left-sidebar.html',
                'col_class' => 'col-lg-6 col-md-6 col-sm-6 col-6',
                'img_width' => 265,
                'img_height' => 270,
            ],
            [
                'title' => 'Dekorasi',
                'image' => asset('ecommerce/images/category-banner/home1-banner4.webp'),
                'url' => 'shop-left-sidebar.html',
                'col_class' => 'col-lg-6 col-md-6 col-sm-6 col-6',
                'img_width' => 265,
                'img_height' => 270,
            ],
        ];
    @endphp

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
            <div class="row">
                <!-- Furnitur -->
                <div class="{{ $featuredCategories[0]['col_class'] }}">
                    <div class="banner">
                        <a href="{{ $featuredCategories[0]['url'] }}">
                            <img width="{{ $featuredCategories[0]['img_width'] }}" height="{{ $featuredCategories[0]['img_height'] }}" src="{{ $featuredCategories[0]['image'] }}" class="img-fluid" alt="">
                        </a>
                        <span class="banner-category-title">
                            <a href="{{ $featuredCategories[0]['url'] }}">{{ $featuredCategories[0]['title'] }}</a>
                        </span>
                    </div>
                </div>
                <!-- Ruang, Pencahayaan, Dekorasi -->
                <div class="col-lg-6 col-md-6">
                    <div class="row">
                        <div class="{{ $featuredCategories[1]['col_class'] }}">
                            <div class="banner">
                                <a href="{{ $featuredCategories[1]['url'] }}">
                                    <img width="{{ $featuredCategories[1]['img_width'] }}" height="{{ $featuredCategories[1]['img_height'] }}" src="{{ $featuredCategories[1]['image'] }}" class="img-fluid" alt="">
                                </a>
                                <span class="banner-category-title">
                                    <a href="{{ $featuredCategories[1]['url'] }}">{{ $featuredCategories[1]['title'] }}</a>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        @foreach(array_slice($featuredCategories, 2) as $category)
                            <div class="{{ $category['col_class'] }}">
                                <div class="banner">
                                    <a href="{{ $category['url'] }}">
                                        <img width="{{ $category['img_width'] }}" height="{{ $category['img_height'] }}" src="{{ $category['image'] }}" class="img-fluid" alt="">
                                    </a>
                                    <span class="banner-category-title">
                                        <a href="{{ $category['url'] }}">{{ $category['title'] }}</a>
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!--=====  End of featured categories  ======-->

    <!--=============================================
        =            Double row product slider          =
        =============================================-->

    @php
        $produkBaru = [
            [
                'title' => 'Mug Hari Ini Hari Baik',
                'image' => asset('ecommerce/images/products/product05.webp'),
                'price' => 75900,
                'discounted_price' => 69830,
                'badges' => [],
            ],
            [
                'title' => 'Tas Kurir Lapangan',
                'image' => asset('ecommerce/images/products/product01.webp'),
                'price' => 75900,
                'discounted_price' => 69830,
                'badges' => ['BARU', '-8%'],
            ],
            [
                'title' => 'Hoodie Teton Pullover',
                'image' => asset('ecommerce/images/products/product02.webp'),
                'price' => 75900,
                'discounted_price' => null,
                'badges' => ['BARU'],
            ],
            [
                'title' => 'Kaos Hummingbird',
                'image' => asset('ecommerce/images/products/product03.webp'),
                'price' => 75900,
                'discounted_price' => 69830,
                'badges' => ['BARU', '-8%'],
            ],
            [
                'title' => 'Jam Analog Aim',
                'image' => asset('ecommerce/images/products/product04.webp'),
                'price' => 75900,
                'discounted_price' => 69830,
                'badges' => ['BARU', '-8%'],
            ],
        ];
    @endphp

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
            <div class="row">
                <div class="col-lg-12">
                    <!--=======  slider produk baru  =======-->
                    <div class="ptk-slider double-row-slider-container" data-row="2">
                        @foreach($produkBaru as $produk)
                            <div class="col">
                                <div class="ptk-product">
                                    <div class="image">
                                        <a href="#">
                                            <img width="300" height="360" src="{{ $produk['image'] }}" class="img-fluid" alt="">
                                        </a>
                                        <a class="hover-icon" href="#" data-bs-toggle="modal" data-bs-target="#quick-view-modal-container"><i class="lnr lnr-eye"></i></a>
                                        <a class="hover-icon" href="#"><i class="lnr lnr-heart"></i></a>
                                        <a class="hover-icon" href="#"><i class="lnr lnr-cart"></i></a>
                                        <div class="product-badge">
                                            @foreach($produk['badges'] as $badge)
                                                @if($badge == 'BARU')
                                                    <span class="new-badge">{{ $badge }}</span>
                                                @elseif(str_contains($badge, '%'))
                                                    <span class="discount-badge">{{ $badge }}</span>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="content">
                                        <p class="product-title"><a href="#">{{ $produk['title'] }}</a></p>
                                        <p class="product-price">
                                            @if($produk['discounted_price'])
                                                <span class="main-price discounted">Rp{{ number_format($produk['price'], 0, ',', '.') }}</span>
                                                <span class="discounted-price">Rp{{ number_format($produk['discounted_price'], 0, ',', '.') }}</span>
                                            @else
                                                <span class="main-price">Rp{{ number_format($produk['price'], 0, ',', '.') }}</span>
                                            @endif
                                        </p>
                                    </div>
                                    <div class="rating">
                                        <i class="lnr lnr-star active"></i>
                                        <i class="lnr lnr-star active"></i>
                                        <i class="lnr lnr-star active"></i>
                                        <i class="lnr lnr-star active"></i>
                                        <i class="lnr lnr-star"></i>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <!--=======  akhir slider produk baru  =======-->
                </div>
            </div>
        </div>
    </div>

    <!--=====  End of Double row product slider   ======-->

    <!--=============================================
        =            fullwidth banner area         =
        =============================================-->

    @php
        $fullwidthBanners = [
            [
                'bg_class' => 'fullwidth-banner-bg fullwidth-banner-bg-1',
                'title' => 'Ide Anda, Tugas Kami Untuk Mewujudkannya.',
                'description' => 'Kami adalah pembuat furniture yang berbasis di Jakarta, membantu mewujudkan ide Anda.',
                'button_text' => 'Lihat produk kami',
                'button_url' => 'shop-left-sidebar.html',
            ],
        ];
    @endphp

    @foreach($fullwidthBanners as $banner)
        <div class="fullwidth-banner-area {{ $banner['bg_class'] }} pt-120 pb-120 pt-xs-80 pb-xs-80 mb-80">
            <div class="container">
                <div class="row">
                    <div class="col-xl-6 col-lg-7 col-md-9 col-12">
                        <div class="fullwidth-banner-content">
                            <p class="fullwidth-banner-title">{{ $banner['title'] }}</p>
                            <p>{{ $banner['description'] }}</p>
                            <a href="{{ $banner['button_url'] }}">{{ $banner['button_text'] }} <i class="fa fa-angle-right"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <!--=====  End of fullwidth banner area  ======-->

    <!--=============================================
        =            deal and propular product area         =
        =============================================-->

    @php
        $dealsOfTheWeek = [
            [
                'title' => 'Tas Kurir Lapangan',
                'image' => asset('ecommerce/images/products/product01.webp'),
                'price' => 75900,
                'discounted_price' => 69830,
                'badges' => ['BARU', '-8%'],
                'countdown' => '2024/07/01',
            ],
            [
                'title' => 'Hoodie Teton Pullover',
                'image' => asset('ecommerce/images/products/product02.webp'),
                'price' => 75900,
                'discounted_price' => null,
                'badges' => ['BARU'],
                'countdown' => '2024/07/01',
            ],
            [
                'title' => 'Kaos Hummingbird',
                'image' => asset('ecommerce/images/products/product03.webp'),
                'price' => 75900,
                'discounted_price' => 69830,
                'badges' => ['BARU', '-8%'],
                'countdown' => '2024/07/01',
            ],
            [
                'title' => 'Jam Analog Aim',
                'image' => asset('ecommerce/images/products/product04.webp'),
                'price' => 75900,
                'discounted_price' => 69830,
                'badges' => ['BARU', '-8%'],
                'countdown' => '2024/07/01',
            ],
        ];

        $popularProducts = [
            [
                'title' => 'Tas Kurir Lapangan',
                'image' => asset('ecommerce/images/products/product01.webp'),
                'price' => 75900,
                'discounted_price' => 69830,
            ],
            [
                'title' => 'Mug Hari Ini Hari Baik',
                'image' => asset('ecommerce/images/products/product02.webp'),
                'price' => 75900,
                'discounted_price' => 69830,
            ],
            [
                'title' => 'Hoodie Teton Pullover',
                'image' => asset('ecommerce/images/products/product03.webp'),
                'price' => 75900,
                'discounted_price' => 69830,
            ],
            [
                'title' => 'Tas Kurir Lapangan',
                'image' => asset('ecommerce/images/products/product04.webp'),
                'price' => 75900,
                'discounted_price' => 69830,
            ],
            [
                'title' => 'Kaos Hummingbird',
                'image' => asset('ecommerce/images/products/product05.webp'),
                'price' => 75900,
                'discounted_price' => 69830,
            ],
            [
                'title' => 'Tas Kurir Lapangan',
                'image' => asset('ecommerce/images/products/product06.webp'),
                'price' => 75900,
                'discounted_price' => 69830,
            ],
            [
                'title' => 'Kaos Hummingbird',
                'image' => asset('ecommerce/images/products/product07.webp'),
                'price' => 75900,
                'discounted_price' => 69830,
            ],
            [
                'title' => 'Hoodie Teton Pullover',
                'image' => asset('ecommerce/images/products/product08.webp'),
                'price' => 75900,
                'discounted_price' => 69830,
            ],
        ];
    @endphp

    <div class="deal-popular-product-area mb-80">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 mb-md-80 mb-sm-80">
                    <div class="section-title mb-40">
                        <h2>Promo <span>Minggu</span> Ini</h2>
                        <p>Pilihan promo terbaru yang diperbarui setiap minggu!</p>
                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            <!--=======  slider promo minggu ini  =======-->
                            <div class="ptk-slider deal-slider-container">
                                @foreach($dealsOfTheWeek as $deal)
                                    <div class="col">
                                        <div class="product-countdown" data-countdown="{{ $deal['countdown'] }}"></div>
                                        <div class="ptk-product">
                                            <div class="image">
                                                <a href="#">
                                                    <img width="300" height="360" src="{{ $deal['image'] }}" class="img-fluid" alt="">
                                                </a>
                                                <a class="hover-icon" href="#" data-bs-toggle="modal" data-bs-target="#quick-view-modal-container"><i class="lnr lnr-eye"></i></a>
                                                <a class="hover-icon" href="#"><i class="lnr lnr-heart"></i></a>
                                                <a class="hover-icon" href="#"><i class="lnr lnr-cart"></i></a>
                                                <div class="product-badge">
                                                    @foreach($deal['badges'] as $badge)
                                                        @if($badge == 'BARU')
                                                            <span class="new-badge">{{ $badge }}</span>
                                                        @elseif(str_contains($badge, '%'))
                                                            <span class="discount-badge">{{ $badge }}</span>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div class="content">
                                                <p class="product-title"><a href="#">{{ $deal['title'] }}</a></p>
                                                <p class="product-price">
                                                    @if($deal['discounted_price'])
                                                        <span class="main-price discounted">Rp{{ number_format($deal['price'], 0, ',', '.') }}</span>
                                                        <span class="discounted-price">Rp{{ number_format($deal['discounted_price'], 0, ',', '.') }}</span>
                                                    @else
                                                        <span class="main-price">Rp{{ number_format($deal['price'], 0, ',', '.') }}</span>
                                                    @endif
                                                </p>
                                            </div>
                                            <div class="rating">
                                                <i class="lnr lnr-star active"></i>
                                                <i class="lnr lnr-star active"></i>
                                                <i class="lnr lnr-star active"></i>
                                                <i class="lnr lnr-star active"></i>
                                                <i class="lnr lnr-star"></i>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <!--=======  akhir slider promo minggu ini  =======-->
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="section-title mb-40">
                        <h2>Produk <span>Populer</span></h2>
                        <p>Kami menawarkan pilihan furniture terbaik dengan harga yang Anda sukai!</p>
                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            <!--=======  slider produk populer  =======-->
                            <div class="ptk-slider popular-product-slider" data-row="3">
                                @foreach($popularProducts as $product)
                                    <div class="col">
                                        <div class="ptk-product d-flex">
                                            <div class="image">
                                                <a href="#">
                                                    <img width="300" height="360" src="{{ $product['image'] }}" class="img-fluid" alt="">
                                                </a>
                                            </div>
                                            <div class="content">
                                                <p class="product-title"><a href="#">{{ $product['title'] }}</a></p>
                                                <p class="product-price">
                                                    <span class="main-price discounted">Rp{{ number_format($product['price'], 0, ',', '.') }}</span>
                                                    <span class="discounted-price">Rp{{ number_format($product['discounted_price'], 0, ',', '.') }}</span>
                                                </p>
                                                <div class="rating rating-product-style-2">
                                                    <i class="lnr lnr-star active"></i>
                                                    <i class="lnr lnr-star active"></i>
                                                    <i class="lnr lnr-star active"></i>
                                                    <i class="lnr lnr-star active"></i>
                                                    <i class="lnr lnr-star"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <!--=======  akhir slider produk populer  =======-->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!--=====  End of deal and propular product area  ======-->

    <!--=============================================
        =            container width banner         =
        =============================================-->

    @php
        $containerWidthBanner = [
            [
                'url' => 'shop-left-sidebar.html',
                'bg_class' => 'containerwidth-banner-bg containerwidth-banner-bg-1',
                'content' => [
                    'normal_text' => 'Perabotan Ruang Tamu',
                    'color_text' => 'Diskon Hingga 50%',
                    'underline_text' => 'Belanja Gaya Terbaru',
                ],
            ],
        ];
    @endphp

    <div class="containerwidth-banner-area mb-80">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    @foreach($containerWidthBanner as $banner)
                        <a href="{{ $banner['url'] }}">
                            <div class="banner {{ $banner['bg_class'] }}">
                                <div class="row h-100">
                                    <div class="col-lg-4 offset-lg-8 col-md-12">
                                        <div class="banner-content d-flex flex-column align-items-center align-items-lg-start justify-content-center h-100">
                                            <p class="normal-text">{{ $banner['content']['normal_text'] }}</p>
                                            <p class="color-text">{{ $banner['content']['color_text'] }}</p>
                                            <p class="underline-text">{{ $banner['content']['underline_text'] }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!--=====  End of container width banner  ======-->

    <!--=============================================
        =            Top selling product section         =
        =============================================-->

    @php
        $topSellingProducts = [
            [
                'title' => 'Tas Kurir Lapangan',
                'image' => asset('ecommerce/images/products/product01.webp'),
                'price' => 75900,
                'discounted_price' => 69830,
                'badges' => ['BARU', '-8%'],
            ],
            [
                'title' => 'Hoodie Teton Pullover',
                'image' => asset('ecommerce/images/products/product02.webp'),
                'price' => 75900,
                'discounted_price' => null,
                'badges' => ['BARU'],
            ],
            [
                'title' => 'Kaos Hummingbird',
                'image' => asset('ecommerce/images/products/product03.webp'),
                'price' => 75900,
                'discounted_price' => 69830,
                'badges' => ['BARU', '-8%'],
            ],
            [
                'title' => 'Jam Analog Aim',
                'image' => asset('ecommerce/images/products/product04.webp'),
                'price' => 75900,
                'discounted_price' => 69830,
                'badges' => ['BARU', '-8%'],
            ],
            [
                'title' => 'Mug Hari Ini Hari Baik',
                'image' => asset('ecommerce/images/products/product05.webp'),
                'price' => 75900,
                'discounted_price' => 69830,
                'badges' => [],
            ],
        ];
    @endphp

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
            <div class="row">
                <div class="col-lg-12">
                    <!--=======  slider produk terlaris  =======-->
                    <div class="ptk-slider top-selling-product-slider-container">
                        @foreach($topSellingProducts as $product)
                            <div class="col">
                                <div class="ptk-product">
                                    <div class="image">
                                        <a href="single-product.html"></a>
                                            <img width="300" height="360" src="{{ $product['image'] }}" class="img-fluid" alt="">
                                        </a>
                                        <a class="hover-icon" href="#" data-bs-toggle="modal" data-bs-target="#quick-view-modal-container"><i class="lnr lnr-eye"></i></a>
                                        <a class="hover-icon" href="#"><i class="lnr lnr-heart"></i></a>
                                        <a class="hover-icon" href="#"><i class="lnr lnr-cart"></i></a>
                                        <div class="product-badge">
                                            @foreach($product['badges'] as $badge)
                                                @if($badge == 'BARU')
                                                    <span class="new-badge">{{ $badge }}</span>
                                                @elseif(str_contains($badge, '%'))
                                                    <span class="discount-badge">{{ $badge }}</span>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="content">
                                        <p class="product-title"><a href="single-product.html">{{ $product['title'] }}</a></p>
                                        <p class="product-price">
                                            @if($product['discounted_price'])
                                                <span class="main-price discounted">Rp{{ number_format($product['price'], 0, ',', '.') }}</span>
                                                <span class="discounted-price">Rp{{ number_format($product['discounted_price'], 0, ',', '.') }}</span>
                                            @else
                                                <span class="main-price">Rp{{ number_format($product['price'], 0, ',', '.') }}</span>
                                            @endif
                                        </p>
                                    </div>
                                    <div class="rating">
                                        <i class="lnr lnr-star active"></i>
                                        <i class="lnr lnr-star active"></i>
                                        <i class="lnr lnr-star active"></i>
                                        <i class="lnr lnr-star active"></i>
                                        <i class="lnr lnr-star"></i>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <!--=======  akhir slider produk terlaris  =======-->
                </div>
            </div>
        </div>
    </div>

    <!--=====  End of Top selling product section  ======-->


    <!--=============================================
        =            Blog slider section         =
        =============================================-->

    @php
        $blogPosts = [
            [
                'title' => 'Typi non habent claritatem insitam',
                'image' => asset('ecommerce/images/slider/blog/01.webp'),
                'url' => 'blog-post-right-sidebar.html',
            ],
            [
                'title' => 'Typi non habent claritatem insitam',
                'image' => asset('ecommerce/images/slider/blog/02.webp'),
                'url' => 'blog-post-right-sidebar.html',
            ],
            [
                'title' => 'Typi non habent claritatem insitam',
                'image' => asset('ecommerce/images/slider/blog/03.webp'),
                'url' => 'blog-post-right-sidebar.html',
            ],
            [
                'title' => 'Typi non habent claritatem insitam',
                'image' => asset('ecommerce/images/slider/blog/04.webp'),
                'url' => 'blog-post-right-sidebar.html',
            ],
        ];
    @endphp

    <div class="blog-slider-section mb-80">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center mb-40">
                    <div class="section-title">
                        <h2>Blog <span>Kami</span></h2>
                        <p>Ingin menampilkan postingan dengan cara terbaik untuk menyoroti momen menarik dari blog Anda?</p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <!-- Blog slider container -->
                    <div class="blog-post-slider-container ptk-slider">
                        @foreach($blogPosts as $post)
                            <div class="col">
                                <div class="single-slider-blog-post">
                                    <div class="image">
                                        <a href="{{ $post['url'] }}">
                                            <img width="800" height="517" src="{{ $post['image'] }}" class="img-fluid" alt="">
                                        </a>
                                    </div>
                                    <div class="content">
                                        <p class="blog-title"><a href="{{ $post['url'] }}">{{ ucwords(strtolower($post['title'])) }}</a></p>
                                        <a href="{{ $post['url'] }}" class="readmore-btn">Baca Selengkapnya</a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <!-- End of Blog slider container -->
                </div>
            </div>
        </div>
    </div>

    <!--=====  End of Blog slider section  ======-->


    <!--=============================================
        =            instagram section         =
        =============================================-->

    @php
        $instagramImages = [
            ['src' => asset('ecommerce/images/instagram/02.webp'), 'width' => 600, 'height' => 600],
            ['src' => asset('ecommerce/images/instagram/01.webp'), 'width' => 600, 'height' => 600],
            ['src' => asset('ecommerce/images/instagram/03.webp'), 'width' => 600, 'height' => 600],
            ['src' => asset('ecommerce/images/instagram/04.webp'), 'width' => 320, 'height' => 320],
            ['src' => asset('ecommerce/images/instagram/05.webp'), 'width' => 320, 'height' => 320],
        ];
    @endphp

    <div class="instagram-section mb-85">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center mb-40">
                    <div class="section-title instagram-title">
                        <h2>#{{ config('app.name') }} Instagram</h2>
                        <p><a href="#" target="_blank">Ikuti Instagram kami</a></p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <!--=======  instagram slider container  =======-->
                    <div class="ptk-slider instagram-slider-container">
                        @foreach($instagramImages as $img)
                            <div class="col">
                                <div class="single-insta-item">
                                    <a href="{{ $img['src'] }}" class="big-image-popup">
                                        <img width="{{ $img['width'] }}" height="{{ $img['height'] }}" src="{{ $img['src'] }}" class="img-fluid" alt="">
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <!--=======  End of instagram slider container  =======-->
                </div>
            </div>
        </div>
    </div>
@endsection
@push('js')

@endpush
