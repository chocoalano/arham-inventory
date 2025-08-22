<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class WarehouseVariantStock extends Model
{
    use LogsActivity;
    protected $table = 'warehouse_variant_stocks';

    protected $fillable = [
        'warehouse_id','product_variant_id','qty','reserved_qty',
    ];

    protected $casts = [
        'qty'          => 'int',
        'reserved_qty' => 'int',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()                 // log semua field fillable
            ->useLogName('stok area')          // nama log
            ->dontSubmitEmptyLogs();        // hindari log kosong
    }
}
