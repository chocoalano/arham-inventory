<?php

namespace App\AppPanel\Clusters\Inventory\Resources\Invoices;

use App\AppPanel\Clusters\Inventory\InventoryCluster;
use App\AppPanel\Clusters\Inventory\Resources\Invoices\Pages\ManageInvoices;
use App\Models\Inventory\Invoice;
use App\Models\Inventory\Transaction;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static ?string $cluster = InventoryCluster::class;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Header')->columns(2)->schema([
                    Select::make('transaction_id')->label('Transaksi')
                        ->options(fn() => Transaction::query()
                            ->whereIn('type', ['penjualan', 'dropship'])
                            ->whereDoesntHave('invoice')
                            ->latest('transaction_date')
                            ->pluck('reference_number', 'id'))
                        ->searchable()->preload()->required()
                        ->live(),

                    TextInput::make('invoice_number')->label('Nomor Invoice')
                        ->required()->unique(ignoreRecord: true),
                    DateTimePicker::make('issued_at')->label('Tanggal Terbit')->default(now()),
                    DateTimePicker::make('due_at')->label('Jatuh Tempo')->nullable(),
                ]),

                Section::make('Nilai')->columns(2)->schema([
                    TextInput::make('subtotal')->numeric()->prefix('Rp')->default(0)->required(),
                    TextInput::make('discount_total')->numeric()->prefix('Rp')->default(0),
                    TextInput::make('tax_total')->numeric()->prefix('Rp')->default(0),
                    TextInput::make('shipping_fee')->numeric()->prefix('Rp')->default(0),
                    TextInput::make('total_amount')->numeric()->prefix('Rp')->default(0)->helperText('Boleh diisi manual atau dihitung dari subtotal - diskon + pajak + ongkir.'),
                    TextInput::make('paid_amount')->numeric()->prefix('Rp')->default(0),
                    Placeholder::make('status_paid')->label('Status')
                        ->content(fn(Get $get) => ((float) $get('paid_amount') >= (float) $get('total_amount')) ? 'Lunas' : 'Belum Lunas'),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Nomor Invoice')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('transaction.reference_number')
                    ->label('Nomor Transaksi')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->label('Jumlah Total')
                    ->money('IDR')
                    ->summarize(Sum::make()->label('Total Invoice')),
                TextColumn::make('paid_amount')
                    ->label('Jumlah Dibayar')
                    ->money('IDR')
                    ->summarize(Sum::make()->label('Total Dibayar')),
                IconColumn::make('is_paid')
                    ->label('Status')
                    ->boolean()
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Tanggal Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('cetak_invoice')
                    ->label('Cetak invoice')
                    ->url(fn(): string => route('inventory.cetak-invoice'))
                    ->openUrlInNewTab()
                    ->visible(fn(): bool => auth()->user()->hasPermissionTo('viewAny-invoice')),
                EditAction::make(),
                DeleteAction::make(),
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
            'index' => ManageInvoices::route('/'),
        ];
    }
}
