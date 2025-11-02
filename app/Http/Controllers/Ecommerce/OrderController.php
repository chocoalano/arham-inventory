<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Inventory\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * GET /orders/{idOrRef}
     */
    public function show($idOrRef)
    {
        $customer = Auth::guard('customer')->user();

        $trx = $this->findCustomerTransaction($customer->id, $idOrRef);
        $trx->load(['details' => fn ($q) => $q->orderBy('id'), 'details.product', 'details.variant']);
        $subtotal = (float) ($trx->details->sum('total'));
        $shippingFee = (float) ($trx->shipping_fee ?? 0);
        $grandTotal = (float) ($trx->grand_total ?? ($subtotal + $shippingFee));
        $status = (string) ($trx->status ?? 'draft');

        $midtransClientKey = null;
        $snapJsUrl = null;
        if ($status === $this->statusDraft()) {
            $isProduction = (bool) (config('midtrans.is_production') ?? config('services.midtrans.is_production', false));
            $midtransClientKey = (string) (config('midtrans.client_key') ?? config('services.midtrans.client_key') ?? env('MIDTRANS_CLIENT_KEY', ''));
            $snapJsUrl = $isProduction
                                ? 'https://app.midtrans.com/snap/snap.js'
                                : 'https://app.sandbox.midtrans.com/snap/snap.js';
        }

        return view('ecommerce.pages.auth.orders.show', compact(
            'trx',
            'subtotal',
            'shippingFee',
            'grandTotal',
            'status',
            'midtransClientKey',
            'snapJsUrl'
        ));
    }

    /**
     * POST /orders/{idOrRef}/snap
     * Generate Snap Token (order_id selalu unik).
     */
    public function snap(Request $request, $idOrRef)
    {
        $customer = Auth::guard('customer')->user();

        $trx = $this->findCustomerTransaction($customer->id, $idOrRef);
        if ($trx->status !== $this->statusDraft()) {
            return response()->json(['message' => 'Pesanan tidak dalam status draft.'], 422);
        }

        $trx->load(['details.product', 'details.variant']);

        $subtotal = (float) $trx->details->sum('total');
        $shippingFee = (float) ($trx->shipping_fee ?? 0);
        $grandTotal = (float) ($trx->grand_total ?? ($subtotal + $shippingFee));
        if ($grandTotal <= 0) {
            return response()->json(['message' => 'Total transaksi tidak valid.'], 422);
        }

        $this->initMidtransConfig();

        // Bangun order_id unik & <= 50 char
        $orderId = $this->buildSnapOrderId($trx);

        // item_details
        $items = $trx->details->map(function ($d) {
            $name = optional($d->product)->name ?: 'Produk';
            $v = optional($d->variant)->name ?: optional($d->variant)->color;
            $nm = $v ? ($name.' ('.$v.')') : $name;

            return [
                'id' => (string) ($d->product_variant_id ?: $d->product_id ?: $d->id),
                'price' => (int) round((float) $d->price),  // net per unit (IDR)
                'quantity' => (int) $d->qty,
                'name' => mb_strimwidth($nm, 0, 50, '…'),
            ];
        })->values()->all();

        if ($shippingFee > 0) {
            $items[] = [
                'id' => 'SHIPPING',
                'price' => (int) round($shippingFee),
                'quantity' => 1,
                'name' => 'Shipping Fee',
            ];
        }

        // Pastikan gross == sum(item_details)
        $gross = (int) round($grandTotal);
        $itemsSum = 0;
        foreach ($items as $it) {
            $itemsSum += ((int) $it['price']) * ((int) $it['quantity']);
        }
        if ($itemsSum !== $gross) {
            $diff = $gross - $itemsSum;
            if ($diff !== 0) {
                $items[] = [
                    'id' => 'ADJ',
                    'price' => (int) $diff,
                    'quantity' => 1,
                    'name' => 'Adjustment',
                ];
            }
        }

        // Customer detail
        [$firstName, $lastName] = $this->splitName((string) ($trx->customer_name ?: 'Customer'));
        $customerDetails = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => optional($customer)->email,
            'phone' => $trx->customer_phone,
            'billing_address' => $this->midtransAddressFromTransaction($trx),
            'shipping_address' => $this->midtransAddressFromTransaction($trx),
        ];

        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $gross,
            ],
            'item_details' => $items,
            'customer_details' => $customerDetails,
            'callbacks' => [
                'finish' => route('midtrans.finish'),
                'unfinish' => route('midtrans.unfinish'),
                'error' => route('midtrans.error'),
            ],
        ];

        try {
            $snap = \Midtrans\Snap::createTransaction($params);
        } catch (\Exception $e) {
            // Jika order_id sudah dipakai → regenerasi sekali (tambahkan suffix acak) lalu retry
            if (stripos($e->getMessage(), 'order_id') !== false) {
                $orderId = $this->buildSnapOrderId($trx, true); // pakai random suffix
                $params['transaction_details']['order_id'] = $orderId;
                $snap = \Midtrans\Snap::createTransaction($params);
            } else {
                throw $e;
            }
        }

        return response()->json([
            'order_id' => $orderId,
            'snap_token' => $snap->token ?? null,
            'redirect_url' => $snap->redirect_url ?? null,
        ]);
    }

    /**
     * POST /midtrans/notification
     * Webhook resmi Midtrans → update status transaksi.
     */
    public function notification(Request $request)
    {
        $this->initMidtransConfig();

        try {
            $notif = new \Midtrans\Notification; // otomatis parsir JSON body
        } catch (\Exception $e) {
            Log::warning('[Midtrans][notification] parse error: '.$e->getMessage());

            return response()->json(['message' => 'Invalid notification'], 400);
        }

        $orderId = (string) ($notif->order_id ?? '');
        $trxStatus = (string) ($notif->transaction_status ?? '');
        $fraud = (string) ($notif->fraud_status ?? '');

        $trx = $this->findByMidtransOrderId($orderId);
        if (! $trx) {
            Log::warning("[Midtrans][notification] Order not found for {$orderId}");

            return response()->json(['message' => 'Order not found'], 404);
        }

        $newStatus = $this->mapMidtransToLocalStatus($trxStatus, $fraud);

        // Update hanya bila berubah
        if ($newStatus && $newStatus !== (string) $trx->status) {
            $trx->update(['status' => $newStatus]);
            Log::info("[Midtrans][notification] trx#{$trx->id} status => {$newStatus} (from {$trxStatus}/{$fraud})");
        }

        return response()->json(['message' => 'OK']);
    }

    /**
     * GET /midtrans/finish
     * Dipanggil setelah user sukses bayar (via Snap callbacks). Kita verifikasi status ke Midtrans,
     * lalu update transaksi agar real-time (tetap, source of truth sebaiknya dari notification()).
     */
    public function finish(Request $request)
    {
        // Midtrans mengirim ?order_id=ORD-{trxId}-{timestamp} (dari buildSnapOrderId)
        $orderId = (string) $request->query('order_id', $request->input('order_id', ''));

        if (! $orderId) {
            return redirect()
                ->route('orders.index')
                ->with('warning', 'Parameter order_id tidak ditemukan.');
        }

        // Ambil trx_id dari pola ORD-{id}-...
        $trxId = null;
        if (preg_match('/^ORD-(\d+)-/i', $orderId, $m)) {
            $trxId = (int) $m[1];
        }

        if (! $trxId) {
            return redirect()
                ->route('orders.index')
                ->with('warning', 'Format order_id tidak valid.');
        }

        $trx = Transaction::with(['invoice.payments'])->find($trxId);
        if (! $trx) {
            return redirect()
                ->route('orders.index')
                ->with('warning', 'Transaksi tidak ditemukan.');
        }

        // Idempotency: jika sudah posted & invoice sudah lunas, langsung arahkan ke detail.
        $postedConst = defined(Transaction::class.'::STATUS_POSTED') ? Transaction::STATUS_POSTED : 'posted';
        $draftConst = defined(Transaction::class.'::STATUS_DRAFT') ? Transaction::STATUS_DRAFT : 'draft';

        DB::transaction(function () use ($trx, $orderId, $postedConst, $draftConst) {
            // 1) Update status transaksi → posted (jika masih draft)
            if (($trx->status ?? $draftConst) !== $postedConst) {
                $trx->status = $postedConst;
                // kalau model kamu punya kolom posted_at, set juga:
                if ($trx->isFillable('posted_at') || \Schema::hasColumn($trx->getTable(), 'posted_at')) {
                    $trx->posted_at = now();
                }
                $trx->save();
            }

            // 2) Pastikan ada invoice (hasOne). Buat jika belum ada.
            $invoice = $trx->invoice; // lewat eager load; bisa null
            if (! $invoice) {
                $invoice = $trx->invoice()->create([
                    // invoice_number akan diisi otomatis oleh boot() di model Invoice (auto-number)
                    'issued_at' => now(),
                    'due_at' => now()->copy()->addDays(7),
                    'subtotal' => (float) ($trx->grand_total ?? 0), // jika perlu, bisa hitung ulang dari details
                    'discount_total' => 0,
                    'tax_total' => 0,
                    'shipping_fee' => (float) ($trx->shipping_fee ?? 0),
                    'total_amount' => (float) ($trx->grand_total ?? 0),
                    'paid_amount' => 0,           // akan di-update setelah payment dibuat
                    'is_paid' => false,       // akan di-update setelah payment dibuat
                ]);
            }

            // 3) Catat pembayaran (hasMany payments) jika belum ada record pembayaran utk order ini
            $alreadyHasThisRef = $invoice->payments
                ->where('reference_no', $orderId)
                ->first();

            if (! $alreadyHasThisRef) {
                $payment = $invoice->payments()->create([
                    'amount' => (float) ($trx->grand_total ?? 0),
                    'method' => 'Midtrans',
                    'reference_no' => $orderId, // gunakan order_id dari Midtrans agar unik
                    'paid_at' => now(),
                    'notes' => 'Pembayaran via Midtrans (finish)',
                    // receiver_id / received_by: jika skema butuh user internal, isi null atau created_by
                    'received_by' => $trx->created_by ?? null,
                ]);

                // Update status invoice jadi lunas
                $invoice->paid_amount = (float) $invoice->paid_amount + (float) $payment->amount;
                $invoice->is_paid = $invoice->paid_amount >= (float) $invoice->total_amount;
                $invoice->save();
            }
        });

        // Redirect ke detail pesanan
        $ref = $trx->reference_number ?? $trx->id;

        return redirect()
            ->route('orders.show', $ref)
            ->with('success', 'Pembayaran berhasil. Transaksi sudah diposting dan invoice tercatat.');
    }

    /**
     * GET /midtrans/unfinish – user belum menyelesaikan pembayaran.
     */
    public function unfinish(Request $request)
    {
        $orderId = (string) $request->query('order_id', '');
        if ($trx = $this->findByMidtransOrderId($orderId)) {
            // Biarkan tetap 'draft'
            return redirect()->route('orders.show', $trx->reference_number ?? $trx->id)
                ->with('warning', 'Pembayaran belum selesai.');
        }

        return redirect()->route('auth.profile'.'#orders')->with('warning', 'Pembayaran belum selesai.');
    }

    /**
     * GET /midtrans/error – terjadi error saat pembayaran.
     */
    public function error(Request $request)
    {
        $orderId = (string) $request->query('order_id', '');
        if ($trx = $this->findByMidtransOrderId($orderId)) {
            // Amankan dengan set cancelled? Lebih baik tunggu notifikasi Midtrans,
            // di sini cukup arahkan ke detail.
            return redirect()->route('orders.show', $trx->reference_number ?? $trx->id)
                ->with('error', 'Terjadi kesalahan pada pembayaran.');
        }

        return redirect()->route('auth.profile'.'#orders')->with('error', 'Terjadi kesalahan pada pembayaran.');
    }

    /* ========================= Helpers ========================= */

    protected function findCustomerTransaction(int $customerId, $idOrRef): Transaction
    {
        $q = Transaction::query()->where('created_by', $customerId);
        if (is_numeric($idOrRef)) {
            $trx = (clone $q)->whereKey((int) $idOrRef)->first();
            if ($trx) {
                return $trx;
            }
        }

        return $q->where(function ($qq) use ($idOrRef) {
            $qq->where('reference_number', $idOrRef);
        })
            ->latest('id')
            ->firstOrFail();
    }

    /**
     * Temukan transaksi dari order_id Midtrans.
     * Format order_id kita: "ORD-{trxId}-{timestamp}".
     */
    protected function findByMidtransOrderId(string $orderId): ?Transaction
    {
        if (preg_match('/^ORD-(\d+)-/i', $orderId, $m)) {
            $trxId = (int) $m[1];

            return Transaction::find($trxId);
        }

        // fallback (jarang perlu): cari by reference_number persis
        return Transaction::where('reference_number', $orderId)->first();
    }

    protected function statusDraft(): string
    {
        if (defined(Transaction::class.'::STATUS_DRAFT')) {
            return Transaction::STATUS_DRAFT;
        }

        return 'draft';
    }

    protected function statusPosted(): string
    {
        if (defined(Transaction::class.'::STATUS_POSTED')) {
            return Transaction::STATUS_POSTED;
        }

        return 'posted';
    }

    protected function statusCancelled(): string
    {
        if (defined(Transaction::class.'::STATUS_CANCELLED')) {
            return Transaction::STATUS_CANCELLED;
        }

        return 'cancelled';
    }

    /**
     * Mapping status dari Midtrans ke status lokal.
     * - sukses (capture+accept, settlement)  => posted
     * - pending                              => draft
     * - deny/cancel/expire/failure           => cancelled
     */
    protected function mapMidtransToLocalStatus(string $transactionStatus, string $fraudStatus = ''): ?string
    {
        $ts = strtolower($transactionStatus);
        $fs = strtolower($fraudStatus);

        if ($ts === 'capture') {
            if ($fs === 'accept') {
                return $this->statusPosted();
            }
            if (in_array($fs, ['challenge', 'deny'])) {
                return $this->statusCancelled();
            }

            return $this->statusCancelled();
        }
        if ($ts === 'settlement') {
            return $this->statusPosted();
        }
        if ($ts === 'pending') {
            return $this->statusDraft();
        }
        if (in_array($ts, ['deny', 'cancel', 'expire', 'failure', 'refund', 'chargeback', 'partial_refund'])) {
            return $this->statusCancelled();
        }

        return null; // unknown → jangan ubah
    }

    protected function splitName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName));
        $first = $parts[0] ?? 'Customer';
        $last = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '';

        return [$first, $last];
    }

    protected function midtransAddressFromTransaction(Transaction $trx): array
    {
        return [
            'first_name' => $this->splitName((string) $trx->customer_name)[0],
            'last_name' => $this->splitName((string) $trx->customer_name)[1],
            'email' => null,
            'phone' => $trx->customer_phone,
            'address' => (string) $trx->customer_full_address,
            'city' => null,
            'postal_code' => null,
            'country_code' => 'IDN',
        ];
    }

    protected function initMidtransConfig(): void
    {
        \Midtrans\Config::$serverKey = config('midtrans.server_key', env('MIDTRANS_SERVER_KEY'));
        \Midtrans\Config::$isProduction = (bool) (config('midtrans.is_production', env('MIDTRANS_IS_PRODUCTION', false)));
        \Midtrans\Config::$isSanitized = true;
        \Midtrans\Config::$is3ds = true;
    }

    /**
     * Bangun order_id unik & <= 50 char.
     * Default: "ORD-{trxId}-{YYYYMMDD}T{HHMMSS}"
     * Jika $withRandom = true → tambah "-{RND4}" untuk menghindari bentrok.
     */
    protected function buildSnapOrderId(Transaction $trx, bool $withRandom = false): string
    {
        $prefix = 'ORD-'.(string) $trx->id.'-';
        $base = $prefix.now()->format('Ymd\THis'); // contoh: ORD-123-20251102T153045
        if ($withRandom) {
            $rnd = strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
            $base .= '-'.$rnd;
        }

        return substr($base, 0, 50);
    }
}
