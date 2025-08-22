<?php

namespace App\AppPanel\Clusters\Produk;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class ProdukCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCloud;
    protected static ?string $navigationLabel = 'Produk';
    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }
        return $user->hasAnyPermission(['viewAny-product', 'view-product']);
    }
}
