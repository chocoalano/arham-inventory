<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FiscalYear extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'year', 'starts_on', 'ends_on', 'is_closed'
    ];

    public function periods()
    {
        return $this->hasMany(Period::class);
    }
}
