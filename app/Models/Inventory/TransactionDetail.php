<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TransactionDetail extends Model
{
    use HasFactory, LogsActivity;
    protected $fillable = [
        'transaction_id','product_id','product_variant_id',
        'warehouse_id','qty','price','discount_amount','line_total',
    ];

    protected $casts = [
        'qty'            => 'int',
        'price'          => 'decimal:2',
        'discount_amount'=> 'decimal:2',
        'line_total'     => 'decimal:2',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()                 // log semua field fillable
            ->useLogName('detail transaksi')          // nama log
            ->dontSubmitEmptyLogs();        // hindari log kosong
    }
}
