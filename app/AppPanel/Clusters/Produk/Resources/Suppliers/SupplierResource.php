<?php

namespace App\AppPanel\Clusters\Produk\Resources\Suppliers;

use App\AppPanel\Clusters\Produk\ProdukCluster;
use App\AppPanel\Clusters\Produk\Resources\Suppliers\Pages\ListSupplierActivities;
use App\AppPanel\Clusters\Produk\Resources\Suppliers\Pages\ManageSuppliers;
use App\AppPanel\Clusters\Produk\Resources\Suppliers\Schema\Form;
use App\Models\Inventory\Supplier;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserPlus;

    protected static ?string $cluster = ProdukCluster::class;

    protected static ?string $recordTitleAttribute = 'Pemasok';

    protected static ?string $modelLabel = 'Pemasok Produk';
    protected static ?string $navigationLabel = 'Pemasok Produk';
    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }
        return $user->hasAnyPermission(['viewAny-product', 'view-product']);
    }
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(Form::schemaForm());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('Pemasok')
            ->columns([
                TextColumn::make('code')->label('Kode')->searchable()->sortable(),
                TextColumn::make('name')->label('Gudang')->searchable()->sortable(),
                TextColumn::make('city')->label('Kota')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('province')->label('Provinsi')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('phone')->label('Telepon')->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')->label('Status')
                    ->boolean()
                    ->trueColor('success')->falseColor('gray'),
                TextColumn::make('created_at')->dateTime('d M Y H:i')->label('Dibuat')->sortable()->toggleable(),
            ])
            ->filters([
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
                Filter::make('payment_date')
                    ->form([
                        DatePicker::make('min_date')
                            ->label('Tanggal mulai'),
                        DatePicker::make('max_date')
                            ->label('Tanggal selesai'),
                    ])
                    ->columns(2)
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_date'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['max_date'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
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
                        ->url(fn($record) => SupplierResource::getUrl('activities', ['record' => $record])),
                    EditAction::make(),
                    DeleteAction::make(),
                    ForceDeleteAction::make(),
                    RestoreAction::make(),
                ])
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSuppliers::route('/'),
            'activities' => ListSupplierActivities::route('/{record}/activities'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
