<?php

namespace App\Models\Inventory;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_POSTED = 'posted';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'reference_number',
        'type',
        'transaction_date',
        'source_warehouse_id',
        'destination_warehouse_id',
        'customer_name',
        'customer_phone',
        'customer_full_address',
        'supplier_id',
        'item_count',
        'grand_total',
        'status',
        'posted_at',
        'created_by',
        'remarks',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'posted_at' => 'datetime',
        'item_count' => 'int',
        'grand_total' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        // Event 'creating' akan dipanggil sebelum model disimpan pertama kali
        static::creating(function (Transaction $transaction) {
            // Jika reference_number belum diisi, generate yang baru
            if (empty($transaction->reference_number)) {
                $transaction->reference_number = self::generateUniqueReferenceNumber();
            }
        });
    }

    private static function generateUniqueReferenceNumber(): string
    {
        do {
            $datePrefix = now()->format('Ymd');
            $randomString = strtoupper(Str::random(6));
            $referenceNumber = 'TRX-' . $datePrefix . '-' . $randomString;
        } while (self::where('reference_number', $referenceNumber)->exists());

        return $referenceNumber;
    }

    public function details(): HasMany
    {
        return $this->hasMany(TransactionDetail::class);
    }

    public function sourceWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'source_warehouse_id');
    }

    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function invoice(): HasOne
    {
        // one-to-one via invoices.transaction_id (inverse would be in Invoice)
        return $this->hasOne(Invoice::class, 'transaction_id');
    }
    public function inventoryMovement(): HasOne
    {
        // one-to-one via invoices.transaction_id (inverse would be in Invoice)
        return $this->hasOne(InventoryMovement::class, 'transaction_id');
    }

    /** Helper: apakah sudah posted? */
    public function getIsPostedAttribute(): bool
    {
        return $this->status === self::STATUS_POSTED;
    }
}
