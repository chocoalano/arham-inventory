<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Cart;
use App\Models\Ecommerce\CartItem;
use App\Models\Ecommerce\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CartController extends Controller
{
    /**
     * GET /cart
     */
    public function index(Request $request)
    {
        $cart = $this->resolveCart($request);
        $cart->load(['items.product', 'items.variant']);

        return view('ecommerce.pages.auth.cart', compact('cart'));
    }

    /**
     * PATCH /cart/{cart}
     * Body: { item_id?: int, variant_id?: int, qty: int }
     * Catatan: Blade kita kirimkan item_id, tapi tetap dukung variant_id.
     */
    public function update(Request $request, Cart $cart)
    {
        $this->assertOwner($cart);

        $validated = $request->validate([
            'item_id'    => ['nullable','integer'],
            'variant_id' => ['nullable','integer', Rule::exists('product_variants','id')],
            'qty'        => ['required','integer','min:1'],
        ]);

        return DB::transaction(function () use ($cart, $validated) {
            /** @var CartItem|null $item */
            $item = null;

            if (!empty($validated['item_id'])) {
                $item = $cart->items()->with(['product','variant'])
                    ->whereKey($validated['item_id'])
                    ->first();
            } elseif (!empty($validated['variant_id'])) {
                $item = $cart->items()->with(['product','variant'])
                    ->where('product_variant_id', $validated['variant_id'])
                    ->first();
            }

            if (!$item) {
                return response()->json([
                    'message' => 'Item tidak ditemukan di keranjang.'
                ], 404);
            }

            $item->quantity = (int) $validated['qty'];
            $item->save();

            $cart->load(['items.product','items.variant']);
            [$cartSubtotal, $itemsCount] = $this->computeCart($cart);

            $lineSubtotal = $this->priceOf($item) * (int)$item->quantity;

            return response()->json([
                'line_subtotal'     => (int) round($lineSubtotal),
                'cart_subtotal'     => (int) round($cartSubtotal),
                'cart_items_count'  => (int) $itemsCount,
                'item_id'           => $item->id,
            ]);
        });
    }

    /**
     * PATCH /cart/{cart}/sync
     * Body: { items: [ {item_id: int, qty: int}, ... ] }
     */
    public function sync(Request $request, Cart $cart)
    {
        $this->assertOwner($cart);

        $validated = $request->validate([
            'items'           => ['required','array','min:1'],
            'items.*.item_id' => ['required','integer'],
            'items.*.qty'     => ['required','integer','min:1'],
        ]);

        return DB::transaction(function () use ($cart, $validated) {
            $lineMap = [];

            foreach ($validated['items'] as $row) {
                /** @var CartItem|null $item */
                $item = $cart->items()->with(['product','variant'])
                    ->whereKey($row['item_id'])
                    ->first();

                if (!$item) {
                    // Skip silently; bisa juga return 404 kalau mau strict
                    continue;
                }

                $item->quantity = (int) $row['qty'];
                $item->save();

                $lineMap[$item->id] = (int) round($this->priceOf($item) * (int)$item->quantity);
            }

            $cart->load(['items.product','items.variant']);
            [$cartSubtotal, $itemsCount] = $this->computeCart($cart);

            return response()->json([
                'lines'            => $lineMap,           // { item_id: line_subtotal }
                'cart_subtotal'    => (int) round($cartSubtotal),
                'cart_items_count' => (int) $itemsCount,
            ]);
        });
    }

    /**
     * DELETE /cart/{cart}/items/{item}
     */
    public function destroyItem(Request $request, Cart $cart, CartItem $item)
    {
        $this->assertOwner($cart);

        if ((int)$item->cart_id !== (int)$cart->id) {
            abort(404);
        }

        return DB::transaction(function () use ($cart, $item) {
            $itemId = $item->id;
            $item->delete();

            $cart->load(['items.product','items.variant']);
            [$cartSubtotal, $itemsCount] = $this->computeCart($cart);

            return response()->json([
                'removed_item_id'   => $itemId,
                'cart_subtotal'     => (int) round($cartSubtotal),
                'cart_items_count'  => (int) $itemsCount,
            ]);
        });
    }

    /**
     * DELETE /cart/{cart} — kosongkan keranjang
     */
    public function destroy(Cart $cart)
    {
        $this->assertOwner($cart);

        $cart->items()->delete();
        $cart->delete();

        return redirect()->route('cart.index')->with('success', 'Keranjang berhasil dikosongkan.');
    }

    public function store(Request $request)
{
    $customer = Auth::guard('customer')->user();

    $data = $request->validate([
        'product_id'         => ['required', 'integer', 'exists:products,id'],
        'product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
        'qty'                => ['nullable', 'integer', 'min:1'],
    ]);

    $qty = (int)($data['qty'] ?? 1);
    $productId = (int)$data['product_id'];
    $variantId = $data['product_variant_id'] ?? null;

    // Ambil atau buat cart untuk customer
    $cart = \App\Models\Ecommerce\Cart::firstOrCreate([
        'customer_id' => $customer->id,
    ]);

    // Cari item yang sama (product + variant)
    $itemQuery = $cart->items()
        ->where('product_id', $productId)
        ->where('product_variant_id', $variantId);

    $item = $itemQuery->first();

    // Tentukan harga (gross) saat add-to-cart jika kolom price belum diset di cart_items
    $variantPrice = optional(\App\Models\ProductVariant::find($variantId))->price;
    $productPrice = optional(\App\Models\Product::find($productId))->price;
    $grossPrice   = (float) ($variantPrice ?? $productPrice ?? 0);

    if ($item) {
        $item->increment('qty', $qty);
        // opsional: jika price kosong di item, set sekarang
        if (is_null($item->price)) {
            $item->price = $grossPrice;
            $item->save();
        }
    } else {
        $item = $cart->items()->create([
            'product_id'         => $productId,
            'product_variant_id' => $variantId,
            'qty'                => $qty,
            'price'              => $grossPrice,       // simpan gross sekarang
            // 'discount'         => 0,                // isi jika ada
            // 'line_total'       => ... (boleh dihitung di DB trigger)
        ]);
    }

    // Hitung subtotal cart: Σ qty × (price - discount)
    $cart->load('items.product', 'items.variant');
    $subtotal = $cart->items->sum(function($ci){
        $gross = (float)($ci->price
                ?? optional($ci->variant)->price
                ?? optional($ci->product)->price
                ?? 0);
        $disc  = (float)($ci->discount ?? $ci->discount_amount ?? 0);
        $net   = max(0, $gross - $disc);
        $qty   = (int)($ci->qty ?? 0);
        return $qty * $net;
    });

    $itemsCount = (int) $cart->items->sum('qty');

    if ($request->wantsJson() || $request->ajax()) {
        return response()->json([
            'message'            => 'Item ditambahkan ke keranjang.',
            'cart_items_count'   => $itemsCount,
            'cart_subtotal'      => $subtotal,
            'added_item_id'      => $item->id,
        ]);
    }

    return back()->with('success', 'Item ditambahkan ke keranjang.');
    }


    /* ====================== Helpers ====================== */

    protected function resolveCart(Request $request): Cart
    {
        $guard = Auth::guard('customer');
        if ($guard->check()) {
            return Cart::firstOrCreate(['customer_id' => (int)$guard->id()]);
        }

        // fallback by session
        $sessionId = $request->session()->get('cart_session_id');
        if (!$sessionId) {
            $sessionId = (string) \Str::uuid();
            $request->session()->put('cart_session_id', $sessionId);
        }

        return Cart::firstOrCreate(['session_id' => $sessionId]);
    }

    protected function assertOwner(Cart $cart): void
    {
        $authId = Auth::guard('customer')->id();
        if ($authId && (int)$cart->customer_id === (int)$authId) {
            return;
        }
        // Jika guest by session, opsional: cocokkan session_id juga
        // Untuk singkatnya, izinkan saja kalau bukan milik customer lain
        if ($cart->customer_id) {
            abort(403);
        }
    }

    /** Hitung subtotal & total item */
    protected function computeCart(Cart $cart): array
    {
        $subtotal = 0;
        $count    = 0;

        foreach ($cart->items as $it) {
            $price = $this->priceOf($it);
            $subtotal += $price * (int)$it->quantity;
            $count    += (int)$it->quantity;
        }

        return [$subtotal, $count];
    }

    /** Ambil harga item, prioritas variant => product */
    protected function priceOf(CartItem $item): float|int
    {
        if ($item->relationLoaded('variant') && $item->variant) {
            return (float) ($item->variant->price ?? 0);
        }
        if ($item->relationLoaded('product') && $item->product) {
            return (float) ($item->product->price ?? 0);
        }
        // Fallback kalau field price disimpan di item
        return (float) ($item->price ?? 0);
    }
}
