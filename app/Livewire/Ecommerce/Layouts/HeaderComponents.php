<?php

namespace App\Livewire\Ecommerce\Layouts;

use App\Models\Ecommerce\Cart;
use App\Models\Ecommerce\Wislist;
use App\Models\Inventory\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class HeaderComponents extends Component
{
    // Public properties
    public $cartItemsCount = 0;
    public $wishlistItemsCount = 0;
    public $cartSubtotal = 0;
    public $searchQuery = '';

    // Protected properties untuk listeners
    protected $listeners = [
        'cartUpdated' => 'refreshCart',
        'wishlistUpdated' => 'refreshWishlist',
        'itemAddedToCart' => 'refreshCart',
        'itemRemovedFromCart' => 'refreshCart',
        'itemAddedToWishlist' => 'refreshWishlist',
        'itemRemovedFromWishlist' => 'refreshWishlist',
    ];

    /**
     * Mount component - inisialisasi data
     */
    public function mount()
    {
        $this->refreshCart();
        $this->refreshWishlist();
    }

    /**
     * Refresh cart data
     */
    public function refreshCart()
    {
        if (Auth::guard('customer')->check()) {
            $cart = Cart::with(['items.product.images', 'items.variant'])
                ->where('customer_id', Auth::guard('customer')->id())
                ->first();

            if ($cart) {
                $this->cartItemsCount = $cart->items->sum('quantity');
                $this->cartSubtotal = $this->calculateCartSubtotal($cart);
            } else {
                $this->cartItemsCount = 0;
                $this->cartSubtotal = 0;
            }
        } else {
            $this->cartItemsCount = 0;
            $this->cartSubtotal = 0;
        }
    }

    /**
     * Refresh wishlist data
     */
    public function refreshWishlist()
    {
        if (Auth::guard('customer')->check()) {
            $wishlist = Wislist::where('customer_id', Auth::guard('customer')->id())->first();
            $this->wishlistItemsCount = $wishlist ? $wishlist->items()->count() : 0;
        } else {
            $this->wishlistItemsCount = 0;
        }
    }

    /**
     * Calculate cart subtotal
     */
    private function calculateCartSubtotal($cart)
    {
        $subtotal = 0;

        foreach ($cart->items as $item) {
            $price = $item->variant?->price ?? $item->product?->price ?? 0;
            $quantity = $item->quantity ?? 1;
            $subtotal += $price * $quantity;
        }

        return $subtotal;
    }

    /**
     * Remove item from mini cart
     */
    public function removeCartItem($itemId)
    {
        try {
            if (!Auth::guard('customer')->check()) {
                session()->flash('error', 'Silakan login terlebih dahulu');
                return;
            }

            $cart = Cart::where('customer_id', Auth::guard('customer')->id())->first();

            if ($cart) {
                $cart->items()->where('id', $itemId)->delete();

                $this->refreshCart();
                $this->dispatch('cartUpdated');

                session()->flash('success', 'Produk berhasil dihapus dari keranjang');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Terjadi kesalahan saat menghapus produk');
        }
    }

    /**
     * Handle search submission
     */
    public function search()
    {
        if (empty($this->searchQuery)) {
            return redirect()->route('ecommerce.products.index');
        }

        return redirect()->route('ecommerce.products.index', [
            'search' => $this->searchQuery
        ]);
    }

    /**
     * Get product categories for menu
     */
    public function getCategories()
    {
        return Cache::remember('product_categories', 3600, function () {
            return Product::query()
                ->select('brand', 'model')
                ->whereNotNull('brand')
                ->orWhereNotNull('model')
                ->get()
                ->map(function ($item) {
                    if (!empty($item->brand)) {
                        return $item->brand;
                    }
                    if (!empty($item->model)) {
                        return $item->model;
                    }
                    return null;
                })
                ->filter()
                ->unique()
                ->values();
        });
    }

    /**
     * Get cart items for mini cart display
     */
    public function getCartItems()
    {
        if (!Auth::guard('customer')->check()) {
            return collect();
        }

        $cart = Cart::with([
            'items.product.images',
            'items.variant'
        ])
        ->where('customer_id', Auth::guard('customer')->id())
        ->first();

        return $cart?->items ?? collect();
    }

    /**
     * Render component
     */
    public function render()
    {
        return view('livewire.ecommerce.layouts.header-components', [
            'categories' => $this->getCategories(),
            'cartItems' => $this->getCartItems(),
            'isAuthenticated' => Auth::guard('customer')->check(),
        ]);
    }
}
