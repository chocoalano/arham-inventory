<?php

namespace App\Models\Ecommerce;

use App\Models\Inventory\Transaction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Customer extends Authenticatable
{
    use HasFactory, SoftDeletes;

    /**
     * Nama tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'customers';

    /**
     * Atribut yang dapat diisi secara massal (mass assignable).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'remember_token',
        'password',
        'billing_address',
        'shipping_address',
        'company',
        'vat_number',
        'preferred_payment_method',
        'metadata',
        'is_active',
        'created_by',
    ];

    /**
     * Atribut yang harus disembunyikan untuk serialisasi.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        // Tambahkan 'uuid' di sini jika Anda tidak ingin itu diekspos secara default,
        // meskipun biasanya 'uuid' dimaksudkan untuk referensi publik.
    ];

    /**
     * Atribut yang harus di-cast ke tipe bawaan.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'uuid' => 'string',
        'email_verified_at' => 'datetime',
        'billing_address' => 'array',
        'shipping_address' => 'array',
        'metadata' => 'array',
        'total_spent' => 'decimal:2', // Pastikan presisi tetap
        'orders_count' => 'integer',
        'loyalty_points' => 'integer',
        'last_order_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Boot model.
     */
    protected static function boot()
    {
        parent::boot();

        // Otomatis mengisi UUID saat membuat Customer baru
        static::creating(function ($customer) {
            if (empty($customer->uuid)) {
                $customer->uuid = Str::uuid();
            }
        });
    }

    // ---
    ## Relasi
    // ---

    /**
     * Dapatkan user admin yang membuat customer.
     */
    public function creator()
    {
        // Ganti 'App\Models\User' dengan model User atau Admin yang sebenarnya
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    // ---
    ## Accessor (Opsional)
    // ---

    /**
     * Dapatkan nama lengkap customer.
     *
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Relasi ke Cart
     */
    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    /**
     * Relasi ke Wishlist
     */
    public function wishlist(): HasOne
    {
        return $this->hasOne(Wislist::class);
    }

    /**
     * Get or create cart for customer
     */
    public function getOrCreateCart(): Cart
    {
        return $this->cart()->firstOrCreate([
            'customer_id' => $this->id,
        ]);
    }

    /**
     * Get or create wishlist for customer
     */
    public function getOrCreateWishlist(): Wislist
    {
        return $this->wishlist()->firstOrCreate([
            'customer_id' => $this->id,
        ]);
    }

    /**
     * Check if customer has verified email
     */
    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Ambil statistik pesanan untuk dashboard.
     *
     * Mengembalikan array:
     *  - total_orders
     *  - total_paid_orders
     *  - total_unpaid_orders
     */
    public function dashboardOrderStats(): array
    {
        $base = \App\Models\Inventory\Transaction::where('created_by', $this->id);

        $total = (clone $base)->count();

        $paid = (clone $base)
            ->where('status', 'posted')
            ->count();

        $unpaid = (clone $base)
            ->where('status', 'draft')
            ->orWhereNotNull('status')
            ->count();
        $cancelled = (clone $base)
            ->where('status', 'cancelled')
            ->count();

        return [
            'total_orders' => $total,
            'total_paid_orders' => $paid,
            'total_unpaid_orders' => $unpaid,
            'total_cancelled_orders' => $cancelled,
        ];
    }
    public function getOrders(){
        return Transaction::query()
            ->where('created_by', $this->id)
            ->latest('transaction_date')
            ->withCount('details');
    }
}
