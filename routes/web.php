<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\Api\CarritoController;
use App\Http\Controllers\StorefrontController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Rutas públicas de la tienda (requieren tenant)
Route::get('/', [StorefrontController::class, 'home'])->name('home');
Route::get('/carrito', [StorefrontController::class, 'cart'])->name('cart');
Route::get('/productos/{id}', [StorefrontController::class, 'product'])->name('product.show');
Route::get('/conocenos', [StorefrontController::class, 'about'])->name('about');
Route::get('/pedido/gracias/{id}', [StorefrontController::class, 'thankYou'])->name('order.thankyou');

// API del carrito
Route::prefix('api')->group(function () {
    Route::get('/carrito', [CarritoController::class, 'show']);
    Route::post('/carrito/agregar', [CarritoController::class, 'agregar']);
    Route::put('/carrito/actualizar/{id}', [CarritoController::class, 'actualizar']);
    Route::delete('/carrito/eliminar/{id}', [CarritoController::class, 'eliminar']);
    Route::delete('/carrito/vaciar', [CarritoController::class, 'vaciar']);
});

// Rutas de autenticación
Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
    Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');
});

// Rutas del Super Admin (sin necesidad de tenant - van al panel central)
Route::middleware(['auth', 'admin.role'])->prefix('super-admin')->group(function () {
    Route::get('/', [SuperAdminController::class, 'dashboard'])->name('superadmin.dashboard');
    
    // Gestión de tiendas
    Route::post('/stores', [SuperAdminController::class, 'store'])->name('superadmin.stores.store');
    Route::put('/stores/{store}', [SuperAdminController::class, 'updateStore'])->name('superadmin.stores.update');
    Route::delete('/stores/{store}', [SuperAdminController::class, 'destroyStore'])->name('superadmin.stores.destroy');
    Route::get('/stores/{store}/stats', [SuperAdminController::class, 'storeStats']);
    
    // Gestión de usuarios
    Route::post('/users', [SuperAdminController::class, 'storeUser'])->name('superadmin.users.store');
    Route::put('/users/{user}', [SuperAdminController::class, 'updateUser'])->name('superadmin.users.update');
    Route::delete('/users/{user}', [SuperAdminController::class, 'destroyUser'])->name('superadmin.users.destroy');
    Route::put('/users/{user}/password', [SuperAdminController::class, 'updateUserPassword']);

    // Reportes y notificaciones
    Route::get('/reports', [SuperAdminController::class, 'reports']);
    Route::get('/notifications', [SuperAdminController::class, 'notifications']);
    Route::post('/notifications/read', [SuperAdminController::class, 'markNotificationRead']);
});

// Rutas del Admin de tienda (requieren tenant)
Route::middleware(['auth', 'admin.role'])->prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

    Route::post('/productos', [AdminController::class, 'storeProduct'])->name('admin.products.store');
    Route::put('/productos/{producto}', [AdminController::class, 'updateProduct'])->name('admin.products.update');
    Route::delete('/productos/{producto}', [AdminController::class, 'deleteProduct'])->name('admin.products.delete');
    Route::post('/productos/{producto}/toggle-coleccion', [AdminController::class, 'toggleNuevaColeccion'])->name('admin.products.toggleColeccion');

    Route::post('/ofertas', [AdminController::class, 'storeOffer'])->name('admin.offers.store');
    Route::put('/ofertas/{oferta}', [AdminController::class, 'updateOffer'])->name('admin.offers.update');
    Route::delete('/ofertas/{oferta}', [AdminController::class, 'deleteOffer'])->name('admin.offers.delete');

    Route::put('/noticias', [AdminController::class, 'updateNews'])->name('admin.news.update');
    Route::post('/settings', [AdminController::class, 'updateStoreSettings'])->name('admin.settings.update');

    Route::post('/insumos', [AdminController::class, 'storeSupply'])->name('admin.supplies.store');
    Route::put('/insumos/{insumo}', [AdminController::class, 'updateSupply'])->name('admin.supplies.update');
    Route::delete('/insumos/{insumo}', [AdminController::class, 'deleteSupply'])->name('admin.supplies.delete');

    // Bloques del home
    Route::post('/bloques', [AdminController::class, 'updateBloque'])->name('admin.bloques.update');
    Route::post('/bloques/{id}/imagenes', [AdminController::class, 'storeBloqueImagen'])->name('admin.bloques.imagenes.store');
    Route::delete('/bloques/{id}/imagenes/{imgId}', [AdminController::class, 'destroyBloqueImagen'])->name('admin.bloques.imagenes.destroy');
});
