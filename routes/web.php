<?php

use App\Http\Controllers\InventoryController;
use Illuminate\Support\Facades\Route;


Route::get('/cetak-resi/{id}', [InventoryController::class, 'cetak_resi'])
    ->name('inventory.cetak-resi');
Route::get('/cetak-invoice/{id}', [InventoryController::class, 'cetak_invoice'])
    ->name('inventory.cetak-invoice');


// Memperbaiki path file. Menggunakan '/' sebagai pemisah, bukan './'
require __DIR__ . '/ecommerce.php';
