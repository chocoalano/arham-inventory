@extends('ecommerce.layouts.app')
@section('title', 'Detail Pesanan')

@push('css')
<style>
  .status-badge { text-transform: uppercase; font-size:.75rem }
  .status-draft { background:#f1c40f;color:#000 }
  .status-posted { background:#27ae60;color:#fff }
  .status-cancelled { background:#e74c3c;color:#fff }
  .kv{ margin-bottom:.5rem } .kv small{ color:#666; display:block }
  .summary-box{ border:1px solid #e5e7eb;border-radius:.5rem;padding:1rem }
</style>
@endpush

@section('content')
  @include('ecommerce.layouts.partials.breadscrum')

  <div class="page-section mb-80">
    <div class="container">
      @php
        $ref = $trx->reference_number ?? $trx->order_no ?? $trx->order_code ?? $trx->id;
        $date = \Illuminate\Support\Carbon::parse($trx->transaction_date);
        $st   = (string) ($status ?? $trx->status ?? 'draft');
        $badge = $st === 'posted' ? 'status-posted' : ($st === 'cancelled' ? 'status-cancelled' : 'status-draft');
      @endphp

      <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Detail Pesanan</h4>
        {{-- Perbaikan: kembali ke daftar pesanan --}}
        <a href="{{ route('auth.profile') }}#orders" class="btn btn-sm btn-outline-secondary">← Kembali</a>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-lg-8">
          <div class="summary-box">
            <div class="row">
              <div class="col-md-6 kv">
                <small>Referensi</small>
                <div class="fw-semibold">{{ $ref }}</div>
              </div>
              <div class="col-md-6 kv">
                <small>Tanggal</small>
                <div>{{ $date->format('d M Y H:i') }}</div>
              </div>
              <div class="col-md-6 kv">
                <small>Status</small>
                <div><span class="badge status-badge {{ $badge }}">{{ $st }}</span></div>
              </div>
              <div class="col-md-6 kv">
                <small>Jumlah Item</small>
                <div>{{ (int)($trx->item_count ?? $trx->details->sum('qty')) }}</div>
              </div>
              <div class="col-md-6 kv">
                <small>Nama Customer</small>
                <div>{{ $trx->customer_name ?? '-' }}</div>
              </div>
              <div class="col-md-6 kv">
                <small>No. Telepon</small>
                <div>{{ $trx->customer_phone ?? '-' }}</div>
              </div>
              <div class="col-12 kv">
                <small>Alamat</small>
                <div>{{ $trx->customer_full_address ?? '-' }}</div>
              </div>
              @if(!empty($trx->remarks))
              <div class="col-12 kv">
                <small>Catatan</small>
                <div>{{ $trx->remarks }}</div>
              </div>
              @endif
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="summary-box">
            <h6 class="fw-semibold mb-3">Ringkasan</h6>
            <div class="d-flex justify-content-between">
              <span>Subtotal</span>
              <span>Rp{{ number_format((float)($subtotal ?? 0), 0, ',', '.') }}</span>
            </div>
            <div class="d-flex justify-content-between">
              <span>Ongkir</span>
              <span>
                @if(($shippingFee ?? 0) > 0)
                  Rp{{ number_format((float) $shippingFee, 0, ',', '.') }}
                @else
                  Gratis
                @endif
              </span>
            </div>
            <hr>
            <div class="d-flex justify-content-between fw-semibold">
              <span>Grand Total</span>
              <span>Rp{{ number_format((float)($grandTotal ?? 0), 0, ',', '.') }}</span>
            </div>

            @if(!empty($midtransClientKey) && !empty($snapJsUrl) && $st === 'draft')
              <button id="btn-pay" class="btn btn-primary w-100 mt-3"
                      data-ref="{{ $ref }}">Bayar Sekarang</button>
              <small class="text-muted d-block mt-1">Pembayaran diproses oleh Midtrans.</small>
            @endif
          </div>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th>Produk</th>
              <th class="text-center" style="width:120px;">Qty</th>
              <th class="text-end" style="width:160px;">Harga Satuan</th>
              <th class="text-end" style="width:180px;">Subtotal</th>
            </tr>
          </thead>
          <tbody>
            @forelse($trx->details as $d)
              @php
                $name = optional($d->product)->name ?: 'Produk';
                $v    = optional($d->variant)->name ?: optional($d->variant)->color;
                $unit = (float) ($d->price ?? 0);
                $qty  = (int) ($d->qty ?? 0);
                $line = (float) ($d->total ?? ($unit * $qty));
              @endphp
              <tr>
                <td>{{ $name }} @if($v) <small class="text-muted">({{ $v }})</small> @endif</td>
                <td class="text-center">{{ $qty }}</td>
                <td class="text-end">Rp{{ number_format($unit, 0, ',', '.') }}</td>
                <td class="text-end">Rp{{ number_format($line, 0, ',', '.') }}</td>
              </tr>
            @empty
              <tr><td colspan="4" class="text-center text-muted">Tidak ada item.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
@endsection

@push('js')
  @if(!empty($midtransClientKey) && !empty($snapJsUrl) && $status === 'draft')
    <script src="{{ $snapJsUrl }}" data-client-key="{{ $midtransClientKey }}"></script>
    <script>
      (function(){
        const btn = document.getElementById('btn-pay');
        if (!btn) return;

        // Ambil ref dari query param (?ref=...) → fallback ke data-ref (server)
        const qs = new URLSearchParams(location.search);
        const refFromQs = qs.get('ref');
        const refFromDom = btn.getAttribute('data-ref');
        const ref = refFromQs || refFromDom;

        // Bangun URL endpoint Snap dengan placeholder diganti ref
        const snapUrlTmpl = @json(route('orders.snap', '__REF__'));
        const snapUrl = snapUrlTmpl.replace('__REF__', encodeURIComponent(ref));

        const finishUrl   = @json(route('midtrans.finish'));
        const unfinishUrl = @json(route('midtrans.unfinish'));
        const errorUrl    = @json(route('midtrans.error'));

        btn.addEventListener('click', function(){
          btn.disabled = true;

          fetch(snapUrl, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': @json(csrf_token()),
              'Accept': 'application/json'
            },
            body: JSON.stringify({})
          })
          .then(r => r.json())
          .then(res => {
            if (!res.snap_token) {
              alert(res.message || 'Gagal membuat token pembayaran.');
              btn.disabled = false;
              return;
            }

            // Redirect callback menambahkan ?order_id=... dari respons
            const orderId = res.order_id || '';
            const q = orderId ? ('?order_id=' + encodeURIComponent(orderId)) : '';

            window.snap.pay(res.snap_token, {
              onSuccess: function(){ window.location.href = finishUrl + q;   },
              onPending: function(){ window.location.href = unfinishUrl + q; },
              onError:   function(){ window.location.href = errorUrl + q;    },
              onClose:   function(){ btn.disabled = false; }
            });
          })
          .catch(() => { alert('Gagal menghubungkan ke server.'); btn.disabled = false; });
        });
      })();
    </script>
  @endif
@endpush
