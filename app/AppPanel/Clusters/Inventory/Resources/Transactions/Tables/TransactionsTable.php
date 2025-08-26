<?php

namespace App\AppPanel\Clusters\Inventory\Resources\Transactions\Tables;

use App\AppPanel\Clusters\Inventory\Resources\Transactions\TransactionResource;
use App\Models\Inventory\Transaction;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        $user = Auth::user();
        $query = $user->hasRole('Superadmin')
            ? Transaction::query()
            : Transaction::where(function ($q) use ($user) {
                $q->where('source_warehouse_id', $user->warehouse_id)
                    ->orWhere('destination_warehouse_id', $user->warehouse_id);
            });
        return $table
            ->query($query)
            ->columns([
                TextColumn::make('reference_number')
                    ->label('Nomor Referensi')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipe Transaksi')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'penjualan' => 'success',
                        'dropship' => 'info',
                        'pengiriman' => 'warning',
                        'penerimaan' => 'primary',
                        default => 'secondary',
                    })
                    ->searchable()
                    ->sortable(),
                TextColumn::make('transaction_date')
                    ->label('Tanggal Transaksi')
                    ->date()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipe Transaksi')
                    ->options([
                        'penjualan' => 'Penjualan',
                        'dropship' => 'Dropship',
                        'pengiriman' => 'Pengiriman',
                        'penerimaan' => 'Penerimaan',
                    ]),
                Filter::make('transaction_date')
                    ->form([
                        DatePicker::make('min_date'),
                        DatePicker::make('max_date'),
                    ])->columns(2)
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_date'],
                                fn(Builder $query, $date): Builder => $query->whereDate('transaction_date', '>=', $date),
                            )
                            ->when(
                                $data['max_date'],
                                fn(Builder $query, $date): Builder => $query->whereDate('transaction_date', '<=', $date),
                            );
                    }),
                TrashedFilter::make(),
            ], layout: FiltersLayout::AboveContent)
            ->recordActions([
                ActionGroup::make([
                    Action::make('activities')
                        ->label('Aktivitas')
                        ->icon('heroicon-m-clock')
                        ->color('primary')
                        ->visible(fn(): bool => auth()->user()?->hasRole('Superadmin'))
                        ->url(fn($record) => TransactionResource::getUrl('activities', ['record' => $record])),
                    ViewAction::make()
                        ->modalHeading('Detail Transaksi')
                        ->modalWidth(Width::Full)
                        ->mutateRecordDataUsing(function (array $data, Transaction $record): array {
                            // Eager load relasi yang dibutuhkan untuk tampilan
                            $record->loadMissing([
                                'details',
                                'sourceWarehouse',
                                'destinationWarehouse',
                                'supplier',
                                'creator',
                                'invoice',
                                // Sesuaikan dengan nama relasi movement Anda:
                                // 'movements', // atau 'inventoryMovements'
                            ]);

                            // Mapping details -> array datar untuk Repeater
                            $data['details'] = $record->details
                                ->map(function ($d) use ($record) {
                                return [
                                    'warehouse_id' => (int) ($d->warehouse_id
                                        ?? $record->source_warehouse_id
                                        ?? $record->destination_warehouse_id),
                                    'product_id' => (int) $d->product_id,
                                    'product_variant_id' => (int) $d->product_variant_id,
                                    'qty' => (int) $d->qty,
                                    'price' => (int) ($d->price ?? 0),
                                    'discount_amount' => (int) ($d->discount_amount ?? 0),
                                    'line_total' => (int) ($d->line_total ?? ((int) ($d->price ?? 0) * (int) ($d->qty ?? 0))),
                                ];
                            })
                                ->values()
                                ->all();

                            // Sinkronkan field header – penting agar rules visible/dehydrate di form tetap benar
                            $data['reference_number'] = $record->reference_number;
                            $data['type'] = $record->type;
                            $data['transaction_date'] = $record->transaction_date;
                            $data['source_warehouse_id'] = $record->source_warehouse_id;
                            $data['destination_warehouse_id'] = in_array($record->type, ['pemindahan', 'pengembalian', 'penyesuaian'])
                                ? $record->destination_warehouse_id
                                : null;

                            // Field pelanggan hanya relevan saat penjualan (biar komponen visible() kamu bekerja mulus)
                            $isSale = $record->type === 'penjualan';
                            $data['customer_name'] = $isSale ? $record->customer_name : null;
                            $data['customer_phone'] = $isSale ? $record->customer_phone : null;
                            $data['customer_full_address'] = $isSale ? $record->customer_full_address : null;

                            $data['remarks'] = $record->remarks;

                            return $data;
                        })
                        ->modalWidth(Width::Full),
                    DeleteAction::make(),
                    RestoreAction::make(),
                    ReplicateAction::make()
                        ->form([
                            TextInput::make('reference_number')
                                ->label('Nomor Referensi')
                                ->required()
                                ->maxLength(64)
                                ->default(fn(array $data) => Transaction::generateUniqueReferenceNumber())
                                ->unique(table: Transaction::class, column: 'reference_number'),
                        ]),
                    ForceDeleteAction::make()
                ])
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make()
                ]),
            ]);
    }
}
