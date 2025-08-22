<?php

namespace App\AppPanel\Clusters\Settings\Resources\Users\Tables;

use App\AppPanel\Clusters\Settings\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;

use Filament\Tables;
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
                // Filter verified / unverified / any
                TernaryFilter::make('verified')
                    ->label('Email Verified')
                    ->placeholder('Semua')
                    ->trueLabel('Terverifikasi')
                    ->falseLabel('Belum verifikasi')
                    ->queries(
                        true: fn(Builder $query) => $query->whereNotNull('email_verified_at'),
                        false: fn(Builder $query) => $query->whereNull('email_verified_at'),
                        blank: fn(Builder $query) => $query
                    ),

                // Filter berdasarkan role (butuh relasi roles() di model User)
                SelectFilter::make('roles')
                    ->label('Role')
                    ->multiple()
                    ->preload()
                    ->relationship('roles', 'name'),

                // Filter domain email sederhana (@gmail.com, @company.com)
                Filter::make('email_domain')
                    ->label('Domain Email')
                    ->form([
                        TextInput::make('domain')
                            ->placeholder('contoh: gmail.com')
                            ->datalist(['gmail.com', 'yahoo.com', 'outlook.com']),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (filled($data['domain'] ?? null)) {
                            $domain = ltrim($data['domain'], '@');
                            $query->where('email', 'like', "%@{$domain}");
                        }
                    }),

                // Filter rentang tanggal dibuat
                Filter::make('created_between')
                    ->label('Dibuat antara')
                    ->form([
                        DatePicker::make('from')->native(false),
                        DatePicker::make('until')->native(false),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when(filled($data['from'] ?? null), fn($q) => $q->whereDate('created_at', '>=', $data['from']))
                            ->when(filled($data['until'] ?? null), fn($q) => $q->whereDate('created_at', '<=', $data['until']));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (filled($data['from'] ?? null)) {
                            $indicators[] = 'Dari: ' . \Illuminate\Support\Carbon::parse($data['from'])->format('d M Y');
                        }
                        if (filled($data['until'] ?? null)) {
                            $indicators[] = 'Sampai: ' . \Illuminate\Support\Carbon::parse($data['until'])->format('d M Y');
                        }
                        return $indicators;
                    }),

                // Aktifkan ini hanya jika model User pakai SoftDeletes
                // TrashedFilter::make(),
            ])

            ->recordActions([
                Action::make('activities')
                    ->label('Aktivitas')
                    ->icon('heroicon-m-clock')
                    ->visible(fn(): bool => auth()->user()?->hasRole('Superadmin'))
                    ->url(fn($record) => UserResource::getUrl('activities', ['record' => $record])),
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn(): bool => auth()->user()?->can('update', User::class) ?? true),
                DeleteAction::make(),
            ])

            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    // Aktifkan dua ini jika pakai SoftDeletes:
                    // RestoreBulkAction::make(),
                    // ForceDeleteBulkAction::make(),
                ]),
            ]);
    }
}
