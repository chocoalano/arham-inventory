<?php

namespace App\Models\RawMaterial;

use App\Models\Inventory\Warehouse;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class RawMaterialStockMovement extends Model
{
    use HasFactory;
    protected $table = 'raw_material_stock_movements';
    protected $fillable = ['raw_material_id','warehouse_id','batch_id','unit_id','type','qty','unit_cost','reference_type','reference_id','note','moved_at'];
    protected $casts = [ 'moved_at' => 'datetime' ];


    public function rawMaterial(): BelongsTo { return $this->belongsTo(RawMaterial::class); }
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function batch(): BelongsTo { return $this->belongsTo(RawMaterialBatch::class, 'batch_id'); }
    public function unit(): BelongsTo { return $this->belongsTo(Unit::class); }
    public function reference(): MorphTo { return $this->morphTo(); }
}
