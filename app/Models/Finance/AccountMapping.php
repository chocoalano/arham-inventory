<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountMapping extends Model
{
    use HasFactory;
    protected $fillable = ['key','account_id'];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
