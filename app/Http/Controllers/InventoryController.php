<?php

namespace App\Http\Controllers;

use App\Models\Inventory\InventoryMovement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function cetak_resi(int $id)
    {
        $data = InventoryMovement::with([
            'transaction',
            'transaction.details.variant.product',
            'from_warehouse',
            'to_warehouse',
            'variant',
            'creator',
        ])->find($id);

        if (!$data) {
            abort(404, 'Data not found');
        }
        $pdf = Pdf::loadView('pdf.packing-slip', compact('data'));
        $customPaper = array(0, 0, 684, 396);
        $pdf->setPaper($customPaper, 'portrait');

        return $pdf->stream('packing-slip_' . $data->id_resi . '.pdf');
    }
    public function cetak_invoice(int $id)
    {
        $data = InventoryMovement::with([
            'transaction',
            'transaction.details.variant.product',
            'from_warehouse',
            'to_warehouse',
            'variant.product',
            'creator',
        ])->find($id);

        if (!$data) {
            abort(404, 'Data not found');
        }

        $pdf = Pdf::loadView('pdf.invoice', compact('data'));
        return $pdf->stream('invoice_' . $data->id_resi . '.pdf');
    }
}
