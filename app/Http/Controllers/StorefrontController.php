<?php

namespace App\Http\Controllers;

use App\Models\BloqueHome;
use App\Models\CatalogBanner;
use App\Models\Carrito;
use App\Models\Categoria;
use App\Models\Noticia;
use App\Models\Producto;
use App\Services\TenantManager;
use App\Traits\HasMediaUrls;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class StorefrontController extends Controller
{
    use HasMediaUrls;

    public function home(): Response
    {
        // El trait HasTenant filtra automáticamente por el store actual
        // Límite de 200 para evitar cargar catálogos enormes en memoria del cliente
        $productos = Producto::with('categoria')
            ->where('estado', 'DISPONIBLE')
            ->orderBy('id', 'desc')
            ->limit(200)
            ->get()
            ->map(fn(Producto $producto) => $this->mapProducto($producto));

        $categorias = Categoria::orderBy('categoria')
            ->get()
            ->map(fn(Categoria $categoria) => [
                'id' => $categoria->id,
                'nombre' => $categoria->categoria,
            ]);

        // Obtener bloques del home
        $bloques = $this->getBloquesHome();

        // Obtener info del store actual
        $tenantManager = app(TenantManager::class);
        $store = $tenantManager->getStore();

        return Inertia::render('HomePage', [
            'productos' => $productos,
            'categorias' => $categorias,
            'promociones' => Noticia::getPromocionesActivas(),
            'carousel' => $bloques[1] ?? null,
            'catalogBanners' => $this->getCatalogBanners(),
            'store' => $store ? [
                'nombre'    => $store->nombre,
                'logo'      => $store->logo_path ? asset('storage/' . $store->logo_path) : null,
                'telefono'  => $store->telefono,
                'email'     => $store->email,
                'bg_color'  => $store->bg_color ?? '#ffffff',
                'navbar_color' => $store->navbar_color ?? '#ffffff',
                'footer_color' => $store->footer_color ?? '#ffffff',
            ] : null,
        ]);
    }

    /**
     * Obtener bloques home configurables
     */
    private function getBloquesHome(): array
    {
        $result = [];

        for ($posicion = 1; $posicion <= 2; $posicion++) {
            $bloque = BloqueHome::getPorPosicion($posicion);

            if (!$bloque) {
                $result[$posicion] = null;
                continue;
            }

            $data = [
                'id' => $bloque->id,
                'tipo' => $bloque->tipo,
                'posicion' => $bloque->posicion,
                'titulo' => $bloque->titulo,
                'contenido' => $bloque->contenido,
                'tamano_texto' => $bloque->tamano_texto,
                'activo' => $bloque->activo,
            ];

            if ($bloque->tipo === 'banner') {
                $data['imagenes'] = $bloque->imagenes->map(fn($img) => [
                    'id' => $img->id,
                    'nombre' => $img->nombre,
                    'identificador' => $img->identificador,
                    'imagen' => $this->mediaUrl($img->imagen),
                    'url_destino' => $img->url_destino,
                    'orden' => $img->orden,
                ]);
            }

            $result[$posicion] = $data;
        }

        return $result;
    }

    private function getCatalogBanners(): array
    {
        return CatalogBanner::query()
            ->where('activo', true)
            ->orderBy('posicion')
            ->get()
            ->map(fn(CatalogBanner $banner) => [
                'id' => $banner->id,
                'nombre' => $banner->nombre,
                'identificador' => $banner->identificador,
                'posicion' => $banner->posicion,
                'imagen' => $this->mediaUrl($banner->imagen),
                'url_destino' => $banner->url_destino,
            ])
            ->values()
            ->all();
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

    public function galleries(): Response
    {
        $galleries = \App\Models\Gallery::where('activo', true)
            ->with(['imagenes' => function ($query) {
                $query->orderBy('orden');
            }])
            ->orderBy('orden')
            ->get()
            ->map(function ($gallery) {
                return [
                    'id' => $gallery->id,
                    'nombre' => $gallery->nombre,
                    'descripcion' => $gallery->descripcion,
                    'imagenes' => $gallery->imagenes->map(function ($imagen) {
                        return [
                            'id' => $imagen->id,
                            'imagen_url' => $this->mediaUrl($imagen->imagen),
                            'aspect_ratio' => $imagen->aspect_ratio,
                            'productos' => $imagen->productos()
                                ->with('producto.variantes')
                                ->orderBy('orden')
                                ->get()
                                ->map(fn($gip) => [
                                    'id' => $gip->id,
                                    'producto_id' => $gip->producto_id,
                                    'referencia' => $gip->producto->referencia,
                                    'nombre' => $gip->producto->nombre,
                                    'precio' => (float) $gip->producto->precio,
                                    'tallas' => $gip->producto->tallas_array,
                                    'colores' => $gip->producto->colores,
                                ])->values(),
                        ];
                    })->values(),
                ];
            });

        return Inertia::render('GalleryPage', [
            'galleries' => $galleries,
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
        $imagenPrincipal = $producto->foto ? asset('storage/' . $producto->foto) : asset('images/sinfoto.jpg');
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

                return str_starts_with($ruta, 'http') ? $ruta : asset('storage/' . $ruta);
            })->filter()->values();
        }

        return [
            'id'            => $producto->id,
            'sku'           => $producto->referencia,
            'nombre'        => $producto->nombre ?: $producto->referencia,
            'precio'        => (float) $producto->precio,
            'categoria'     => $producto->categoria?->categoria ?? 'General',
            'estado'        => $producto->estado,
            'tallas'        => $producto->tallas ? array_values(array_filter(explode(',', $producto->tallas))) : [],
            'foto'          => $imagenPrincipal,
            'descripcion'   => $producto->descripcion ?: null,
            'destacado'     => (bool) ($producto->getAttribute('destacado') ?? false),
            'nuevo'         => (bool) ($producto->getAttribute('nuevo') ?? false),
            'nuevaColeccion' => (bool) ($producto->getAttribute('nueva_coleccion') ?? false) || $isNewByDate,
            'createdAt'     => $producto->created_at?->toIso8601String(),
            'galeria'       => $galeria,
            'tieneStock'    => $producto->tiene_stock,
        ];
    }
}
