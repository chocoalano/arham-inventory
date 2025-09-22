<?php

namespace App\Models\RawMaterial;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitConversation extends Model
{
    use HasFactory;
    protected $table = "unit_conversions";
    public $timestamps = true;
    protected $fillable = ['from_unit_id','to_unit_id','factor'];
    public function from(): BelongsTo { return $this->belongsTo(Unit::class, 'from_unit_id'); }
    public function to(): BelongsTo { return $this->belongsTo(Unit::class, 'to_unit_id'); }
}
