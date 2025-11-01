@extends('ecommerce.layouts.app')
@section('title', 'Keranjang Belanja Anda')
@push('css')
    {{-- Tambahkan CSS kustom di sini jika diperlukan --}}
@endpush

@section('content')

    @include('ecommerce.layouts.partials.breadscrum')

<div class="page-section mb-80">
		<div class="container">
			<div class="row">
				<div class="col-12">

					<!-- Checkout Form s-->
					<form action="#" class="checkout-form">
						<div class="row row-40">

							<div class="col-lg-7 mb-20">

								<!-- Billing Address -->
								<div id="billing-form" class="mb-40">
									<h4 class="checkout-title">Billing Address</h4>

									<div class="row">

										<div class="col-md-6 col-12 mb-20">
											<label>First Name*</label>
											<input type="text" placeholder="First Name">
										</div>

										<div class="col-md-6 col-12 mb-20">
											<label>Last Name*</label>
											<input type="text" placeholder="Last Name">
										</div>

										<div class="col-md-6 col-12 mb-20">
											<label>Email Address*</label>
											<input type="email" placeholder="Email Address">
										</div>

										<div class="col-md-6 col-12 mb-20">
											<label>Phone no*</label>
											<input type="text" placeholder="Phone number">
										</div>

										<div class="col-12 mb-20">
											<label>Company Name</label>
											<input type="text" placeholder="Company Name">
										</div>

										<div class="col-12 mb-20">
											<label>Address*</label>
											<input type="text" placeholder="Address line 1">
											<input type="text" placeholder="Address line 2">
										</div>

										<div class="col-md-6 col-12 mb-20">
											<label>Country*</label>
											<select class="nice-select" style="display: none;">
												<option>Bangladesh</option>
												<option>China</option>
												<option>country</option>
												<option>India</option>
												<option>Japan</option>
											</select><div class="nice-select" tabindex="0"><span class="current">Bangladesh</span><ul class="list"><li data-value="Bangladesh" class="option selected">Bangladesh</li><li data-value="China" class="option">China</li><li data-value="country" class="option">country</li><li data-value="India" class="option">India</li><li data-value="Japan" class="option">Japan</li></ul></div>
										</div>

										<div class="col-md-6 col-12 mb-20">
											<label>Town/City*</label>
											<input type="text" placeholder="Town/City">
										</div>

										<div class="col-md-6 col-12 mb-20">
											<label>State*</label>
											<input type="text" placeholder="State">
										</div>

										<div class="col-md-6 col-12 mb-20">
											<label>Zip Code*</label>
											<input type="text" placeholder="Zip Code">
										</div>

										<div class="col-12 mb-20">
											<div class="check-box">
												<input type="checkbox" id="create_account">
												<label for="create_account">Create an Acount?</label>
											</div>
											<div class="check-box">
												<input type="checkbox" id="shiping_address" data-shipping="">
												<label for="shiping_address">Ship to Different Address</label>
											</div>
										</div>

									</div>

								</div>

								<!-- Shipping Address -->
								<div id="shipping-form" class="mb-40">
									<h4 class="checkout-title">Shipping Address</h4>

									<div class="row">

										<div class="col-md-6 col-12 mb-20">
											<label>First Name*</label>
											<input type="text" placeholder="First Name">
										</div>

										<div class="col-md-6 col-12 mb-20">
											<label>Last Name*</label>
											<input type="text" placeholder="Last Name">
										</div>

										<div class="col-md-6 col-12 mb-20">
											<label>Email Address*</label>
											<input type="email" placeholder="Email Address">
										</div>

										<div class="col-md-6 col-12 mb-20">
											<label>Phone no*</label>
											<input type="text" placeholder="Phone number">
										</div>

										<div class="col-12 mb-20">
											<label>Company Name</label>
											<input type="text" placeholder="Company Name">
										</div>

										<div class="col-12 mb-20">
											<label>Address*</label>
											<input type="text" placeholder="Address line 1">
											<input type="text" placeholder="Address line 2">
										</div>

										<div class="col-md-6 col-12 mb-20">
											<label>Country*</label>
											<select class="nice-select" style="display: none;">
												<option>Bangladesh</option>
												<option>China</option>
												<option>country</option>
												<option>India</option>
												<option>Japan</option>
											</select><div class="nice-select" tabindex="0"><span class="current">Bangladesh</span><ul class="list"><li data-value="Bangladesh" class="option selected">Bangladesh</li><li data-value="China" class="option">China</li><li data-value="country" class="option">country</li><li data-value="India" class="option">India</li><li data-value="Japan" class="option">Japan</li></ul></div>
										</div>

										<div class="col-md-6 col-12 mb-20">
											<label>Town/City*</label>
											<input type="text" placeholder="Town/City">
										</div>

										<div class="col-md-6 col-12 mb-20">
											<label>State*</label>
											<input type="text" placeholder="State">
										</div>

										<div class="col-md-6 col-12 mb-20">
											<label>Zip Code*</label>
											<input type="text" placeholder="Zip Code">
										</div>

									</div>

								</div>

							</div>

							<div class="col-lg-5">
								<div class="row">



										<!-- Cart Total -->
