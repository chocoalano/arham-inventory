<?php

namespace App\Models\Views;

use Illuminate\Database\Eloquent\Model;

class ProfitAndLoss extends Model
{
    protected $table = 'v_profit_and_loss';
    public $timestamps = false;
    public $incrementing = false;
    protected $guarded = [];
    protected $keyType = 'string';
}
