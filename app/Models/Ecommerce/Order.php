<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;
    protected $connection = 'olstore';

    protected $fillable = [
        'order_number', 'customer_id', 'voucher_id',
        'customer_name', 'customer_email', 'customer_phone',
        'billing_address_id', 'billing_address_snapshot',
        'shipping_address_id', 'shipping_address_snapshot',
        'currency', 'subtotal', 'discount_total', 'tax_total', 'shipping_total', 'grand_total',
        'shipping_courier', 'shipping_service', 'shipping_cost', 'shipping_etd', 'weight_total_gram',
        'status', 'placed_at', 'paid_at', 'cancelled_at', 'source', 'notes', 'meta',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'shipping_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'placed_at' => 'datetime',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'meta' => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
