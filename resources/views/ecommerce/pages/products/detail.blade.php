@extends('ecommerce.layouts.app')
@section('title', 'Detail Produk')
@push('css')

@endpush
@section('content')
@php
    // ====== Hitung ringkas ulasan ======
    $totalUlasan = is_countable($ulasan ?? []) ? count($ulasan) : 0;
    $avgRating   = 0;
    $dist        = [1=>0,2=>0,3=>0,4=>0,5=>0];
    if ($totalUlasan) {
        foreach ($ulasan as $u) {
            $r = (int) ($u['rating'] ?? 0);
            if ($r >= 1 && $r <= 5) $dist[$r] += 1;
            $avgRating += $r;
        }
        $avgRating = round($avgRating / max($totalUlasan,1), 1);
    }
@endphp

{{-- =========================================
     Breadcrumb Area (sesuai template)
========================================= --}}
@include('ecommerce.layouts.partials.breadscrum')

{{-- =========================================
     Single Product Content (sesuai template)
========================================= --}}
<div class="single-product-page-content mb-80">
    <div class="container">
        <div class="row">
            {{-- Galeri Gambar (kiri) --}}
            <div class="col-lg-6 mb-md-50 mb-sm-50">
                <div class="product-image-slider pts1-product-image-slider pts-product-image-slider flex-row-reverse">
                    {{-- Gambar Besar --}}
                    <div class="tab-content product-large-image-list pts-product-large-image-list pts1-product-large-image-list" id="myTabContent">
                        @forelse($produk['galeri'] as $src)
                            @php
                                $paneId = 'single-slide-'.($loop->iteration);
                                $tabId  = 'single-slide-tab-'.($loop->iteration);
                            @endphp
                            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                                 id="{{ $paneId }}" role="tabpanel" aria-labelledby="{{ $tabId }}">
                                <div class="single-product-img img-full">
                                    <img width="600" height="719" src="{{ asset('storage/'.$src['image_path']) }}" class="img-fluid" alt="{{ $produk['judul'] }}">
                                    <a href="{{ asset('storage/'.$src['image_path']) }}" class="big-image-popup"><i class="fa fa-search-plus"></i></a>
                                </div>
                            </div>
                        @empty
                            <div class="tab-pane fade show active" id="single-slide-1" role="tabpanel" aria-labelledby="single-slide-tab-1">
                                <div class="single-product-img img-full">
                                    <img width="600" height="719" src="{{ asset('ecommerce/images/single-product-slider/01.webp') }}" class="img-fluid" alt="{{ $produk['judul'] ?? 'Produk' }}">
                                </div>
                            </div>
                        @endforelse
                    </div>

                    {{-- Thumbnail --}}
                    <div class="product-small-image-list pts-product-small-image-list pts1-product-small-image-list">
                        <div class="nav small-image-slider pts-small-image-slider pts1-small-image-slider" role="tablist">
                            @foreach($produk['galeri'] as $src)
                                @php
                                    $paneId = 'single-slide-'.($loop->iteration);
                                    $tabId  = 'single-slide-tab-'.($loop->iteration);
                                @endphp
                                <div class="single-small-image img-full">
                                    <a data-bs-toggle="tab" id="{{ $tabId }}" href="#{{ $paneId }}">
                                        <img width="600" height="719" src="{{ asset('storage/'.$src['image_path']) }}" class="img-fluid" alt="{{ $produk['judul'] }}">
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div> {{-- /product-image-slider --}}
            </div>

            {{-- Detail Produk (kanan) --}}
            <div class="col-lg-6">
                <div class="single-product-details-container">
                    <p class="product-title mb-15">{{ $produk['judul'] }}</p>
                    <p class="reference-text mb-15">Referensi: {{ $produk['referensi'] }}</p>

                    <div class="rating d-inline-block mb-15">
                        @for($i=1;$i<=5;$i++)
                            <i class="lnr lnr-star{{ $i <= (int)($produk['rating'] ?? 0) ? ' active' : '' }}"></i>
                        @endfor
                    </div>
                    <p class="review-links d-inline-block">
                        <a href="#ulasan"><i class="fa fa-comment-o"></i> Lihat ulasan ({{ $totalUlasan }})</a>
                        <a href="#ulasan"><i class="fa fa-pencil"></i> Tulis ulasan</a>
                    </p>

                    <h2 class="product-price mb-30">
                        @if(!empty($produk['harga']['normal_fmt']) && !empty($produk['harga']['diskon_fmt']))
                            <span class="main-price discounted">{{ $produk['harga']['normal_fmt'] }}</span>
                            <span class="discounted-price">{{ $produk['harga']['diskon_fmt'] }}</span>
                            @if(!empty($produk['harga']['label_diskon']))
                                <span class="discount-percentage">Hemat {{ $produk['harga']['label_diskon'] }}</span>
                            @endif
                        @elseif(!empty($produk['harga']['normal_fmt']))
                            <span class="main-price">{{ $produk['harga']['normal_fmt'] }}</span>
                        @endif
                    </h2>

                    <p class="product-description mb-15">{{ $produk['deskripsi'] }}</p>

                    <div class="cart-buttons mb-30">
                        <p class="mb-15">Kuantitas</p>
                        <div class="pro-qty mr-10">
                            <input type="text" value="1">
                        </div>
                        <a href="#" class="pataku-btn"><i class="fa fa-shopping-cart"></i> Tambah ke Keranjang</a>
                    </div>

                    <p class="wishlist-link mb-30">
                        <a href="#"><i class="fa fa-heart"></i> Tambah ke Wishlist</a>
                    </p>

                    <div class="social-share-buttons mb-30">
                        <p>Bagikan</p>
                        <ul>
                            <li><a class="twitter" href="#"><i class="fa fa-twitter"></i></a></li>
                            <li><a class="facebook" href="#"><i class="fa fa-facebook"></i></a></li>
                            <li><a class="google-plus" href="#"><i class="fa fa-google-plus"></i></a></li>
                            <li><a class="pinterest" href="#"><i class="fa fa-pinterest"></i></a></li>
                        </ul>
                    </div>

                    <div class="policy-list">
                        <ul>
                            @foreach($produk['kebijakan'] as $p)
                                <li>
                                    <img width="25" height="25" src="{{ $p['icon'] }}" alt="">
                                    {{ $p['teks'] }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div> {{-- /single-product-details-container --}}
            </div>
        </div>
    </div>
</div>

{{-- =========================================
     Single Product Tab (sesuai template)
========================================= --}}
<div class="single-product-tab-section mb-80">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="tab-slider-wrapper">
                    <nav>
                        <div class="nav nav-tabs" id="nav-tab" role="tablist">
                            <a class="nav-item nav-link active" id="description-tab" data-bs-toggle="tab"
                               href="#description" role="tab" aria-selected="true">Deskripsi</a>
                            <a class="nav-item nav-link" id="features-tab" data-bs-toggle="tab"
                               href="#features" role="tab" aria-selected="false">Fitur</a>
                            <a class="nav-item nav-link" id="review-tab" data-bs-toggle="tab"
                               href="#review" role="tab" aria-selected="false">Ulasan ({{ $totalUlasan }})</a>
                        </div>
                    </nav>

                    <div class="tab-content" id="nav-tabContent">
                        {{-- Tab Deskripsi --}}
                        <div class="tab-pane fade show active" id="description" role="tabpanel" aria-labelledby="description-tab">
                            <p class="product-desc">{{ $produk['deskripsi'] }}</p>
                        </div>

                        {{-- Tab Fitur --}}
                        <div class="tab-pane fade" id="features" role="tabpanel" aria-labelledby="features-tab">
                            <table class="table-data-sheet">
                                <tbody>
                                @forelse($fitur as $f)
                                    <tr class="{{ $loop->odd ? 'odd' : 'even' }}">
                                        <td>{{ $f['label'] }}</td>
                                        <td>{{ $f['nilai'] }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2">Belum ada data fitur.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{-- Tab Ulasan --}}
                        <div class="tab-pane fade" id="review" role="tabpanel" aria-labelledby="review-tab">
                            <div class="product-ratting-wrap" id="ulasan">
                                <div class="pro-avg-ratting">
                                    <h4>{{ $avgRating }} <span>(Rata-rata)</span></h4>
                                    <span>Berdasarkan {{ $totalUlasan }} Ulasan</span>
                                </div>

                                <div class="ratting-list">
                                    @for($s=5; $s>=1; $s--)
                                        <div class="sin-list float-start">
                                            @for($i=1; $i<=5; $i++)
                                                <i class="fa fa-star{{ $i <= $s ? '' : '-o' }}"></i>
                                            @endfor
                                            <span>({{ $dist[$s] ?? 0 }})</span>
                                        </div>
                                    @endfor
                                </div>

                                <div class="rattings-wrapper">
                                    @forelse($ulasan as $u)
                                        <div class="sin-rattings">
                                            <div class="ratting-author">
                                                <h3>{{ $u['nama'] }}</h3>
                                                <div class="ratting-star">
                                                    @for($i=1;$i<=5;$i++)
                                                        <i class="fa fa-star{{ $i <= (int)($u['rating'] ?? 0) ? '' : '-o' }}"></i>
                                                    @endfor
                                                    <span>({{ $u['rating'] ?? 0 }})</span>
                                                </div>
                                            </div>
                                            <p>{{ $u['teks'] }}</p>
                                        </div>
                                    @empty
                                        <p class="text-muted">Belum ada ulasan.</p>
                                    @endforelse
                                </div>

                                <div class="ratting-form-wrapper fix">
                                    <h3>Tambah Komentar</h3>
                                    <form action="#" method="post">
                                        <div class="ratting-form row">
                                            <div class="col-12 mb-15">
                                                <h5>Rating:</h5>
                                                <div class="ratting-star fix">
                                                    <i class="fa fa-star-o"></i>
                                                    <i class="fa fa-star-o"></i>
                                                    <i class="fa fa-star-o"></i>
                                                    <i class="fa fa-star-o"></i>
                                                    <i class="fa fa-star-o"></i>
                                                </div>
                                            </div>
                                            <div class="col-md-6 col-12 mb-15">
                                                <label for="name">Nama:</label>
                                                <input id="name" placeholder="Nama" type="text">
                                            </div>
                                            <div class="col-md-6 col-12 mb-15">
                                                <label for="email">Email:</label>
                                                <input id="email" placeholder="Email" type="email">
                                            </div>
                                            <div class="col-12 mb-15">
                                                <label for="your-review">Ulasan Anda:</label>
                                                <textarea name="review" id="your-review" placeholder="Tulis ulasan..."></textarea>
                                            </div>
                                            <div class="col-12">
                                                <input value="Kirim Ulasan" type="submit">
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div> {{-- /product-ratting-wrap --}}
                        </div>
                    </div> {{-- /tab-content --}}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- =========================================
     Related Product Slider (sesuai template)
========================================= --}}
<div class="related-product-area mb-80">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 mb-40">
                <div class="section-title">
                    <h2 class="mb-0">Produk <span>Terkait</span></h2>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="ptk-slider related-product-slider-container">
                    @foreach($produkTerkait as $pt)
                        @php
                            // Helpers parse & format
                            $toNumber = function ($v) {
                                if (is_null($v)) return null;
                                if (is_numeric($v)) return (float) $v; // "83078.24" atau 83078
                                $s = (string) $v;

                                // Jika mengandung "Rp" atau pola ribuan titik (ID)
                                if (stripos($s, 'rp') !== false || preg_match('/\.\d{3}\b/', $s)) {
                                    $digits = preg_replace('/[^\d]/', '', $s); // "Rp 83.078" -> "83078"
                                    return $digits === '' ? null : (float) $digits;
                                }

                                // Umum: simpan digit & titik desimal
                                $clean = preg_replace('/[^\d.]/', '', $s);
                                return $clean === '' ? null : (float) $clean;
                            };
                            $fmt = fn($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');

                            // Ambil & normalisasi harga
                            $priceN = $toNumber($pt['price'] ?? null);
                            $costN  = $toNumber($pt['cost_price'] ?? null);

                            $hasBoth     = is_numeric($priceN) && is_numeric($costN);
                            $hasDiscount = $hasBoth && $costN > $priceN;

                            // Tentukan tampilan harga
                            if ($hasDiscount) {
                                $normalFmt = $fmt($costN);    // harga normal (coret)
                                $diskonFmt = $fmt($priceN);   // harga diskon
                            } else {
                                $base = $priceN ?? $costN;    // salah satu yang ada
                                $normalFmt = $base !== null ? $fmt($base) : null;
                                $diskonFmt = null;
                            }

                            // Badge
                            $badgeBaru   = !empty(data_get($pt, 'badges.new'));
                            $badgeDiskon = data_get($pt, 'badges.discount');
                            if (!$badgeDiskon && $hasDiscount && $costN > 0) {
                                $badgeDiskon = '-' . round((($costN - $priceN) / $costN) * 100) . '%';
                            }

                            $stok  = (int) ($pt['stock'] ?? 0);
                            $habis = $stok <= 0;
                        @endphp

                        <div class="col">
                            <div class="ptk-product">
                                <div class="image">
                                    <a href="{{ $pt['url'] }}">
                                        <img width="300" height="360" src="{{ $pt['image'] }}" class="img-fluid" alt="{{ $pt['title'] }}">
                                    </a>

                                    {{-- Ikon hover (Quick View) --}}
                                    <a class="hover-icon quick-view-btn"
                                    href="#"
                                    data-bs-toggle="modal"
                                    data-bs-target="#quick-view-modal-container"
                                    data-product='@json($pt)'>
                                        <i class="lnr lnr-eye"></i>
                                    </a>
                                    <a class="hover-icon" href="#"><i class="lnr lnr-heart"></i></a>
                                    <a class="hover-icon" href="#"><i class="lnr lnr-cart"></i></a>

                                    {{-- Badge --}}
                                    <div class="product-badge">
                                        @if($badgeBaru)   <span class="new-badge">NEW</span> @endif
                                        @if(!empty($badgeDiskon)) <span class="discount-badge">{{ $badgeDiskon }}</span> @endif
                                        @if($habis) <span class="outofstock-badge">HABIS</span> @endif
                                    </div>
                                </div>

                                <div class="content">
                                    <p class="product-title"><a href="{{ $pt['url'] }}">{{ $pt['title'] }}</a></p>
                                    <p class="product-price">
                                        @if($diskonFmt && $normalFmt)
                                            <span class="main-price discounted">{{ $normalFmt }}</span>
                                            <span class="discounted-price">{{ $diskonFmt }}</span>
                                        @elseif($normalFmt)
                                            <span class="main-price">{{ $normalFmt }}</span>
                                        @endif
                                    </p>
                                </div>

                                <div class="rating">
                                    @for($i=1; $i<=5; $i++)
                                        <i class="lnr lnr-star{{ $i <= (int)($pt['rating'] ?? 0) ? ' active' : '' }}"></i>
                                    @endfor
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div> {{-- /related-product-slider-container --}}
            </div>
        </div>
    </div>
</div>

{{-- QUICK VIEW MODAL --}}
<div class="modal fade quick-view-modal-container" id="quick-view-modal-container" tabindex="-1" role="dialog"
        aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-lg-6 col-md-6 col-xs-12">
                        <!-- product quickview image gallery -->
                        <div class="product-image-slider quickview-product-image-slider flex-row-reverse">
                            <!-- Modal Tab Content (akan diisi dinamis) -->
                            <div class="tab-content product-large-image-list quickview-product-large-image-list">
                                {{-- konten diganti via JS --}}
                            </div>
                            <!-- Modal Tab Menu (akan diisi dinamis) -->
                            <div class="product-small-image-list quickview-product-small-image-list">
                                <div class="nav small-image-slider quickview-small-image-slider" role="tablist">
                                    {{-- konten diganti via JS --}}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-6 col-xs-12">
                        <form action="{{ route('cart.store') }}" attr-csrf="{{ csrf_token() }}" id="quick-view-add-to-cart-form" redirect-to="{{ route('cart.index') }}">
                            <div class="product-feature-details">
                                <h2 class="product-title mb-15">Product Title</h2>

                                <h2 class="product-price mb-15">
                                    <span class="main-price discounted d-none"></span>
                                    <span class="discounted-price d-none"></span>
                                    <span class="discount-percentage d-none"></span>
                                </h2>

                                <div class="product-variants mb-20"></div>

                                <p class="product-description mb-20">Deskripsi produk akan ditampilkan di sini.</p>

                                <div class="cart-buttons mb-20">
                                    <div class="pro-qty mr-10">
                                        <input type="text" value="1">
                                    </div>
                                    <div class="add-to-cart-btn">
                                        <button class="add-to-cart-btn pataku-btn" onclick="addToCart()"><i class="fa fa-shopping-cart"></i> Masukan ke keranjang</button>
                                    </div>
                                </div>

                                <div class="social-share-buttons">
                                    <h3>Bagikan produk ini</h3>
                                    <ul>
                                        <li><a class="twitter" href="#"><i class="fa fa-twitter"></i></a></li>
                                        <li><a class="facebook" href="#"><i class="fa fa-facebook"></i></a></li>
                                        <li><a class="google-plus" href="#"><i class="fa fa-google-plus"></i></a></li>
                                        <li><a class="pinterest" href="#"><i class="fa fa-pinterest"></i></a></li>
                                    </ul>
                                </div>
                            </div>
                        </form>
                    </div>
                    <!-- end right column -->
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('js')
<script src="{{ asset('ecommerce/js/pages/product-detail.js') }}"></script>
@endpush
