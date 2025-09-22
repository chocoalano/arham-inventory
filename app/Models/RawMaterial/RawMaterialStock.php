<?php

namespace App\Models\RawMaterial;

use App\Models\Inventory\Warehouse;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RawMaterialStock extends Model
{
    use HasFactory;
    protected $table = 'raw_material_stocks';
    protected $fillable = ['raw_material_id','warehouse_id','batch_id','unit_id','quantity'];
    public function rawMaterial(): BelongsTo { return $this->belongsTo(RawMaterial::class); }
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function batch(): BelongsTo { return $this->belongsTo(RawMaterialBatch::class, 'batch_id'); }
    public function unit(): BelongsTo { return $this->belongsTo(Unit::class); }
}
