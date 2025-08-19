<?php

namespace App\AppPanel\Clusters\Produk;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class ProdukCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCloud;
    protected static ?string $navigationLabel = 'Produk';
}
