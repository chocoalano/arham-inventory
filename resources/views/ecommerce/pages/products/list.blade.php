@extends('ecommerce.layouts.app')

@section('title', 'Daftar Produk')

@push('css')
    {{-- Tambahkan CSS kustom di sini jika diperlukan --}}
@endpush

@section('content')

    {{-- Logika Breadcrumbs: Menghasilkan jalur navigasi berdasarkan segmen URL --}}
    @php
        $viewModes = [
            ['icon' => 'fa-th', 'target' => 'grid', 'active' => true],
            ['icon' => 'fa-list', 'target' => 'list', 'active' => false],
        ];

        $selectedSort = $appliedFilter['sort'] ?? ($selectedSort ?? 'popular');

        $from = $products->firstItem() ?? 0;
        $to = $products->lastItem() ?? 0;
        $total = $products->total();
    @endphp

    {{-- Breadcrumb Area --}}
    @include('ecommerce.layouts.partials.breadscrum')

    {{-- Konten Utama Halaman Toko --}}
    <div class="shop-page-content mb-80">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">

                    {{-- Header Toko (Pengurutan & Mode Tampilan) --}}
                    <div class="shop-header mb-20">
                        <div class="row">
                            <div class="col-lg-6 col-md-6 col-sm-12 mb-sm-20 d-flex align-items-center">
                                <div class="view-mode-icons">
                                    @foreach($viewModes as $mode)
                                        <a class="{{ $mode['active'] ? 'active' : '' }}" href="#"
                                            data-target="{{ $mode['target'] }}">
                                            <i class="fa {{ $mode['icon'] }}"></i>
                                        </a>
                                    @endforeach
                                </div>
                                <p class="result-show-message">
                                    Menampilkan {{ $from }}–{{ $to }} dari {{ $total }} hasil
                                </p>
                            </div>

                            <div
                                class="col-lg-6 col-md-6 col-sm-12 d-flex flex-column flex-sm-row justify-content-start justify-content-md-end align-items-sm-center">
                                <div class="sort-by-dropdown d-flex align-items-center mb-xs-10">
                                    <p class="mr-10 mb-0">Urutkan:</p>
                                    <select name="sort-by" id="sort-by" class="nice-select"
                                        onchange="changeSort(this.value)">
                                        @foreach ($sortOptions as $opt)
                                            <option value="{{ $opt['value'] }}" @selected($selectedSort === $opt['value'])>
                                                {{ $opt['label'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Daftar Produk (Grid & List View) --}}
                    <div class="shop-product-wrap grid row">
                        @forelse($products as $product)
                            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                                <!-- Tampilan Grid Produk -->
                                <div class="ptk-product shop-grid-view-product">
                                    <div class="image">
                                        <a href="{{ route('ecommerce.products.show', $product['sku']) }}">
                                            <img width="300" height="360" src="{{ $product['image'] }}" class="img-fluid"
                                                alt="{{ $product['title'] }}">
                                        </a>
                                        <!-- Ikon Aksi -->
                                        <a class="hover-icon quick-view-btn" href="#" data-bs-toggle="modal"
                                            data-bs-target="#quick-view-modal-container" data-product='@json($product)'>
                                            <i class="lnr lnr-eye"></i>
                                        </a>
                                        <a class="hover-icon" href="#"><i class="lnr lnr-heart"></i></a>
                                        <a class="hover-icon" href="#"><i class="lnr lnr-cart"></i></a>

                                        <!-- Badge -->
                                        @php
                                            $hasBadge = !empty($product['badges']['new']) || !empty($product['badges']['discount']);
                                        @endphp
                                        @if($hasBadge)
                                            <div class="product-badge">
                                                @if(!empty($product['badges']['new']))
                                                    <span class="new-badge">BARU</span>
                                                @endif
                                                @if(!empty($product['badges']['discount']))
                                                    <span class="discount-badge">{{ $product['badges']['discount'] }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>

                                    <div class="content">
                                        <p class="product-title">
                                            <a href="single-product.html">{{ $product['title'] }}</a>
                                        </p>
                                        <p class="product-price">
                                            @if(!empty($product['cost_price']))
                                                <span class="main-price discounted">{{ $product['cost_price'] }}</span>
                                            @endif
                                            @if(!empty($product['price']))
                                                <span class="discounted-price">{{ $product['price'] }}</span>
                                            @endif
                                        </p>
                                    </div>

                                    <div class="rating">
                                        @for($i = 1; $i <= 5; $i++)
                                            <i class="lnr lnr-star{{ $i <= (int) $product['rating'] ? ' active' : '' }}"></i>
                                        @endfor
                                    </div>
                                </div>
                                <!-- End Grid -->

                                <!-- Tampilan List Produk -->
                                <div class="ptk-product shop-list-view-product">
                                    <div class="image">
                                        <a href="single-product.html">
                                            <img width="300" height="360" src="{{ $product['image'] }}" class="img-fluid"
                                                alt="{{ $product['title'] }}">
                                        </a>

                                        @if($hasBadge)
                                            <div class="product-badge">
                                                @if(!empty($product['badges']['new']))
                                                    <span class="new-badge">BARU</span>
                                                @endif
                                                @if(!empty($product['badges']['discount']))
                                                    <span class="discount-badge">{{ $product['badges']['discount'] }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>

                                    <div class="content">
                                        <p class="product-title">
                                            <a href="single-product.html">{{ $product['title'] }}</a>
                                        </p>

                                        <div class="rating">
                                            @for($i = 1; $i <= 5; $i++)
                                                <i class="lnr lnr-star{{ $i <= (int) $product['rating'] ? ' active' : '' }}"></i>
                                            @endfor
                                        </div>

                                        <p class="product-price">
                                            @if(!empty($product['cost_price']))
                                                <span class="main-price discounted">{{ $product['cost_price'] }}</span>
                                            @endif
                                            @if(!empty($product['price']))
                                                <span class="discounted-price">{{ $product['price'] }}</span>
                                            @endif
                                        </p>

                                        <p class="product-description">{{ $product['description'] }}</p>

                                        <div class="hover-icons">
                                            <a class="hover-icon quick-view-btn" href="#" data-bs-toggle="modal"
                                                data-bs-target="#quick-view-modal-container" data-product='@json($product)'>
                                                <i class="lnr lnr-eye"></i>
                                            </a>
                                            <a class="hover-icon" href="#"><i class="lnr lnr-heart"></i></a>
                                            <a class="hover-icon" href="#"><i class="lnr lnr-cart"></i></a>
                                        </div>
                                    </div>
                                </div>
                                <!-- End List -->
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="alert alert-info mb-0">
                                    Produk tidak ditemukan. Coba ubah kata kunci, filter, atau urutan.
                                </div>
                            </div>
                        @endforelse
                    </div>

                    {{-- Pagination --}}
                    <div class="pagination-container mt-50 pb-20 mb-md-80 mb-sm-80">
                        <div class="row">
                            <div class="col-lg-4 col-md-4 col-sm-12 text-center text-md-start mb-sm-20">
                                <p class="show-result-text">
                                    Menampilkan {{ $from }}-{{ $to }} dari {{ $total }} item
                                </p>
                            </div>
                            <div class="col-lg-8 col-md-8 col-sm-12">
                                <div class="pagination-content text-center text-md-end">
                                    {{ $products->links() }}
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- End Pagination --}}

                </div>
            </div>
        </div>
    </div>

    {{-- QUICK VIEW MODAL --}}
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
                            <form action="{{ route('cart.store') }}" attr-csrf="{{ csrf_token() }}"
                                id="quick-view-add-to-cart-form" redirect-to="{{ route('cart.index') }}">
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
                                            <button class="add-to-cart-btn pataku-btn" onclick="addToCart()"><i
                                                    class="fa fa-shopping-cart"></i> Masukan ke keranjang</button>
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
    <script src="{{ asset('ecommerce/js/pages/product-list.js') }}"></script>
@endpush
