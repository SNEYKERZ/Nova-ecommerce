<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\GalleryController;
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
Route::get('/galerias', [StorefrontController::class, 'galleries'])->name('galleries');
Route::get('/conocenos', [StorefrontController::class, 'about'])->name('about');
Route::get('/pedido/gracias/{id}', [StorefrontController::class, 'thankYou'])->name('order.thankyou');

// API del carrito y utilidades
Route::prefix('api')->group(function () {
    Route::get('/carrito', [CarritoController::class, 'show']);
    Route::post('/carrito/agregar', [CarritoController::class, 'agregar']);
    Route::put('/carrito/actualizar/{id}', [CarritoController::class, 'actualizar']);
    Route::delete('/carrito/eliminar/{id}', [CarritoController::class, 'eliminar']);
    Route::delete('/carrito/vaciar', [CarritoController::class, 'vaciar']);
    Route::post('/carrito/aplicar-cupon', [CarritoController::class, 'aplicarCupon']);
    Route::delete('/carrito/quitar-cupon', [CarritoController::class, 'quitarCupon']);
    Route::post('/carrito/pedir-whatsapp', [CarritoController::class, 'pedirWhatsapp']);

    Route::get('/user/me', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        return response()->json($user ? [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ] : null);
    })->middleware('auth');
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

    // Impersonación
    Route::post('/users/{user}/impersonate', [SuperAdminController::class, 'impersonate'])->name('superadmin.users.impersonate');

    // Reportes y notificaciones
    Route::get('/reports', [SuperAdminController::class, 'reports']);
    Route::get('/notifications', [SuperAdminController::class, 'notifications']);
    Route::post('/notifications/read', [SuperAdminController::class, 'markNotificationRead']);
});

Route::post('/admin/leave-impersonation', [AdminAuthController::class, 'leaveImpersonation'])->name('admin.leave-impersonation');

// Rutas del Admin de tienda (requieren tenant)
// Ruta API de pedidos (usada por CartPage.vue)
Route::post('/api/pedidos', [\App\Http\Controllers\Api\PedidoController::class, 'store']);

Route::middleware(['auth', 'admin.role'])->prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('admin.dashboard');

    // TEST: Inertia rendering test
    Route::get('/test-inertia', function () {
        return \Inertia\Inertia::render('admin/TestPage', [
            'test_data' => 'Hello from server!',
        ]);
    })->name('admin.test-inertia');

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
    Route::post('/visual-assets', [AdminController::class, 'saveVisualAssets'])->name('admin.visual-assets.save');
    Route::post('/catalog-banners', [AdminController::class, 'upsertCatalogBanner'])->name('admin.catalog-banners.upsert');
    Route::delete('/catalog-banners/{posicion}', [AdminController::class, 'destroyCatalogBanner'])->name('admin.catalog-banners.destroy');

    // Cupones de descuento
    Route::post('/cupones', [AdminController::class, 'storeCupon'])->name('admin.cupones.store');
    Route::put('/cupones/{cupon}', [AdminController::class, 'updateCupon'])->name('admin.cupones.update');
    Route::delete('/cupones/{cupon}', [AdminController::class, 'deleteCupon'])->name('admin.cupones.delete');

    // Gestión de pedidos del admin de tienda
    Route::put('/pedidos/{pedido}/estado', [AdminController::class, 'updatePedidoEstado'])->name('admin.pedidos.estado');
    Route::put('/pedidos/{pedido}', [AdminController::class, 'updatePedido'])->name('admin.pedidos.update');
    Route::put('/pedidos/{pedido}/items/{item}', [AdminController::class, 'updatePedidoItem'])->name('admin.pedidos.items.update');
    Route::delete('/pedidos/{pedido}/items/{item}', [AdminController::class, 'deletePedidoItem'])->name('admin.pedidos.items.delete');
    Route::post('/pedidos/{pedido}/items', [AdminController::class, 'storePedidoItem'])->name('admin.pedidos.items.store');

    // Galerías
    Route::get('/galleries', [GalleryController::class, 'index'])->name('admin.galleries.index');
    Route::post('/galleries', [GalleryController::class, 'store'])->name('admin.galleries.store');
    Route::get('/galleries/{gallery}', [GalleryController::class, 'show'])->name('admin.galleries.show');
    Route::put('/galleries/{gallery}', [GalleryController::class, 'update'])->name('admin.galleries.update');
    Route::delete('/galleries/{gallery}', [GalleryController::class, 'destroy'])->name('admin.galleries.destroy');
    Route::post('/galleries/{gallery}/images', [GalleryController::class, 'addImage'])->name('admin.galleries.images.add');
    Route::put('/galleries/{gallery}/images/{imagen}', [GalleryController::class, 'updateImage'])->name('admin.galleries.images.update');
    Route::delete('/galleries/{gallery}/images/{imagen}', [GalleryController::class, 'deleteImage'])->name('admin.galleries.images.delete');
    Route::post('/galleries/{gallery}/images/{imagen}/products', [GalleryController::class, 'associateProduct'])->name('admin.galleries.images.products.associate');
    Route::delete('/galleries/{gallery}/images/{imagen}/products/{gip}', [GalleryController::class, 'disassociateProduct'])->name('admin.galleries.images.products.disassociate');
});

// TEST: simple JSON debug endpoint
Route::middleware(['auth', 'admin.role'])->prefix('admin')->get('/test-json', function (Illuminate\Http\Request $r) {
    $user = $r->user();
    $tm = app(App\Services\TenantManager::class);
    return response()->json([
        'ok' => true,
        'user_role' => $user?->role,
        'user_store' => $user?->store_id,
        'store' => $tm->getStore()?->only(['id', 'slug', 'nombre']),
    ]);
});
