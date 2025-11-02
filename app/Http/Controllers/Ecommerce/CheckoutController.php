<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Ecommerce\Cart;
use App\Models\Inventory\Transaction;

class CheckoutController extends Controller
{
    /** GET /checkout */
    public function index(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        $cart = $this->resolveCart($request, $customer);

        if (!$cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('warning', 'Keranjang Anda masih kosong.');
        }

        // SUBTOTAL = Σ line_total (prioritas: kolom DB jika ada, fallback hitung manual)
        $subtotal    = $cart->items->sum(fn ($it) => $this->lineTotal($it));
        $shippingFee = 0;
        $grandTotal  = $subtotal + $shippingFee;

        // TIDAK ADA variabel Midtrans yang diteruskan ke view
        return view('ecommerce.pages.auth.checkout', [
            'cart'        => $cart,
            'subtotal'    => $subtotal,
            'shippingFee' => $shippingFee,
            'grandTotal'  => $grandTotal,
        ]);
    }

    /** POST /checkout (tanpa Midtrans) */
    public function store(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        $validated = $request->validate([
            // Billing
            'billing.first_name' => ['required','string','max:100'],
            'billing.last_name'  => ['nullable','string','max:100'],
            'billing.email'      => ['required','email','max:150'],
            'billing.phone'      => ['required','string','max:30'],
            'billing.company'    => ['nullable','string','max:150'],
            'billing.address1'   => ['required','string','max:255'],
            'billing.address2'   => ['nullable','string','max:255'],
            'billing.city'       => ['required','string','max:100'],
            'billing.state'      => ['required','string','max:100'],
            'billing.postcode'   => ['required','string','max:20'],

            // Shipping
            'ship_to_different'   => ['nullable','boolean'],
            'shipping.first_name' => ['required_if:ship_to_different,1','nullable','string','max:100'],
            'shipping.last_name'  => ['required_if:ship_to_different,1','nullable','string','max:100'],
            'shipping.email'      => ['nullable','email','max:150'],
            'shipping.phone'      => ['nullable','string','max:30'],
            'shipping.company'    => ['nullable','string','max:150'],
            'shipping.address1'   => ['required_if:ship_to_different,1','nullable','string','max:255'],
            'shipping.address2'   => ['nullable','string','max:255'],
            'shipping.city'       => ['required_if:ship_to_different,1','nullable','string','max:100'],
            'shipping.state'      => ['required_if:ship_to_different,1','nullable','string','max:100'],
            'shipping.postcode'   => ['required_if:ship_to_different,1','nullable','string','max:20'],

            // Payment (tanpa 'midtrans')
            // Sesuaikan daftar ini dengan metode yang Anda gunakan di UI
            'payment_method'      => ['required','in:bank_transfer,cod,bank,cash,check'],
            'accept_terms'        => ['accepted'],
        ], [
            'accept_terms.accepted' => 'Anda harus menyetujui syarat & ketentuan.',
        ]);

        $paymentMethod = $validated['payment_method'];

        // Simpan transaksi (tanpa proses Midtrans)
        $trx = DB::transaction(function () use ($customer, $validated, $request, $paymentMethod) {
            $cart = $this->resolveCart($request, $customer, lock: true);

            if (!$cart || $cart->items->isEmpty()) {
                throw new \RuntimeException('Keranjang Anda kosong.');
            }

            // Hitung totals
            $subtotal    = $cart->items->sum(fn ($it) => $this->lineTotal($it));
            $shippingFee = 0;
            $grandTotal  = $subtotal + $shippingFee;

            $billing  = $validated['billing'];
            $shipping = $request->boolean('ship_to_different')
                ? ($validated['shipping'] ?? $validated['billing'])
                : $validated['billing'];

            $itemCount  = (int) $cart->items->sum('qty');
            $addressStr = $this->formatAddress($shipping);

            // Header transaksi
            $trx = Transaction::create([
                'type'                  => Transaction::TYPE_PENJUALAN,
                'transaction_date'      => now(),
                'customer_name'         => trim(($billing['first_name'] ?? '').' '.($billing['last_name'] ?? '')),
                'customer_phone'        => $billing['phone'] ?? null,
                'customer_full_address' => $addressStr,
                'item_count'            => $itemCount,
                'grand_total'           => $grandTotal,
                'status'                => Transaction::STATUS_DRAFT, // atau STATUS_PENDING_PAYMENT jika tersedia
                'remarks'               => sprintf('payment:%s; shipping_fee:%s', $paymentMethod, $shippingFee),
                'created_by'            => auth('customer')->id(),
            ]);

            // Detail per item
            foreach ($cart->items as $ci) {
                [$unitGross, $unitDiscount, $unitNet] = $this->netUnitPrice($ci);
                $qty       = (int) ($ci->qty ?? 0);
                $lineTotal = $this->lineTotal($ci, $unitGross, $unitDiscount);

                $trx->details()->create([
                    'product_id'         => $ci->product_id,
                    'product_variant_id' => $ci->product_variant_id,
                    'qty'                => $qty,
                    'price'              => $unitNet,    // harga net per unit
                    'total'              => $lineTotal,  // qty × net
                ]);
            }

            // Bersihkan cart
            $cart->items()->delete();

            return $trx;
        });

        // Alur selesai tanpa gateway: arahkan ke halaman/route yang Anda miliki.
        // Di sini, fallback ke cart.index dengan flash message sukses.
        // (Silakan ganti ke route('orders.show', $trx->id) atau 'checkout.thankyou' jika ada.)
        $successMsg = match ($paymentMethod) {
            'bank_transfer', 'bank', 'check' => "Pesanan #{$trx->id} berhasil dibuat. Silakan selesaikan pembayaran via transfer bank.",
            'cod'                            => "Pesanan #{$trx->id} berhasil dibuat. Pembayaran akan dilakukan di tempat (COD).",
            'cash'                           => "Pesanan #{$trx->id} berhasil dibuat. Silakan siapkan pembayaran tunai.",
            default                          => "Pesanan #{$trx->id} berhasil dibuat.",
        };

        return redirect()
            ->route('cart.index')
            ->with([
                'status'          => $successMsg,
                'checkout_tx_id'  => $trx->id,
                'payment_method'  => $paymentMethod,
            ]);
    }

