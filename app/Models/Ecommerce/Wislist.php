<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wislist extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'wishlists'; // Mengikuti nama tabel di migrasi (wislists)

    /**
     * Atribut yang dapat diisi secara massal (mass assignable).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_id',
    ];

    /**
     * Relasi ke model Customer.
     *
     * @return BelongsTo
     */
    public function customer(): BelongsTo
    {
        // Asumsi model Customer berada di App\Models\Customer
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relasi ke item-item di dalam Wishlist.
     *
     * @return HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(WislistItem::class, 'wishlist_id'); // Menggunakan nama kolom FK yang benar
    }
}
