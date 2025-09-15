<?php

namespace App\AppPanel\Clusters\Finance\Resources\FiscalYears\RelationManagers;

use App\AppPanel\Clusters\Finance\Resources\FiscalYears\FiscalYearResource;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Repeater;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Zvizvi\RelationManagerRepeater\Tables\RelationManagerRepeaterAction;

class PeriodsRelationManager extends RelationManager
{
    protected static string $relationship = 'periods';

    protected static ?string $relatedResource = FiscalYearResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                RelationManagerRepeaterAction::make()
                    ->modalWidth('5xl')
                    ->modalHeading('Edit Related Records')
                    ->configureRepeater(function (Repeater $repeater) {
                        return $repeater
                            ->reorderable()
                            ->collapsible()
                            ->cloneable()
                            ->defaultItems(0)
                            ->maxItems(5);
                    }),
            ])
            ->columns([
                TextColumn::make('period_no')->label('No'),
                TextColumn::make('starts_on')->date(),
                TextColumn::make('ends_on')->date(),
                IconColumn::make('is_closed')->boolean(),
            ]);
    }
}
