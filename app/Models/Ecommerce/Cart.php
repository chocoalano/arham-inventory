<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'carts';

    /**
     * Atribut yang dapat diisi secara massal (mass assignable).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_id',
    ];

    /**
     * Relasi ke model Customer.
     *
     * @return BelongsTo
     */
    public function customer(): BelongsTo
    {
        // Asumsi model Customer berada di App\Models\Customer
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relasi ke item-item di dalam Cart.
     *
     * @return HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Get total items quantity in cart
     */
    public function getTotalQuantityAttribute(): int
    {
        return $this->items->sum('quantity');
    }

    /**
     * Calculate cart subtotal
     */
    public function getSubtotalAttribute(): float
    {
        return $this->items->reduce(function ($carry, $item) {
            $price = $item->variant?->price ?? $item->product?->price ?? 0;
            return $carry + ($price * $item->quantity);
        }, 0);
    }

    /**
     * Add item to cart or update quantity if exists
     */
    public function addItem(int $productId, int $variantId, int $quantity = 1): CartItem
    {
        $item = $this->items()
            ->where('product_id', $productId)
            ->where('product_variant_id', $variantId)
            ->first();

        if ($item) {
            $item->increment('quantity', $quantity);
            return $item->fresh();
        }

        return $this->items()->create([
            'product_id' => $productId,
            'product_variant_id' => $variantId,
            'quantity' => $quantity,
        ]);
    }

    /**
     * Remove item from cart
     */
    public function removeItem(int $itemId): bool
    {
        return $this->items()->where('id', $itemId)->delete();
    }

    /**
     * Update item quantity
     */
    public function updateItemQuantity(int $itemId, int $quantity): bool
    {
        if ($quantity <= 0) {
            return $this->removeItem($itemId);
        }

        return $this->items()
            ->where('id', $itemId)
            ->update(['quantity' => $quantity]);
    }

    /**
     * Clear all items from cart
     */
    public function clearItems(): bool
    {
        return $this->items()->delete();
    }
}
