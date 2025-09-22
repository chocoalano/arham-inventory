<?php

namespace App\Models\RawMaterial;

use App\Models\Inventory\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RawMaterialSupplier extends Model
{
    use HasFactory;
    protected $table = 'raw_material_suppliers';
    protected $fillable = ['raw_material_id','supplier_id','supplier_sku','is_preferred'];


    public function material(): BelongsTo {
        return $this->belongsTo(RawMaterial::class, 'raw_material_id', 'id');
    }
    public function prices(): HasOne {
        return $this->hasOne(RawMaterialSupplierPrice::class, 'raw_material_supplier_id', 'id');
    }
    public function supplier(): BelongsTo {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}
