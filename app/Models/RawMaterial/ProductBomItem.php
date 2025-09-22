<?php

namespace App\Models\RawMaterial;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBomItem extends Model
{
    use HasFactory;
    protected $table = 'product_bom_items';
    protected $fillable = ['product_bom_id','raw_material_id','unit_id','qty','waste_percent','sort_order'];
    public function bom(): BelongsTo { return $this->belongsTo(ProductBom::class, 'product_bom_id'); }
    public function rawMaterial(): BelongsTo { return $this->belongsTo(RawMaterial::class); }
    public function unit(): BelongsTo { return $this->belongsTo(Unit::class); }
}
