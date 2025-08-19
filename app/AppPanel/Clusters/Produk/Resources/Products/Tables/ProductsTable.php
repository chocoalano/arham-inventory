<?php

namespace App\AppPanel\Clusters\Produk\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sku'),
                TextColumn::make('name'),
                TextColumn::make('model'),
                ImageColumn::make('images.image_path'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->action(function ($record, DeleteAction $action) {
                        // ganti 'transactions' sesuai nama relasi yg kamu pakai
                        if ($record->transactions()->exists()) {
                            Notification::make()
                                ->title('Tidak bisa dihapus')
                                ->body('Produk ini sudah memiliki transaksi.')
                                ->danger()
                                ->send();

                            // hentikan aksi delete
                            $action->halt();
                            return;
                        }

                        $record->delete();

                        Notification::make()
                            ->title('Produk dihapus')
                            ->success()
                            ->send();
                    })
                    // Opsional: nonaktifkan tombol saat punya transaksi (UX lebih jelas)
                    ->disabled(fn($record) => $record->transactions()->exists())
                    ->tooltip(fn($record) => $record->transactions()->exists()
                        ? 'Produk memiliki transaksi dan tidak bisa dihapus.'
                        : null),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $blocked = collect();
                            $deletable = collect();

                            foreach ($records as $record) {
                                // ganti 'transactions' sesuai nama relasi kamu
                                if ($record->transactions()->exists()) {
                                    $blocked->push($record);
                                } else {
                                    $deletable->push($record);
                                }
                            }

                            // hapus yang aman
                            $deletable->each->delete();

                            // kasih tahu item yang tidak dihapus
                            if ($blocked->isNotEmpty()) {
                                Notification::make()
                                    ->title('Sebagian tidak dihapus')
                                    ->body('Produk berikut tidak dihapus karena sudah memiliki transaksi: ' .
                                        $blocked->pluck('name')->join(', '))
                                    ->danger()
                                    ->send();
                            }

                            if ($deletable->isNotEmpty()) {
                                Notification::make()
                                    ->title('Produk tanpa transaksi dihapus')
                                    ->success()
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion()
                ]),
            ]);
    }
}
