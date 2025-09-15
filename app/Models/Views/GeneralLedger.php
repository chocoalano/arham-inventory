<?php

namespace App\Models\Views;

use Illuminate\Database\Eloquent\Model;

class GeneralLedger extends Model
{
    protected $table = 'v_general_ledger';
    public $timestamps = false;
    public $incrementing = false;
    protected $guarded = [];
}
