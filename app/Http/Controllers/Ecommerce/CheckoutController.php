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
        $subtotal = $cart->items->sum(fn ($it) => $this->lineTotal($it));
        $shippingFee = 0;
        $grandTotal  = $subtotal + $shippingFee;

        return view('ecommerce.pages.auth.checkout', [
            'cart'        => $cart,
            'subtotal'    => $subtotal,
            'shippingFee' => $shippingFee,
            'grandTotal'  => $grandTotal,
        ]);
    }

    /** POST /checkout */
    public function store(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        $validated = $request->validate([
            // Billing
            'billing.first_name' => ['required','string','max:100'],
            'billing.last_name'  => ['required','string','max:100'],
            'billing.email'      => ['required','email','max:150'],
            'billing.phone'      => ['required','string','max:30'],
            'billing.company'    => ['nullable','string','max:150'],
            'billing.address1'   => ['required','string','max:255'],
            'billing.address2'   => ['nullable','string','max:255'],
            'billing.country'    => ['required','string','max:100'],
            'billing.city'       => ['required','string','max:100'],
            'billing.state'      => ['required','string','max:100'],
            'billing.postcode'   => ['required','string','max:20'],

            // Shipping
            'ship_to_different'  => ['nullable','boolean'],
            'shipping.first_name'=> ['required_if:ship_to_different,1','nullable','string','max:100'],
            'shipping.last_name' => ['required_if:ship_to_different,1','nullable','string','max:100'],
            'shipping.email'     => ['nullable','email','max:150'],
            'shipping.phone'     => ['nullable','string','max:30'],
            'shipping.company'   => ['nullable','string','max:150'],
            'shipping.address1'  => ['required_if:ship_to_different,1','nullable','string','max:255'],
            'shipping.address2'  => ['nullable','string','max:255'],
            'shipping.country'   => ['required_if:ship_to_different,1','nullable','string','max:100'],
            'shipping.city'      => ['required_if:ship_to_different,1','nullable','string','max:100'],
            'shipping.state'     => ['required_if:ship_to_different,1','nullable','string','max:100'],
            'shipping.postcode'  => ['required_if:ship_to_different,1','nullable','string','max:20'],

            // Payment
            'payment_method'     => ['required','in:check,bank,cash,paypal,payoneer'],
            'accept_terms'       => ['accepted'],
        ], [
            'accept_terms.accepted' => 'Anda harus menyetujui syarat & ketentuan.',
        ]);

        // Simpan & kembalikan model Transaction dari closure (bukan redirect di dalamnya)
        $trx = DB::transaction(function () use ($customer, $validated, $request) {
            $cart = $this->resolveCart($request, $customer, lock: true);

            if (!$cart || $cart->items->isEmpty()) {
                // lempar exception ringan agar rollback dan ditangani di luar
                throw new \RuntimeException('Keranjang Anda kosong.');
            }

            // Totals berbasis line_total DB atau hitung manual
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
                'status'                => Transaction::STATUS_DRAFT,
                'remarks'               => sprintf('payment:%s; shipping_fee:%s', $validated['payment_method'], $shippingFee),
            ]);

            // Detail per item
            foreach ($cart->items as $ci) {
                [$unitGross, $unitDiscount, $unitNet] = $this->netUnitPrice($ci);
                $qty = (int) ($ci->qty ?? 0);
                $lineTotal = $this->lineTotal($ci, $unitGross, $unitDiscount); // konsisten

                // Simpan 'price' sebagai HARGA NET PER UNIT (sesuai total)
                $trx->details()->create([
                    'product_id'         => $ci->product_id,
                    'product_variant_id' => $ci->product_variant_id,
                    'qty'                => $qty,
                    'price'              => $unitNet,     // unit NET = price - discount
                    'total'              => $lineTotal,   // qty × (price - discount)
                ]);
            }

            // Bersihkan cart
            $cart->items()->delete();

            return $trx;
        });

        // Sukses
        $redirectUrl = route('orders.show', $trx->id);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'message'        => 'Order created',
                'transaction_id' => $trx->id,
                'redirect'       => $redirectUrl,
            ]);
        }

        return redirect($redirectUrl)->with('success', 'Pesanan berhasil dibuat. Terima kasih!');
    }

    /** Harga varian → produk → 0 (GROSS / sebelum diskon) */
    protected function unitPrice($cartItem): float
    {
        $variantPrice = optional($cartItem->variant)->price
            ?? optional($cartItem->variant)->final_price;

        $productPrice = optional($cartItem->product)->price
            ?? optional($cartItem->product)->final_price
            ?? optional($cartItem->product)->regular_price;

        return (float) ($variantPrice ?? $productPrice ?? 0);
    }

    /**
     * Hitung NET unit price (price - discount) dan kembalikan tuple:
     * [gross, discount, net]
     * - Ambil dari kolom item jika tersedia: $item->price, $item->discount / discount_amount
     * - Fallback ke unitPrice() jika kolom price null.
     */
    protected function netUnitPrice($item): array
    {
        $gross = (float) ($item->price ?? $this->unitPrice($item));
        $disc  = (float) ($item->discount ?? $item->discount_amount ?? 0);
        $net   = max(0, $gross - $disc);
        return [$gross, $disc, $net];
    }

    /**
     * Dapatkan line_total: prioritas pakai kolom DB 'line_total' jika ada & valid,
     * jika tidak, hitung: qty × (price - discount).
     */
    protected function lineTotal($item, ?float $gross = null, ?float $disc = null): float
    {
        // Jika sudah ada kolom line_total di DB (mis. via trigger / sebelum checkout)
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

    /** Format alamat untuk header transaksi */
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

    // ====== resolveCart & mergeCarts (tanpa perubahan logika) ======
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
                    'price'              => $item->price ?? null,     // ikutkan jika kolom ada
                    'discount'           => $item->discount ?? null,  // ikutkan jika kolom ada
                    // 'line_total'       => (opsional) bisa diisi trigger DB
                ]);
            }
        }
    }
}
