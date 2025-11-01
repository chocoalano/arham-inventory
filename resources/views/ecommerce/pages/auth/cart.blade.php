@extends('ecommerce.layouts.app')
@section('title', 'Keranjang Belanja Anda')

@section('content')
    @include('ecommerce.layouts.partials.breadscrum')

    <div class="page-section mb-80">
        <div class="container">
            <div class="row">
                <div class="col-12">

                    <form id="cart-form">
                        @csrf

                        <div class="cart-table table-responsive mb-40">
                            @if(isset($cart) && $cart->items->count())
                                <table class="table" id="cart-table">
                                    <thead>
                                        <tr>
                                            <th class="pro-thumbnail">Gambar</th>
                                            <th class="pro-title">Produk</th>
                                            <th class="pro-price">Harga Satuan</th>
                                            <th class="pro-quantity">Jumlah</th>
                                            <th class="pro-subtotal">Subtotal</th>
                                            <th class="pro-remove">Hapus</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $grandTotal = 0; @endphp

                                        @foreach($cart->items as $item)
                                            @php
                                                $product = $item->product;
                                                $variant = $item->variant ?? null;
                                                $price = $variant?->price ?? $product?->price ?? 0;
                                                $lineTotal = $price * (int) $item->quantity;
                                                $grandTotal += $lineTotal;

                                                $productName = $product->name ?? 'Produk Tidak Ditemukan';
                                                $variantName = $variant->name ?? ($variant->color ?? null);
                                                $imageUrl = asset('ecommerce/images/products/product01.webp');
                                                $productSlug = $product->slug ?? '#';
                                            @endphp

                                            <tr data-item-id="{{ $item->id }}"
                                                data-variant-id="{{ (int) $item->product_variant_id }}"
                                                data-price="{{ (float) $price }}">
                                                <td class="pro-thumbnail">
                                                    <a href="{{ route('ecommerce.products.show', $productSlug) }}">
                                                        <img width="300" height="360" src="{{ $imageUrl }}" class="img-fluid"
                                                            alt="{{ $productName }}">
                                                    </a>
                                                </td>

                                                <td class="pro-title">
                                                    <a href="{{ route('ecommerce.products.show', $productSlug) }}">
                                                        {{ $productName }}
                                                        @if($variantName)
                                                            <br><small>Varian: {{ $variantName }}</small>
                                                        @endif
                                                    </a>
                                                </td>

                                                <td class="pro-price">
                                                    <span class="item-price">{{ number_format($price, 0, ',', '.') }}</span>
                                                </td>

                                                <td class="pro-quantity">
                                                    <div class="pro-qty">
                                                        <input type="number" value="{{ (int) $item->quantity }}" min="1"
                                                            data-qty-input onchange="updateQuantity(this)">
                                                        <a href="#" class="inc qty-btn" onclick="return stepQty(this, +1);"><i
                                                                class="fa fa-angle-up"></i></a>
                                                        <a href="#" class="dec qty-btn" onclick="return stepQty(this, -1);"><i
                                                                class="fa fa-angle-down"></i></a>
                                                    </div>
                                                </td>

                                                <td class="pro-subtotal">
                                                    <span class="item-subtotal">{{ number_format($lineTotal, 0, ',', '.') }}</span>
                                                </td>

                                                <td class="pro-remove">
                                                    <a href="#" class="btn-remove" data-item-id="{{ $item->id }}">
                                                        <i class="fa fa-trash-o"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <div class="alert alert-info text-center">Keranjang belanja Anda kosong.</div>
                            @endif
                        </div>
                    </form>

                    {{-- Ringkasan --}}
                    <div class="row">
                        <div class="col-lg-6 col-12">
                            <div class="calculate-shipping">
                                <h4>Kalkulasi Biaya Pengiriman</h4>
                                <form action="#">
                                    <div class="row">
                                        <div class="col-md-6 col-12 mb-25">
                                            <select class="nice-select">
                                                <option>Bangladesh</option>
                                                <option>China</option>
                                                <option>country</option>
                                                <option>India</option>
                                                <option>Japan</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 col-12 mb-25">
                                            <select class="nice-select">
                                                <option>Dhaka</option>
                                                <option>Barisal</option>
                                                <option>Khulna</option>
                                                <option>Comilla</option>
                                                <option>Chittagong</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 col-12 mb-25"><input type="text" placeholder="Postcode / Zip">
                                        </div>
                                        <div class="col-md-6 col-12 mb-25"><input type="submit" value="Estimate"></div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="col-lg-6 col-12 d-flex">
                            <div class="cart-summary">
                                <div class="cart-summary-wrap">
                                    <h4>Ringkasan Belanja</h4>
                                    <p>
                                        Subtotal Produk (<span
                                            id="total-items">{{ $cart->items->sum('quantity') ?? 0 }}</span> item)
                                        <span id="cart-subtotal">{{ number_format($grandTotal ?? 0, 0, ',', '.') }}</span>
                                    </p>
                                    <p>Biaya Pengiriman <span id="shipping-cost">Gratis</span></p>
                                    <h2>Total Keseluruhan
                                        <span
                                            id="cart-grand-total">{{ number_format($grandTotal ?? 0, 0, ',', '.') }}</span>
                                    </h2>
                                </div>
                                <div class="cart-summary-button">
                                    {{-- FIX: gunakan <a href> untuk ke checkout --}}
                                        <a href="{{ route('checkout.index') }}"
                                            class="checkout-btn {{ ($cart->items->count() ?? 0) ? '' : 'disabled' }}"
                                            @if(($cart->items->count() ?? 0) === 0) aria-disabled="true" tabindex="-1" @endif>
                                            Lanjut ke Checkout
                                        </a>

                                        <button class="update-btn" onclick="optimisticUpdate();">
                                            Perbarui Keranjang
                                        </button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div> <!-- col-12 -->
            </div>
        </div>
    </div>
