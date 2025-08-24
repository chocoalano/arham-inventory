<?php

namespace App\AppPanel\Clusters\Settings\Resources\Logs\Pages;

use App\AppPanel\Clusters\Settings\Resources\Logs\LogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageLogs extends ManageRecords
{
    protected static string $resource = LogResource::class;
}
