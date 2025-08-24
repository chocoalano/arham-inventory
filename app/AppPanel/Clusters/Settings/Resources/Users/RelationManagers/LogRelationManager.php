<?php

namespace App\AppPanel\Clusters\Settings\Resources\Users\RelationManagers;

use App\AppPanel\Clusters\Settings\Resources\Logs\LogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class LogRelationManager extends RelationManager
{
    protected static string $relationship = 'log';

    protected static ?string $relatedResource = LogResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                // CreateAction::make(),
            ]);
    }
}
