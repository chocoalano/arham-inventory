<?php

namespace App\Models\Ecommerce;

use App\Models\Inventory\Product;
use App\Models\Inventory\ProductVariant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'cart_items';

    /**
     * Atribut yang dapat diisi secara massal (mass assignable).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'cart_id',
        'product_id',
        'product_variant_id',
        'quantity',
    ];

    /**
     * Atribut yang harus di-cast ke tipe bawaan.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
    ];

    /**
     * Relasi ke Cart tempat item ini berada.
     *
     * @return BelongsTo
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
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
