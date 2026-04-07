<?php

use Illuminate\Support\Facades\Route;
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
