<?php

namespace App\AppPanel\Clusters\Settings\Resources\Users\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detail data')
                    ->inlineLabel()
                    ->components([
                        TextEntry::make('name'),
                        TextEntry::make('email')
                            ->label('Email address'),
                        TextEntry::make('email_verified_at')
                            ->dateTime(),
                        TextEntry::make('warehouse.name')
                            ->label('Area penempatan kerja'),
                        TextEntry::make('roles.name')
                            ->color('primary')
                            ->label('Peran pengguna'),
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->dateTime(),
                    ])
            ]);
    }
}
