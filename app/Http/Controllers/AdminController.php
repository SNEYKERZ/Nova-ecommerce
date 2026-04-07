<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Insumo;
use App\Models\Noticia;
use App\Models\Oferta;
use App\Models\Producto;
use App\Models\ProductoImagen;
use App\Models\ProductoVariante;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function dashboard(): Response
    {
        $productos = Producto::with(['categoria', 'variantes', 'imagenes'])
            ->orderByDesc('id')
            ->get()
            ->map(function (Producto $producto) {
                return [
                    'id' => $producto->id,
                    'referencia' => $producto->referencia,
                    'precio' => (float) $producto->precio,
                    'categoria_id' => $producto->categoria_id,
                    'categoria' => $producto->categoria?->categoria,
                    'tallas' => $producto->tallas,
                    'estado' => $producto->estado,
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
            'insumos' => Insumo::orderBy('nombre')->get(),
            'ofertas' => Oferta::with(['producto:id,referencia', 'categoria:id,categoria'])
                ->orderByDesc('id')
                ->get()
                ->map(function (Oferta $oferta) {
                    return [
                        'id' => $oferta->id,
                        'titulo' => $oferta->titulo,
                        'descripcion' => $oferta->descripcion,
                        'producto_id' => $oferta->producto_id,
                        'categoria_id' => $oferta->categoria_id,
                        'producto' => $oferta->producto?->referencia,
                        'categoria' => $oferta->categoria?->categoria,
                        'descuento_porcentaje' => $oferta->descuento_porcentaje,
                        'descuento_fijo' => $oferta->descuento_fijo,
                        'precio_oferta' => $oferta->precio_oferta,
                        'fecha_inicio' => optional($oferta->fecha_inicio)->format('Y-m-d\TH:i'),
                        'fecha_fin' => optional($oferta->fecha_fin)->format('Y-m-d\TH:i'),
                        'esta_activa' => (bool) $oferta->esta_activa,
                    ];
                }),
            'noticia' => Noticia::first()?->campos_adicionales ?? '',
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

        Noticia::updateOrCreate(
            ['id' => 1],
            ['campos_adicionales' => $validated['campos_adicionales'] ?? '']
        );

        return response()->json(['success' => true, 'message' => 'Noticias actualizadas.']);
    }

    public function storeSupply(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:80', 'unique:insumos,sku'],
            'unidad' => ['required', 'string', 'max:50'],
            'stock_actual' => ['required', 'integer', 'min:0'],
            'stock_minimo' => ['required', 'integer', 'min:0'],
            'costo_unitario' => ['required', 'numeric', 'min:0'],
            'proveedor' => ['nullable', 'string', 'max:255'],
            'activo' => ['required', 'boolean'],
        ]);

        Insumo::create($validated);

        return response()->json(['success' => true, 'message' => 'Insumo creado.']);
    }

    public function updateSupply(Request $request, Insumo $insumo): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:80', 'unique:insumos,sku,'.$insumo->id],
            'unidad' => ['required', 'string', 'max:50'],
            'stock_actual' => ['required', 'integer', 'min:0'],
            'stock_minimo' => ['required', 'integer', 'min:0'],
            'costo_unitario' => ['required', 'numeric', 'min:0'],
            'proveedor' => ['nullable', 'string', 'max:255'],
            'activo' => ['required', 'boolean'],
        ]);

        $insumo->update($validated);

        return response()->json(['success' => true, 'message' => 'Insumo actualizado.']);
    }

    public function deleteSupply(Insumo $insumo): JsonResponse
    {
        $insumo->delete();

        return response()->json(['success' => true, 'message' => 'Insumo eliminado.']);
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
}
