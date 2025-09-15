<?php

namespace App\Models\Views;

use Illuminate\Database\Eloquent\Model;

class BalanceSheet extends Model
{
    protected $table = 'v_balance_sheet';
    public $timestamps = false;
    public $incrementing = false;
    protected $guarded = [];
    protected $keyType = 'string';
}
