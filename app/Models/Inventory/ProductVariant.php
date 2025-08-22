<?php

namespace App\Models\Inventory;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ProductVariant extends Model
{
    use SoftDeletes, LogsActivity;

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
    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'product_variant_id');
    }

    public function scopeForUser(Builder $query, ?User $user = null): Builder
    {
        // Gunakan user terautentikasi jika tidak diberikan
        $user = $user ?? auth()->user();

        // Jika tidak ada user → aman: hasil kosong
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        // Superadmin → tanpa pembatasan
        if (method_exists($user, 'hasRole') && $user->hasRole('Superadmin')) {
            return $query;
        }

        // Non-superadmin → batasi varian yang tersedia di gudang miliknya
        $wid = (int) ($user->warehouse_id ?? 0);
        if ($wid <= 0) {
            return $query->whereRaw('1 = 0');
        }

        // Tampilkan varian yang ada stoknya (on hand > 0) di gudang user
        return $query->whereHas('stocks', function (Builder $q) use ($wid) {
            $q->where('warehouse_id', $wid)
                ->whereRaw('(COALESCE(qty,0) - COALESCE(reserved_qty,0)) > 0');
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()                 // log semua field fillable
            ->useLogName('varian produk')          // nama log
            ->dontSubmitEmptyLogs();        // hindari log kosong
    }
}
