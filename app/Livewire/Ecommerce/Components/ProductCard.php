<?php

namespace App\Livewire\Ecommerce\Components;

use App\Livewire\Ecommerce\Layouts\HeaderComponents;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ProductCard extends Component
{
    /** Props */
    public $product;            // Model/array
    public $productId;          // int|string
    public $sku;                // string|null
    public $name;               // string
    public $url;                // string
    public $img;                // url/path
    public $price = 0;          // int|float
    public $compare;            // int|float|null
    public $badges = [];        // ['BARU','-10%']
    public $isWishlisted = false;
    public $qty = 1;

    public function mount(
        $product,
        ?string $url = null,
        ?string $img = null,
        $price = null,
        $compare = null,
        $badges = [],
        $isWishlisted = false,
        $qty = 1
    ): void {
        $this->product    = $product;
        $this->productId  = data_get($product, 'id') ?? data_get($product, 'product_id');
        $this->sku        = data_get($product, 'sku');
        $this->name       = data_get($product, 'name') ?? data_get($product, 'title', 'Produk');

        // Gambar: kalau bukan absolute URL, anggap path storage
        $rawImg   = $img ?? data_get($product, 'image_url') ?? data_get($product, 'imagesPrimary.image_path');
        $this->img = $rawImg && ! filter_var($rawImg, FILTER_VALIDATE_URL)
            ? asset('storage/' . ltrim($rawImg, '/'))
            : ($rawImg ?: asset('ecommerce/images/placeholder/300x360.png'));

        // Harga
        $this->price   = (float) ($price ?? data_get($product, 'final_price') ?? data_get($product, 'price') ?? data_get($product, 'variants.0.price', 0));
        $this->compare = $compare ?? data_get($product, 'regular_price') ?? data_get($product, 'variants.0.compare_price');

        // URL
        $this->url = $url
            ?? data_get($product, 'url')
            ?? ($this->sku ? route('ecommerce.products.show', $this->sku) : '#');

        $this->badges       = is_array($badges) ? $badges : [];
        $this->isWishlisted = (bool) $isWishlisted;
        $this->qty          = max(1, (int) $qty);
    }

    public function getHasDiscountProperty(): bool
    {
        return (bool) ($this->compare && $this->compare > $this->price);
    }

    /** Tambah ke keranjang => update DB jika ada model Cart, fallback session */
    public function addToCart(): void
    {
        $totalQty = 0;

        try {
            if (class_exists(\App\Models\Ecommerce\Cart::class)) {
                $customer = Auth::guard('customer')->user();
                if (! $customer) {
                    throw new \RuntimeException('guest');
                }

                /** @var \App\Models\Ecommerce\Cart $cart */
                $cart = \App\Models\Ecommerce\Cart::firstOrCreate([
                    'customer_id' => $customer->id,
                ]);

                // item: pakai kolom "quantity" (menyesuaikan HeaderComponents yang sum('quantity'))
                $variantId = data_get($this->product, 'variants.0.id');

                $item = $cart->items()->firstOrCreate(
                    [
                        'product_id'         => $this->productId,
                        'product_variant_id' => $variantId,
                    ],
                    [
                        'quantity' => 0,
                    ]
                );

                $item->increment('quantity', $this->qty);

                $totalQty = (int) $cart->items()->sum('quantity');
            } else {
                throw new \RuntimeException('no-cart-model');
            }
        } catch (\Throwable $e) {
            // Fallback: simpan di session
            $cart = collect(session('cart', []));
            $cart[$this->productId] = ($cart[$this->productId] ?? 0) + $this->qty;
            session(['cart' => $cart->all()]);
            $totalQty = $cart->sum();
        }

        // Flash message (opsional)
        session()->flash('success', "{$this->name} ditambahkan ke keranjang");

        /**
         * Penting: kirim Livewire events ke HeaderComponents (listener):
         * - itemAddedToCart -> refreshCart()
         * - cartUpdated     -> refreshCart()
         */
        $this->dispatch('itemAddedToCart')->to(HeaderComponents::class);
        $this->dispatch('cartUpdated')->to(HeaderComponents::class);
    }

    /** Toggle wishlist => DB jika ada model Wishlist, fallback session */
    public function toggleWishlist(): void
    {
        $added = false;

        try {
            if (class_exists(\App\Models\Ecommerce\Wislist::class)) {
                $customer = Auth::guard('customer')->user();
                if (! $customer) {
                    throw new \RuntimeException('guest');
                }

                $wishlist = \App\Models\Ecommerce\Wislist::firstOrCreate([
                    'customer_id' => $customer->id,
                ]);

                $exists = $wishlist->items()->where('product_id', $this->productId)->exists();

                if ($exists) {
                    $wishlist->items()->detach($this->productId);
                    $this->isWishlisted = false;
                    $added = false;
                } else {
                    $wishlist->items()->attach($this->productId);
                    $this->isWishlisted = true;
                    $added = true;
                }
            } else {
                throw new \RuntimeException('no-wishlist-model');
            }
        } catch (\Throwable $e) {
            // Fallback session
            $list = collect(session('wishlist', []));
            if ($list->contains($this->productId)) {
                $list = $list->reject(fn ($id) => $id == $this->productId)->values();
                $this->isWishlisted = false;
                $added = false;
            } else {
                $list = $list->push($this->productId)->unique()->values();
                $this->isWishlisted = true;
                $added = true;
            }
            session(['wishlist' => $list->all()]);
        }

        // Flash kecil (opsional)
        session()->flash(
            $added ? 'success' : 'info',
            $added ? "{$this->name} masuk wishlist" : "{$this->name} dihapus dari wishlist"
        );

        /**
         * Penting: dispatch events ke HeaderComponents (listener):
         * - itemAddedFromWishlist / itemRemovedFromWishlist (sesuai)
         * - wishlistUpdated
         */
        if ($added) {
            $this->dispatch('itemAddedToWishlist')->to(HeaderComponents::class);
        } else {
            $this->dispatch('itemRemovedFromWishlist')->to(HeaderComponents::class);
        }
        $this->dispatch('wishlistUpdated')->to(HeaderComponents::class);
    }

    public function render()
    {
        return view('livewire.ecommerce.components.product-card');
    }
}
