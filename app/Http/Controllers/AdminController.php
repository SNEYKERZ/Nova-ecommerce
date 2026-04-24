<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Insumo;
use App\Models\Noticia;
use App\Models\Oferta;
use App\Models\Producto;
use App\Models\ProductoImagen;
use App\Models\ProductoVariante;
use App\Models\Store;
use App\Services\TenantManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function dashboard(Request $request): Response
    {
        // Obtener el store actual desde el TenantManager
        $tenantManager = app(TenantManager::class);
        $store = $tenantManager->getStore();

        // Si es super admin y no hay store específico, mostrar dashboard diferente
        // o redirigir a la gestión de stores
        
        $settings = $store ? $store : null;

        $search = $request->input('search', '');
        $perPage = 10;

        // Productos con búsqueda y paginación
        $productos = Producto::with(['categoria', 'variantes', 'imagenes'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('referencia', 'like', "%{$search}%")
                      ->orWhere('nombre', 'like', "%{$search}%")
                      ->orWhere('codigo', 'like', "%{$search}%")
                      ->orWhereHas('categoria', fn ($cq) => $cq->where('categoria', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage)->withQueryString();

        // Mapear datos manteniendo la paginación
        $productos->getCollection()->transform(function (Producto $producto) {
            return [
                'id' => $producto->id,
                'referencia' => $producto->referencia,
                'precio' => (float) $producto->precio,
                'categoria_id' => $producto->categoria_id,
                'categoria' => $producto->categoria?->categoria,
                'tallas' => $producto->tallas,
                'estado' => $producto->estado,
                'nueva_coleccion' => (bool) $producto->nueva_coleccion,
                'stock_total' => (int) $producto->variantes->sum('stock'),
                'stock_por_talla' => $producto->variantes->map(fn ($variante) => [
                    'id' => $variante->id,
                    'talla' => $variante->talla,
                    'stock' => (int) $variante->stock,
                ])->values(),
                'imagenes' => $producto->imagenes->sortBy('orden')->map(function (ProductoImagen $imagen) {
                    return [
                        'id' => $imagen->id,
                        'url' => asset('storage/'.$imagen->imagen),
                        'es_principal' => (bool) $imagen->es_principal,
                    ];
                })->values(),
            ];
        });

        return Inertia::render('admin/AdminDashboardPage', [
            'stats' => [
                'productos' => Producto::count(),
                'insumos' => Insumo::count(),
                'ofertas' => Oferta::count(),
                'ofertasActivas' => Oferta::where('esta_activa', true)->count(),
            ],
            'categorias' => Categoria::orderBy('categoria')->get(['id', 'categoria']),
            'productos' => $productos,
            'insumos' => $this->paginateAndMap(Insumo::class, $search, [
                'sku' => 'sku',
                'nombre' => 'nombre', 
                'codigo' => 'codigo',
                'referencia' => 'referencia',
            ], $perPage),
            'settings' => [
                'store_name' => $settings?->nombre ?? 'Mi Tienda',
                'logo_url' => $settings?->logo_path ? asset('storage/'.$settings->logo_path) : null,
            ],
            'ofertas' => $this->paginateAndMapOfertas($search, $perPage),
            'noticia' => Noticia::first()?->campos_adicionales ?? '',
            'bloques' => $this->getBloques(),
            'currentStore' => $store ? [
                'id' => $store->id,
                'slug' => $store->slug,
                'nombre' => $store->nombre,
            ] : null,
        ]);
    }

    public function storeProduct(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'referencia' => ['required', 'string', 'max:255', 'unique:productos,referencia'],
            'precio' => ['required', 'numeric', 'min:0'],
            'categoria_id' => ['required', 'exists:categorias,id'],
            'tallas' => ['nullable', 'string'],
            'estado' => ['required', 'in:DISPONIBLE,NO_DISPONIBLE'],
            'nueva_coleccion' => ['nullable', 'boolean'],
            'stock_por_talla' => ['nullable', 'array'],
            'stock_por_talla.*.talla' => ['required', 'string', 'max:40'],
            'stock_por_talla.*.stock' => ['required', 'integer', 'min:0'],
            'images' => ['nullable', 'array', 'max:4'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        DB::transaction(function () use ($validated, $request) {
            $producto = Producto::create([
                'referencia' => $validated['referencia'],
                'precio' => $validated['precio'],
                'categoria_id' => $validated['categoria_id'],
                'tallas' => $validated['tallas'] ?? null,
                'estado' => $validated['estado'],
                'nueva_coleccion' => $validated['nueva_coleccion'] ?? false,
            ]);

            $this->syncStockBySize($producto, $validated['stock_por_talla'] ?? []);
            $this->syncProductImages($producto, $request, false);
        });

        return response()->json(['success' => true, 'message' => 'Producto creado.']);
    }

    public function updateProduct(Request $request, Producto $producto): JsonResponse
    {
        $validated = $request->validate([
            'referencia' => ['required', 'string', 'max:255', 'unique:productos,referencia,'.$producto->id],
            'precio' => ['required', 'numeric', 'min:0'],
            'categoria_id' => ['required', 'exists:categorias,id'],
            'tallas' => ['nullable', 'string'],
            'estado' => ['required', 'in:DISPONIBLE,NO_DISPONIBLE'],
            'nueva_coleccion' => ['nullable', 'boolean'],
            'stock_por_talla' => ['nullable', 'array'],
            'stock_por_talla.*.talla' => ['required', 'string', 'max:40'],
            'stock_por_talla.*.stock' => ['required', 'integer', 'min:0'],
            'images' => ['nullable', 'array', 'max:4'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        DB::transaction(function () use ($validated, $request, $producto) {
            $producto->update([
                'referencia' => $validated['referencia'],
                'precio' => $validated['precio'],
                'categoria_id' => $validated['categoria_id'],
                'tallas' => $validated['tallas'] ?? null,
                'estado' => $validated['estado'],
                'nueva_coleccion' => $validated['nueva_coleccion'] ?? false,
            ]);

            $this->syncStockBySize($producto, $validated['stock_por_talla'] ?? []);
            $this->syncProductImages($producto, $request, true);
        });

        return response()->json(['success' => true, 'message' => 'Producto actualizado.']);
    }

    public function deleteProduct(Producto $producto): JsonResponse
    {
        $paths = $producto->imagenes()->pluck('imagen')->toArray();
        if (!empty($paths)) {
            Storage::disk('public')->delete($paths);
        }
        $producto->delete();

        return response()->json(['success' => true, 'message' => 'Producto eliminado.']);
    }

    /**
     * Toggle nueva colección desde la lista de productos
     */
    public function toggleNuevaColeccion(Producto $producto): JsonResponse
    {
        $producto->update(['nueva_coleccion' => !$producto->nueva_coleccion]);

        return response()->json([
            'success' => true,
            'nueva_coleccion' => $producto->nueva_coleccion,
            'message' => $producto->nueva_coleccion ? 'Marcado como nueva colección' : 'Desmarcado de nueva colección',
        ]);
    }

    public function storeOffer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'producto_id' => ['nullable', 'exists:productos,id'],
            'categoria_id' => ['nullable', 'exists:categorias,id'],
            'descuento_porcentaje' => ['nullable', 'numeric', 'min:0'],
            'descuento_fijo' => ['nullable', 'numeric', 'min:0'],
            'precio_oferta' => ['nullable', 'numeric', 'min:0'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after:fecha_inicio'],
            'esta_activa' => ['required', 'boolean'],
        ]);

        if (empty($validated['producto_id']) && empty($validated['categoria_id'])) {
            return response()->json(['success' => false, 'message' => 'Selecciona producto o categoria.'], 422);
        }

        Oferta::create($validated);

        return response()->json(['success' => true, 'message' => 'Promocion creada.']);
    }

    public function updateOffer(Request $request, Oferta $oferta): JsonResponse
    {
        $validated = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'producto_id' => ['nullable', 'exists:productos,id'],
            'categoria_id' => ['nullable', 'exists:categorias,id'],
            'descuento_porcentaje' => ['nullable', 'numeric', 'min:0'],
            'descuento_fijo' => ['nullable', 'numeric', 'min:0'],
            'precio_oferta' => ['nullable', 'numeric', 'min:0'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after:fecha_inicio'],
            'esta_activa' => ['required', 'boolean'],
        ]);

        if (empty($validated['producto_id']) && empty($validated['categoria_id'])) {
            return response()->json(['success' => false, 'message' => 'Selecciona producto o categoria.'], 422);
        }

        $oferta->update($validated);

        return response()->json(['success' => true, 'message' => 'Promocion actualizada.']);
    }

    public function deleteOffer(Oferta $oferta): JsonResponse
    {
        $oferta->delete();

        return response()->json(['success' => true, 'message' => 'Promocion eliminada.']);
    }

    public function updateNews(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'campos_adicionales' => ['nullable', 'string'],
        ]);

        // Solo actualizar si hay una noticia existente (usa el trait para filtrar por store)
        $noticia = Noticia::first();
        if ($noticia) {
            $noticia->update(['campos_adicionales' => $validated['campos_adicionales'] ?? '']);
        } else {
            Noticia::create(['campos_adicionales' => $validated['campos_adicionales'] ?? '']);
        }

        return response()->json(['success' => true, 'message' => 'Noticias actualizadas.']);
    }

    public function storeSupply(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:80', 'unique:insumos,sku'],
            'unidad' => ['required', 'string', 'max:50'],
            'tipo_registro' => ['required', 'in:PAQUETE,UNIDAD'],
            'unidades_por_paquete' => ['nullable', 'integer', 'min:1'],
            'cantidad_compra' => ['nullable', 'integer', 'min:1'],
            'costo_total_compra' => ['required', 'numeric', 'min:0.01'],
            'stock_actual' => ['required', 'integer', 'min:0'],
            'stock_minimo' => ['required', 'integer', 'min:0'],
            'proveedor' => ['nullable', 'string', 'max:255'],
            'activo' => ['required', 'boolean'],
        ]);

        $payload = $this->normalizeSupplyPayload($validated);

        Insumo::create($payload);

        return response()->json(['success' => true, 'message' => 'Insumo creado.']);
    }

    public function updateSupply(Request $request, Insumo $insumo): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:80', 'unique:insumos,sku,'.$insumo->id],
            'unidad' => ['required', 'string', 'max:50'],
            'tipo_registro' => ['required', 'in:PAQUETE,UNIDAD'],
            'unidades_por_paquete' => ['nullable', 'integer', 'min:1'],
            'cantidad_compra' => ['nullable', 'integer', 'min:1'],
            'costo_total_compra' => ['required', 'numeric', 'min:0.01'],
            'stock_actual' => ['required', 'integer', 'min:0'],
            'stock_minimo' => ['required', 'integer', 'min:0'],
            'proveedor' => ['nullable', 'string', 'max:255'],
            'activo' => ['required', 'boolean'],
        ]);

        $payload = $this->normalizeSupplyPayload($validated);

        $insumo->update($payload);

        return response()->json(['success' => true, 'message' => 'Insumo actualizado.']);
    }

    public function deleteSupply(Insumo $insumo): JsonResponse
    {
        $insumo->delete();

        return response()->json(['success' => true, 'message' => 'Insumo eliminado.']);
    }

    public function updateStoreSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'store_name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:5120'],
        ]);

        $tenantManager = app(TenantManager::class);
        $store = $tenantManager->getStore();

        if (!$store) {
            return response()->json(['success' => false, 'message' => 'No hay tienda seleccionada.'], 422);
        }

        $store->nombre = $validated['store_name'];

        if ($request->hasFile('logo')) {
            if ($store->logo_path) {
                Storage::disk('public')->delete($store->logo_path);
            }

            $store->logo_path = $request->file('logo')->store('store', 'public');
        }

        $store->save();

        return response()->json(['success' => true, 'message' => 'Configuracion de tienda actualizada.']);
    }

    private function syncStockBySize(Producto $producto, array $stocks): void
    {
        $sizeKeys = collect($stocks)
            ->pluck('talla')
            ->filter()
            ->values();

        if ($sizeKeys->isEmpty()) {
            ProductoVariante::where('producto_id', $producto->id)->delete();
            return;
        }

        ProductoVariante::where('producto_id', $producto->id)
            ->whereNotIn('talla', $sizeKeys->all())
            ->delete();

        foreach ($stocks as $item) {
            ProductoVariante::updateOrCreate(
                [
                    'producto_id' => $producto->id,
                    'talla' => $item['talla'],
                    'color' => 'STD',
                ],
                [
                    'sku' => $producto->referencia.'-'.$item['talla'],
                    'stock' => $item['stock'],
                    'precio_adicional' => 0,
                    'esta_activa' => true,
                ]
            );
        }
    }

    private function syncProductImages(Producto $producto, Request $request, bool $replaceExisting): void
    {
        if (!$request->hasFile('images')) {
            return;
        }

        $files = collect($request->file('images'))->take(4)->values();

        if ($replaceExisting) {
            $oldPaths = $producto->imagenes()->pluck('imagen')->toArray();
            if (!empty($oldPaths)) {
                Storage::disk('public')->delete($oldPaths);
            }
            $producto->imagenes()->delete();
        }

        foreach ($files as $index => $file) {
            $path = $file->store('productos', 'public');

            $producto->imagenes()->create([
                'imagen' => $path,
                'orden' => $index + 1,
                'es_principal' => $index === 0,
            ]);
        }

        $firstPath = $producto->imagenes()->orderBy('orden')->value('imagen');
        if ($firstPath) {
            $producto->foto = $firstPath;
            $producto->save();
        }
    }

    private function normalizeSupplyPayload(array $validated): array
    {
        $mode = $validated['tipo_registro'];
        $total = (float) $validated['costo_total_compra'];

        if ($mode === 'PAQUETE') {
            $units = (int) ($validated['unidades_por_paquete'] ?? 0);
            if ($units <= 0) {
                throw ValidationException::withMessages([
                    'unidades_por_paquete' => 'Debes indicar unidades por paquete.',
                ]);
            }

            $validated['cantidad_compra'] = null;
            $validated['costo_unitario'] = round($total / $units, 2);
            return $validated;
        }

        $qty = (int) ($validated['cantidad_compra'] ?? 0);
        if ($qty <= 0) {
            throw ValidationException::withMessages([
                'cantidad_compra' => 'Debes indicar cantidad comprada.',
            ]);
        }

        $validated['unidades_por_paquete'] = null;
        $validated['costo_unitario'] = round($total / $qty, 2);

        return $validated;
    }

    // ==================== BLOQUES HOME ====================

    /**
     * GET /admin/bloques - Ver configuración de bloques
     */
    public function getBloques(): array
    {
        $result = [];
        
        for ($posicion = 1; $posicion <= 2; $posicion++) {
            $bloque = \App\Models\BloqueHome::getAllPorPosicion($posicion);
            
            if (!$bloque) {
                $result[$posicion] = [
                    'posicion' => $posicion,
                    'tipo' => 'banner',
                    'titulo' => null,
                    'contenido' => null,
                    'tamano_texto' => 'normal',
                    'activo' => true,
                    'imagenes' => [],
                ];
                continue;
            }

            $data = [
                'id' => $bloque->id,
                'posicion' => $bloque->posicion,
                'tipo' => $bloque->tipo,
                'titulo' => $bloque->titulo,
                'contenido' => $bloque->contenido,
                'tamano_texto' => $bloque->tamano_texto,
                'activo' => $bloque->activo,
            ];

            if ($bloque->tipo === 'banner') {
                $data['imagenes'] = $bloque->imagenes->map(fn($img) => [
                    'id' => $img->id,
                    'imagen' => asset('storage/' . $img->imagen),
                    'url_destino' => $img->url_destino,
                    'orden' => $img->orden,
                ]);
            } else {
                $data['imagenes'] = [];
            }

            $result[$posicion] = $data;
        }

        return $result;
    }

    /**
     * PUT /admin/bloques - Guardar configuración de bloque
     */
    public function updateBloque(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'posicion' => 'required|integer|min:1|max:2',
            'tipo' => 'required|in:banner,texto',
            'titulo' => 'nullable|string|max:255',
            'contenido' => 'nullable|string',
            'tamano_texto' => 'nullable|in:normal,grande',
            'activo' => 'nullable|boolean',
        ]);

        $bloque = \App\Models\BloqueHome::getAllPorPosicion($validated['posicion']);
        
        if ($bloque) {
            $bloque->update([
                'tipo' => $validated['tipo'],
                'titulo' => $validated['titulo'] ?? null,
                'contenido' => $validated['contenido'] ?? null,
                'tamano_texto' => $validated['tamano_texto'] ?? 'normal',
                'activo' => $validated['activo'] ?? true,
            ]);
        } else {
            $bloque = \App\Models\BloqueHome::create([
                'posicion' => $validated['posicion'],
                'tipo' => $validated['tipo'],
                'titulo' => $validated['titulo'] ?? null,
                'contenido' => $validated['contenido'] ?? null,
                'tamano_texto' => $validated['tamano_texto'] ?? 'normal',
                'activo' => $validated['activo'] ?? true,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Bloque guardado',
        ]);
    }

    /**
     * POST /admin/bloques/{id}/imagenes - Agregar imagen
     */
    public function storeBloqueImagen(\Illuminate\Http\Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'imagen' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            'url_destino' => 'nullable|url',
        ]);

        $bloque = \App\Models\BloqueHome::findOrFail($id);

        if ($bloque->tipo !== 'banner') {
            return response()->json([
                'success' => false,
                'message' => 'Este bloque es de tipo texto',
            ], 422);
        }

        if ($bloque->imagenes()->count() >= 4) {
            return response()->json([
                'success' => false,
                'message' => 'Máximo 4 imágenes',
            ], 422);
        }

        $imagen = $request->file('imagen')->store('bloques', 'public');

        $bloqueImg = \App\Models\BloqueHomeImagen::create([
            'bloque_home_id' => $bloque->id,
            'imagen' => $imagen,
            'url_destino' => $validated['url_destino'] ?? null,
            'orden' => $bloque->imagenes()->max('orden') + 1,
        ]);

        return response()->json([
            'success' => true,
            'imagen' => [
                'id' => $bloqueImg->id,
                'imagen' => asset('storage/' . $bloqueImg->imagen),
                'url_destino' => $bloqueImg->url_destino,
                'orden' => $bloqueImg->orden,
            ],
        ]);
    }

    /**
     * DELETE /admin/bloques/{id}/imagenes/{imgId} - Eliminar imagen
     */
    public function destroyBloqueImagen(int $id, int $imgId): \Illuminate\Http\JsonResponse
    {
        $imagen = \App\Models\BloqueHomeImagen::where('bloque_home_id', $id)->findOrFail($imgId);
        
        \Illuminate\Support\Facades\Storage::disk('public')->delete($imagen->imagen);
        $imagen->delete();

        return response()->json([
            'success' => true,
            'message' => 'Imagen eliminada',
        ]);
    }

    /**
     * Helper para paginar y mapear modelos simples (sin relaciones complejas)
     */
    private function paginateAndMap(string $modelClass, string $search, array $searchFields, int $perPage = 10)
    {
        $query = $modelClass::query();
        
        if ($search) {
            $query->where(function ($q) use ($search, $searchFields) {
                foreach ($searchFields as $column => $label) {
                    $q->orWhere($column, 'like', "%{$search}%");
                }
            });
        }

        $query->orderBy('id', 'desc');
        
        // Si es Insumo, ordenamos por nombre
        if ($modelClass === Insumo::class) {
            $query->orderBy('nombre');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Helper para paginar ofertas con relaciones
     */
    private function paginateAndMapOfertas(string $search, int $perPage = 10)
    {
        $query = Oferta::with(['producto:id,referencia', 'categoria:id,categoria']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('titulo', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%")
                  ->orWhereHas('producto', fn ($pq) => $pq->where('referencia', 'like', "%{$search}%"))
                  ->orWhereHas('categoria', fn ($cq) => $cq->where('categoria', 'like', "%{$search}%"));
            });
        }

        return $query->orderByDesc('id')->paginate($perPage)->withQueryString();
    }
}
