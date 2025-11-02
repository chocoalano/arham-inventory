<!--=============================================
	=             Header One         =
	=============================================-->

<div class="header-container header-sticky">

    <!--=======  header top  =======-->

    <div class="header-top pt-15 pb-15">
        <div class="container">
            <div class="row">
                <div class="col-12 col-md-6 text-center text-md-start mb-sm-15">
                    <span class="header-top-text">Selamat datang di Toko Online {{ config('app.name', 'Toko ku') }}!</span>
                </div>
                <div class="col-12 col-md-6">

                    <!--=======  header top dropdowns  =======-->

                    <div class="header-top-dropdown d-flex justify-content-center justify-content-md-end">

                        <!--=======  single dropdown  =======-->


                        <div class="single-dropdown">
                            <a href="#" id="changeAccount"><span id="accountMenuName">Akun <i
                                        class="fa fa-angle-down"></i></span></a>
                            <div class="language-currency-list hidden" id="accountList">
                                @if (Auth::guard('customer')->check())
                                    <ul>
                                        <li><a href="{{ route('auth.profile') }}">Profil</a></li>
                                        <li><a href="{{ route('auth.profile') }}#orders">Pesanan</a></li>
                                        <li>
                                            <form method="POST" action="{{ route('auth.logout') }}" style="display:inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-link p-0 m-0 align-baseline" style="color:inherit;text-decoration:none;">Keluar</button>
                                            </form>
                                        </li>
                                    </ul>
                                @else
                                    <ul>
                                        <li><a href="{{ route('login.register') }}">Daftar/Masuk</a></li>
                                    </ul>
                                @endif
                            </div>
                        </div>
                        <!--=======  End of single dropdown  =======-->
                    </div>
                    <!--=======  End of header top dropdowns  =======-->
                </div>
            </div>
        </div>
    </div>

    <!--=======  End of header top  =======-->

    <!--=======  Menu Top  =======-->

    <div class="menu-top pt-35 pb-35 pt-sm-20 pb-sm-20">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-12 col-lg-3 col-md-3 text-center text-md-start mb-sm-20">
                    <!--=======  logo  =======-->

                    <div class="logo">
                        <a href="index.html">
                            <img width="180" height="54" src="{{ asset('ecommerce/images/logo.webp') }}"
                                class="img-fluid" alt="">
                        </a>
                    </div>

                    <!--=======  End of logo  =======-->
                </div>
                <div class="col-12 col-lg-6 col-md-5 mb-sm-20">
                    <!--=======  Search bar  =======-->
                    <form method="get" action="{{ route('ecommerce.products.index') }}">
                        <div class="search-bar">
                            <input type="search" name="category" placeholder="Cari busana impian anda di toko ini ...">
                            <button type="submit"><i class="lnr lnr-magnifier"></i></button>
                        </div>
                    </form>

                    <!--=======  End of Search bar  =======-->
                </div>
                <div class="col-12 col-lg-3 col-md-4">
                    <!--=======  menu top icons  =======-->

                    <div class="menu-top-icons d-flex justify-content-center align-items-center justify-content-md-end">
                        <!--=======  single icon  =======-->

                        <div class="single-icon mr-20">
                            <a href="wishlist.html">
                                <i class="lnr lnr-heart"></i>
                                <span class="text">Wishlist</span>
                                <span class="count">
                                    {{ isset($cart) ? $cart->items->sum('quantity') : 0 }}
                                </span>
                            </a>
                        </div>

                        <!--=======  End of single icon  =======-->

                        <!--=======  single icon  =======-->

                        <div class="single-icon">
                            <a href="javascript:void(0)" id="cart-icon">
                                <i class="lnr lnr-cart"></i>
                                <span class="text">Keranjang</span>
                                <span class="count">
                                    {{ isset($cart) ? $cart->items->sum('quantity') : 0 }}
                                </span>
                            </a>
                            <!-- cart floating box -->
                            <div class="cart-floating-box hidden" id="cart-floating-box">
                                <div class="cart-items">
                                    @if(isset($cart) && $cart->items->count())
                                        @foreach ($cart->items as $item)
                                        @php
                                            $product = $item->product;
                                            $variant = $item->variant ?? null;

                                            // route show kamu pakai {product:sku}, jadi gunakan SKU:
                                            $sku     = $product->sku ?? null;
                                            $link    = $sku ? route('ecommerce.products.show', $sku) : '#';

                                            $name    = $product->name ?? 'Produk';
                                            $vlabel  = $variant?->name ?? $variant?->color ?? null;

                                            $price   = $variant->price ?? $product->price ?? 0;
                                            $qty     = (int) ($item->quantity ?? 1);
                                            $image   = $product->image_url
                                                        ?? asset('ecommerce/images/products/product01.webp');
                                        @endphp

                                        <div class="cart-float-single-item d-flex" data-item-id="{{ $item->id }}">
                                            <span class="remove-item">
                                            <a href="#" class="mini-remove" data-item-id="{{ $item->id }}">
                                                <i class="fa fa-times"></i>
                                            </a>
                                            </span>

                                            <div class="cart-float-single-item-image">
                                            <a href="{{ $link }}">
                                                <img width="300" height="360" src="{{ $image }}" class="img-fluid" alt="{{ $name }}">
                                            </a>
                                            </div>

                                            <div class="cart-float-single-item-desc">
                                            <p class="product-title">
                                                <a href="{{ $link }}">
                                                {{ $name }}
                                                @if($vlabel)
                                                    <br><small>Varian: {{ $vlabel }}</small>
                                                @endif
                                                </a>
                                            </p>
                                            <p class="price">
                                                <span class="quantity">{{ $qty }} x</span>
                                                Rp{{ number_format($price, 0, ',', '.') }}
                                            </p>
                                            </div>
                                        </div>
                                        @endforeach
                                    @else
                                        <div class="text-center py-3">Keranjang belanja Anda kosong.</div>
                                    @endif
                                    </div>

                                <div class="cart-calculation">
                                    <div class="calculation-details">
                                        @php
                                            $miniSubtotal = 0;
                                            if (isset($cart) && $cart->items->count()) {
                                                foreach ($cart->items as $ci) {
                                                    $unit = optional($ci->variant)->price
                                                        ?? optional($ci->product)->price
                                                        ?? 0;
                                                    $qty  = (int) ($ci->quantity ?? 1);
                                                    $miniSubtotal += $unit * $qty;
                                                }
                                            }
                                        @endphp

                                        <p class="total">
                                            Subtotal
                                            <span id="mini-cart-subtotal">
                                            Rp{{ number_format($miniSubtotal, 0, ',', '.') }}
                                            </span>
                                        </p>
                                        </div>

                                    <div class="floating-cart-btn text-center">
                                        <a class="floating-cart-btn" href="{{ route('checkout.index') }}">Checkout</a>
                                        <a class="floating-cart-btn" href="{{ route('cart.index') }}">View Cart</a>
                                    </div>
                                </div>
                            </div>
                            <!-- end of cart floating box -->
                        </div>

                        <!--=======  End of single icon  =======-->
                    </div>

                    <!--=======  End of menu top icons  =======-->
                </div>
            </div>
        </div>
    </div>

    <!--=======  End of Menu Top  =======-->

    <!--=======  navigation menu  =======-->

    <div class="navigation-menu">
        <div class="container">
            <div class="row">
                <div class="col-12 col-lg-3">
                    <!--=======  category menu  =======-->

                    <div class="hero-side-category">
                        <!-- Category Toggle Wrap -->
                        <div class="category-toggle-wrap">
                            <!-- Category Toggle -->
                            <button class="category-toggle"> <span class="lnr lnr-text-align-left"></span> Pilih
                                kategori <span class="lnr lnr-chevron-down"></span></button>
                        </div>

                        <!-- Category Menu -->
                        @php
                            $categories = cache()->remember('product_categories', 60, function () {
                                return \App\Models\Inventory\Product::query()
                                    ->select('brand', 'model')
                                    ->get()
                                    ->map(fn($item) => !empty($item->brand) ? "{$item->brand}" : (!empty($item->model) ? "{$item->model}" : null))
                                    ->unique()
                                    ->values();
                            });
                        @endphp
                        <nav class="category-menu">
                            <ul>
                                @foreach($categories as $category)
                                    @if (!empty($category))
                                        <li><a
                                                href="{{ route('ecommerce.products.index', ['category' => $category]) }}">{{ $category }}</a>
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        </nav>
                    </div>

                    <!--=======  End of category menu =======-->
                </div>
                <div class="col-12 col-lg-9">
                    <!-- navigation section -->
                    <div class="main-menu">
                        <nav>
                            <ul>
                                @php
                                    $currentRoute = Route::currentRouteName();
                                    $menu = [
                                        ['label' => 'Beranda', 'link' => route('ecommerce.index'), 'route' => 'ecommerce.index'],
                                        ['label' => 'Tentang Kami', 'link' => route('ecommerce.about'), 'route' => 'ecommerce.about'],
                                        ['label' => 'Artikel', 'link' => route('ecommerce.articles'), 'route' => 'ecommerce.articles'],
                                    ];
                                @endphp
                                @foreach($menu as $item)
                                    <li>
                                        <a href="{{ $item['link'] }}"
                                            class="{{ $currentRoute == $item['route'] ? 'active' : '' }}">
                                            {{ $item['label'] }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </nav>
                    </div>
                    <!-- end of navigation section -->
                </div>
                <div class="col-12 d-block d-lg-none">
                    <!-- Mobile Menu -->
                    <div class="mobile-menu"></div>
                </div>
            </div>
        </div>
    </div>

    <!--=======  End of navigation menu  =======-->
</div>
