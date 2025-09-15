<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ProductImage extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, SoftDeletes;

    protected $fillable = [
        'product_id','image_path','is_primary','sort_order',
    ];

    protected $casts = [
        'is_primary' => 'bool',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()                 // log semua field fillable
            ->useLogName('produk image')          // nama log
            ->dontSubmitEmptyLogs();        // hindari log kosong
    }
}
