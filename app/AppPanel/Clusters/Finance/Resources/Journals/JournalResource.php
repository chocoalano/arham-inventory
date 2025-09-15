<?php

namespace App\AppPanel\Clusters\Finance\Resources\Journals;

use App\AppPanel\Clusters\Finance\FinanceCluster;
use App\AppPanel\Clusters\Finance\Resources\Journals\Pages\ManageJournals;
use App\Enums\JournalStatus;
use App\Models\Finance\Journal;
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
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class JournalResource extends Resource
{
    protected static ?string $model = Journal::class;

    protected static string|BackedEnum|null $navigationIcon = "memory-journal";

    protected static ?string $cluster = FinanceCluster::class;

    protected static ?string $recordTitleAttribute = 'Journal';

    /** Label singular/plural di UI */
    public static function getModelLabel(): string
    {
        return 'Jurnal';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Jurnal';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(\App\AppPanel\Clusters\Finance\Resources\Components\Forms\Journal::forms());
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('journal_no'),
                TextEntry::make('journal_date')
                    ->date(),
                TextEntry::make('period_id')
                    ->numeric(),
                TextEntry::make('source_type'),
                TextEntry::make('source_id')
                    ->numeric(),
                TextEntry::make('status'),
                TextEntry::make('created_by')
                    ->numeric(),
                TextEntry::make('posted_by')
                    ->numeric(),
                TextEntry::make('posted_at')
                    ->dateTime(),
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
            ->recordTitleAttribute('Journal')
            ->columns([
                TextColumn::make('journal_no')->label('No')->searchable()->sortable(),
                TextColumn::make('journal_date')->date()->sortable(),
                TextColumn::make('period.fiscalYear.year')->label('FY'),
                TextColumn::make('period.starts_on')->label('Periode Mulai'),
                TextColumn::make('period.ends_on')->label('Periode Selesai'),
                BadgeColumn::make('status')->colors([
                    'warning' => JournalStatus::Draft->value,
                    'success' => JournalStatus::Posted->value,
                    'danger' => JournalStatus::Void->value,
                ]),
                TextColumn::make('source_type')->label('Source')->toggleable(),
                TextColumn::make('source_id')->toggleable(),
                TextColumn::make('updated_at')->since()->label('Update'),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('status')->options(
                    collect(JournalStatus::cases())->mapWithKeys(fn($e) => [$e->value => ucfirst($e->value)])->toArray()
                ),
                Filter::make('date_range')->form([
                    DatePicker::make('from'),
                    DatePicker::make('until'),
                ])->query(function ($query, array $data) {
                    return $query
                        ->when($data['from'] ?? null, fn($q, $v) => $q->whereDate('journal_date', '>=', $v))
                        ->when($data['until'] ?? null, fn($q, $v) => $q->whereDate('journal_date', '<=', $v));
                }),
                SelectFilter::make('period_id')->relationship('period', 'period_no')->label('Periode'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->modalWidth(Width::SevenExtraLarge)
                        ->slideOver(),
                    DeleteAction::make(),
                    ForceDeleteAction::make(),
                    RestoreAction::make(),
                    Action::make('post')
                        ->label('Post')
                        ->icon('heroicon-o-check-circle')
                        ->requiresConfirmation()
                        ->visible(fn(Journal $record) => $record->status === JournalStatus::Draft)
                        ->action(function (Journal $record) {
                            $period = $record->period;
                            if (!$period || $period->is_closed) {
                                Notification::make()->danger()->title('Gagal')->body('Period belum dipilih atau sudah ditutup.')->send();
                                return;
                            }
                            $debit = $record->lines()->sum('debit');
                            $credit = $record->lines()->sum('credit');
                            if (round($debit, 2) !== round($credit, 2)) {
                                Notification::make()->danger()->title('Tidak Seimbang')
                                    ->body("Debit ({$debit}) ≠ Credit ({$credit}).")->send();
                                return;
                            }
                            $record->update([
                                'status' => JournalStatus::Posted,
                                'posted_at' => now(),
                                'posted_by' => Auth::id(),
                            ]);
                            Notification::make()->success()->title('Journal Posted')->send();
                        }),
                    Action::make('unpost')
                        ->label('Unpost')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->visible(fn(Journal $record) => $record->status === JournalStatus::Posted)
                        ->action(function (Journal $record) {
                            // Implementasikan aturan bisnis unpost sesuai kebutuhan (cek lock, period closed, dll.)
                            if ($record->period?->is_closed) {
                                Notification::make()->danger()->title('Gagal')
                                    ->body('Period sudah ditutup. Tidak bisa Unpost.')->send();
                                return;
                            }
                            $record->update([
                                'status' => JournalStatus::Draft,
                                'posted_at' => null,
                                'posted_by' => null,
                            ]);
                            Notification::make()->success()->title('Journal Unposted')->send();
                        }),
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
            'index' => ManageJournals::route('/'),
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
