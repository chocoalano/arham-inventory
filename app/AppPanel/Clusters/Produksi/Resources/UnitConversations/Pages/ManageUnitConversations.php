<?php

namespace App\AppPanel\Clusters\Produksi\Resources\UnitConversations\Pages;

use App\AppPanel\Clusters\Produksi\Resources\UnitConversations\UnitConversationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageUnitConversations extends ManageRecords
{
    protected static string $resource = UnitConversationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
