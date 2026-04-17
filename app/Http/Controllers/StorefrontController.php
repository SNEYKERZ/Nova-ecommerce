<?php

namespace App\Http\Controllers;

use App\Models\Carrito;
use App\Models\Categoria;
use App\Models\Noticia;
use App\Models\Producto;
use App\Services\TenantManager;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class StorefrontController extends Controller
{
    public function home(): Response
    {
        // El trait HasTenant filtra automáticamente por el store actual
        $productos = Producto::with('categoria')
            ->where('estado', 'DISPONIBLE')
            ->orderBy('id', 'desc')
            ->get()
            ->map(fn (Producto $producto) => $this->mapProducto($producto));

        $categorias = Categoria::orderBy('categoria')
            ->get()
            ->map(fn (Categoria $categoria) => [
                'id' => $categoria->id,
                'nombre' => $categoria->categoria,
            ]);

        // Obtener info del store actual
        $tenantManager = app(TenantManager::class);
        $store = $tenantManager->getStore();

        return Inertia::render('HomePage', [
            'productos' => $productos,
            'categorias' => $categorias,
            'promociones' => Noticia::getPromocionesActivas(),
            'store' => $store ? [
                'nombre' => $store->nombre,
                'logo' => $store->logo_path ? asset('storage/'.$store->logo_path) : null,
            ] : null,
        ]);
    }

    public function product(int $id): Response
    {
        $producto = Producto::with(['categoria', 'imagenes', 'variantes'])
            ->findOrFail($id);

        return Inertia::render('ProductPage', [
            'producto' => $this->mapProducto($producto, true),
        ]);
    }

    public function cart(): Response
    {
        $carrito = Carrito::getOrCreateCarrito();

        return Inertia::render('CartPage', [
            'carritoId' => $carrito->id,
        ]);
    }

    public function about(): Response
    {
        $tenantManager = app(TenantManager::class);
        $store = $tenantManager->getStore();

        return Inertia::render('AboutPage', [
            'store' => $store ? [
                'nombre' => $store->nombre,
                'descripcion' => $store->descripcion,
                'email' => $store->email,
                'telefono' => $store->telefono,
            ] : null,
        ]);
    }

    public function thankYou(int $id): Response
    {
        return Inertia::render('ThankYouPage', [
            'pedidoId' => $id,
        ]);
    }

    private function mapProducto(Producto $producto, bool $detalle = false): array
    {
        $imagenPrincipal = $producto->foto ? asset('storage/'.$producto->foto) : asset('images/sinfoto.jpg');
        $galeria = collect();
        $isNewByDate = $producto->created_at instanceof Carbon
            ? $producto->created_at->greaterThanOrEqualTo(now()->subDays(30))
            : false;

        if ($detalle && $producto->relationLoaded('imagenes')) {
            $galeria = $producto->imagenes->map(function ($imagen) {
                $ruta = $imagen->imagen ?? null;

                if (!$ruta) {
                    return null;
                }

                return str_starts_with($ruta, 'http') ? $ruta : asset('storage/'.$ruta);
            })->filter()->values();
        }

        return [
            'id' => $producto->id,
            'sku' => $producto->referencia,
            'precio' => (float) $producto->precio,
            'categoria' => $producto->categoria?->categoria ?? 'General',
            'estado' => $producto->estado,
            'tallas' => $producto->tallas ? array_values(array_filter(explode(',', $producto->tallas))) : [],
            'foto' => $imagenPrincipal,
            'descripcion' => $producto->descripcion ?: 'Producto versatil para una tienda e-commerce moderna.',
            'destacado' => (bool) ($producto->getAttribute('destacado') ?? false),
            'nuevo' => (bool) ($producto->getAttribute('nuevo') ?? false),
            'nuevaColeccion' => (bool) ($producto->getAttribute('nuevo') ?? false) || $isNewByDate,
            'createdAt' => $producto->created_at?->toIso8601String(),
            'galeria' => $galeria,
            'tieneStock' => $producto->tiene_stock,
        ];
    }
}
