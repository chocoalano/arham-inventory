<?php

namespace App\AppPanel\Clusters\Finance\Resources\Journals\Pages;

use App\AppPanel\Clusters\Finance\Resources\Journals\JournalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;

class ManageJournals extends ManageRecords
{
    protected static string $resource = JournalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->modalWidth(Width::SevenExtraLarge)
                ->slideOver(),
        ];
    }
}
