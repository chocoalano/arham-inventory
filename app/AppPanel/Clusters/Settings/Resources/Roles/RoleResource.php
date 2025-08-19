<?php

namespace App\AppPanel\Clusters\Settings\Resources\Roles;

use App\AppPanel\Clusters\Settings\Resources\Roles\Pages\ManageRoles;
use App\AppPanel\Clusters\Settings\SettingsCluster;
use App\Models\RBAC\Permission;
use App\Models\RBAC\Role;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $recordTitleAttribute = 'Peran';
    protected static ?string $modelLabel = 'Peran Pengguna';
    protected static ?string $navigationLabel = 'Peran Pengguna';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Peran')
                    ->helperText('Nama unik untuk peran (contoh: admin, editor).')
                    ->required()
                    ->maxLength(50),

                TextInput::make('label')
                    ->label('Label Tampilan')
                    ->helperText('Nama yang lebih mudah dibaca untuk peran (contoh: Administrator).')
                    ->required()
                    ->maxLength(50),

                Textarea::make('desc')
                    ->label('Deskripsi')
                    ->helperText('Penjelasan singkat tentang hak akses dan fungsi peran ini.')
                    ->required()
                    ->columnSpanFull()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('Role')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('label')
                    ->searchable(),
                TextColumn::make('desc')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('akses')
                    ->label('Atur Akses')
                    ->modalWidth(Width::FiveExtraLarge)
                    ->modalHeading('Atur Hak Akses untuk Peran')
                    ->modalSubmitActionLabel('Simpan Perubahan')
                    ->mountUsing(function (Schema $form, Model $record) {
                        // semua permission yang dimiliki role ini
                        $selectedIds = $record->permissions()->pluck('id')->map(fn($id) => (string) $id);

                        // kelompokkan permission berdasarkan label grup
                        $grouped = Permission::with('group')
                            ->orderBy('permission_group_id')
                            ->orderBy('label')
                            ->get()
                            ->groupBy(fn($p) => $p->group?->label ?? 'Lainnya');

                        $state = ['permissions_by_group' => []];

                        foreach ($grouped as $groupLabel => $perms) {
                            $groupKey = Str::slug($groupLabel);
                            $idsInGrp = $perms->pluck('id')->map(fn($id) => (string) $id);

                            // simpan hanya yang termasuk group ini
                            $state['permissions_by_group'][$groupKey] = $selectedIds
                                ->intersect($idsInGrp)
                                ->values()
                                ->all();
                        }

                        $form->fill($state);
                    })

                    ->form(function (Model $record) {
                        // build schema (tanpa set state lagi)
                        $grouped = Permission::with('group')
                            ->orderBy('permission_group_id')
                            ->orderBy('label')
                            ->get()
                            ->groupBy(fn($p) => $p->group?->label ?? 'Lainnya');

                        $schemas = [];

                        foreach ($grouped as $groupLabel => $perms) {
                            $groupKey = Str::slug($groupLabel);
                            $options = $perms->pluck('label', 'id')
                                ->mapWithKeys(fn($label, $id) => [(string) $id => $label])
                                ->toArray();

                            $schemas[] = Fieldset::make($groupLabel)
                                ->schema([
                                    CheckboxList::make("permissions_by_group.$groupKey")
                                        ->options($options)
                                        ->columns(2)
                                        ->bulkToggleable(),
                                ])
                                ->columns(1);
                        }

                        return [
                            Section::make('Daftar Hak Akses')
                                ->description('Pilih izin yang akan diberikan kepada peran ini.')
                                ->schema($schemas)
                                ->columns([
                                    'default' => 1,
                                    'sm' => 2,
                                    'md' => 2,
                                    'lg' => 3,
                                    'xl' => 2,
                                    '2xl' => 2,
                                ]),
                        ];
                    })

                    ->action(function (Model $record, array $data) {
                        // gabungkan semua pilihan dari tiap group
                        $selected = collect($data['permissions_by_group'] ?? [])
                            ->flatten()
                            ->filter()
                            ->unique()
                            ->map(fn($id) => (int) $id) // kembalikan ke int sebelum sync
                            ->values()
                            ->all();

                        $record->permissions()->sync($selected);
                    }),
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
            'index' => ManageRoles::route('/'),
        ];
    }
}
