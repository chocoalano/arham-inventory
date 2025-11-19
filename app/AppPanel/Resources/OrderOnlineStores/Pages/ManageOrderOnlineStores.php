<?php

namespace App\AppPanel\Resources\OrderOnlineStores\Pages;

use App\AppPanel\Resources\OrderOnlineStores\OrderOnlineStoreResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageOrderOnlineStores extends ManageRecords
{
    protected static string $resource = OrderOnlineStoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
        ];
    }
}
