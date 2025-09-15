<?php

namespace App\AppPanel\Clusters\Finance;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class FinanceCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::Banknotes;
}
