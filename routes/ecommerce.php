<?php

use App\Http\Controllers\Ecommerce\AuthController;
use App\Http\Controllers\Ecommerce\CartController;
use App\Http\Controllers\Ecommerce\CheckoutController;
use App\Http\Controllers\Ecommerce\HomeController;
use App\Http\Controllers\Ecommerce\OrderController;
use App\Http\Controllers\Ecommerce\ProductController;
use App\Http\Controllers\Ecommerce\TransactionController;
use App\Http\Controllers\Ecommerce\WishlistController;
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
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::get('/', [AuthController::class, 'profile'])->name('profile');
        Route::post('/', [AuthController::class, 'profile_update'])->name('profile.update');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    });
    Route::prefix('cart')->name('cart.')->group(function () {
        Route::get('/', [CartController::class, 'index'])->name('index');
        Route::patch('/{cart}', [CartController::class, 'update'])->name('update');
        Route::patch('/{cart}/sync', [CartController::class, 'sync'])->name('sync');
        Route::post('/', [CartController::class, 'store'])->name('store');
        Route::delete('/{cart}/items/{item}', [CartController::class, 'destroyItem'])->name('items.destroy');
        Route::delete('/{cart}', [CartController::class, 'destroy'])->name('destroy');
    });
    Route::prefix('wishlist')->name('wishlist.')->group(function () {
        Route::get('/', [WishlistController::class, 'index'])->name('index');
        Route::post('/', [WishlistController::class, 'store'])->name('store');
        Route::delete('/{item}', [WishlistController::class, 'destroy'])->name('destroy');
    });

    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    // halaman menampilkan Snap JS jika perlu
    Route::get('/checkout/pay/{order}', fn ($order) => view('ecommerce.pages.auth.checkout-pay', compact('order')))->name('checkout.pay');

    Route::get('/transaction', [TransactionController::class, 'index'])
        ->name('transaction.view');
    Route::post('/transaction', [TransactionController::class, 'store'])
        ->name('transaction.store');

    Route::prefix('orders')->name('orders.')->group(function () {
        Route::post('/', [OrderController::class, 'update'])->name('update');
        Route::get('/{idOrRef}', [OrderController::class, 'show'])->name('show');
        Route::post('/{idOrRef}/snap', [OrderController::class, 'snap'])->name('snap');
    });
});

Route::get('/about', [HomeController::class, 'index'])
    ->name('ecommerce.about');
Route::get('/articles', [HomeController::class, 'articles'])
    ->name('ecommerce.articles');
Route::get('/articles/{slug}', [HomeController::class, 'articles_detail'])
    ->name('ecommerce.articles.detail');

// Midtrans callbacks
// Midtrans callbacks
Route::post('/midtrans/notification', [OrderController::class, 'notification'])->name('midtrans.notification');
Route::get('/midtrans/finish', [OrderController::class, 'finish'])->name('midtrans.finish');
Route::get('/midtrans/unfinish', [OrderController::class, 'unfinish'])->name('midtrans.unfinish');
Route::get('/midtrans/error', [OrderController::class, 'error'])->name('midtrans.error');
