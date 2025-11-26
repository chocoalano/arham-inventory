<?php

namespace App\AppPanel\Clusters\Produk\Resources\Products\Tables;

use App\AppPanel\Clusters\Produk\Resources\Products\ProductResource;
use App\AppPanel\Clusters\Produk\Resources\Products\Schemas\ProductForm;
use App\Models\Inventory\Product;
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
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sku')->searchable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('model')->searchable(),
                ImageColumn::make('imagesPrimary.image_path')
                    ->label('Gambar')
                    ->circular()
                    ->defaultImageUrl(url('/images/no-image.png'))
                    ->disk('public'),
            ])
            ->filters([
                // Status aktif / nonaktif
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Semua')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif')
                    ->queries(
                        true: fn($q) => $q->where('is_active', true),
                        false: fn($q) => $q->where('is_active', false),
                        blank: fn($q) => $q,
                    ),

                // Supplier (butuh relasi supplier())
                SelectFilter::make('supplier_id')
                    ->label('Supplier')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload(),

                // Brand (dropdown berdasarkan data unik di tabel)
                Filter::make('brand')
                    ->label('Brand')
                    ->form([
                        TextInput::make('value')->label('Brand'),
                    ])
                    ->query(
                        fn($q, array $data) =>
                        filled($data['value'] ?? null)
                        ? $q->where('brand', 'like', '%' . $data['value'] . '%')
                        : $q
                    ),

                // Rentang tanggal dibuat
                Filter::make('created_between')
                    ->label('Dibuat Antara')
                    ->form([
                        DatePicker::make('from')->label('Dari')->native(false),
                        DatePicker::make('until')->label('Sampai')->native(false),
                    ])
                    ->columns(2)
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(filled($data['from']), fn($q) => $q->whereDate('created_at', '>=', $data['from']))
                            ->when(filled($data['until']), fn($q) => $q->whereDate('created_at', '<=', $data['until']));
                    })
                    ->indicateUsing(function (array $data): array {
                        $badges = [];
                        if (filled($data['from'])) {
                            $badges[] = 'Dari: ' . Carbon::parse($data['from'])->format('d M Y');
                        }
                        if (filled($data['until'])) {
                            $badges[] = 'Sampai: ' . Carbon::parse($data['until'])->format('d M Y');
                        }
                        return $badges;
                    }),

                // Jika model pakai SoftDeletes
                TrashedFilter::make(),
            ], layout: FiltersLayout::AboveContent)
            ->recordActions([
                ActionGroup::make([
                    Action::make('activities')
                        ->label('Aktivitas')
                        ->icon('heroicon-m-clock')
                        ->color('primary')
                        ->visible(fn(): bool => auth()->user()?->hasRole('Superadmin'))
                        ->url(fn($record) => ProductResource::getUrl('activities', ['record' => $record])),
                    EditAction::make()
                        ->disabled(fn($record) => $record->transactions()->exists())
                        ->tooltip(fn($record) => $record->transactions()->exists()
                            ? 'Produk memiliki transaksi dan tidak bisa diperbaharui.'
                            : null),
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
                    RestoreAction::make(),
                    ReplicateAction::make()
                        ->mutateRecordDataUsing(function (array $data): array {
                            // contoh: generate SKU unik saat duplikasi
                            $data['sku'] = Product::generateUniqueSku($data['sku'] ?? ($data['brand'] . ' ' . $data['model'] . ' ' . $data['name'] ?? null));
                            return $data;
                        })
                        ->form([
                            Section::make([
                                TextInput::make('name')
                                    ->label('Nama Produk')
                                    ->required()
                                    ->maxLength(200),

                                TextInput::make('sku')
                                    ->label('SKU')
                                    ->required()
                                    ->maxLength(64)
                                    ->default(fn(array $data) => Product::generateUniqueSku($data['sku'] ?? ($data['brand'] . ' ' . $data['model'] . ' ' . $data['name'] ?? null)))
                                    ->unique(table: Product::class, column: 'sku'),

                                TextInput::make('brand')->label('Brand')->maxLength(100),
                                TextInput::make('model')->label('Model')->maxLength(100),
                            ])->columns(2)
                        ]),
                    ForceDeleteAction::make()
                ])
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
                        ->deselectRecordsAfterCompletion(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make()
                ]),
            ]);
    }
}
