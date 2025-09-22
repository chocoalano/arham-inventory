<?php

namespace App\Models\RawMaterial;

use App\Models\Inventory\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class RawMaterial extends Model
{
    use HasFactory;
    protected $table = "raw_materials";
    protected $fillable = ['category_id','default_unit_id','code','name','spec','min_stock','is_active'];
    public function category(): BelongsTo { return $this->belongsTo(RawMaterialCategory::class, 'category_id'); }
    public function supplier(): HasOneThrough
    {
        return $this->hasOneThrough(
            Supplier::class,             // model tujuan
            RawMaterialSupplier::class,  // model perantara
            'id',                        // PK di RawMaterialSupplier
            'id',                        // PK di Supplier
            'raw_material_supplier_id',  // FK di RawMaterialSupplierPrice
            'supplier_id'                // FK di RawMaterialSupplier
        );
    }
    public function defaultUnit(): BelongsTo { return $this->belongsTo(Unit::class, 'default_unit_id'); }
    public function images(): HasMany { return $this->hasMany(RawMaterialImage::class); }
    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'raw_material_suppliers')
            ->using(RawMaterialSupplier::class)
            ->withPivot(['id','supplier_sku','is_preferred'])
            ->withTimestamps();
    }
    public function batches(): HasMany { return $this->hasMany(RawMaterialBatch::class); }
    public function stocks(): HasMany { return $this->hasMany(RawMaterialStock::class); }
    public function movements(): HasMany { return $this->hasMany(RawMaterialStockMovement::class); }


    // helpers
    public function stockOnHand(?int $warehouseId = null): float
    {
        $q = $this->stocks();
        if ($warehouseId) {
            $q->where('warehouse_id', $warehouseId);
        }
        return (float) $q->sum('quantity');
    }
}
