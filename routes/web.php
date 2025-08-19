<?php

use App\Http\Controllers\InventoryController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('app');
});
Route::get('/cetak-resi', [InventoryController::class, 'cetak_resi'])
    ->name('inventory.cetak-resi');
Route::get('/cetak-invoice', [InventoryController::class, 'cetak_invoice'])
    ->name('inventory.cetak-invoice');

