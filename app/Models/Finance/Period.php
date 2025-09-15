<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Period extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'fiscal_year_id', 'period_no', 'starts_on', 'ends_on', 'is_closed'
    ];

    public function fiscalYear()
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function journals()
    {
        return $this->hasMany(Journal::class);
    }
}
