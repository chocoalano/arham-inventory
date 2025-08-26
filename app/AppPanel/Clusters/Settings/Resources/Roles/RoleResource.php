<?php

namespace App\AppPanel\Clusters\Settings\Resources\Roles;

use App\AppPanel\Clusters\Settings\Resources\Roles\Pages\ListRoleActivities;
use App\AppPanel\Clusters\Settings\Resources\Roles\Pages\ManageRoles;
use App\AppPanel\Clusters\Settings\SettingsCluster;
use App\Models\RBAC\Permission;
use App\Models\RBAC\Role;
use BackedEnum;
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
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;
    protected static ?string $cluster = SettingsCluster::class;
    protected static ?string $recordTitleAttribute = 'Peran';
    protected static ?string $modelLabel = 'Peran Pengguna';
    protected static ?string $navigationLabel = 'Peran Pengguna';

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }
        return $user->hasAnyPermission(['viewAny-role', 'view-role']);
    }

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
                    ->limit(130)
                    ->searchable(),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Data Terhapus'),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('activities')
                        ->label('Aktivitas')
                        ->icon('heroicon-m-clock')
                        ->color('primary')
                        ->visible(fn(): bool => auth()->user()?->hasRole('Superadmin'))
                        ->url(fn($record) => RoleResource::getUrl('activities', ['record' => $record])),
                    Action::make('akses')
                        ->label('Atur Akses')
                        ->icon('heroicon-o-tag')
                        ->color('primary')
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
                    RestoreAction::make(),
                    ReplicateAction::make('replicate')
                        ->label('Duplikasi')
                        ->mutateRecordDataUsing(function (array $data): array {
                            // hasil duplikasi harus punya name unik
                            $data['name'] = Role::generateUniqueName($data['name'] ?? null);
                            // opsional: isi label human-friendly dari name
                            $data['label'] = Str::of($data['name'])->replace('-', ' ')->headline()->toString();
                            return $data;
                        })
                        ->form([
                            TextInput::make('name')
                                ->label('Nama Peran (unik)')
                                ->placeholder('mis: admin-sales')
                                ->helperText('Huruf kecil, angka, dan tanda minus. Akan dibuat unik otomatis saat duplikasi.')
                                ->required()
                                ->maxLength(64)
                                ->default(fn(array $data) => Role::generateUniqueName($data['name'] ?? null))
                                ->rule('regex:/^[a-z0-9-]+$/')
                                ->afterStateUpdated(fn($state, callable $set) => $set('name', Str::slug((string) $state, '-')))
                                ->unique(table: Role::class, column: 'name'),
                            TextInput::make('label')
                                ->label('Label')
                                ->placeholder('Administrator Sales')
                                ->maxLength(150)
                                ->default(fn(callable $get) => Str::of((string) $get('name'))
                                    ->replace('-', ' ')
                                    ->headline()
                                    ->toString()),
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

    public static function getPages(): array
    {
        return [
            'index' => ManageRoles::route('/'),
            'activities' => ListRoleActivities::route('/{record}/activities'),
        ];
    }
}
