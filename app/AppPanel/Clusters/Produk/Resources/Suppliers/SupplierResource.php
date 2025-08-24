<?php

namespace App\AppPanel\Clusters\Produk\Resources\Suppliers;

use App\AppPanel\Clusters\Produk\ProdukCluster;
use App\AppPanel\Clusters\Produk\Resources\Suppliers\Pages\ListSupplierActivities;
use App\AppPanel\Clusters\Produk\Resources\Suppliers\Pages\ManageSuppliers;
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
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\App;
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
            ->components([
                Section::make('Data Pemasok')
                    ->columns(3)
                    ->schema([
                        TextInput::make('code')
                            ->label('Kode')
                            ->maxLength(32)
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->default(fn() => App::environment(['local', 'debug']) ? fake()->unique()->numerify('CODE-#####') : null),
                        TextInput::make('name')
                            ->label('Nama')
                            ->maxLength(150)
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->default(fn() => App::environment(['local', 'debug']) ? fake()->company() : null),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                        TextInput::make('contact_name')
                            ->label('PIC')
                            ->maxLength(150)
                            ->default(fn() => App::environment(['local', 'debug']) ? fake()->name() : null),
                        TextInput::make('phone')
                            ->label('Telepon')
                            ->maxLength(32)
                            ->default(fn() => App::environment(['local', 'debug']) ? fake()->phoneNumber() : null),
                        TextInput::make('email')
                            ->email()
                            ->default(fn() => App::environment(['local', 'debug']) ? fake()->unique()->safeEmail() : null),
                    ])
                    ->columnSpanFull(),
                Section::make('Alamat')
                    ->columns(3)
                    ->schema([
                        TextInput::make('address')
                            ->label('Alamat')
                            ->columnSpan(3)
                            ->default(fn() => App::environment(['local', 'debug']) ? fake()->streetAddress() : null),
                        TextInput::make('district')
                            ->label('Kecamatan')
                            ->default(fn() => App::environment(['local', 'debug']) ? fake()->citySuffix() : null),
                        TextInput::make('city')
                            ->label('Kota')
                            ->default(fn() => App::environment(['local', 'debug']) ? fake()->city() : null),
                        TextInput::make('province')
                            ->label('Provinsi')
                            ->default(fn() => App::environment(['local', 'debug']) ? fake()->state() : null),
                        TextInput::make('postal_code')
                            ->label('Kode Pos')
                            ->maxLength(16)
                            ->default(fn() => App::environment(['local', 'debug']) ? fake()->postcode() : null),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('lat')
                                    ->numeric()
                                    ->label('Latitude')
                                    ->default(fn() => App::environment(['local', 'debug']) ? fake()->latitude() : null),
                                TextInput::make('lng')
                                    ->numeric()
                                    ->label('Longitude')
                                    ->default(fn() => App::environment(['local', 'debug']) ? fake()->longitude() : null),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
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
                TrashedFilter::make(),
            ])
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
