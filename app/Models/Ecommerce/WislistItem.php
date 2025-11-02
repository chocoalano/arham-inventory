<?php

namespace App\Models\Ecommerce;

use App\Models\Inventory\Product;
use App\Models\Inventory\ProductVariant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WislistItem extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'wishlist_items'; // Mengikuti nama tabel di migrasi (wislist_items)

    /**
     * Atribut yang dapat diisi secara massal (mass assignable).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'wislist_id',
        'product_id',
        'product_variant_id',
    ];

    /**
     * Relasi ke Wishlist tempat item ini berada.
     *
     * @return BelongsTo
     */
    public function wishlist(): BelongsTo
    {
        // Menggunakan foreign key 'wislist_id' yang sesuai dengan migrasi
        return $this->belongsTo(Wislist::class, 'wislist_id');
    }

    /**
     * Relasi ke Product.
     *
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        // Asumsi model Product berada di App\Models\Product
        return $this->belongsTo(Product::class);
    }

    /**
     * Relasi ke ProductVariant.
     *
     * @return BelongsTo
     */
    public function variant(): BelongsTo
    {
        // Asumsi model ProductVariant berada di App\Models\ProductVariant
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
