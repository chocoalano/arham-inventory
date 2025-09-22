<?php

namespace App\Models\RawMaterial;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RawMaterialImage extends Model
{
    use HasFactory;
    protected $table = "raw_material_images";
    protected $fillable = ['raw_material_id','image_path','is_primary','sort_order'];
    public function rawMaterial(): BelongsTo { return $this->belongsTo(RawMaterial::class); }
}
