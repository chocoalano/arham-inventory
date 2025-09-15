<?php

namespace App\AppPanel\Clusters\Finance\Resources\Accounts;

use App\AppPanel\Clusters\Finance\FinanceCluster;
use App\AppPanel\Clusters\Finance\Resources\Accounts\Pages\ManageAccounts;
use App\Enums\AccountType;
use App\Models\Finance\Account;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Wallet;

    protected static ?string $cluster = FinanceCluster::class;

    protected static ?string $recordTitleAttribute = 'Account';

    /** Label singular/plural di UI */
    public static function getModelLabel(): string
    {
        return 'Account';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Akun';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Akun')
                    ->description('Masukkan detail dasar untuk akun baru.')
                    ->schema([
                        TextInput::make('number')
                            ->label('Nomor Akun')
                            ->required()
                            ->maxLength(32)
                            // Memastikan nomor akun unik, mengabaikan record saat ini (untuk mode edit)
                            ->unique(ignoreRecord: true)
                            ->helperText('Masukkan nomor unik untuk akun ini. Contoh: 1010, 2100.')
                            // Menambahkan Aksi (tombol) di bawah input field
                            ->suffixAction(
                                Action::make('generate')
                                    ->label('Generate')
                                    ->icon('heroicon-o-arrow-path-rounded-square')
                                    ->tooltip('Buat nomor acak berbasis waktu')
                                    // Aksi ini akan mengisi field 'number' dengan timestamp
                                    ->action(fn($set) => $set('number', now()->timestamp))
                            ),

                        TextInput::make('name')
                            ->label('Nama Akun')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Nama yang deskriptif untuk akun, misal: Kas, Piutang Usaha.'),

                        Select::make('type')
                            ->label('Tipe')
                            // Mengambil semua case dari Enum AccountType dan mengubahnya menjadi array [value => Label]
                            // Contoh: ['asset' => 'Asset', 'liability' => 'Liability']
                            ->options(collect(AccountType::cases())->mapWithKeys(fn($e) => [$e->value => ucfirst($e->value)]))
                            ->required()
                            ->native(false) // Menggunakan select2 yang lebih modern
                            ->searchable()
                            ->helperText('Pilih tipe utama dari akun sesuai standar akuntansi.'),

                        TextInput::make('subtype')
                            ->label('Sub-tipe')
                            ->maxLength(64)
                            ->helperText('Kategori lebih spesifik (opsional), misal: Bank, Kas Kecil.'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(), // Mengubah layout menjadi 2 kolom agar lebih rapi di layar lebar

                // --- BAGIAN PENGATURAN TAMBAHAN ---
                Section::make('Pengaturan')
                    ->schema([
                        Toggle::make('is_postable')
                            ->label('Bisa diposting')
                            ->default(true)
                            ->helperText('Aktifkan jika akun ini bisa digunakan untuk mencatat transaksi jurnal.'),

                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->helperText('Nonaktifkan untuk menyembunyikan akun dari pilihan tanpa menghapusnya.'),
                    ])
                    ->columns(2)
                    ->columnSpanFull()
                    ->collapsible(), // Section ini bisa di-collapse (buka/tutup)
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('number'),
                TextEntry::make('name'),
                TextEntry::make('type'),
                TextEntry::make('subtype'),
                IconEntry::make('is_postable')
                    ->boolean(),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
                TextEntry::make('deleted_at')
                    ->dateTime(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('Account')
            ->columns([
                TextColumn::make('number')->label('No')->searchable()->sortable(),
                TextColumn::make('name')->label('Nama')->searchable()->wrap(),
                TextColumn::make('type')->badge()->sortable(),
                TextColumn::make('subtype')->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_postable')->boolean()->label('Postable'),
                IconColumn::make('is_active')->boolean()->label('Aktif'),
                TextColumn::make('updated_at')->dateTime()->since()->label('Update'),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('type')->options(
                    collect(AccountType::cases())->mapWithKeys(fn($e) => [$e->value => ucfirst($e->value)])->toArray()
                ),
                TernaryFilter::make('is_active')->label('Aktif'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
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
            'index' => ManageAccounts::route('/'),
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