<div class="col-12 mb-60">
  <h4 class="checkout-title">Cart Total</h4>

  <div class="checkout-cart-total">
    <h4>Product <span>Total</span></h4>

    <ul>
      @forelse($cart->items as $it)
        @php
          $unit = optional($it->variant)->price
            ?? optional($it->variant)->final_price
            ?? optional($it->product)->price
            ?? optional($it->product)->final_price
            ?? optional($it->product)->regular_price
            ?? 0;
          $qty   = (int) $it->quantity;
          $line  = (float) $unit * $qty;
          $name  = $it->product->name ?? 'Produk';
          $vname = $it->variant->name ?? ($it->variant->color ?? null);
        @endphp
        <li>
          {{ $name }} @if($vname) ({{ $vname }}) @endif X {{ $qty }}
          <span>Rp{{ number_format($line, 0, ',', '.') }}</span>
        </li>
      @empty
        <li>Keranjang kosong <span>Rp0</span></li>
      @endforelse
    </ul>

    <p>Sub Total <span>Rp{{ number_format($subtotal ?? 0, 0, ',', '.') }}</span></p>
    <p>Shipping Fee
      <span>
        @if(($shippingFee ?? 0) > 0)
          Rp{{ number_format($shippingFee, 0, ',', '.') }}
        @else
          Gratis
        @endif
      </span>
    </p>

    <h4>Grand Total <span>Rp{{ number_format($grandTotal ?? 0, 0, ',', '.') }}</span></h4>
  </div>
</div>




									<!-- Payment Method -->
									<div class="col-12">

										<h4 class="checkout-title">Payment Method</h4>

										<div class="checkout-payment-method">

											<div class="single-method">
												<input type="radio" id="payment_check" name="payment-method" value="check">
												<label for="payment_check">Check Payment</label>
												<p data-method="check">Please send a Check to Store name with Store Street, Store Town, Store
													State, Store Postcode, Store Country.</p>
											</div>

											<div class="single-method">
												<input type="radio" id="payment_bank" name="payment-method" value="bank">
												<label for="payment_bank">Direct Bank Transfer</label>
												<p data-method="bank">Please send a Check to Store name with Store Street, Store Town, Store
													State, Store Postcode, Store Country.</p>
											</div>

											<div class="single-method">
												<input type="radio" id="payment_cash" name="payment-method" value="cash">
												<label for="payment_cash">Cash on Delivery</label>
												<p data-method="cash">Please send a Check to Store name with Store Street, Store Town, Store
													State, Store Postcode, Store Country.</p>
											</div>

											<div class="single-method">
												<input type="radio" id="payment_paypal" name="payment-method" value="paypal">
												<label for="payment_paypal">Paypal</label>
												<p data-method="paypal">Please send a Check to Store name with Store Street, Store Town, Store
													State, Store Postcode, Store Country.</p>
											</div>

											<div class="single-method">
												<input type="radio" id="payment_payoneer" name="payment-method" value="payoneer">
												<label for="payment_payoneer">Payoneer</label>
												<p data-method="payoneer">Please send a Check to Store name with Store Street, Store Town, Store
													State, Store Postcode, Store Country.</p>
											</div>

											<div class="single-method">
												<input type="checkbox" id="accept_terms">
												<label for="accept_terms">I’ve read and accept the terms &amp; conditions</label>
											</div>

										</div>

										<a href="{{ route('checkout.place') }}" class="btn btn-primary my-3">
                                        Place order
                                        </a>


									</div>

								</div>
							</div>

						</div>
					</form>

				</div>
			</div>
		</div>
	</div>
@endsection
