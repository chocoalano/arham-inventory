<?php

namespace App\AppPanel\Clusters\Settings\Resources\Users\Tables;

use App\AppPanel\Clusters\Settings\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;

use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TagsColumn;

use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        $query = Auth::user()->hasRole('Superadmin')
            ? User::query()
            : User::where('warehouse_id', Auth::user()->warehouse_id);
        return $table
            ->query($query)
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(
                        query: fn(Builder $query, string $search) =>
                        // Search khusus email agar tidak membebani kolom lain
                        $query->orWhere('email', 'like', "%{$search}%")
                    )
                    ->copyable()
                    ->toggleable(),

                // Status verifikasi email sebagai ikon boolean
                IconColumn::make('is_verified')
                    ->label('Verified')
                    ->boolean()
                    ->tooltip(fn($record) => $record->email_verified_at?->format('d M Y H:i'))
                    ->state(fn($record) => filled($record->email_verified_at))
                    ->toggleable(),

                // Tampilkan roles sebagai tags (jika ada relasi roles())
                TagsColumn::make('roles.name')
                    ->label('Roles')
                    ->separator(', ')
                    ->limitList(3)
                    ->toggleable(),

                TextColumn::make('warehouse.name')
                    ->label('Area Penugasan')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->since() // hover akan tampil “x waktu lalu”
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('updated_at')
                    ->label('Diubah')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
                // 1) Status verifikasi email
                TernaryFilter::make('verified')
                    ->label('Email Terverifikasi')
                    ->placeholder('Semua')
                    ->trueLabel('Terverifikasi')
                    ->falseLabel('Belum Terverifikasi')
                    ->queries(
                        true: fn(Builder $q) => $q->whereNotNull('email_verified_at'),
                        false: fn(Builder $q) => $q->whereNull('email_verified_at'),
                        blank: fn(Builder $q) => $q,
                    ),

                // 2) Role (butuh relasi roles() di model User)
                SelectFilter::make('roles')
                    ->label('Role')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload(),

                // 3) Domain email (gmail.com, company.com, dll.)
                Filter::make('email_domain')
                    ->label('Domain Email')
                    ->form([
                        TextInput::make('domain')
                            ->placeholder('contoh: gmail.com')
                            ->datalist(['gmail.com', 'yahoo.com', 'outlook.com']),
                    ])
                    ->query(function (Builder $q, array $data) {
                        $domain = trim(ltrim($data['domain'] ?? '', '@'));
                        if ($domain !== '') {
                            $q->where('email', 'like', "%@{$domain}");
                        }
                    })
                    ->indicateUsing(fn(array $data) => filled($data['domain'] ?? null)
                        ? ['Domain: ' . ltrim($data['domain'], '@')]
                        : []),

                // 4) Rentang tanggal dibuat
                Filter::make('created_between')
                    ->label('Dibuat Antara')
                    ->form([
                        DatePicker::make('from')->label('Dari')->native(false),
                        DatePicker::make('until')->label('Sampai')->native(false),
                    ])->columns(2)
                    ->query(function (Builder $q, array $data) {
                        return $q
                            ->when(filled($data['from'] ?? null), fn($qq) => $qq->whereDate('created_at', '>=', $data['from']))
                            ->when(filled($data['until'] ?? null), fn($qq) => $qq->whereDate('created_at', '<=', $data['until']));
                    })
                    ->indicateUsing(function (array $data): array {
                        $badges = [];
                        if (filled($data['from'] ?? null)) {
                            $badges[] = 'Dari: ' . Carbon::parse($data['from'])->format('d M Y');
                        }
                        if (filled($data['until'] ?? null)) {
                            $badges[] = 'Sampai: ' . Carbon::parse($data['until'])->format('d M Y');
                        }
                        return $badges;
                    }),

                // 5) Terhapus (SoftDeletes)
                // Menampilkan: Tanpa Terhapus / Hanya Terhapus / Dengan Terhapus
                TrashedFilter::make()
                    ->label('Data Terhapus'),
            ], layout: FiltersLayout::AboveContent)

            ->recordActions([
                ActionGroup::make([
                    Action::make('activities')
                        ->label('Aktivitas')
                        ->icon('heroicon-m-clock')
                        ->visible(fn(): bool => auth()->user()?->hasRole('Superadmin'))
                        ->color('primary')
                        ->url(fn($record) => UserResource::getUrl('activities', ['record' => $record])),
                    ViewAction::make()->color('primary'),
                    EditAction::make()
                        ->visible(fn(): bool => auth()->user()?->can('update', User::class) ?? true),
                    DeleteAction::make(),
                    RestoreAction::make(),
                    ReplicateAction::make()
                        ->mutateRecordDataUsing(function (array $data): array {
                            $data['email'] = User::generateUniqueEmail($data['email'] ?? null); // ← pakai method model
                            return $data;
                        })
                        ->form([
                            TextInput::make('email')
                                ->label('Alamat Email')
                                ->helperText('Masukkan alamat email aktif yang valid.')
                                ->email()
                                ->required()
                                ->default(fn(array $data) => User::generateUniqueEmail($data['email'] ?? null))
                                ->unique(table: User::class, column: 'email'),
                        ]),
                    ForceDeleteAction::make()
                ])
            ])

            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make()
                ]),
            ]);
    }
}
