@extends('ecommerce.layouts.app')
@section('title', 'Keranjang Belanja Anda')

@push('css')
<style>
  .cart-summary .checkout-btn.disabled { pointer-events: none; opacity:.6 }
  .pro-qty { position: relative; display: inline-flex; align-items: center; gap: .25rem }
  .pro-qty input[type=number]{ width:72px; text-align:center }
  .qty-btn{ color:inherit }
  /* Status loading/disabled */
  .row-loading{ opacity:.6; pointer-events:none }
  .toast-container{ z-index: 9999 }
</style>
@endpush

@section('content')
  @include('ecommerce.layouts.partials.breadscrum')

  <div class="page-section mb-80">
    <div class="container">
      <div class="row">
        <div class="col-12">

          <form id="cart-form" onsubmit="return false;">
            @csrf

            <div class="cart-table table-responsive mb-40">
              @if($cartItems->count())
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
                    @foreach($cartItems as $item)
                      @php
                        $product   = $item->product;
                        $variant   = $item->variant ?? null;

                        // harga prioritas: cart_items.price → variant.price → product.price
                        $gross     = (float)($item->price ?? $variant?->price ?? $product?->price ?? 0);
                        $discount  = (float)($item->discount_amount ?? $item->discount ?? 0);
                        $netUnit   = max(0, $gross - $discount);

                        $qty       = (int)($item->quantity ?? $item->qty ?? 0);
                        $lineTotal = $netUnit * $qty;

                        $productName  = $product->name ?? 'Produk Tidak Ditemukan';
                        $variantName  = $variant->name ?? ($variant->color ?? null);
                        $productSku   = $product->sku ?? null;
                        $productUrl   = ($product && $productSku) ? route('ecommerce.products.show', $productSku) : '#';

                        // fallback gambar (silakan ganti ke thumbnail Anda)
                        // Pastikan ini valid
                        $imageUrl     = asset('ecommerce/images/products/product01.webp');
                      @endphp

                      <tr data-item-id="{{ $item->id }}"
                          data-price="{{ $netUnit }}"
                          data-qty-last="{{ $qty }}">
                        <td class="pro-thumbnail">
                          <a href="{{ $productUrl }}">
                            <img width="300" height="360" src="{{ $imageUrl }}" class="img-fluid" alt="{{ $productName }}">
                          </a>
                        </td>

                        <td class="pro-title">
                          <a href="{{ $productUrl }}">
                            {{ $productName }}
                            @if($variantName)
                              <br><small>Varian: {{ $variantName }}</small>
                            @endif
                          </a>
                        </td>

                        <td class="pro-price">
                          <span class="item-price">{{ number_format($netUnit, 0, ',', '.') }}</span>
                        </td>

                        <td class="pro-quantity">
                          <div class="pro-qty">
                            {{-- PENTING: Hapus onchange="..." untuk mencegah pemicuan ganda --}}
                            <input type="number" min="1"
                                   value="{{ $qty }}"
                                   data-qty-input>
                            {{-- PENTING: Hapus onclick="..." untuk mencegah pemicuan ganda --}}
                            <a href="#" class="inc qty-btn">
                              <i class="fa fa-angle-up"></i>
                            </a>
                            <a href="#" class="dec qty-btn">
                              <i class="fa fa-angle-down"></i>
                            </a>
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
                <div class="alert alert-info text-center mb-0">Keranjang belanja Anda kosong.</div>
              @endif
            </div>
          </form>

          {{-- Ringkasan --}}
          <div class="row">
            <div class="col-lg-6 col-12">
                <div class="alert alert-primary" role="alert">
                    Pastikan jumlah dan varian produk sudah benar sebelum melanjutkan ke proses checkout.
                </div>
            </div>

            <div class="col-lg-6 col-12 d-flex">
              @php
                $hasItems      = $itemsCount > 0;
                $displaySub    = (int) $subtotal;
                $displayGrand  = (int) $grandTotal;
              @endphp

              <div class="cart-summary ms-lg-auto w-100">
                <div class="cart-summary-wrap">
                  <h4>Ringkasan Belanja</h4>
                  <p>
                    Subtotal Produk
                    (<span id="total-items">{{ number_format($itemsCount, 0, ',', '.') }}</span> item)
                    <span id="cart-subtotal">{{ number_format($displaySub, 0, ',', '.') }}</span>
                  </p>
                  <p>Biaya Pengiriman <span id="shipping-cost">Gratis</span></p>
                  <h2>Total Keseluruhan
                    <span id="cart-grand-total">{{ number_format($displayGrand, 0, ',', '.') }}</span>
                  </h2>
                </div>

                <div class="cart-summary-button d-flex gap-2 flex-wrap">
                  {{-- Gunakan window.location.href di JS untuk navigasi jika perlu --}}
                   <button type="button"
                        class="checkout-btn btn btn-outline-secondary {{ $hasItems ? '' : 'disabled' }}"
                        attr-url="{{ route('checkout.index', absolute: false) }}"
                        @if(!$hasItems) aria-disabled="true" tabindex="-1" @endif>
                        Checkout
                    </button>

                  {{-- Mempertahankan onclick di sini tidak masalah karena hanya memanggil syncAll --}}
                  <button type="button" class="update-btn btn btn-outline-secondary" onclick="optimisticUpdate();">
                    Perbarui
                  </button>
                </div>
              </div>
            </div>

          </div>

        </div> </div>
    </div>
  </div>
@endsection

@push('js')
<script>
  // Definisikan variabel konfigurasi Laravel untuk diakses file JS eksternal
  window.cartConfig = {
    cartId: @json($cart?->id),
    updateUrl: @json($cart?->id ? route('cart.update', $cart->id) : null),
    syncUrl: @json($cart?->id ? route('cart.sync', $cart->id) : null),
    deleteTpl: @json($cart?->id ? route('cart.items.destroy', [$cart->id, '__ID__']) : null),
    csrfToken: @json(csrf_token()),
  };
</script>
{{-- Panggil file JavaScript yang sudah dipisahkan --}}
<script src="{{ asset('ecommerce/js/pages/cart.js') }}"></script>
@endpush
