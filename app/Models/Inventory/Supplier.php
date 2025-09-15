<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Supplier extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, SoftDeletes;

    protected $fillable = [
        'code','name','contact_name','phone','email',
        'address','district','city','province','postal_code',
        'lat','lng','is_active',
    ];

    protected $casts = [
        'lat'       => 'decimal:7',
        'lng'       => 'decimal:7',
        'is_active' => 'bool',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()                 // log semua field fillable
            ->useLogName('pemasok')          // nama log
            ->dontSubmitEmptyLogs();        // hindari log kosong
    }
}
