@extends('ecommerce.layouts.app')
@section('title', 'Pembayaran')

@push('css')
<style>
  .is-invalid{border-color:#dc3545}
  .invalid-feedback{display:block}
  .checkout-title{font-weight:600;margin-bottom:1rem}
</style>
@endpush

@section('content')
  @include('ecommerce.layouts.partials.breadscrum')

  <div class="page-section mb-80">
    <div class="container">

      {{-- Notifikasi validasi/global --}}
      @if ($errors->any())
        <div class="alert alert-danger mb-20">
          <strong>Terjadi kesalahan.</strong> Periksa kembali isian Anda.
          <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif
      @if (session('status'))
        <div class="alert alert-success mb-20">{{ session('status') }}</div>
      @endif

      <div class="row">
        <div class="col-12">
          <form id="checkout-form" class="checkout-form" action="{{ route('checkout.store') }}" method="POST" novalidate>
            @csrf

            <div class="row row-40">
              {{-- ====================== LEFT: BILLING ====================== --}}
              <div class="col-lg-7 mb-20">
                <div id="billing-form" class="mb-40">
                  <h4 class="checkout-title">Alamat Penagihan</h4>

                  <div class="row">
                    <div class="col-md-6 col-12 mb-20">
                      <label for="billing_first_name">Nama Depan*</label>
                      <input
                        id="billing_first_name"
                        type="text"
                        name="billing[first_name]"
                        value="{{ old('billing.first_name') }}"
                        placeholder="Nama Depan"
                        class="@error('billing.first_name') is-invalid @enderror"
                        required
                        autocomplete="given-name"
                      >
                      @error('billing.first_name')
                        <small class="invalid-feedback">{{ $message }}</small>
                      @enderror
                    </div>

                    <div class="col-md-6 col-12 mb-20">
                      <label for="billing_last_name">Nama Belakang</label>
                      <input
                        id="billing_last_name"
                        type="text"
                        name="billing[last_name]"
                        value="{{ old('billing.last_name') }}"
                        placeholder="Nama Belakang"
                        autocomplete="family-name"
                        class="@error('billing.last_name') is-invalid @enderror"
                      >
                      @error('billing.last_name')
                        <small class="invalid-feedback">{{ $message }}</small>
                      @enderror
                    </div>

                    <div class="col-md-6 col-12 mb-20">
                      <label for="billing_email">Alamat Email*</label>
                      <input
                        id="billing_email"
                        type="email"
                        name="billing[email]"
                        value="{{ old('billing.email') }}"
                        placeholder="Alamat Email"
                        class="@error('billing.email') is-invalid @enderror"
                        required
                        autocomplete="email"
                      >
                      @error('billing.email')
                        <small class="invalid-feedback">{{ $message }}</small>
                      @enderror
                    </div>

                    <div class="col-md-6 col-12 mb-20">
                      <label for="billing_phone">Nomor Telepon*</label>
                      <input
                        id="billing_phone"
                        type="tel"
                        name="billing[phone]"
                        value="{{ old('billing.phone') }}"
                        placeholder="Nomor Telepon"
                        class="@error('billing.phone') is-invalid @enderror"
                        required
                        autocomplete="tel"
                      >
                      @error('billing.phone')
                        <small class="invalid-feedback">{{ $message }}</small>
                      @enderror
                    </div>

                    <div class="col-12 mb-20">
                      <label for="billing_company">Nama Perusahaan (Opsional)</label>
                      <input
                        id="billing_company"
                        type="text"
                        name="billing[company]"
                        value="{{ old('billing.company') }}"
                        placeholder="Nama Perusahaan"
                        class="@error('billing.company') is-invalid @enderror"
                      >
                      @error('billing.company')
                        <small class="invalid-feedback">{{ $message }}</small>
                      @enderror
                    </div>

                    <div class="col-12 mb-20">
                      <label for="billing_address1">Alamat Lengkap*</label>
                      <input
                        id="billing_address1"
                        type="text"
                        name="billing[address1]"
                        value="{{ old('billing.address1') }}"
                        placeholder="Alamat Lengkap Baris 1 (Contoh: Nama Jalan, Nomor Rumah)"
                        class="@error('billing.address1') is-invalid @enderror"
                        required
                        autocomplete="address-line1"
                      >
                      @error('billing.address1')
                        <small class="invalid-feedback">{{ $message }}</small>
                      @enderror

                      <input
                        class="mt-10 @error('billing.address2') is-invalid @enderror"
                        type="text"
                        name="billing[address2]"
                        value="{{ old('billing.address2') }}"
                        placeholder="Alamat Lengkap Baris 2 (Contoh: Blok/Unit/Patokan)"
                        autocomplete="address-line2"
                      >
                      @error('billing.address2')
                        <small class="invalid-feedback">{{ $message }}</small>
                      @enderror
                    </div>

                    <div class="col-md-6 col-12 mb-20">
                      <label for="billing_city">Kota/Kabupaten*</label>
                      <input
                        id="billing_city"
                        type="text"
                        name="billing[city]"
                        value="{{ old('billing.city') }}"
                        placeholder="Kota/Kabupaten"
                        class="@error('billing.city') is-invalid @enderror"
                        required
                        autocomplete="address-level2"
                      >
                      @error('billing.city')
                        <small class="invalid-feedback">{{ $message }}</small>
                      @enderror
                    </div>

                    <div class="col-md-6 col-12 mb-20">
                      <label for="billing_state">Provinsi*</label>
                      <input
                        id="billing_state"
                        type="text"
                        name="billing[state]"
                        value="{{ old('billing.state') }}"
                        placeholder="Provinsi"
                        class="@error('billing.state') is-invalid @enderror"
                        required
                        autocomplete="address-level1"
                      >
                      @error('billing.state')
                        <small class="invalid-feedback">{{ $message }}</small>
                      @enderror
                    </div>

                    <div class="col-md-6 col-12 mb-20">
                      <label for="billing_postcode">Kode Pos*</label>
                      <input
                        id="billing_postcode"
                        type="text"
                        name="billing[postcode]"
                        value="{{ old('billing.postcode') }}"
                        placeholder="Kode Pos"
                        class="@error('billing.postcode') is-invalid @enderror"
                        required
                        autocomplete="postal-code"
                      >
                      @error('billing.postcode')
                        <small class="invalid-feedback">{{ $message }}</small>
                      @enderror
                    </div>
                  </div>
                </div>
              </div>

              {{-- ====================== RIGHT: CART + PAYMENT ====================== --}}
              <div class="col-lg-5">
                <div class="row">
                  {{-- Cart Total --}}
                  <div class="col-12 mb-60">
                    <h4 class="checkout-title">Total Keranjang</h4>
                    <div class="checkout-cart-total">
                      <h4>Produk <span>Total</span></h4>
                      <ul>
                        @forelse($cart->items as $it)
                          @php
                            $unit = optional($it->variant)->price
                                ?? optional($it->variant)->final_price
                                ?? optional($it->product)->price
                                ?? optional($it->product)->final_price
                                ?? optional($it->product)->regular_price
                                ?? 0;
                            $qty  = (int) $it->quantity;
                            $line = (float) $unit * $qty;
                            $name = $it->product->name ?? 'Produk';
                            $vname = $it->variant->name ?? ($it->variant->color ?? null);
                          @endphp
                          <li>
                            {{ $name }} @if($vname) ({{ $vname }}) @endif × {{ $qty }}
                            <span>Rp{{ number_format($line, 0, ',', '.') }}</span>
                          </li>
                        @empty
                          <li>Keranjang kosong <span>Rp0</span></li>
                        @endforelse
                      </ul>

                      <p>Subtotal <span>Rp{{ number_format($subtotal ?? 0, 0, ',', '.') }}</span></p>
                      <p>Biaya Pengiriman
                        <span>
                          @if(($shippingFee ?? 0) > 0)
                            Rp{{ number_format($shippingFee, 0, ',', '.') }}
                          @else
                            Gratis
                          @endif
                        </span>
                      </p>

                      <h4>Total Keseluruhan
                        <span>Rp{{ number_format($grandTotal ?? 0, 0, ',', '.') }}</span>
                      </h4>
                    </div>
                  </div>

                  {{-- Payment Method (tanpa Midtrans) --}}
                  <div class="col-12">
                    <h4 class="checkout-title">Metode Pembayaran</h4>
                    <div class="checkout-payment-method">
                      <div class="single-method mb-10">
                        <div class="form-check">
                          <input
                            class="form-check-input"
                            type="radio"
                            id="pm_transfer"
                            name="payment_method"
                            value="bank_transfer"
                            {{ old('payment_method', 'bank_transfer') === 'bank_transfer' ? 'checked' : '' }}
                            required
                          >
                          <label class="form-check-label" for="pm_transfer">Transfer Bank (manual)</label>
                        </div>
                      </div>
                      <div class="single-method mb-10">
                        <div class="form-check">
                          <input
                            class="form-check-input"
                            type="radio"
                            id="pm_cod"
                            name="payment_method"
                            value="cod"
                            {{ old('payment_method') === 'cod' ? 'checked' : '' }}
                          >
                          <label class="form-check-label" for="pm_cod">COD (Bayar di tempat)</label>
                        </div>
                      </div>
                      @error('payment_method')
                        <small class="invalid-feedback d-block">{{ $message }}</small>
                      @enderror

                      <div class="single-method mt-20">
                        <div class="form-check">
                          <input
                            class="form-check-input @error('accept_terms') is-invalid @enderror"
                            type="checkbox"
                            id="accept_terms"
                            name="accept_terms"
                            value="1"
                            {{ old('accept_terms') ? 'checked' : '' }}
                            required
                          >
                          <label class="form-check-label" for="accept_terms">
                            Saya telah membaca dan menyetujui syarat & ketentuan
                          </label>
                        </div>
                        @error('accept_terms')
                          <small class="invalid-feedback d-block">{{ $message }}</small>
                        @enderror
                      </div>
                    </div>

                    <button id="btn-place-order" type="submit" class="btn btn-primary my-3 w-100">
                      Buat Pesanan
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </form>
        </div> <!-- col-12 -->
      </div>
    </div>
  </div>
@endsection

{{-- Tidak ada script Midtrans / Snap JS --}}
@push('js')
  <script>
    // (Opsional) cegah double submit tanpa bergantung Midtrans
    document.getElementById('checkout-form')?.addEventListener('submit', function(e){
      const btn = document.getElementById('btn-place-order');
      if (btn) {
        btn.disabled = true;
        btn.innerText = 'Memproses...';
      }
    });
  </script>
@endpush
