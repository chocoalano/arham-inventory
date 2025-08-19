<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function cetak_resi(Request $request){
        dd($request->all());
    }
    public function cetak_invoice(Request $request){
        dd($request->all());
    }
}
