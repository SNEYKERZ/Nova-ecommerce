<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\StorefrontController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [StorefrontController::class, 'home'])->name('home');
Route::get('/carrito', [StorefrontController::class, 'cart'])->name('cart');
Route::get('/productos/{id}', [StorefrontController::class, 'product'])->name('product.show');
Route::get('/conocenos', [StorefrontController::class, 'about'])->name('about');
Route::get('/pedido/gracias/{id}', [StorefrontController::class, 'thankYou'])->name('order.thankyou');

Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
    Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');
});

Route::middleware(['auth', 'admin.role'])->prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

    Route::post('/productos', [AdminController::class, 'storeProduct'])->name('admin.products.store');
    Route::put('/productos/{producto}', [AdminController::class, 'updateProduct'])->name('admin.products.update');
    Route::delete('/productos/{producto}', [AdminController::class, 'deleteProduct'])->name('admin.products.delete');

    Route::post('/ofertas', [AdminController::class, 'storeOffer'])->name('admin.offers.store');
    Route::put('/ofertas/{oferta}', [AdminController::class, 'updateOffer'])->name('admin.offers.update');
    Route::delete('/ofertas/{oferta}', [AdminController::class, 'deleteOffer'])->name('admin.offers.delete');

    Route::put('/noticias', [AdminController::class, 'updateNews'])->name('admin.news.update');

    Route::post('/insumos', [AdminController::class, 'storeSupply'])->name('admin.supplies.store');
    Route::put('/insumos/{insumo}', [AdminController::class, 'updateSupply'])->name('admin.supplies.update');
    Route::delete('/insumos/{insumo}', [AdminController::class, 'deleteSupply'])->name('admin.supplies.delete');
});
