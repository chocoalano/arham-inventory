@php
    use Carbon\Carbon;

    $appName = config('app.name', env('APP_NAME', 'Aplikasi')); // ambil dari .env
    $trxDate = data_get($data, 'transaction.transaction_date');
    $formattedDate = $trxDate ? Carbon::parse($trxDate)->format('d/m/Y H:i') : '-';
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $appName }} — Resi Barang Masuk/Keluar</title>
    <style>
        @page { margin: 12mm; }
        body { font-family: monospace; font-size: 10px; color: #000; }
        .container { width: 100%; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; }
        .brand h1 { margin: 0 0 2px 0; font-size: 14px; }
        .brand .subtitle { font-size: 12px; }
        .small { font-size: 9px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        td, th { padding: 4px; border: 1px solid #000; vertical-align: top; word-wrap: break-word; }
        .text-center { text-align: center; }
        .no-border td, .no-border th { border: none !important; padding: 2px 0; }
        .meta-table td:first-child { width: 110px; }
        .items th { background: #f5f5f5; }
        .sign td { height: 18px; }
        thead { display: table-header-group; } /* supaya header tabel muncul tiap halaman saat print */
        tfoot { display: table-footer-group; }

        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
<div class="container">

    <div class="header">
        <div class="brand">
            <h1>{{ $appName }}</h1>
            <div class="subtitle">Packing Slip</div>
        </div>
        <div class="small">
            Dicetak: {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>

    <table class="no-border meta-table" style="margin-top: 6px;">
        <tbody>
        <tr>
            <td>Tanggal</td>
            <td>: {{ $formattedDate }}</td>
        </tr>
        <tr>
            <td>ID Packing Slip</td>
            <td>: PS-{{ data_get($data, 'transaction.reference_number', '-') }}</td>
        </tr>
        <tr>
            <td>Gudang</td>
            <td>: {{ data_get($data, 'from_warehouse.name', '-') }}</td>
        </tr>
        <tr>
            <td>Kode Gudang</td>
            <td>: {{ data_get($data, 'from_warehouse.code', '-') }}</td>
        </tr>
        <tr>
            <td>Status Produk</td>
            <td>: {{ data_get($data, 'type', '-') }}</td>
        </tr>
        </tbody>
    </table>

    <br>

    <table class="items">
        <thead>
        <tr>
            <th style="width: 28px;" class="text-center">No</th>
            <th>Nama Barang</th>
            <th style="width: 70px;" class="text-center">Jumlah</th>
            <th>Keterangan</th>
        </tr>
        </thead>
        <tbody>
        @forelse (data_get($data, 'transaction.details', []) as $detail)
            <tr>
                <td class="text-center">{{ $loop->iteration }}</td>
                <td>
                    {{ data_get($detail, 'variant.product.name')
                        ?? data_get($detail, 'variant.name')
                        ?? data_get($detail, 'product_name', '-') }}
                </td>
                <td class="text-center">
                    {{ (int) (data_get($detail, 'quantity', data_get($detail, 'qty', 0))) }}
                </td>
                <td>
                    {{ data_get($detail, 'notes', data_get($detail, 'note', data_get($detail, 'description', '-'))) }}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="text-center">Tidak ada data produk</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <br>

    <table class="no-border sign">
        <tbody>
        <tr>
            <td style="width: 50%;">Penerima</td>
            <td style="width: 50%;">Pengirim</td>
        </tr>
        <tr><td style="height: 48px;"></td><td></td></tr>
        <tr>
            <td>( ____________ )</td>
            <td>( ____________ )</td>
        </tr>
        </tbody>
    </table>

</div>
</body>
</html>
