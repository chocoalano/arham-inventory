<?php

namespace App\Models\RawMaterial;

use App\Models\Inventory\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class RawMaterialSupplierPrice extends Model
{
    use HasFactory;
    protected $table = 'raw_material_supplier_prices';
    protected $fillable = ['raw_material_supplier_id','unit_id','price','valid_from','valid_to'];
    public function supplierLink(): BelongsTo {
        return $this->belongsTo(RawMaterialSupplier::class, 'raw_material_supplier_id');
    }

    public function supplier(): HasOneThrough
    {
        return $this->hasOneThrough(
            Supplier::class,             // model tujuan
            RawMaterialSupplier::class,  // model perantara
            'id',                        // Foreign key di RawMaterialSupplier (PK-nya)
            'id',                        // Foreign key di Supplier (PK-nya)
            'raw_material_supplier_id',  // FK di RawMaterialSupplierPrice
            'supplier_id'                // FK di RawMaterialSupplier
        );
    }

    public function unit(): BelongsTo {
        return $this->belongsTo(Unit::class);
    }
}
