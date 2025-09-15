<?php

namespace App\Models\Finance;

use App\Models\Inventory\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductAccountLink extends Model
{
    use HasFactory;
    protected $fillable = [
        'product_id',
        'inventory_account_id',
        'cogs_account_id',
        'sales_account_id',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function inventoryAccount()
    {
        return $this->belongsTo(Account::class, 'inventory_account_id');
    }

    public function cogsAccount()
    {
        return $this->belongsTo(Account::class, 'cogs_account_id');
    }

    public function salesAccount()
    {
        return $this->belongsTo(Account::class, 'sales_account_id');
    }
}
