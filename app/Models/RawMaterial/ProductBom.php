<?php

namespace App\Models\RawMaterial;

use App\Models\Inventory\Product;
use App\Models\Inventory\ProductVariant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductBom extends Model
{
    use HasFactory;
    protected $table = 'product_boms';
    protected $fillable = ['product_id','product_variant_id','version','is_active','note'];
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function productVariant(): BelongsTo { return $this->belongsTo(ProductVariant::class); }
    public function items(): HasMany { return $this->hasMany(ProductBomItem::class); }
}
