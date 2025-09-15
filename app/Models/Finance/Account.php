<?php

namespace App\Models\Finance;

use App\Enums\AccountType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'number','name','type','subtype','is_postable','is_active'
    ];

    protected $casts = [
        'type' => AccountType::class,
        'is_postable' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function lines()
    {
        return $this->hasMany(JournalLine::class);
    }
}
