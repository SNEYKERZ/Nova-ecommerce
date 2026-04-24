<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\ProductoImagenController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\PedidoController;
use App\Http\Controllers\Api\CarritoController;
use App\Http\Controllers\Api\OfertaController;
use App\Http\Controllers\Api\PlantillaController;
use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\SlideController;
use App\Http\Controllers\Api\ResenaController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Rutas API también necesitan resolver el tenant desde el subdomain
Route::middleware('tenant')->group(function () {

// ==================== BLOQUES HOME ====================

Route::get('/bloques-home', [\App\Http\Controllers\Api\BloqueHomeController::class, 'index']);
Route::post('/bloques-home', [\App\Http\Controllers\Api\BloqueHomeController::class, 'store']);
Route::post('/bloques-home/{id}/imagenes', [\App\Http\Controllers\Api\BloqueHomeController::class, 'storeImagen']);
Route::put('/bloques-home/{id}/imagenes/{imgId}', [\App\Http\Controllers\Api\BloqueHomeController::class, 'updateImagen']);
Route::delete('/bloques-home/{id}/imagenes/{imgId}', [\App\Http\Controllers\Api\BloqueHomeController::class, 'destroyImagen']);

// ==================== PRODUCTOS ====================

// Rutas públicas
Route::get('/productos', [ProductoController::class, 'index']);
Route::get('/productos/basicas', [ProductoController::class, 'basicas']);
Route::get('/productos/destacados', [ProductoController::class, 'destacados']);
Route::get('/productos/nuevos', [ProductoController::class, 'nuevos']);
Route::get('/productos/{id}', [ProductoController::class, 'show']);

// Imágenes de producto
Route::get('/productos/{producto}/imagenes', [ProductoImagenController::class, 'index']);

// ==================== CATEGORÍAS ====================

Route::get('/categorias', [CategoriaController::class, 'index']);
Route::get('/categorias/{id}', [CategoriaController::class, 'show']);
Route::get('/categorias/{id}/productos', [CategoriaController::class, 'productos']);

// ==================== PEDIDOS ====================

Route::post('/pedidos', [PedidoController::class, 'store']);
Route::get('/pedidos', [PedidoController::class, 'index']);
Route::get('/pedidos/{id}', [PedidoController::class, 'show']);

// ==================== OFERTAS ====================

Route::get('/ofertas', [OfertaController::class, 'index']);
Route::get('/ofertas/activas', [OfertaController::class, 'activas']);

// ==================== PLANTILLAS / TEMPORADAS ====================

Route::get('/plantillas/activa', [PlantillaController::class, 'activa']);
Route::get('/banners', [BannerController::class, 'index']);
Route::get('/slides', [SlideController::class, 'index']);

// ==================== RESEÑAS ====================

Route::get('/productos/{id}/resenas', [ResenaController::class, 'index']);
Route::post('/productos/{id}/resenas', [ResenaController::class, 'store']);

// ==================== ADMIN (PROTEGIDAS) ====================

Route::middleware('auth:sanctum')->group(function () {
    // Productos admin
    Route::post('/productos', [ProductoController::class, 'store']);
    Route::put('/productos/{id}', [ProductoController::class, 'update']);
    Route::delete('/productos/{id}', [ProductoController::class, 'destroy']);
    
    // Imágenes admin
    Route::post('/productos/{producto}/imagenes', [ProductoImagenController::class, 'store']);
    Route::put('/imagenes/{id}', [ProductoImagenController::class, 'update']);
    Route::delete('/imagenes/{id}', [ProductoImagenController::class, 'destroy']);

    // Categorías admin
    Route::post('/categorias', [CategoriaController::class, 'store']);
    Route::put('/categorias/{id}', [CategoriaController::class, 'update']);
    Route::delete('/categorias/{id}', [CategoriaController::class, 'destroy']);

    // Pedidos admin
    Route::put('/pedidos/{id}/estado', [PedidoController::class, 'updateEstado']);

    // Ofertas admin
    Route::apiResource('ofertas', OfertaController::class)->except(['index', 'show']);

    // Plantillas admin
    Route::apiResource('plantillas', PlantillaController::class);
    Route::apiResource('banners', BannerController::class);
    Route::apiResource('slides', SlideController::class);
    
    // Reseñas admin
    Route::get('/resenas/pendientes', [ResenaController::class, 'pendientes']);
    Route::put('/resenas/{id}/aprobar', [ResenaController::class, 'aprobar']);
});

}); // Fin grupo tenant
