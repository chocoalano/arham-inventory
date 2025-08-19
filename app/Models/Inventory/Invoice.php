<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'transaction_id','invoice_number','issued_at','due_at',
        'subtotal','discount_total','tax_total','shipping_fee',
        'total_amount','paid_amount','is_paid',
    ];

    protected $casts = [
        'issued_at'     => 'datetime',
        'due_at'        => 'datetime',
        'subtotal'      => 'decimal:2',
        'discount_total'=> 'decimal:2',
        'tax_total'     => 'decimal:2',
        'shipping_fee'  => 'decimal:2',
        'total_amount'  => 'decimal:2',
        'paid_amount'   => 'decimal:2',
        'is_paid'       => 'bool',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** Helper: sisa tagihan */
    public function getOutstandingAttribute(): string
    {
        $outstanding = (float) $this->total_amount - (float) $this->paid_amount;
        return number_format(max(0, $outstanding), 2, '.', '');
    }

    /** Scope: belum lunas */
    public function scopeUnpaid($q)
    {
        return $q->where('is_paid', false);
    }
}
