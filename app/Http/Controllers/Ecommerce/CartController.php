<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Cart;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    public function index()
    {
        $customer = Auth::guard('customer')->user();
        $cart = $customer?->cart()
            ->with(['items.product', 'items.variant'])
            ->first();

        $cartItems = $cart?->items ?? collect();
        $subtotal = $cartItems->sum(function ($ci) {
            $qty = (int) ($ci->quantity ?? $ci->qty ?? 0);

            $gross = (float) (
                $ci->price
                ?? optional($ci->variant)->price
                ?? optional($ci->product)->price
                ?? 0
            );
            $discount = (float) (
                $ci->discount_amount
                ?? $ci->discount
                ?? 0
            );
            $netUnit = max(0, $gross - $discount);
            return $qty * $netUnit;
        });
        $shippingFee = 0;
        $grandTotal = $subtotal + $shippingFee;
        $itemsCount = (int) $cartItems->sum(function ($ci) {
            return (int) ($ci->quantity ?? $ci->qty ?? 0);
        });

        return view('ecommerce.pages.auth.cart', compact(
            'cart',
            'cartItems',
            'subtotal',
            'shippingFee',
            'grandTotal',
            'itemsCount'
        ));
    }

    /**
     * Add item to cart (AJAX)
     */
    public function store(Request $request)
    {
        // Check authentication
        if (! Auth::guard('customer')->check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Silakan login terlebih dahulu',
                ], 401);
            }

            return redirect()->route('login.register')
                ->with('warning', 'Silakan login terlebih dahulu');
        }

        // Validation
        $validator = Validator::make($request->all(), [
            'product_id' => 'nullable|exists:products,id',
            'sku' => 'nullable|string|exists:product_variants,sku_variant',
            'variant_id' => 'required|exists:product_variants,id',
            'qty' => 'required|integer|min:1|max:999',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak valid',
                    'errors' => $validator->errors(),
                ], 422);
            }

            return back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            $customer = Auth::guard('customer')->user();

            // Find product
            $input = $request->all();
            $product = null;
            $variant = null;

            if ($request->filled('product_id')) {
                $product = Product::find($input['product_id']);
            } elseif ($request->filled('sku')) {
                // 'sku' refers to product_variants.sku_variant per validation
                $variant = ProductVariant::with('product')->where('sku_variant', $input['sku'])->first();
                $product = $variant?->product;
            } else {
                // use the variant_id to find the variant and its product safely
                $variant = ProductVariant::with('product')->find($input['variant_id']);
                $product = $variant?->product;
            }

            if (! $product) {
                throw new \Exception('Produk tidak ditemukan');
            }

            // Find variant
            $variant = ProductVariant::with('stocks')
                ->where('id', $request->variant_id)
                ->where('product_id', $product->id)
                ->first();

            if (! $variant) {
                throw new \Exception('Varian tidak ditemukan');
            }

            // Check stock
            $totalStock = $variant->stocks->sum('qty');
            if ($totalStock < $request->qty) {
                throw new \Exception("Stok tidak mencukupi. Tersedia: {$totalStock} pcs");
            }

            // Get or create cart
            $cart = $customer->getOrCreateCart();

            // Add or update item
            $existingItem = $cart->items()
                ->where('product_id', $product->id)
                ->where('product_variant_id', $variant->id)
                ->first();

            if ($existingItem) {
                $newQty = $existingItem->quantity + $request->qty;

                // Check if new quantity exceeds stock
                if ($newQty > $totalStock) {
                    throw new \Exception("Stok tidak mencukupi. Tersedia: {$totalStock} pcs");
                }

                $existingItem->update(['quantity' => $newQty]);
            } else {
                $cart->items()->create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'quantity' => $request->qty,
                ]);
            }

            DB::commit();

            // Get updated cart count
            $cartCount = $cart->items->sum('quantity');

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Produk berhasil ditambahkan ke keranjang',
                    'cart_count' => $cartCount,
                    'redirect' => false,
                ]);
            }

            return redirect()->route('cart.index')
                ->with('success', 'Produk berhasil ditambahkan ke keranjang');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[CartController] Add to cart error: '.$e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 400);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Update cart item quantity
     */
    public function update(Request $request, Cart $cart)
    {
        // 1) Auth & kepemilikan cart
        $customer = Auth::guard('customer')->user();
        if (! $customer) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => 'Unauthorized'], 401)
                : redirect()->route('login.register');
        }
        if ((int) $cart->customer_id !== (int) $customer->id) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => 'Forbidden'], 403)
                : abort(403);
        }

        // 2) Validasi payload (JS kamu kirim { item_id, qty } atau { item_id, quantity })
        $v = Validator::make($request->all(), [
            'item_id' => ['required', 'integer'],
            'qty' => ['nullable', 'integer', 'min:1', 'max:999'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:999'],
        ]);
        $v->after(function ($va) use ($request) {
            if (! $request->filled('qty') && ! $request->filled('quantity')) {
                $va->errors()->add('qty', 'Field qty/quantity wajib diisi.');
            }
        });
        if ($v->fails()) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'errors' => $v->errors()], 422)
                : back()->withErrors($v);
        }

        $itemId = (int) $request->integer('item_id');
        $newQty = (int) ($request->integer('qty') ?: $request->integer('quantity'));

        try {
            $payload = DB::transaction(function () use ($cart, $itemId, $newQty) {
                // 3) Ambil item pada cart ini
                $item = $cart->items()
                    ->with(['product', 'variant', 'variant.stocks'])
                    ->whereKey($itemId)
                    ->lockForUpdate()
                    ->first();

                if (! $item) {
                    throw new \Exception('Item tidak ditemukan');
                }

                // 4) Cek stok (fleksibel; kalau tidak ada data stok, cek dilewati)
                $totalStock = null;
                if ($item->variant) {
                    try {
                        $totalStock = (int) $item->variant->stocks()->sum('qty');
                    } catch (\Throwable $e) {
                    }
                    foreach ([
                        $item->variant->total_stocks ?? null,
                        $item->variant->stock_qty ?? null,
                        $item->variant->stock_quantity ?? null,
                        $item->variant->qty ?? null,
                    ] as $c) {
                        if (is_numeric($c)) {
                            $totalStock = (int) $c;
                            break;
                        }
                    }
                } elseif ($item->product) {
                    foreach ([
                        $item->product->stock_qty ?? null,
                        $item->product->stock_quantity ?? null,
                        $item->product->inventory ?? null,
                        $item->product->qty ?? null,
                    ] as $c) {
                        if (is_numeric($c)) {
                            $totalStock = (int) $c;
                            break;
                        }
                    }
                }
                if ($totalStock !== null && $newQty > $totalStock) {
                    throw new \Exception("Stok tidak mencukupi. Tersedia: {$totalStock} pcs");
                }

                // 5) Update quantity saja (tidak ada line_total di DB)
                $item->update(['quantity' => $newQty]);

                // 6) Hitung subtotal baris (on-the-fly) = qty × (price - discount)
                $gross = (float) ($item->price
                            ?? optional($item->variant)->price
                            ?? optional($item->product)->price
                            ?? 0);
                $disc = (float) ($item->discount_amount ?? $item->discount ?? 0);
                $net = max(0, $gross - $disc);
                $lineSubtotal = $net * $newQty;

                // 7) Hitung subtotal cart (on-the-fly juga)
                $cart->load(['items.product', 'items.variant']);
                $cartSubtotal = (float) $cart->items->sum(function ($ci) {
                    $g = (float) ($ci->price
                            ?? optional($ci->variant)->price
                            ?? optional($ci->product)->price
                            ?? 0);
                    $d = (float) ($ci->discount_amount ?? $ci->discount ?? 0);
                    $n = max(0, $g - $d);
                    $q = (int) ($ci->quantity ?? 0);

                    return $n * $q;
                });
                $itemsCount = (int) $cart->items->sum('quantity');

                return [
                    'item_id' => $item->id,
                    'line_subtotal' => $lineSubtotal,   // <- TIDAK disimpan di DB
                    'cart_subtotal' => $cartSubtotal,   // <- TIDAK disimpan di DB
                    'cart_items_count' => $itemsCount,
                ];
            });

            // 8) Balasan JSON untuk AJAX jQuery di Blade
            if ($request->expectsJson()) {
                return response()->json(array_merge([
                    'success' => true,
                    'message' => 'Kuantitas berhasil diupdate',
                ], $payload));
            }

            return back()->with('success', 'Kuantitas berhasil diupdate');

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                $code = str_contains(strtolower($e->getMessage()), 'stok') ? 422 : 400;

                return response()->json(['success' => false, 'message' => $e->getMessage()], $code);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Remove item from cart
     */
    public function destroy(Request $request, $itemId)
    {
        if (! Auth::guard('customer')->check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            return redirect()->route('login.register');
        }

        try {
            $customer = Auth::guard('customer')->user();
            $cart = $customer->cart;

            if (! $cart) {
                throw new \Exception('Keranjang tidak ditemukan');
            }

            $item = $cart->items()->find($itemId);

            if (! $item) {
                throw new \Exception('Item tidak ditemukan');
            }

            $item->delete();

            $cartCount = $cart->fresh()->items->sum('quantity');

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Item berhasil dihapus',
                    'cart_count' => $cartCount,
                ]);
            }

            return back()->with('success', 'Item berhasil dihapus');

        } catch (\Exception $e) {
            Log::error('[CartController] Remove error: '.$e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 400);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Clear all cart items
     */
    public function clear(Request $request)
    {
        if (! Auth::guard('customer')->check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            return redirect()->route('login.register');
        }

        try {
            $customer = Auth::guard('customer')->user();
            $cart = $customer->cart;

            if ($cart) {
                $cart->clearItems();
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Keranjang berhasil dikosongkan',
                    'cart_count' => 0,
                ]);
            }

            return back()->with('success', 'Keranjang berhasil dikosongkan');

        } catch (\Exception $e) {
            Log::error('[CartController] Clear error: '.$e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengosongkan keranjang',
                ], 400);
            }

            return back()->with('error', 'Gagal mengosongkan keranjang');
        }
    }

    /**
     * Bulk update cart item quantities (Sync)
     */
    public function sync(Request $request)
    {
        if (! Auth::guard('customer')->check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            return redirect()->route('login.register');
        }

        // --- 1. Validation ---
        $validator = Validator::make($request->all(), [
            'items' => ['required', 'array'],
            'items.*.item_id' => ['required', 'integer', 'exists:cart_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ], [
            'items.required' => 'Daftar item wajib diisi.',
            'items.array' => 'Daftar item harus berupa array.',
            'items.*.item_id.required' => 'ID item wajib diisi.',
            'items.*.item_id.exists' => 'Item ID :input tidak ditemukan.',
            'items.*.quantity.required' => 'Kuantitas wajib diisi.',
            'items.*.quantity.min' => 'Kuantitas minimal 1.',
            'items.*.quantity.max' => 'Kuantitas maksimal 999.',
        ]);

        if ($validator->fails()) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'errors' => $validator->errors()], 422)
                : back()->withErrors($validator);
        }

        $submittedItems = $request->input('items');
        $customer = Auth::guard('customer')->user();

        try {
            $responsePayload = DB::transaction(function () use ($customer, $submittedItems) {
                $cart = $customer->cart;

                if (! $cart) {
                    throw new \Exception('Keranjang tidak ditemukan.');
                }

                $updatedLines = [];
                $cartItemIds = collect($submittedItems)->pluck('item_id');

                // Load items to update, ensuring they belong to this cart and locking them
                $itemsToUpdate = $cart->items()
                    ->with(['product', 'variant', 'variant.stocks'])
                    ->whereIn('id', $cartItemIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                if ($itemsToUpdate->count() !== count($submittedItems)) {
                    // This handles cases where one item ID exists but belongs to another cart,
                    // or if the item was deleted between FE submission and now.
                    throw new \Exception('Satu atau lebih item keranjang tidak valid atau tidak ditemukan dalam keranjang Anda.');
                }

                // --- 2. Process and Update Each Item ---
                foreach ($submittedItems as $submittedItem) {
                    $itemId = $submittedItem['item_id'];
                    $newQty = (int) $submittedItem['quantity'];

                    $item = $itemsToUpdate->get($itemId);

                    // --- Stock Check Logic (Reused from update method) ---
                    $totalStock = null;
                    if ($item->variant) {
                        try {
                            $totalStock = (int) $item->variant->stocks()->sum('qty');
                        } catch (\Throwable $e) {
                            // ignore if stock relation/method fails
                        }
                        // Fallback check for common stock fields on variant
                        foreach ([
                            $item->variant->total_stocks ?? null,
                            $item->variant->stock_qty ?? null,
                            $item->variant->stock_quantity ?? null,
                            $item->variant->qty ?? null,
                        ] as $c) {
                            if (is_numeric($c)) {
                                $totalStock = (int) $c;
                                break;
                            }
                        }
                    } elseif ($item->product) {
                        // Fallback check for common stock fields on product
                        foreach ([
                            $item->product->stock_qty ?? null,
                            $item->product->stock_quantity ?? null,
                            $item->product->inventory ?? null,
                            $item->product->qty ?? null,
                        ] as $c) {
                            if (is_numeric($c)) {
                                $totalStock = (int) $c;
                                break;
                            }
                        }
                    }

                    if ($totalStock !== null && $newQty > $totalStock) {
                        $productName = optional($item->product)->name ?? 'Produk';
                        throw new \Exception("Stok item '{$productName}' tidak mencukupi ({$newQty} pcs). Tersedia: {$totalStock} pcs");
                    }

                    // --- Update Quantity ---
                    $item->quantity = $newQty;
                    $item->save();

                    // --- Calculate Line Subtotal ---
                    $gross = (float) ($item->price
                                ?? optional($item->variant)->price
                                ?? optional($item->product)->price
                                ?? 0);
                    $disc = (float) ($item->discount_amount ?? $item->discount ?? 0);
                    $net = max(0, $gross - $disc);
                    $lineSubtotal = $net * $newQty;

                    $updatedLines[$itemId] = $lineSubtotal;
                }

                // --- 3. Recalculate Final Cart Totals ---
                // Reload cart items to ensure calculation reflects all current quantities
                $cart->load(['items.product', 'items.variant']);
                $cartSubtotal = (float) $cart->items->sum(function ($ci) {
                    $g = (float) ($ci->price
                            ?? optional($ci->variant)->price
                            ?? optional($ci->product)->price
                            ?? 0);
                    $d = (float) ($ci->discount_amount ?? $ci->discount ?? 0);
                    $n = max(0, $g - $d);
                    $q = (int) ($ci->quantity ?? 0);

                    return $n * $q;
                });
                $itemsCount = (int) $cart->items->sum('quantity');

                return [
                    'lines' => $updatedLines, // Subtotals per item
                    'cart_subtotal' => $cartSubtotal, // Total cart subtotal
                    'cart_items_count' => $itemsCount, // Total quantity of all items
                ];
            });

            // --- 4. Return Success JSON ---
            if ($request->expectsJson()) {
                return response()->json(array_merge([
                    'success' => true,
                    'message' => 'Keranjang berhasil disinkronkan',
                ], $responsePayload));
            }

            return back()->with('success', 'Keranjang berhasil disinkronkan');

        } catch (\Exception $e) {
            // --- 5. Handle Exceptions ---
            Log::error('[CartController] Bulk sync error: '.$e->getMessage());

            if ($request->expectsJson()) {
                // Return 422 if stock issue, otherwise 400
                $code = str_contains(strtolower($e->getMessage()), 'stok') ? 422 : 400;

                return response()->json(['success' => false, 'message' => $e->getMessage()], $code);
            }

            return back()->with('error', $e->getMessage());
        }
    }
}
