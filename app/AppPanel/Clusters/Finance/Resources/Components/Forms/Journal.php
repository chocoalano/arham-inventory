<?php

namespace App\AppPanel\Clusters\Finance\Resources\Components\Forms;

use App\AppPanel\Clusters\Finance\Resources\Accounts\AccountResource;
use App\AppPanel\Clusters\Finance\Resources\CostCenters\CostCenterResource;
use App\AppPanel\Clusters\Finance\Resources\Periods\PeriodResource;
use App\Enums\JournalStatus;
use App\Models\Finance\Period;
use App\Models\Inventory\Payment;
use App\Models\Inventory\Transaction;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class Journal
{
    public static function forms(): array
    {
        return [
            Section::make('Header')
                ->description('Isi informasi utama jurnal. Pastikan tanggal, periode akuntansi, dan status sudah sesuai sebelum menyimpan.')
                ->schema([
                    TextInput::make('journal_no')
                        ->label('Nomor Jurnal')
                        ->helperText('Biarkan kosong jika nomor jurnal dibuat otomatis oleh service layer. Anda juga dapat menekan tombol "Generate" untuk mengisi nomor berbasis waktu (timestamp).')
                        ->maxLength(64)
                        ->unique(ignoreRecord: true)
                        ->suffixAction(
                            Action::make('generate')
                                ->label('Generate')
                                ->icon('heroicon-o-arrow-path-rounded-square')
                                ->tooltip('Buat nomor acak berbasis waktu (timestamp sekarang).')
                                ->action(fn($set) => $set('journal_no', now()->timestamp))
                        ),

                    DatePicker::make('journal_date')
                        ->label('Tanggal Jurnal')
                        ->required()
                        ->helperText('Tanggal terjadinya transaksi jurnal. Gunakan tanggal akuntansi yang benar agar masuk ke periode yang tepat.'),

                    Select::make('period_id')
                        ->label('Periode')
                        ->options(
                            fn() => Period::query()
                                ->orderBy('period_no')
                                ->get()
                                ->mapWithKeys(fn($p) => [$p->id => "{$p->fiscalYear->year}-{$p->period_no}"])
                                ->toArray()
                        )
                        ->searchable()
                        ->required()
                        ->helperText('Pilih periode akuntansi (tahun-bulan) tempat jurnal ini akan dibukukan. Pastikan sesuai dengan tanggal jurnal.')
                        ->suffixAction(
                            Action::make('periode')
                                ->label('Periode')
                                ->icon('heroicon-o-plus')
                                ->tooltip('Buat periode baru.')
                                ->url(fn(): string => PeriodResource::getUrl())
                        ),

                    Select::make('status')
                        ->label('Status')
                        // ✅ Lebih aman: mapping enum ke value => label
                        ->options(
                            collect(JournalStatus::cases())
                                ->mapWithKeys(fn($e) => [$e->value => ucfirst($e->value)])
                                ->toArray()
                        )
                        ->disabled(fn($record) => $record?->status === JournalStatus::Posted)
                        ->required()
                        ->helperText('Status jurnal: draft/posted. Setelah berstatus "posted", jurnal tidak dapat diubah. Gunakan draft saat masih dalam proses review.'),

                    Textarea::make('remarks')
                        ->label('Catatan')
                        ->rows(2)
                        ->helperText('Tambahkan keterangan singkat tentang tujuan atau konteks jurnal agar mudah ditelusuri di kemudian hari.')
                        ->columnSpan(2),

                    MorphToSelect::make('source') // asumsi relasi morphTo 'source' di model Journal
                        ->label('Sumber')
                        ->searchable()
                        ->preload()
                        ->types([
                            MorphToSelect\Type::make(Payment::class)
                                ->titleAttribute('id') // kolom dasar yg pasti ada
                                ->getOptionLabelFromRecordUsing(
                                    fn(Payment $record): string =>
                                    sprintf(
                                        'Invoice: %s • Rp %s',
                                        $record->invoice?->invoice_number ?? '—',
                                        number_format((float) ($record->amount ?? $record->total_amount ?? 0), 2, ',', '.')
                                    )
                                ),

                            MorphToSelect\Type::make(Transaction::class)
                                ->titleAttribute('reference_number') // kolom dasar
                                ->getOptionLabelFromRecordUsing(
                                    fn(Transaction $record): string =>
                                    sprintf(
                                        'No. %s • Tgl. %s • Jns. %s',
                                        $record->reference_number ?? '—',
                                        $record->transaction_date
                                        ? ($record->transaction_date instanceof \Carbon\Carbon
                                            ? $record->transaction_date->format('Y-m-d')
                                            : Carbon::parse((string) $record->transaction_date)->format('Y-m-d'))
                                        : '—',
                                        ucfirst((string) $record->type)
                                    )
                                ),
                        ])
                        ->columnSpan(2),
                ])
                ->columns(4)
                ->columnSpanFull(),

            Section::make('Lines')
                ->description('Masukkan baris jurnal (debit/kredit). Pastikan total debit dan total kredit seimbang (selisih = 0).')
                ->schema([
                    Repeater::make('lines')
                        ->relationship()
                        ->minItems(2)
                        ->columns(12)
                        ->schema([
                            Select::make('account_id')
                                ->label('Akun')
                                ->relationship('account', 'name')
                                ->searchable()
                                ->required()
                                ->helperText('Pilih akun buku besar (GL) yang tepat untuk baris ini. Contoh: Kas/Bank, Piutang Usaha, Penjualan, Beban, dll.')
                                ->suffixAction(
                                    Action::make('account')
                                        ->label('Akun')
                                        ->icon('heroicon-o-plus')
                                        ->tooltip('Buat akun baru.')
                                        ->url(fn(): string => AccountResource::getUrl())
                                )
                                ->columnSpan(4),

                            Select::make('cost_center_id')
                                ->label('Cost Center')
                                ->relationship('costCenter', 'name')
                                ->searchable()
                                ->helperText('Opsional. Gunakan untuk melacak biaya/pendapatan berdasarkan unit/divisi/proyek tertentu. Contoh: Produksi, Marketing, HR.')
                                ->suffixAction(
                                    Action::make('cost_center')
                                        ->label('Cost Center')
                                        ->icon('heroicon-o-plus')
                                        ->tooltip('Buat cost center baru.')
                                        ->url(fn(): string => CostCenterResource::getUrl())
                                )
                                ->columnSpan(3),

                            TextInput::make('description')
                                ->label('Deskripsi')
                                ->maxLength(255)
                                ->helperText('Keterangan singkat untuk baris ini (misalnya nomor dokumen sumber, referensi, atau penjelasan transaksi).')
                                ->columnSpan(5),

                            TextInput::make('debit')
                                ->label('Debit')
                                ->numeric()
                                ->prefix('Rp')
                                ->default(0)
                                ->helperText('Nilai debit dalam Rupiah. Isi 0 jika baris ini bukan debit. Pastikan hanya salah satu dari debit/kredit yang terisi (bukan keduanya).')
                                // 🔒 Validasi ringan: jika debit diubah > 0, nolkan kredit
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $state = (float) ($state ?? 0);
                                    if ($state > 0 && (float) ($get('credit') ?? 0) > 0) {
                                        $set('credit', 0);
                                    }
                                })
                                ->columnSpan(3),

                            TextInput::make('credit')
                                ->label('Kredit')
                                ->numeric()
                                ->prefix('Rp')
                                ->default(0)
                                ->helperText('Nilai kredit dalam Rupiah. Isi 0 jika baris ini bukan kredit. Pastikan hanya salah satu dari debit/kredit yang terisi (bukan keduanya).')
                                // 🔒 Validasi ringan: jika kredit diubah > 0, nolkan debit
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $state = (float) ($state ?? 0);
                                    if ($state > 0 && (float) ($get('debit') ?? 0) > 0) {
                                        $set('debit', 0);
                                    }
                                })
                                ->columnSpan(3),

                            TextInput::make('currency')
                                ->label('Mata Uang')
                                ->maxLength(3)
                                ->placeholder('IDR')
                                ->helperText('Kode mata uang 3 huruf (ISO). Contoh: IDR, USD, JPY. Biarkan "IDR" bila transaksi domestik.')
                                // UX: otomatis uppercase
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn($state, callable $set) => $set('currency', strtoupper((string) $state)))
                                ->columnSpan(2),

                            TextInput::make('fx_rate')
                                ->label('Kurs')
                                ->numeric()
                                ->placeholder('1.00000000')
                                ->helperText('Kurs yang digunakan untuk konversi ke Rupiah jika mata uang ≠ IDR. Biarkan 1 jika transaksi dalam IDR.')
                                ->columnSpan(4),
                        ])
                        ->reorderable(false)
                        ->addActionLabel('Tambah Baris')
                        ->helperText('Gunakan minimal 2 baris untuk mencatat debit dan kredit. Tambah baris sesuai kebutuhan transaksi.'),
                ])
                ->columnSpanFull(),

            Section::make('Ringkasan')
                ->description('Ringkasan total dari baris jurnal. Simpan hanya jika total debit = total kredit (selisih = 0).')
                ->schema([
                    Placeholder::make('total_debit')
                        ->label('Total Debit (Rp)')
                        ->content(fn($get) => number_format(
                            collect($get('lines') ?? [])->sum(fn($l) => (float) ($l['debit'] ?? 0)),
                            2,
                            ',',
                            '.'
                        ))
                        ->dehydrated(false),

                    Placeholder::make('total_credit')
                        ->label('Total Kredit (Rp)')
                        ->content(fn($get) => number_format(
                            collect($get('lines') ?? [])->sum(fn($l) => (float) ($l['credit'] ?? 0)),
                            2,
                            ',',
                            '.'
                        ))
                        ->dehydrated(false),

                    Placeholder::make('selisih')
                        ->label('Selisih (Debit - Kredit)')
                        ->content(function ($get) {
                            $d = collect($get('lines') ?? [])->sum(fn($l) => (float) ($l['debit'] ?? 0));
                            $c = collect($get('lines') ?? [])->sum(fn($l) => (float) ($l['credit'] ?? 0));
                            return number_format($d - $c, 2, ',', '.');
                        })
                        ->dehydrated(false),
                ])
                ->columns(3)
                ->collapsible()
                ->columnSpanFull(),
        ];
    }
}
