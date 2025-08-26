@php
    use Carbon\Carbon;
    use Illuminate\Support\Arr;

    // Nama aplikasi dari .env
    $appName = config('app.name', env('APP_NAME', 'Aplikasi'));
    // Nomor/invoice id: coba ambil id_resi, lalu reference_number, lalu id
    $invoiceNo = $data->id_resi ?? (data_get($data, 'transaction.reference_number') ?? 'IM-' . $data->id);

    // Tanggal terbit: pakai transaction_date, fallback created_at
    $issuedAt = data_get($data, 'transaction.transaction_date') ?? ($data->created_at ?? null);
    $issuedAt = $issuedAt ? Carbon::parse($issuedAt)->format('d/m/Y') : '-';

    // Gudang & pihak terkait
    $fromWhName = data_get($data, 'from_warehouse.name', '-');
    $fromWhCode = data_get($data, 'from_warehouse.code', null);
    $toCsName = data_get($data, 'customer_name', '-');
    $toCsAddress = data_get($data, 'customer_full_address', null);

    // Pembuat / creator
    $creator = data_get($data, 'creator.name', data_get($data, 'creator.username', '-'));

    // Kumpulkan items:
    $details = collect(data_get($data, 'transaction.details', []));

    if ($details->isNotEmpty()) {
        // Normalisasi item dari transaction.details
        $items = $details->map(function ($row) {
            $qty = (float) data_get($row, 'quantity', data_get($row, 'qty', 0));
            $price = (float) data_get($row, 'price', data_get($row, 'unit_price', data_get($row, 'rate', 0)));
            $total = (float) data_get($row, 'total', $qty * $price);
            return [
                'name' =>
                    data_get($row, 'variant.product.name') ??
                    (data_get($row, 'variant.name') ?? data_get($row, 'product_name', '-')),
                'description' => data_get($row, 'notes', data_get($row, 'description', '')),
                'qty' => $qty,
                'price' => $price,
                'total' => $total,
            ];
        });
    } else {
        // Fallback: satu baris dari InventoryMovement::$variant
        $qty = (float) data_get(
            $data,
            'quantity',
            data_get($data, 'qty', data_get($data, 'qty_in', data_get($data, 'qty_out', 1))),
        );
        $price = (float) data_get($data, 'price', data_get($data, 'unit_price', 0));
        $items = collect([
            [
                'name' => data_get($data, 'variant.product.name') ?? data_get($data, 'variant.name', 'Item'),
                'description' => (string) data_get($data, 'notes', ''),
                'qty' => $qty,
                'price' => $price,
                'total' => $qty * $price,
            ],
        ]);
    }

    // Hitung ringkasan
    $subTotal = (float) $items->sum('total');
    $discount = (float) data_get($data, 'transaction.discount', 0);
    $tax = (float) data_get($data, 'transaction.tax', data_get($data, 'transaction.tax_amount', 0));
    $shipping = (float) data_get($data, 'transaction.shipping', data_get($data, 'transaction.shipping_cost', 0));
    $grand = max(0, $subTotal - $discount + $tax + $shipping);
    $paid = (float) data_get($data, 'transaction.paid', 0);
    $balance = max(0, $grand - $paid);

    // Format angka Rupiah sederhana (tanpa intl)
    $fmtRp = fn($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');

    // Terbilang opsional (intl jika tersedia)
    $terbilang = null;
    if (class_exists(\NumberFormatter::class)) {
        try {
            $nf = new \NumberFormatter('id_ID', \NumberFormatter::SPELLOUT);
            $terbilang = ucfirst($nf->format($grand)) . ' rupiah';
        } catch (\Throwable $e) {
            $terbilang = null;
        }
    }
@endphp
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>{{ $appName }} — Invoice {{ $invoiceNo }}</title>
    <style>
        :root {
            --fs-xs: 9px;
            --fs-sm: 10px;
            --fs-md: 11px;
            --fs-lg: 14px;
            --pad: 6px;
            --border: 1px solid #000;
        }

        @page {
            size: A4;
            margin: 14mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: var(--fs-sm);
            color: #000;
        }

        .container {
            width: 100%;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .brand-name {
            margin: 0;
            font-size: var(--fs-lg);
            font-weight: 700;
        }

        .doc-title {
            margin: 0;
            font-size: var(--fs-md);
        }

        .meta {
            text-align: right;
            font-size: var(--fs-xs);
        }

        hr {
            border: 0;
            border-top: 1px solid #000;
            margin: 8px 0 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: var(--border);
            padding: var(--pad);
            vertical-align: top;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-footer-group;
        }

        .no-border th,
        .no-border td {
            border: 0 !important;
            padding: 4px 0;
        }

        .muted {
            color: #444;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        .w-20 {
            width: 20%;
        }

        .w-30 {
            width: 30%;
        }

        .w-40 {
            width: 40%;
        }

        .w-50 {
            width: 50%;
        }

        .w-60 {
            width: 60%;
        }

        .page-footer {
            font-size: var(--fs-xs);
            margin-top: 8px;
            display: flex;
            justify-content: space-between;
        }

        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <div class="container">

        {{-- Tombol cetak saat preview (tidak muncul di PDF) --}}
        <button class="no-print" onclick="window.print()">Cetak / Print</button>

        {{-- Header --}}
        <div class="header">
            <div>
                <h1 class="brand-name">{{ $appName }}</h1>
                <p class="doc-title">INVOICE / RESI</p>
            </div>
            <div class="meta">
                <div>Dicetak: {{ now()->format('d/m/Y H:i') }}</div>
                <div>No: <strong>{{ $invoiceNo }}</strong></div>
            </div>
        </div>

        <hr>

        {{-- Informasi umum --}}
        <table class="no-border" style="margin-bottom: 8px;">
            <tr>
                <td class="w-50" style="padding-left:0;">
                    <table class="no-border">
                        <tr>
                            <td class="muted w-30">Tanggal</td>
                            <td>: {{ $issuedAt }}</td>
                        </tr>
                        @if ($terbilang)
                            <tr>
                                <td class="muted">Terbilang</td>
                                <td>: {{ $terbilang }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="muted">Dibuat oleh</td>
                            <td>: {{ $creator ?: '-' }}</td>
                        </tr>
                    </table>
                </td>
                <td class="w-50" style="padding-right:0;">
                    <table class="no-border">
                        <tr>
                            <td class="muted w-30">Dari Gudang</td>
                            <td>: {{ $fromWhName }}{{ $fromWhCode ? ' (' . $fromWhCode . ')' : '' }}</td>
                        </tr>
                        <tr>
                            <td class="muted">Ke Pembeli</td>
                            <td>: {{ $toCsName }}</td>
                        </tr>
                        <tr>
                            <td class="muted">Alamat Pembeli</td>
                            <td>: {{ $toCsAddress }}</td>
                        </tr>
                        <tr>
                            <td class="muted">Ref Transaksi</td>
                            <td>: {{ data_get($data, 'transaction.reference_number', '-') }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        {{-- Tabel item --}}
        <table>
            <thead>
                <tr>
                    <th style="width:28px;" class="center">No</th>
                    <th>Deskripsi</th>
                    <th style="width:70px;" class="right">Qty</th>
                    <th style="width:110px;" class="right">Harga</th>
                    <th style="width:120px;" class="right">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $i => $it)
                    @php
                        $qty = (float) ($it['qty'] ?? 0);
                        // tampilkan qty tanpa comma jika bulat, kalau tidak tampilkan 2 desimal
                        $qtyDisp =
                            fmod($qty, 1) === 0.0
                                ? number_format($qty, 0, ',', '.')
                                : rtrim(rtrim(number_format($qty, 2, ',', '.'), '0'), ',');
                    @endphp
                    <tr>
                        <td class="center">{{ $i + 1 }}</td>
                        <td>
                            <strong>{{ $it['name'] ?? '-' }}</strong>
                            @if (!empty($it['description']))
                                <div class="muted" style="margin-top:2px;">{!! nl2br(e($it['description'])) !!}</div>
                            @endif
                        </td>
                        <td class="right">{{ $qtyDisp }}</td>
                        <td class="right">{{ $fmtRp($it['price'] ?? 0) }}</td>
                        <td class="right">{{ $fmtRp($it['total'] ?? 0) }}</td>
                    </tr>
                @endforeach

                @if ($items->isEmpty())
                    <tr>
                        <td colspan="5" class="center">Tidak ada item</td>
                    </tr>
                @endif
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="right"><strong>Sub Total</strong></td>
                    <td class="right">{{ $fmtRp($subTotal) }}</td>
                </tr>
                @if ($discount > 0)
                    <tr>
                        <td colspan="4" class="right"><strong>Diskon</strong></td>
                        <td class="right">- {{ $fmtRp($discount) }}</td>
                    </tr>
                @endif
                @if ($tax > 0)
                    <tr>
                        <td colspan="4" class="right"><strong>Pajak</strong></td>
                        <td class="right">{{ $fmtRp($tax) }}</td>
                    </tr>
                @endif
                @if ($shipping > 0)
                    <tr>
                        <td colspan="4" class="right"><strong>Biaya Kirim</strong></td>
                        <td class="right">{{ $fmtRp($shipping) }}</td>
                    </tr>
                @endif
                <tr>
                    <td colspan="4" class="right"><strong>Grand Total</strong></td>
                    <td class="right"><strong>{{ $fmtRp($grand) }}</strong></td>
                </tr>
                @if ($paid > 0 || $balance > 0)
                    <tr>
                        <td colspan="4" class="right">Terbayar</td>
                        <td class="right">{{ $fmtRp($paid) }}</td>
                    </tr>
                    <tr>
                        <td colspan="4" class="right"><strong>Sisa Tagihan</strong></td>
                        <td class="right"><strong>{{ $fmtRp($balance) }}</strong></td>
                    </tr>
                @endif
            </tfoot>
        </table>

        {{-- Catatan --}}
        @php $note = data_get($data, 'transaction.notes') ?? data_get($data, 'notes'); @endphp
        @if ($note)
            <table class="no-border" style="margin-top:10px;">
                <tr>
                    <td class="muted" style="width:120px;">Catatan</td>
                    <td>
                        <div style="border:1px dashed #000; padding:6px; min-height:40px;">{!! nl2br(e($note)) !!}</div>
                    </td>
                </tr>
            </table>
        @endif

        {{-- Footer --}}
        <div class="page-footer">
            <div class="muted">Terima kasih atas kepercayaan Anda.</div>
            <div class="muted">Dokumen: {{ $appName }} • Invoice {{ $invoiceNo }}</div>
        </div>

    </div>
</body>

</html>
