<?php

use App\Http\Controllers\Ecommerce\AboutController;
use App\Http\Controllers\Ecommerce\AuthController;
use App\Http\Controllers\Ecommerce\CartController;
use App\Http\Controllers\Ecommerce\CheckoutController;
use App\Http\Controllers\Ecommerce\HomeController;
use App\Http\Controllers\Ecommerce\OrderController;
use App\Http\Controllers\Ecommerce\ProductController;
use App\Http\Controllers\Ecommerce\ProfileController;
use App\Http\Controllers\Ecommerce\TransactionController;
use Illuminate\Support\Facades\Route;


Route::get('/', [HomeController::class, 'index'])
    ->name('ecommerce.index');
Route::prefix('products')->name('ecommerce.products.')->group(function () {
    Route::get('/', [ProductController::class, 'index'])
        ->name('index');
    Route::get('/{product:sku}', [ProductController::class, 'show'])
        ->name('show');
});
// Route yang hanya boleh diakses oleh customer yang BELUM login
Route::middleware(['guest:customer'])->group(function () {
    Route::get('/login-register', [AuthController::class, 'login_register'])
        ->name('login.register');
    Route::post('/login', [AuthController::class, 'login_submit'])
        ->name('login.submit');
    Route::post('/register', [AuthController::class, 'register_submit'])
        ->name('register.submit');
});
// Route yang hanya boleh diakses oleh customer yang SUDAH login
Route::middleware(['auth:customer'])->group(function () {
    // Route::get('/cart', [CartController::class, 'index'])
    //     ->name('cart.index');
    // Route::post('/cart', [CartController::class, 'store'])
    //     ->name('cart.store');
    // Route::patch('/cart/{cart}', [CartController::class, 'update'])
    //     ->name('cart.update');
    Route::prefix('cart')->name('cart.')->group(function () {
        Route::get('/', [CartController::class, 'index'])->name('index');

        // Otoritatif update satu item (by item_id / variant_id)
        Route::patch('/{cart}', [CartController::class, 'update'])->name('update');

        // Bulk sync semua item
        Route::patch('/{cart}/sync', [CartController::class, 'sync'])->name('sync');

        Route::post('/', [CartController::class, 'store'])->name('store');
        // Hapus 1 item
        Route::delete('/{cart}/items/{item}', [CartController::class, 'destroyItem'])->name('items.destroy');

        // Kosongkan keranjang
        Route::delete('/{cart}', [CartController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('checkout')->name('checkout.')->group(function () {
    Route::get('/',  [CheckoutController::class, 'index'])->name('index');
    Route::post('/', [CheckoutController::class, 'store'])->name('store');

        Route::view('place', 'ecommerce.pages.orders.placeorder')->name('place');
    });

    Route::get('/transaction', [TransactionController::class, 'index'])
        ->name('transaction.view');
    Route::post('/transaction', [TransactionController::class, 'store'])
        ->name('transaction.store');

    Route::middleware(['web','auth:customer'])->group(function () {
        Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::put('/profile', [ProfileController::class, 'updateProfile'])->name('profile.update');
        // Jika ada rute lama yang memanggil viewProfile():
        // Route::get('/profile-old', [ProfileController::class, 'viewProfile']);
        });

    Route::get('/orders', [OrderController::class, 'index'])
        ->name('order.view');
    Route::post('/orders', [OrderController::class, 'update'])
        ->name('order.update');

    Route::post('/logout', [ProductController::class, 'logout'])
        ->name('logout');
});

Route::get('/about', [AboutController::class, 'index'])
    ->name('ecommerce.about');
Route::get('/articles', [HomeController::class, 'articles'])
    ->name('ecommerce.articles');
