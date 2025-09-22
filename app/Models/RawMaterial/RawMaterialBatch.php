<?php

namespace App\Models\RawMaterial;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RawMaterialBatch extends Model
{
    use HasFactory;
    protected $table = 'raw_material_batches';
    protected $fillable = ['raw_material_id','lot_no','mfg_date','exp_date','quality_status'];
    public function rawMaterial(): BelongsTo { return $this->belongsTo(RawMaterial::class); }
    public function stocks(): HasMany { return $this->hasMany(RawMaterialStock::class, 'batch_id'); }
}