@endsection


@push('js')
    <script>
        (function ($) {
            // ------------------ KONFIG ------------------
            const CART_UPDATE_URL = @json(isset($cart) && $cart->id ? route('cart.update', $cart->id) : null);
            const CART_SYNC_URL = @json(isset($cart) && $cart->id ? route('cart.sync', $cart->id) : null);
            const ITEM_DELETE_URL_T = @json(isset($cart) && $cart->id ? route('cart.items.destroy', [$cart->id, '__ID__']) : null);
            const CSRF_TOKEN = @json(csrf_token());
            const nf = new Intl.NumberFormat('id-ID');

            // ------------------ GUARD -------------------
            // Jika belum ada cart, disable kontrol qty agar tidak error
            if (!CART_UPDATE_URL) {
                $('[data-qty-input]').prop('disabled', true);
                // tetap exit: tidak ada endpoint untuk sinkron
                return;
            }

            // ------------------ UTIL --------------------
            function money(n) {
                return nf.format(Math.round(Number(n || 0)));
            }
            function itemDeleteUrl(id) {
                return ITEM_DELETE_URL_T ? ITEM_DELETE_URL_T.replace('__ID__', String(id)) : null;
            }
            function ensureCheckoutState() {
                const hasRows = $('#cart-table tbody tr').length > 0;
                const $btn = $('.checkout-btn');
                if (hasRows) {
                    $btn.removeClass('disabled').removeAttr('aria-disabled').removeAttr('tabindex');
                } else {
                    $btn.addClass('disabled').attr({ 'aria-disabled': true, tabindex: -1 });
                }
            }

            // Hitung ulang UI (optimistic)
            function recalcUI() {
                let sum = 0, items = 0;
                $('#cart-table tbody tr').each(function () {
                    const $tr = $(this);
                    const unit = parseFloat($tr.data('price') || 0);
                    const qty = Math.max(1, parseInt($tr.find('[data-qty-input]').val() || '1', 10));
                    sum += unit * qty;
                    items += qty;
                    $tr.find('.item-subtotal').text(money(unit * qty));
                });
                $('#cart-subtotal').text(money(sum));
                $('#cart-grand-total').text(money(sum));
                $('#total-items').text(money(items));
            }

            // Sinkron 1 baris ke server
            function syncRow($row) {
                const itemId = parseInt($row.data('item-id'), 10);
                const qty = Math.max(1, parseInt($row.find('[data-qty-input]').val() || '1', 10));

                if (!itemId) return;

                // Optimistic UI terlebih dulu
                recalcUI();

                $.ajax({
                    url: CART_UPDATE_URL,
                    method: 'PATCH',
                    headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                    contentType: 'application/json; charset=utf-8',
                    data: JSON.stringify({ item_id: itemId, qty }),
                })
                    .done(function (res) {
                        // Pastikan JSON, kalau tidak reload biar aman
                        if (!res || typeof res !== 'object') { location.reload(); return; }

                        // Sinkronisasi angka otoritatif dari server
                        $row.find('.item-subtotal').text(money(res.line_subtotal));
                        $('#cart-subtotal').text(money(res.cart_subtotal));
                        $('#cart-grand-total').text(money(res.cart_subtotal));
                        $('#total-items').text(money(res.cart_items_count));
                    })
                    .fail(function () {
                        location.reload();
                    });
            }

            // Debounce helper
            function debounce(fn, delay) {
                let t;
                return function () {
                    const ctx = this, args = arguments;
                    clearTimeout(t);
                    t = setTimeout(function () { fn.apply(ctx, args); }, delay);
                };
            }

            // ------------------ EVENT LISTENERS ------------------

            // Input/ubah qty → sync satu baris
            $(document).on('input change', '[data-qty-input]', debounce(function () {
                const $row = $(this).closest('tr');
                syncRow($row);
            }, 250));

            // Tombol +/-
            $(document).on('click', '.qty-btn', function (e) {
                e.preventDefault();
                const $row = $(this).closest('tr');
                const $input = $row.find('[data-qty-input]');
                const diff = $(this).hasClass('inc') ? 1 : -1;
                const val = Math.max(1, parseInt($input.val() || '1', 10) + diff);
                $input.val(val).trigger('change'); // akan memicu handler di atas
            });

            // ------------------ AKSI GLOBAL (dipakai inline) ------------------

            // Shim agar onchange="updateQuantity(this)" di Blade tetap berfungsi
            window.updateQuantity = function (el) {
                const $row = $(el).closest('tr');
                syncRow($row);
                return false;
            };

            // Shim agar onclick="return stepQty(this, ±1)" tetap berfungsi
            window.stepQty = function (btn, diff) {
                const $row = $(btn).closest('tr');
                const $input = $row.find('[data-qty-input]');
                const val = Math.max(1, parseInt($input.val() || '1', 10) + (diff || 0));
                $input.val(val).trigger('change');
                return false;
            };

            window.deleteItem = function (id) {
                const url = itemDeleteUrl(id);
                if (!url) return;

                $.ajax({
                    url,
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                })
                    .done(function (res) {
                        if (!res || typeof res !== 'object') { location.reload(); return; }

                        // Hapus baris di DOM
                        $('#cart-table tbody tr[data-item-id="' + id + '"]').remove();

                        // Update ringkasan dari server
                        $('#cart-subtotal').text(money(res.cart_subtotal));
                        $('#cart-grand-total').text(money(res.cart_subtotal));
                        $('#total-items').text(money(res.cart_items_count));

                        // Jika kosong → ganti tabel dengan pesan kosong & disable checkout
                        if ($('#cart-table tbody tr').length === 0) {
                            $('.cart-table').html('<div class="alert alert-info text-center">Keranjang belanja Anda kosong.</div>');
                        }
                        ensureCheckoutState();
                    })
                    .fail(function () {
                        location.reload();
                    });
            };

            // Bulk sync (tombol "Perbarui Keranjang")
            window.optimisticUpdate = function () {
                const items = [];
                $('#cart-table tbody tr').each(function () {
                    items.push({
                        item_id: parseInt($(this).data('item-id'), 10),
                        qty: Math.max(1, parseInt($(this).find('[data-qty-input]').val() || '1', 10)),
                    });
                });

                // Optimistic recalc
                recalcUI();

                $.ajax({
                    url: CART_SYNC_URL,
                    method: 'PATCH',
                    headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                    contentType: 'application/json; charset=utf-8',
                    data: JSON.stringify({ items }),
                })
                    .done(function (res) {
                        if (!res || typeof res !== 'object') { location.reload(); return; }

                        // Perbarui subtotal per baris jika dikirim server
                        if (res.lines) {
                            Object.keys(res.lines).forEach(function (id) {
                                $('#cart-table tbody tr[data-item-id="' + id + '"] .item-subtotal').text(money(res.lines[id]));
                            });
                        }

                        // Ringkasan otoritatif
                        $('#cart-subtotal').text(money(res.cart_subtotal));
                        $('#cart-grand-total').text(money(res.cart_subtotal));
                        $('#total-items').text(money(res.cart_items_count));

                        ensureCheckoutState();
                    })
                    .fail(function () {
                        location.reload();
                    });
            };

            // Pastikan tombol checkout sinkron saat load
            ensureCheckoutState();

        })(jQuery);
    </script>
@endpush
