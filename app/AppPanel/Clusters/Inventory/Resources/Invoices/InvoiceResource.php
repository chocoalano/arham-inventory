<?php

namespace App\AppPanel\Clusters\Inventory\Resources\Invoices;

use App\AppPanel\Clusters\Inventory\InventoryCluster;
use App\AppPanel\Clusters\Inventory\Resources\Invoices\Pages\ListInvoiceActivities;
use App\AppPanel\Clusters\Inventory\Resources\Invoices\Pages\ManageInvoices;
use App\Models\Inventory\Invoice;
use App\Models\Inventory\Transaction;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;
    protected static ?string $cluster = InventoryCluster::class;
    protected static ?string $modelLabel = 'Faktur Penjualan';
    protected static ?string $navigationLabel = 'Faktur Penjualan';
    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }
        return $user->hasAnyPermission(['viewAny-invoice', 'view-invoice']);
    }
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Faktur')->columns(2)->schema([
                    Select::make('transaction_id')->label('Transaksi')
                        ->options(function () {
                            $user = Auth::user();
                            if (!$user) {
                                return [];
                            }

                            return Transaction::query()
                                ->forUser($user)
                                ->where('type', 'penjualan')
                                ->orderByDesc('id')
                                ->pluck('reference_number', 'id')
                                ->all();
                        })
                        ->searchable()->preload()->required()
                        ->live()
                        ->afterStateUpdated(function (Set $set, Get $get, $state) {
                            // Reset semua field jika transaksi dikosongkan
                            if (blank($state)) {
                                $set('invoice_number', null);
                                $set('issued_at', null);
                                $set('due_at', null);
                                $set('subtotal', 0);
                                $set('discount_total', 0);
                                $set('tax_total', 0);
                                $set('shipping_fee', 0);
                                $set('total_amount', 0);
                                $set('paid_amount', 0);
                                return;
                            }
                            $trx = Transaction::query()
                                ->with('invoice')
                                ->find($state);
                            if (!$trx) {
                                return;
                            }
                            if ($trx->invoice) {
                                $set('transaction_id', null);
                                $set('invoice_number', null);
                                $set('issued_at', null);
                                $set('due_at', null);
                                $set('subtotal', 0);
                                $set('discount_total', 0);
                                $set('tax_total', 0);
                                $set('shipping_fee', 0);
                                $set('total_amount', 0);
                                $set('paid_amount', 0);
                                Notification::make()
                                    ->title('Maaf, Anda tidak dapat membuat invoice baru untuk transaksi ini. Invoice sudah diterbitkan sebelumnya.')
                                    ->danger()
                                    ->send();
                            } else {
                                // 1) Generate nomor invoice baru (menimpa agar otomatis)
                                $prefix = 'INV-' . now()->format('Ymd') . '-';
                                $candidate = null;
                                for ($i = 0; $i < 5; $i++) {
                                    $try = $prefix . Str::upper(Str::random(6));
                                    if (!Invoice::query()->where('invoice_number', $try)->exists()) {
                                        $candidate = $try;
                                        break;
                                    }
                                }
                                $candidate ??= $prefix . Str::upper(Str::random(10));
                                $set('invoice_number', $candidate);

                                // 2) Tanggal terbit = tanggal transaksi (atau now), due = +30 hari
                                $issuedAt = $trx->transaction_date ?? now();
                                $set('issued_at', $issuedAt);
                                $set('due_at', \Illuminate\Support\Carbon::parse($issuedAt)->copy()->addDays(30));

                                // 3) Nilai invoice dari transaksi
                                $subtotal = (int) ($trx->grand_total ?? 0);
                                $set('subtotal', $subtotal);

                                // Normalisasi nilai lain (pertahankan jika sudah terisi, kalau kosong isi 0)
                                $discount = (int) ($get('discount_total') ?? 0);
                                $tax = (int) ($get('tax_total') ?? 0);
                                $shipping = (int) ($get('shipping_fee') ?? 0);

                                $set('discount_total', $discount);
                                $set('tax_total', $tax);
                                $set('shipping_fee', $shipping);

                                // 4) Hitung total & set paid_amount awal = 0
                                $total = max(0, $subtotal - $discount + $tax + $shipping);
                                $set('total_amount', $total);
                                $set('paid_amount', (int) ($get('paid_amount') ?? 0)); // boleh biarkan 0 atau pertahankan
                            }
                        }),

                    TextInput::make('invoice_number')->label('Nomor Invoice')
                        ->required()->unique(ignoreRecord: true),
                    DateTimePicker::make('issued_at')->label('Tanggal Terbit')->default(now()),
                    DateTimePicker::make('due_at')->label('Jatuh Tempo')->nullable(),
                ])->columnSpanFull(),

                Section::make('Detail Nilai Faktur')->columns(2)->schema([
                    // Mengubah label 'subtotal' menjadi 'Subtotal'
                    TextInput::make('subtotal')->label('Subtotal')->numeric()->prefix('Rp')->default(0)->required(),
                    // Mengubah label 'discount_total' menjadi 'Total Diskon'
                    TextInput::make('discount_total')->label('Total Diskon')->numeric()->prefix('Rp')->default(0),
                    // Mengubah label 'tax_total' menjadi 'Total Pajak'
                    TextInput::make('tax_total')->label('Total Pajak')->numeric()->prefix('Rp')->default(0),
                    // Mengubah label 'shipping_fee' menjadi 'Biaya Pengiriman'
                    TextInput::make('shipping_fee')->label('Biaya Pengiriman')->numeric()->prefix('Rp')->default(0),
                    // Mengubah label 'total_amount' menjadi 'Jumlah Total'
                    TextInput::make('total_amount')->label('Jumlah Total')->numeric()->prefix('Rp')->default(0)->helperText('Boleh diisi manual atau dihitung dari subtotal - diskon + pajak + ongkir.'),
                    // Mengubah label 'paid_amount' menjadi 'Jumlah Dibayar'
                    TextInput::make('paid_amount')->label('Jumlah Dibayar')->numeric()->prefix('Rp')->default(0),
                    // Mengubah label 'status_paid' menjadi 'Status Pembayaran'
                    Placeholder::make('status_paid')->label('Status Pembayaran')
                        ->content(fn(Get $get) => ((float) $get('paid_amount') >= (float) $get('total_amount')) ? 'Lunas' : 'Belum Lunas'),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        $query = Invoice::forUser(Auth::user())
            ->with(['transaction', 'payments']);
        return $table
            ->query($query)
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Nomor Invoice')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('transaction.reference_number')
                    ->label('Nomor Transaksi')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->label('Jumlah Total')
                    ->money('IDR')
                    ->summarize(Sum::make()->label('Total Invoice')),
                TextColumn::make('paid_amount')
                    ->label('Jumlah Dibayar')
                    ->money('IDR')
                    ->summarize(Sum::make()->label('Total Dibayar')),
                IconColumn::make('is_paid')
                    ->label('Status')
                    ->boolean()
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Tanggal Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // 1) Status Pembayaran (Lunas / Belum Lunas / Semua)
                TernaryFilter::make('is_paid')
                    ->label('Status Pembayaran')
                    ->trueLabel('Lunas')
                    ->falseLabel('Belum Lunas')
                    ->nullable(), // izinkan "Semua"

                // 2) Rentang Tanggal Dibuat
                Filter::make('created_between')
                    ->label('Rentang Tanggal')
                    ->form([
                        DatePicker::make('from')
                            ->label('Dari'),
                        DatePicker::make('until')
                            ->label('Sampai')
                            ->closeOnDateSelection(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn(Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'] ?? null, fn(Builder $q, $date) => $q->whereDate('created_at', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (!empty($data['from'])) {
                            $indicators[] = 'Dari: ' . \Illuminate\Support\Carbon::parse($data['from'])->isoFormat('D MMM Y');
                        }
                        if (!empty($data['until'])) {
                            $indicators[] = 'Sampai: ' . \Illuminate\Support\Carbon::parse($data['until'])->isoFormat('D MMM Y');
                        }
                        return $indicators;
                    }),

                // 3) Range Nominal Total (IDR)
                Filter::make('total_amount_range')
                    ->label('Range Total')
                    ->form([
                        TextInput::make('min')->label('Min')->numeric(),
                        TextInput::make('max')->label('Max')->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['min'] ?? null, fn(Builder $q, $v) => $q->where('total_amount', '>=', $v))
                            ->when($data['max'] ?? null, fn(Builder $q, $v) => $q->where('total_amount', '<=', $v));
                    })
                    ->indicateUsing(fn(array $data) => array_filter([
                        isset($data['min']) ? 'Min: ' . number_format((float) $data['min']) : null,
                        isset($data['max']) ? 'Max: ' . number_format((float) $data['max']) : null,
                    ])),

                // 5) Ada / Tidak Ada Pembayaran
                TernaryFilter::make('has_payments')
                    ->label('Memiliki Pembayaran')
                    ->queries(
                        true: fn(Builder $q) => $q->whereHas('payments'),
                        false: fn(Builder $q) => $q->doesntHave('payments'),
                        blank: fn(Builder $q) => $q
                    )
                    ->trueLabel('Ada')
                    ->falseLabel('Tidak Ada'),

                // 6) Preset Periode Cepat (Hari ini / Minggu ini / Bulan ini)
                Filter::make('periode')
                    ->label('Periode')
                    ->form([
                        Select::make('preset')
                            ->options([
                                'today' => 'Hari Ini',
                                'this_week' => 'Minggu Ini',
                                'this_month' => 'Bulan Ini',
                                'last_30' => '30 Hari Terakhir',
                            ])
                            ->native(false)
                            ->placeholder('— Pilih Periode —'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $preset = $data['preset'] ?? null;
                        if (!$preset)
                            return $query;

                        $today = now();
                        return match ($preset) {
                            'today' => $query->whereDate('created_at', $today->toDateString()),
                            'this_week' => $query->whereBetween('created_at', [$today->startOfWeek(), $today->endOfWeek()]),
                            'this_month' => $query->whereBetween('created_at', [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()]),
                            'last_30' => $query->where('created_at', '>=', $today->copy()->subDays(30)),
                            default => $query,
                        };
                    })
                    ->indicateUsing(function (array $data): array {
                        return match ($data['preset'] ?? null) {
                            'today' => ['Hari Ini'],
                            'this_week' => ['Minggu Ini'],
                            'this_month' => ['Bulan Ini'],
                            'last_30' => ['30 Hari Terakhir'],
                            default => [],
                        };
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('activities')
                        ->label('Aktivitas')
                        ->icon('heroicon-m-clock')
                        ->color('primary')
                        ->visible(fn(): bool => auth()->user()?->hasRole('Superadmin'))
                        ->url(fn($record): string => InvoiceResource::getUrl('activities', ['record' => $record])),
                    Action::make('cetak_invoice')
                        ->label('Cetak invoice')
                        ->url(fn($record): string => route('inventory.cetak-invoice', ['id' => $record->id]))
                        ->openUrlInNewTab()
                        ->icon('heroicon-m-printer')
                        ->visible(fn(): bool => auth()->user()->hasPermissionTo('viewAny-invoice')),
                    EditAction::make(),
                    DeleteAction::make(),
                ])
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
            'index' => ManageInvoices::route('/'),
            'activities' => ListInvoiceActivities::route('/{record}/activities'),
        ];
    }
}
