<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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

    public function cart()
    {
        return $this->hasOne(Cart::class, 'customer_id');
    }
}
