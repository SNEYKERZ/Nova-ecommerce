<?php

namespace App\Http\Controllers;

use App\Models\Carrito;
use App\Models\Categoria;
use App\Models\Noticia;
use App\Models\Producto;
use Inertia\Inertia;
use Inertia\Response;

class StorefrontController extends Controller
{
    public function home(): Response
    {
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

        return Inertia::render('HomePage', [
            'productos' => $productos,
            'categorias' => $categorias,
            'promociones' => Noticia::getPromocionesActivas(),
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
        return Inertia::render('AboutPage');
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
            'destacado' => (bool) ($producto->destacado ?? false),
            'nuevo' => (bool) ($producto->nuevo ?? false),
            'galeria' => $galeria,
            'tieneStock' => $producto->tiene_stock,
        ];
    }
}
