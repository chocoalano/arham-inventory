<?php

namespace App\AppPanel\Clusters\Settings;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class SettingsCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::Cog8Tooth;
    protected static ?string $navigationLabel = 'Pengaturan';

}
