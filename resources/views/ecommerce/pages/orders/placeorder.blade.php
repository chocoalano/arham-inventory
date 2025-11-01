{{-- resources/views/ecommerce/pages/orders/placeorder.blade.php --}}
@extends('ecommerce.layouts.app')

@section('title', 'Place Order')

@push('css')
<style>
  .hidden { display: none; }
</style>
@endpush

@section('content')
  @include('ecommerce.layouts.partials.breadscrum')

  <div class="container py-4">
    <h3 class="mb-3">Konfirmasi & Place Order</h3>

    {{-- Fallback non-JS: form submit langsung ke checkout.store --}}
    <form id="checkout-form" action="{{ route('checkout.store') }}" method="post" novalidate>
      @csrf

      {{-- ====== Billing (WAJIB sesuai validate di CheckoutController@store) ====== --}}
      <div class="row g-3">
        <div class="col-md-6">
          <input name="billing[first_name]" class="form-control" placeholder="Nama depan" required>
        </div>
        <div class="col-md-6">
          <input name="billing[last_name]" class="form-control" placeholder="Nama belakang" required>
        </div>
        <div class="col-md-6">
          <input name="billing[email]" type="email" class="form-control" placeholder="Email" required>
        </div>
        <div class="col-md-6">
          <input name="billing[phone]" class="form-control" placeholder="No HP/WA" required>
        </div>
        <div class="col-12">
          <input name="billing[address1]" class="form-control" placeholder="Alamat lengkap" required>
        </div>
        <div class="col-md-4">
          <input name="billing[city]" class="form-control" placeholder="Kota" required>
        </div>
        <div class="col-md-4">
          <input name="billing[state]" class="form-control" placeholder="Provinsi" required>
        </div>
        <div class="col-md-2">
          <input name="billing[postcode]" class="form-control" placeholder="Kode Pos" required>
        </div>
        <div class="col-md-2">
          <input name="billing[country]" class="form-control" placeholder="Negara" required>
        </div>

        {{-- ====== Payment & Terms ====== --}}
        <div class="col-md-6">
          <label class="form-label">Metode Pembayaran</label>
          <select name="payment_method" class="form-select" required>
            <option value="bank">Transfer Bank</option>
            <option value="cash">Cash</option>
            <option value="check">Check</option>
            <option value="paypal">PayPal</option>
            <option value="payoneer">Payoneer</option>
          </select>
        </div>

        <div class="col-12">
          <label class="d-block">
            <input type="checkbox" name="accept_terms" value="1" required>
            Saya setuju dengan syarat & ketentuan
          </label>
        </div>

        <div class="col-12">
          {{-- type="submit" agar tanpa JS tetap jalan --}}
          <button type="submit" class="btn btn-primary place-order">
            Place order
            <span class="loading-spinner hidden ms-2">
              <i class="fa fa-spinner fa-spin"></i>
            </span>
          </button>
        </div>
      </div>
    </form>
  </div>
@endsection

@push('js')
<script>
(function($){
  // Intersep submit untuk pakai AJAX + spinner (optional)
  $(document).on('submit', '#checkout-form', function(e){
    e.preventDefault();

    const $form = $(this);
    const $btn  = $form.find('.place-order');
    const $spin = $btn.find('.loading-spinner');

    $btn.prop('disabled', true);
    $spin.removeClass('hidden');

    $.ajax({
      url: $form.attr('action'),
      method: 'POST',
      data: $form.serialize(),
      headers: { 'Accept': 'application/json' }
    })
    .done(function(res){
      if (res && res.redirect) {
        window.location.href = res.redirect;
      } else {
        // fallback jika response bukan JSON
        window.location.href = @json(route('checkout.index'));
      }
    })
    .fail(function(xhr){
      let msg = 'Gagal membuat order. Periksa data Anda.';
      if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;

      // tampilkan error field sederhana (opsional)
      if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
        const errs = xhr.responseJSON.errors;
        msg += '\n\n' + Object.keys(errs).map(k => '- ' + errs[k].join(', ')).join('\n');
      }
      alert(msg);
    })
    .always(function(){
      $spin.addClass('hidden');
      $btn.prop('disabled', false);
    });
  });
})(jQuery);
</script>
@endpush
