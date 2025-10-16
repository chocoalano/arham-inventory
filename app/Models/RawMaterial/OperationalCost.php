<?php

namespace App\Models\RawMaterial;

use Illuminate\Database\Eloquent\Model;

class OperationalCost extends Model
{
    protected $fillable = [
        'bom_id',
        'name',
        'price',
    ];

    public function bom()
    {
        return $this->belongsTo(ProductBom::class, 'bom_id');
    }
}
