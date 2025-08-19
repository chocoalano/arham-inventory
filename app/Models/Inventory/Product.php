<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'supplier_id',
        'sku',
        'name',
        'model',
        'brand',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'bool',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Stok varian produk ini (melalui ProductVariant -> WarehouseVariantStock).
     */
    public function variantStocks(): HasManyThrough
    {
        return $this->hasManyThrough(
            WarehouseVariantStock::class,  // model tujuan
            ProductVariant::class,         // model perantara
            'product_id',                  // FK di ProductVariant yang menunjuk ke Product
            'product_variant_id',          // FK di WarehouseVariantStock yang menunjuk ke ProductVariant
            'id',                          // PK di Product
            'id'                           // PK di ProductVariant
        );
    }

    /**
     * Scope: produk yang memiliki varian tersedia (>0) di warehouse tertentu.
     * Jika $warehouseId null, akan fallback ke request('warehouse_id'),
     * lalu ke Auth::user()->warehouse_id, lalu ke config('inventory.default_warehouse_id', 1).
     */
    public function scopeAvailableInWarehouse(Builder $query, ?int $warehouseId = null): Builder
    {
        $wid = $warehouseId
            ?? (int) (request('warehouse_id') ?? auth()->user()?->warehouse_id ?? 0);

        return $query->whereHas('variants.stocks', function (Builder $q) use ($wid) {
            $q->where('warehouse_id', $wid)
                ->whereRaw('(qty - reserved_qty) > 0');
        });
    }

    /**
     * Ambil varian yang tersedia di warehouse tertentu (dengan fallback sama seperti scope).
     */
    public function availableVariantsInWarehouse(?int $warehouseId = null)
    {
        $wid = $warehouseId
            ?? (int) (request('warehouse_id') ?? auth()->user()->warehouse_id ?? 0);

        return $this->variants()
            ->whereHas('stocks', fn($q) => $q->where('warehouse_id', $wid)
                ->whereRaw('(qty - reserved_qty) > 0'))
            ->with(['stocks' => fn($q) => $q->where('warehouse_id', $wid)]);
    }

    /**
     * Hitung total on-hand (qty - reserved) untuk produk ini di warehouse tertentu.
     */
    public function onHandInWarehouse(?int $warehouseId = null): int
    {
        $wid = $warehouseId
            ? $warehouseId
            : (int) (request('warehouse_id') ?? Auth::user()?->warehouse_id ?? 0);

        return (int) $this->variantStocks()
            ->where('warehouse_id', $wid)
            ->selectRaw('COALESCE(SUM(COALESCE(qty, 0) - COALESCE(reserved_qty, 0)), 0) as onhand')
            ->value('onhand');
    }

    public function transactions(): HasManyThrough
    {
        return $this->hasManyThrough(
            InventoryMovement::class, // related (tujuan akhir)
            ProductVariant::class,    // through (perantara)
            'product_id',             // firstKey: FK di ProductVariant yang refer ke Product
            'product_variant_id',     // secondKey: FK di InventoryMovement yang refer ke ProductVariant
            'id',                     // localKey: PK di Product
            'id'                      // secondLocalKey: PK di ProductVariant
        );
    }

    /**
     * True jika produk punya transaksi (lewat salah satu variannya).
     */
    public function hasAnyTransaction(): bool
    {
        return $this->transactions()->exists();
    }
}