    // ===================== Helper =====================

    protected function unitPrice($cartItem): float
    {
        $variantPrice = optional($cartItem->variant)->price
            ?? optional($cartItem->variant)->final_price;

        $productPrice = optional($cartItem->product)->price
            ?? optional($cartItem->product)->final_price
            ?? optional($cartItem->product)->regular_price;

        return (float) ($variantPrice ?? $productPrice ?? 0);
    }

    protected function netUnitPrice($item): array
    {
        $gross = (float) ($item->price ?? $this->unitPrice($item));
        $disc  = (float) ($item->discount ?? $item->discount_amount ?? 0);
        $net   = max(0, $gross - $disc);
        return [$gross, $disc, $net];
    }

    protected function lineTotal($item, ?float $gross = null, ?float $disc = null): float
    {
        if (isset($item->line_total) && is_numeric($item->line_total)) {
            return (float) $item->line_total;
        }

        $qty = (int) ($item->qty ?? 0);
        if ($gross === null || $disc === null) {
            [$gross, $disc, $net] = $this->netUnitPrice($item);
        } else {
            $net = max(0, $gross - $disc);
        }

        return (float) ($qty * $net);
    }

    protected function formatAddress(array $addr): string
    {
        $parts = [
            $addr['address1'] ?? null,
            $addr['address2'] ?? null,
            $addr['city']     ?? null,
            $addr['state']    ?? null,
            $addr['postcode'] ?? null,
            $addr['country']  ?? null,
        ];
        return implode(', ', array_filter($parts));
    }

    protected function resolveCart(Request $request, $customer, bool $lock = false): Cart
    {
        return DB::transaction(function () use ($request, $customer, $lock) {
            $sessionCartId = $request->session()->get('cart_id');

            $sessionCart = null;
            if ($sessionCartId) {
                $q = Cart::with(['items.product', 'items.variant'])->whereKey($sessionCartId);
                if ($lock) $q->lockForUpdate();
                $sessionCart = $q->first();
            }

            $uq = Cart::with(['items.product', 'items.variant'])
                ->where('customer_id', $customer->id)
                ->latest('id');
            if ($lock) $uq->lockForUpdate();
            $userCart = $uq->first();

            if ($sessionCart && !$userCart) {
                if (is_null($sessionCart->customer_id)) {
                    $sessionCart->customer_id = $customer->id;
                    $sessionCart->save();
                } elseif ((int)$sessionCart->customer_id !== (int)$customer->id) {
                    $sessionCart = null;
                }

                $cart = $sessionCart ?: Cart::create(['customer_id' => $customer->id]);
                $request->session()->put('cart_id', $cart->id);
                return $cart->load(['items.product', 'items.variant']);
            }

            if ($sessionCart && $userCart && $sessionCart->id !== $userCart->id) {
                $this->mergeCarts($userCart, $sessionCart);
                $sessionCart->items()->delete();
                $sessionCart->delete();

                $request->session()->put('cart_id', $userCart->id);
                return $userCart->load(['items.product', 'items.variant']);
            }

            if (!$userCart) {
                $userCart = Cart::create(['customer_id' => $customer->id]);
            }

            $request->session()->put('cart_id', $userCart->id);
            return $userCart->load(['items.product', 'items.variant']);
        });
    }

    protected function mergeCarts(Cart $target, Cart $source): void
    {
        foreach ($source->items as $item) {
            $existing = $target->items()
                ->where('product_id', $item->product_id)
                ->where('product_variant_id', $item->product_variant_id)
                ->first();

            if ($existing) {
                $existing->update([
                    'qty' => (int)$existing->qty + (int)$item->qty,
                ]);
            } else {
                $target->items()->create([
                    'product_id'         => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'qty'                => (int)$item->qty,
                    'price'              => $item->price ?? null,
                    'discount'           => $item->discount ?? null,
                ]);
            }
        }
    }
}
