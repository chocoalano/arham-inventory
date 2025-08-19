<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class ProductVariant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'sku_variant',
        'barcode',
        'color',
        'size',
        'cost_price',
        'price',
        'status',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'price' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(WarehouseVariantStock::class, 'product_variant_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'product_variant_id');
    }

    /** Stock total semua gudang */
    public function getTotalStockAttribute(): int
    {
        return (int) $this->stocks()->sum('qty');
    }

    /** Stock on hand (qty - reserved) semua gudang */
    public function getStockOnHandAttribute(): int
    {
        return (int) $this->stocks()->sum(DB::raw('qty - reserved_qty'));
    }

    /** Scope varian aktif */
    public function scopeActive($q)
    {
        return $q->where('status', 'active');
    }

    // di ProductVariant
    public function stockInWarehouse(int $warehouseId): int
    {
        return (int) $this->stocks()
            ->where('warehouse_id', $warehouseId)
            ->value('qty') ?? 0;
    }

}
