<?php

namespace App\Models\Views;

use Illuminate\Database\Eloquent\Model;

class TrialBalance extends Model
{
    protected $table = 'v_trial_balance';
    public $timestamps = false;
    public $incrementing = false;
    protected $guarded = [];
    protected $keyType = 'string';

    public function scopeForPeriod($q, ?int $fiscalYearId = null, ?int $fromPeriodId = null, ?int $toPeriodId = null)
    {
        if ($fiscalYearId) $q->where('fiscal_year', function($sub) use ($fiscalYearId){});
        if ($fromPeriodId) $q->where('period_id', '>=', $fromPeriodId);
        if ($toPeriodId)   $q->where('period_id', '<=', $toPeriodId);
        return $q;
    }
}
