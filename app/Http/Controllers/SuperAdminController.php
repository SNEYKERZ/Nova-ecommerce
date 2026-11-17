<?php

namespace App\Http\Controllers;

use App\Models\BloqueHome;
use App\Models\BloqueHomeImagen;
use App\Models\CatalogBanner;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\GalleryImage;
use App\Models\Oferta;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Producto;
use App\Models\ProductoImagen;
use App\Models\ProductoVariante;
use App\Models\Store;
use App\Models\User;
use App\Services\StylingService;
use App\Traits\HasMediaUrls;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SuperAdminController extends Controller
{
    use HasMediaUrls;

    /** Subdominios que no pueden usarse como slug de tienda */
    private const RESERVED_SLUGS = ['www', 'admin', 'super-admin', 'api', 'app', 'mail', 'static', 'assets'];

    private const DOMAIN_REGEX = '/^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i';

    /**
     * Dashboard del Super Admin - gestiona todas las tiendas
     */
    public function dashboard(): Response
    {
        // Obtener todas las tiendas con estadísticas
        $stores = Store::withCount(['productos', 'categorias', 'pedidos', 'usuarios'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Store $store) {
                return [
                    'id' => $store->id,
                    'slug' => $store->slug,
                    'nombre' => $store->nombre,
                    'dominio' => $store->dominio,
                    'email' => $store->email,
                    'telefono' => $store->telefono,
                    'rubro' => $store->rubro ?? 'general',
                    'activo' => $store->activo,
                    'bg_color' => $store->bg_color ?? '#ffffff',
                    'navbar_color' => $store->navbar_color ?? '#fff',
                    'footer_color' => $store->footer_color ?? '#000000',
                    'navbar_text_color' => $store->navbar_text_color ?? '',
                    'footer_text_color' => $store->footer_text_color ?? '',
                    'created_at' => $store->created_at?->toIso8601String(),
                    // Stats por tienda
                    'stats' => [
                        'productos' => (int) $store->productos_count,
                        'categorias' => (int) $store->categorias_count,
                        'pedidos' => (int) $store->pedidos_count,
                        'admins' => (int) $store->usuarios_count,
                    ],
                ];
            });

        // Estadísticas globales del sistema
        $stats = [
            'total_tiendas' => Store::count(),
            'tiendas_activas' => Store::where('activo', true)->count(),
            'total_usuarios' => User::count(),
            'super_admins' => User::where('role', 'super_admin')->count(),
            'admins_tienda' => User::where('role', 'admin')->count(),
            // Stats globales
            'total_productos' => Producto::allStores()->count(),
            'total_categorias' => Categoria::allStores()->count(),
            'total_pedidos' => Pedido::allStores()->count(),
            'total_ingresos' => Pedido::allStores()->sum('total') ?? 0,
        ];

        // Todos los usuarios (sin scope de tenant) — eager load store
        $users = User::allStores()
            ->with('store')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (User $user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'store_id' => $user->store_id,
                    'store_nombre' => $user->store?->nombre,
                    'created_at' => $user->created_at?->toIso8601String(),
                ];
            });

        // Actividad reciente — eager load cliente y store para evitar N+1
        $recentOrders = Pedido::allStores()
            ->with(['cliente', 'store'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function ($pedido) {
                return [
                    'id' => $pedido->id,
                    'total' => $pedido->total,
                    'estado' => $pedido->estado,
                    'cliente' => $pedido->cliente?->nombre ?? 'Sin cliente',
                    'store_id' => $pedido->store_id,
                    'store_nombre' => $pedido->store?->nombre,
                    'created_at' => $pedido->created_at?->toIso8601String(),
                ];
            });

        return Inertia::render('admin/SuperAdminDashboard', [
            'stats' => $stats,
            'stores' => $stores,
            'users' => $users,
            'recentOrders' => $recentOrders,
        ]);
    }

    /**
     * Crear una nueva tienda
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:63', 'regex:/^[a-z0-9-]+$/', 'not_in:'.implode(',', self::RESERVED_SLUGS), 'unique:stores,slug'],
            'dominio' => ['nullable', 'string', 'max:255', 'regex:'.self::DOMAIN_REGEX, 'unique:stores,dominio'],
            'email' => ['nullable', 'email'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'descripcion' => ['nullable', 'string'],
            'rubro' => ['nullable', Rule::in(Store::RUBROS)],
            'bg_color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'navbar_color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'footer_color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'navbar_text_color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'footer_text_color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
        ], [
            'bg_color.regex' => 'El color de fondo debe ser un color hex válido',
            'navbar_color.regex' => 'El color navbar debe ser un color hex válido',
            'footer_color.regex' => 'El color footer debe ser un color hex válido',
            'navbar_text_color.regex' => 'El color de texto navbar debe ser un color hex válido',
            'footer_text_color.regex' => 'El color de texto footer debe ser un color hex válido',
        ]);

        $defaults = StylingService::getDefaultColors();

        $store = Store::create([
            'nombre' => $validated['nombre'],
            'slug' => $validated['slug'],
            'dominio' => $validated['dominio'] ?? null,
            'email' => $validated['email'] ?? null,
            'telefono' => $validated['telefono'] ?? null,
            'descripcion' => $validated['descripcion'] ?? null,
            'rubro' => $validated['rubro'] ?? 'general',
            'bg_color' => StylingService::validateColor($validated['bg_color'] ?? null, '#ffffff'),
            'navbar_color' => StylingService::validateColor($validated['navbar_color'] ?? null, '#ffffff'),
            'footer_color' => StylingService::validateColor($validated['footer_color'] ?? null, '#ffffff'),
            'navbar_text_color' => StylingService::validateColor($validated['navbar_text_color'] ?? null, '#111111'),
            'footer_text_color' => StylingService::validateColor($validated['footer_text_color'] ?? null, '#111111'),
            'activo' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tienda creada exitosamente',
            'store' => $store,
        ]);
    }

    /**
     * Actualizar una tienda
     */
    public function updateStore(Request $request, Store $store): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:63', 'regex:/^[a-z0-9-]+$/', 'not_in:'.implode(',', self::RESERVED_SLUGS), Rule::unique('stores', 'slug')->ignore($store->id)],
            'dominio' => ['nullable', 'string', 'max:255', 'regex:'.self::DOMAIN_REGEX, Rule::unique('stores', 'dominio')->ignore($store->id)],
            'email' => ['nullable', 'email'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'descripcion' => ['nullable', 'string'],
            'rubro' => ['nullable', Rule::in(Store::RUBROS)],
            'activo' => ['required', 'boolean'],
            'bg_color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'navbar_color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'footer_color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'navbar_text_color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'footer_text_color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
        ], [
            'bg_color.regex' => 'El color de fondo debe ser un color hex válido',
            'navbar_color.regex' => 'El color navbar debe ser un color hex válido',
            'footer_color.regex' => 'El color footer debe ser un color hex válido',
            'navbar_text_color.regex' => 'El color de texto navbar debe ser un color hex válido',
            'footer_text_color.regex' => 'El color de texto footer debe ser un color hex válido',
        ]);

        $store->update([
            'nombre' => $validated['nombre'],
            'slug' => $validated['slug'],
            'dominio' => $validated['dominio'] ?? null,
            'email' => $validated['email'] ?? null,
            'telefono' => $validated['telefono'] ?? null,
            // Si el formulario no envía la descripción, se conserva la actual (antes se borraba).
            'descripcion' => $request->has('descripcion') ? ($validated['descripcion'] ?? null) : $store->descripcion,
            'activo' => $validated['activo'],
            'rubro' => $validated['rubro'] ?? $store->rubro ?? 'general',
            'bg_color' => StylingService::validateColor($validated['bg_color'] ?? null, $store->bg_color),
            'navbar_color' => StylingService::validateColor($validated['navbar_color'] ?? null, $store->navbar_color),
            'footer_color' => StylingService::validateColor($validated['footer_color'] ?? null, $store->footer_color),
            'navbar_text_color' => StylingService::validateColor($validated['navbar_text_color'] ?? null, $store->navbar_text_color),
            'footer_text_color' => StylingService::validateColor($validated['footer_text_color'] ?? null, $store->footer_text_color),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tienda actualizada',
            'store' => $store,
        ]);
    }

    /**
     * Eliminar una tienda
     */
    public function destroyStore(Store $store): JsonResponse
    {
        // No permitir eliminar si es la última tienda
        if (Store::count() <= 1) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes eliminar la última tienda',
            ], 422);
        }

        $storeName = $store->nombre;
        $paths = $this->storeMediaPaths($store);

        $store->delete();

        // Las filas se eliminan en cascada (FK); los archivos hay que borrarlos aparte
        foreach ($paths as $path) {
            $this->deleteMediaPath($path);
        }

        return response()->json([
            'success' => true,
            'message' => "Tienda '$storeName' eliminada",
        ]);
    }

    /**
     * Crear un nuevo usuario (admin de tienda o super admin)
     */
    public function storeUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:super_admin,admin'],
            'store_id' => ['nullable', 'exists:stores,id'],
        ]);

        // Si es admin de tienda, debe tener store_id
        if ($validated['role'] === 'admin' && empty($validated['store_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Los administradores de tienda deben tener una tienda asignada',
            ], 422);
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'],
            'store_id' => $validated['store_id'] ?? null,
            'email_verified_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Usuario creado exitosamente',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'store_id' => $user->store_id,
            ],
        ]);
    }

    /**
     * Actualizar un usuario
     */
    public function updateUser(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', 'in:super_admin,admin,cliente'],
            'store_id' => ['nullable', 'exists:stores,id'],
        ]);

        // Si es admin, debe tener store_id
        if ($validated['role'] === 'admin' && empty($validated['store_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Los administradores de tienda deben tener una tienda asignada',
            ], 422);
        }

        // Si cambia de admin a super_admin, limpiar store_id
        if ($validated['role'] === 'super_admin') {
            $validated['store_id'] = null;
        }

        // No quedarse sin super administradores (ni degradarse a sí mismo)
        if ($user->role === 'super_admin' && $validated['role'] !== 'super_admin') {
            if ($user->id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes quitarte el rol de super administrador.',
                ], 422);
            }

            if (User::withoutGlobalScopes()->where('role', 'super_admin')->count() <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debe existir al menos un super administrador.',
                ], 422);
            }
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Usuario actualizado',
        ]);
    }

    /**
     * Eliminar un usuario
     */
    public function destroyUser(User $user): JsonResponse
    {
        // No permitir eliminarse a sí mismo
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes eliminar tu propia cuenta',
            ], 422);
        }

        // No permitir eliminar el último super admin
        if ($user->role === 'super_admin' && User::where('role', 'super_admin')->count() <= 1) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes eliminar el último super administrador',
            ], 422);
        }

        $userName = $user->name;
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => "Usuario '$userName' eliminado",
        ]);
    }

    /**
     * Cambiar contraseña de usuario
     */
    public function updateUserPassword(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contraseña actualizada',
        ]);
    }

    /**
     * Impersonar a otro usuario
     */
    public function impersonate(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->role !== 'super_admin') {
            abort(403, 'Solo el super admin puede impersonar.');
        }

        if ($user->role === 'super_admin') {
            abort(403, 'No puedes impersonar a otro super admin.');
        }

        if ($user->role === 'admin' && ! $user->store_id) {
            abort(422, 'El usuario no tiene una tienda asignada.');
        }

        $impersonatorId = $request->user()->id;

        Auth::login($user);

        // Nueva sesión (evita fijación de sesión) conservando los datos y
        // guardando quién era el super admin original
        $request->session()->regenerate();
        $request->session()->put('impersonator_id', $impersonatorId);

        // Redirigir según el rol del usuario impersonado
        return match ($user->role) {
            'admin' => redirect()->intended('/admin'),
            'cliente' => redirect('/'),
            default => redirect('/'),
        };
    }

    /**
     * Ver estadísticas de una tienda específica
     */
    public function storeStats(Store $store): JsonResponse
    {
        $stats = [
            'productos' => Producto::forStore($store)->count(),
            'categorias' => Categoria::forStore($store)->count(),
            'pedidos' => Pedido::forStore($store)->count(),
            'clientes' => Cliente::forStore($store)->count(),
            'ofertas' => Oferta::forStore($store)->count(),
            'admins' => User::where('store_id', $store->id)->count(),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }

    /**
     * Reportes globales del sistema
     */
    public function reports(Request $request): JsonResponse
    {
        $period = $request->get('period', 'month'); // day, week, month, year

        $startDate = match ($period) {
            'day' => now()->subDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'year' => now()->subYear(),
            default => now()->subMonth(),
        };

        // Ventas por período
        $salesByPeriod = Pedido::allStores()
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as orders'),
                DB::raw('SUM(total) as revenue')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Ventas por tienda
        $salesByStore = Store::withCount(['pedidos' => function ($q) use ($startDate) {
            $q->where('created_at', '>=', $startDate);
        }])->withSum(['pedidos' => function ($q) use ($startDate) {
            $q->where('created_at', '>=', $startDate);
        }], 'total')->get()->map(function ($store) {
            $revenue = (float) ($store->pedidos_sum_total ?? 0);
            $orders = (int) $store->pedidos_count;

            return [
                'id' => $store->id,
                'nombre' => $store->nombre,
                'slug' => $store->slug,
                'pedidos' => $orders,
                'ingresos' => $revenue,
                'ticket_promedio' => $orders > 0 ? $revenue / $orders : 0,
            ];
        })->sortByDesc('ingresos')->values();

        // Top productos más vendidos
        $topProducts = PedidoItem::allStores()
            ->where('created_at', '>=', $startDate)
            ->with('producto')
            ->select('producto_id', DB::raw('SUM(cantidad) as total_vendido'), DB::raw('SUM(subtotal) as total_ingresos'))
            ->groupBy('producto_id')
            ->orderByDesc('total_vendido')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'producto' => $item->producto?->referencia ?? 'Producto eliminado',
                    'cantidad' => $item->total_vendido,
                    'ingresos' => $item->total_ingresos,
                ];
            });

        // Resumen
        $summary = [
            'periodo' => $period,
            'total_ventas' => Pedido::allStores()->where('created_at', '>=', $startDate)->sum('total') ?? 0,
            'total_pedidos' => Pedido::allStores()->where('created_at', '>=', $startDate)->count(),
            'tiendas_con_ventas' => Pedido::allStores()
                ->where('created_at', '>=', $startDate)
                ->distinct('store_id')
                ->count('store_id'),
            'ticket_promedio' => Pedido::allStores()
                ->where('created_at', '>=', $startDate)
                ->avg('total') ?? 0,
        ];

        return response()->json([
            'success' => true,
            'salesByPeriod' => $salesByPeriod,
            'salesByStore' => $salesByStore,
            'topProducts' => $topProducts,
            'summary' => $summary,
        ]);
    }

    /**
     * Notificaciones del sistema
     */
    public function notifications(Request $request): JsonResponse
    {
        $type = $request->get('type', 'all'); // all, orders, stores, system

        $notifications = collect();

        // Notificaciones de nuevos pedidos en las tiendas
        if ($type === 'all' || $type === 'orders') {
            $newOrders = Pedido::allStores()
                ->where('created_at', '>=', now()->subHours(24))
                ->with(['cliente', 'store'])
                ->orderByDesc('created_at')
                ->limit(20)
                ->get()
                ->map(function ($order) {
                    return [
                        'id' => 'order-'.$order->id,
                        'type' => 'order',
                        'title' => 'Nuevo pedido #'.$order->id,
                        'message' => 'Pedido de '.($order->cliente?->nombre ?? 'Cliente').' - '.number_format($order->total, 0, ',', '.').' COP',
                        'store' => $order->store?->nombre,
                        'timestamp' => $order->created_at?->toIso8601String(),
                        'read' => false,
                    ];
                });
            $notifications = $notifications->merge($newOrders);
        }

        // Notificaciones de tiendas nuevas
        if ($type === 'all' || $type === 'stores') {
            $newStores = Store::where('created_at', '>=', now()->subDays(7))
                ->get()
                ->map(function ($store) {
                    return [
                        'id' => 'store-'.$store->id,
                        'type' => 'store',
                        'title' => 'Nueva tienda creada',
                        'message' => $store->nombre.' ('.$store->slug.')',
                        'store' => $store->nombre,
                        'timestamp' => $store->created_at?->toIso8601String(),
                        'read' => false,
                    ];
                });
            $notifications = $notifications->merge($newStores);
        }

        // Notificaciones de alertas (bajo stock, etc.)
        if ($type === 'all' || $type === 'system') {
            $lowStockProducts = ProductoVariante::allStores()
                ->where('stock', '<=', 5)
                ->with('producto')
                ->limit(10)
                ->get()
                ->map(function ($variante) {
                    return [
                        'id' => 'stock-'.$variante->id,
                        'type' => 'alert',
                        'title' => 'Stock bajo',
                        'message' => ($variante->producto?->referencia ?? 'Producto').' - Talla '.$variante->talla.': '.$variante->stock.' unidades',
                        'store' => null,
                        'timestamp' => now()->toIso8601String(),
                        'read' => false,
                    ];
                });
            $notifications = $notifications->merge($lowStockProducts);
        }

        // Ordenar por timestamp
        $notifications = $notifications->sortByDesc('timestamp')->values();

        return response()->json([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $notifications->where('read', false)->count(),
        ]);
    }

    /**
     * Marcar notificación como leída
     */
    public function markNotificationRead(Request $request): JsonResponse
    {
        // Por ahora solo confirmamos la acción
        return response()->json([
            'success' => true,
            'message' => 'Notificación marcada como leída',
        ]);
    }

    /**
     * Rutas de archivos subidos que pertenecen a una tienda (para borrarlos
     * cuando se elimina la tienda y no dejar huérfanos en disco).
     */
    private function storeMediaPaths(Store $store): array
    {
        $paths = array_merge(
            [$store->logo_path],
            ProductoImagen::forStore($store)->pluck('imagen')->all(),
            Producto::forStore($store)->pluck('foto')->all(),
            GalleryImage::forStore($store)->pluck('imagen')->all(),
            CatalogBanner::forStore($store)->pluck('imagen')->all(),
            BloqueHomeImagen::withoutGlobalScopes()
                ->whereIn('bloque_home_id', BloqueHome::forStore($store)->select('id'))
                ->pluck('imagen')
                ->all(),
        );

        return array_values(array_unique(array_filter($paths)));
    }
}
