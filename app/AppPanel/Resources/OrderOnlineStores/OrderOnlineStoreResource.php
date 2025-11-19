<?php

namespace App\AppPanel\Resources\OrderOnlineStores;

use App\AppPanel\Resources\OrderOnlineStores\Pages\ManageOrderOnlineStores;
use App\Models\Ecommerce\Order;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrderOnlineStoreResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Order Online Store';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('order_number')
                    ->label('Order Number'),
                TextEntry::make('customer_name')
                    ->label('Customer Name'),
                TextEntry::make('customer_email')
                    ->label('Customer Email'),
                TextEntry::make('customer_phone')
                    ->label('Customer Phone'),
                TextEntry::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'processing' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextEntry::make('currency')
                    ->label('Currency'),
                TextEntry::make('subtotal')
                    ->money(fn ($record) => $record->currency ?? 'IDR'),
                TextEntry::make('discount_total')
                    ->money(fn ($record) => $record->currency ?? 'IDR'),
                TextEntry::make('tax_total')
                    ->money(fn ($record) => $record->currency ?? 'IDR'),
                TextEntry::make('shipping_total')
                    ->money(fn ($record) => $record->currency ?? 'IDR'),
                TextEntry::make('grand_total')
                    ->money(fn ($record) => $record->currency ?? 'IDR')
                    ->weight('bold'),
                TextEntry::make('shipping_courier')
                    ->label('Courier'),
                TextEntry::make('shipping_service')
                    ->label('Service'),
                TextEntry::make('shipping_cost')
                    ->money(fn ($record) => $record->currency ?? 'IDR'),
                TextEntry::make('shipping_etd')
                    ->label('ETD'),
                TextEntry::make('weight_total_gram')
                    ->suffix(' gram'),
                TextEntry::make('source')
                    ->badge(),
                TextEntry::make('notes')
                    ->columnSpanFull(),
                TextEntry::make('placed_at')
                    ->dateTime(),
                TextEntry::make('paid_at')
                    ->dateTime(),
                TextEntry::make('cancelled_at')
                    ->dateTime(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('order_number')
            ->columns([
                TextColumn::make('order_number')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('customer_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer_email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('customer_phone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'processing' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('grand_total')
                    ->money(fn ($record) => $record->currency ?? 'IDR')
                    ->sortable(),
                TextColumn::make('shipping_courier')
                    ->label('Courier')
                    ->toggleable(),
                TextColumn::make('shipping_service')
                    ->label('Service')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('source')
                    ->badge()
                    ->sortable(),
                TextColumn::make('placed_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('paid_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageOrderOnlineStores::route('/'),
        ];
    }
}
