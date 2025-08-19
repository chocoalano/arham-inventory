<?php

namespace App\AppPanel\Clusters\Inventory\Resources\Payments;

use App\AppPanel\Clusters\Inventory\InventoryCluster;
use App\AppPanel\Clusters\Inventory\Resources\Payments\Pages\ManagePayments;
use App\Models\Inventory\Invoice;
use App\Models\Inventory\Payment;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $cluster = InventoryCluster::class;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pembayaran')->columns(2)->schema([
                    Select::make('invoice_id')->label('Invoice')
                        ->options(fn() => Invoice::query()
                            ->orderByDesc('issued_at')->pluck('invoice_number', 'id'))
                        ->searchable()->preload()->required()
                        ->live() // Kunci: Membuat field ini "hidup"
                        ->afterStateUpdated(function (Get $get, Set $set) {
                            // Ambil ID invoice yang dipilih
                            $invoiceId = $get('invoice_id');

                            // Jika invoice dipilih, cari total_amount-nya
                            if ($invoiceId) {
                                $invoice = Invoice::find($invoiceId);
                                if ($invoice) {
                                    // Isi field 'amount' dengan total_amount dari invoice
                                    $set('amount', $invoice->total_amount);
                                }
                            } else {
                                // Jika tidak ada invoice, reset amount menjadi 0
                                $set('amount', 0);
                            }
                        }),
                    TextInput::make('amount')->label('Jumlah')->numeric()->prefix('Rp')->required(),
                    Select::make('method')->label('Metode')->options([
                        'transfer' => 'Transfer',
                        'cash' => 'Tunai',
                        'card' => 'Kartu',
                        'e-wallet' => 'E-Wallet'
                    ])->required(),
                    TextInput::make('reference_no')->label('No. Referensi'),
                    DateTimePicker::make('paid_at')->label('Tanggal Bayar')->default(now())->required(),
                    Select::make('received_by')
                        ->relationship('receiver', 'email')
                        ->label('Diterima oleh?')
                        ->required(),
                    Textarea::make('notes')->label('Catatan')->rows(2)->columnSpanFull(),
                ])->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice.invoice_number')
                    ->label('Nomor Invoice')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->summarize(Sum::make()->label('Total')),
                TextColumn::make('method')
                    ->label('Metode')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Tanggal Pembayaran')
                    ->date()
                    ->sortable(),
                TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('method')
                    ->label('Filter Metode')
                    ->options([
                        'Tunai' => 'Tunai',
                        'Transfer Bank' => 'Transfer Bank',
                        'Kartu Kredit' => 'Kartu Kredit',
                        'E-wallet' => 'E-wallet',
                    ]),
                Filter::make('payment_date')
                    ->form([
                        DatePicker::make('min_date')
                            ->label('Tanggal Minimum'),
                        DatePicker::make('max_date')
                            ->label('Tanggal Maksimum'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_date'],
                                fn(Builder $query, $date): Builder => $query->whereDate('payment_date', '>=', $date),
                            )
                            ->when(
                                $data['max_date'],
                                fn(Builder $query, $date): Builder => $query->whereDate('payment_date', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
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
            'index' => ManagePayments::route('/'),
        ];
    }
}
