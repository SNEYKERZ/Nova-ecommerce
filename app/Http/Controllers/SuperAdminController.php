<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\User;
use App\Models\Producto;
use App\Models\Pedido;
use App\Models\Categoria;
use App\Services\TenantManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SuperAdminController extends Controller
{
    /**
     * Dashboard del Super Admin - gestiona todas las tiendas
     */
    public function dashboard(): Response
    {
        // Obtener todas las tiendas con estadísticas
        $stores = Store::orderByDesc('created_at')->get()->map(function (Store $store) {
            return [
                'id' => $store->id,
                'slug' => $store->slug,
                'nombre' => $store->nombre,
                'dominio' => $store->dominio,
                'email' => $store->email,
                'telefono' => $store->telefono,
                'activo' => $store->activo,
                'created_at' => $store->created_at?->toIso8601String(),
                // Stats por tienda
                'stats' => [
                    'productos' => Producto::forStore($store)->count(),
                    'categorias' => Categoria::forStore($store)->count(),
                    'pedidos' => Pedido::forStore($store)->count(),
                    'admins' => User::where('store_id', $store->id)->count(),
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

        // Todos los usuarios (sin scope de tenant)
        $users = User::allStores()
            ->orderByDesc('created_at')
            ->get()
            ->map(function (User $user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'store_id' => $user->store_id,
                    'store_nombre' => $user->store_id ? Store::find($user->store_id)?->nombre : null,
                    'created_at' => $user->created_at?->toIso8601String(),
                ];
            });

        // Actividad reciente (últimos pedidos en el sistema)
        $recentOrders = Pedido::allStores()
            ->with(['cliente'])
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
                    'store_nombre' => Store::find($pedido->store_id)?->nombre,
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
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/', 'unique:stores,slug'],
            'dominio' => ['nullable', 'string', 'max:255', 'unique:stores,dominio'],
            'email' => ['nullable', 'email'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'descripcion' => ['nullable', 'string'],
        ]);

        $store = Store::create([
            ...$validated,
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
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/', Rule::unique('stores', 'slug')->ignore($store->id)],
            'dominio' => ['nullable', 'string', 'max:255', Rule::unique('stores', 'dominio')->ignore($store->id)],
            'email' => ['nullable', 'email'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'descripcion' => ['nullable', 'string'],
            'activo' => ['required', 'boolean'],
        ]);

        $store->update($validated);

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
        $store->delete();

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
            'password' => $validated['password'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contraseña actualizada',
        ]);
    }

    /**
     * Ver estadísticas de una tienda específica
     */
    public function storeStats(Store $store): JsonResponse
    {
        $stats = [
            'productos' => \App\Models\Producto::forStore($store)->count(),
            'categorias' => \App\Models\Categoria::forStore($store)->count(),
            'pedidos' => \App\Models\Pedido::forStore($store)->count(),
            'clientes' => \App\Models\Cliente::forStore($store)->count(),
            'ofertas' => \App\Models\Oferta::forStore($store)->count(),
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

        $startDate = match($period) {
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
        }])->with(['pedidos' => function ($q) use ($startDate) {
            $q->where('created_at', '>=', $startDate);
        }])->get()->map(function ($store) {
            $revenue = $store->pedidos->sum('total');
            $orders = $store->pedidos->count();
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
        $topProducts = \App\Models\PedidoItem::allStores()
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
                ->with('cliente')
                ->orderByDesc('created_at')
                ->limit(20)
                ->get()
                ->map(function ($order) {
                    $store = Store::find($order->store_id);
                    return [
                        'id' => 'order-' . $order->id,
                        'type' => 'order',
                        'title' => 'Nuevo pedido #' . $order->id,
                        'message' => 'Pedido de ' . ($order->cliente?->nombre ?? 'Cliente') . ' - ' . number_format($order->total, 0, ',', '.') . ' COP',
                        'store' => $store?->nombre,
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
                        'id' => 'store-' . $store->id,
                        'type' => 'store',
                        'title' => 'Nueva tienda creada',
                        'message' => $store->nombre . ' (' . $store->slug . ')',
                        'store' => $store->nombre,
                        'timestamp' => $store->created_at?->toIso8601String(),
                        'read' => false,
                    ];
                });
            $notifications = $notifications->merge($newStores);
        }

        // Notificaciones de alertas (bajo stock, etc.)
        if ($type === 'all' || $type === 'system') {
            $lowStockProducts = \App\Models\ProductoVariante::allStores()
                ->where('stock', '<=', 5)
                ->with('producto')
                ->limit(10)
                ->get()
                ->map(function ($variante) {
                    return [
                        'id' => 'stock-' . $variante->id,
                        'type' => 'alert',
                        'title' => 'Stock bajo',
                        'message' => ($variante->producto?->referencia ?? 'Producto') . ' - Talla ' . $variante->talla . ': ' . $variante->stock . ' unidades',
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
}