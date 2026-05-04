# Vendex — Contexto Completo del Proyecto
> Última actualización: 2026-04-30 | Analizado por Claude Sonnet 4.6

---

## 1. Qué es este proyecto

**Vendex** es una plataforma SaaS de e-commerce multi-tenant construida sobre Laravel 12 + Vue 3 + Inertia.js. Permite crear múltiples tiendas en línea desde un único servidor, cada una con su propio subdominio (`tienda.vendex.app`) o dominio personalizado (`mitienda.com`). Cada tienda tiene su catálogo, inventario, órdenes y configuración visual aislados del resto.

El proyecto está diseñado para ser vendido como servicio B2B a pequeños y medianos negocios (pymes), especialmente en Colombia.

---

## 2. Stack tecnológico

| Capa | Tecnología | Versión |
|------|-----------|---------|
| Backend | Laravel | 12.x |
| PHP | PHP | ^8.2 |
| Frontend | Vue 3 + Inertia.js | Vue 3.5 / Inertia 3.0 |
| CSS | Tailwind CSS | v4.0 |
| Auth | Laravel Sanctum | 4.0 |
| Build | Vite | 6.x |
| JS Misc | Alpine.js | 3.x |
| Realtime (setup) | Laravel Echo + Pusher | instalado, no activo |

---

## 3. Arquitectura multi-tenant

### Estrategia: Single Database, múltiples stores
Todos los modelos comparten la misma base de datos. El aislamiento se logra con `store_id` en cada tabla y un `HasTenant` trait que inyecta un global scope automáticamente en todos los queries.

### Resolución del tenant
1. **Por subdomain**: `tienda1.vendex.app` → extrae `tienda1` → busca `Store.slug = 'tienda1'`
2. **Por dominio personalizado**: `mitienda.com` → busca `Store.dominio = 'mitienda.com'`
3. **Localhost (dev)**: usa el store con slug `demo` automáticamente

### Flujo del middleware
```
Request → ResolveTenant → TenantManager.resolveFromRequest() → Store → HasTenant scope activo
```

### Archivos clave del multi-tenant
- `app/Traits/HasTenant.php` — Global scope + auto-assign store_id al crear
- `app/Services/TenantManager.php` — Singleton del store actual, isSuperAdmin flag
- `app/Http/Middleware/ResolveTenant.php` — Middleware que resuelve el tenant
- `app/Providers/TenantServiceProvider.php` — Registra `tenant.store` en el container
- `config/vendex.php` — Dominio base configurable (`VENDEX_DOMAIN`)

---

## 4. Roles de usuario

| Rol | Acceso | Descripción |
|-----|--------|-------------|
| `super_admin` | `/super-admin/*` | Gestiona todas las tiendas, usuarios y reportes globales |
| `admin` | `/admin/*` | Admin de una tienda específica (store_id asignado) |
| `cliente` | — | Solo storefront, sin panel |

La autenticación usa sesiones web (formulario en `/admin/login`) y Sanctum para la API.

---

## 5. Modelos y base de datos

### Modelos principales

| Modelo | Tabla | Tenant? | Descripción |
|--------|-------|---------|-------------|
| `Store` | `stores` | — | Entidad tenant |
| `User` | `users` | ✓ | Admins y clientes (role: super_admin/admin/cliente) |
| `Producto` | `productos` | ✓ | Producto del catálogo |
| `ProductoImagen` | `producto_imagenes` | ✓ | Galería (hasta 4 imágenes, campo `es_principal`) |
| `ProductoVariante` | `producto_variantes` | ✓ | Stock por talla/color |
| `Categoria` | `categorias` | ✓ | Categorías de productos |
| `Carrito` | `carritos` | ✓ | Carrito de sesión anónima |
| `CarritoItem` | `carrito_items` | ✓ | Items del carrito |
| `Pedido` | `pedidos` | ✓ | Orden de compra |
| `PedidoItem` | `pedido_items` | ✓ | Items de la orden |
| `Cliente` | `clientes` | ✓ | Datos del cliente al hacer pedido |
| `Oferta` | `ofertas` | ✓ | Promociones (porcentaje/fijo/precio especial) |
| `Insumo` | `insumos` | ✓ | Inventario de materias primas |
| `Noticia` | `noticias` | ✓ | Ticker de noticias/promos en home |
| `BloqueHome` | `bloque_homes` | ✓ | Bloques configurables del home (banner/texto) |
| `BloqueHomeImagen` | `bloque_home_imagenes` | ✓ | Imágenes de los bloques banner |
| `Resena` | `resenas` | ✓ | Reseñas de productos (requieren aprobación) |
| `Wishlist` | `wishlists` | ✓ | Lista de deseos |
| `HistorialPrecio` | `historial_precios` | ✓ | Historial de cambios de precio |
| `Plantilla` | `plantillas` | ✓ | Temporadas/plantillas visuales |
| `Banner` | `banners` | ✓ | Banners (via API REST) |
| `Slide` | `slides` | ✓ | Slides (via API REST) |

### Campos del Producto (IMPORTANTE)
El producto usa **`referencia`** como nombre de display (es el SKU). No existe columna `nombre` ni `descripcion` en la migración original, aunque la migración `2026_04_17_000003_add_extra_fields_to_productos.php` puede agregar campos. Las tallas se guardan **doble**: como string CSV en `productos.tallas` Y como registros en `producto_variantes` (con stock por talla).

---

## 6. Rutas del sistema

### Web (Storefront público)
```
GET  /                          → HomePage (catálogo + filtros)
GET  /productos/{id}            → ProductPage (detalle)
GET  /carrito                   → CartPage (checkout incluido en modal)
GET  /conocenos                 → AboutPage
GET  /pedido/gracias/{id}       → ThankYouPage
```

### Web (Carrito API — session)
```
GET    /api/carrito              → show
POST   /api/carrito/agregar      → agregar
PUT    /api/carrito/actualizar/{id} → actualizar
DELETE /api/carrito/eliminar/{id}   → eliminar
DELETE /api/carrito/vaciar          → vaciar
```

### Web (Auth)
```
GET  /admin/login   → formulario login
POST /admin/login   → submit login
POST /admin/logout  → logout
```

### Web (Admin de tienda — requiere auth + admin.role)
```
GET  /admin                     → AdminDashboardPage (Inertia)
POST /admin/productos            → storeProduct
PUT  /admin/productos/{id}       → updateProduct
DEL  /admin/productos/{id}       → deleteProduct
POST /admin/productos/{id}/toggle-coleccion
POST /admin/ofertas
PUT  /admin/ofertas/{id}
DEL  /admin/ofertas/{id}
PUT  /admin/noticias
POST /admin/settings             → nombre tienda + logo
POST /admin/insumos
PUT  /admin/insumos/{id}
DEL  /admin/insumos/{id}
POST /admin/bloques              → updateBloque (posicion 1-2, tipo banner/texto)
POST /admin/bloques/{id}/imagenes
DEL  /admin/bloques/{id}/imagenes/{imgId}
```

### Web (Super Admin — requiere auth + admin.role, sin tenant)
```
GET  /super-admin                        → SuperAdminDashboard
POST /super-admin/stores
PUT  /super-admin/stores/{id}
DEL  /super-admin/stores/{id}
GET  /super-admin/stores/{id}/stats
POST /super-admin/users
PUT  /super-admin/users/{id}
DEL  /super-admin/users/{id}
PUT  /super-admin/users/{id}/password
GET  /super-admin/reports               → ventas por periodo/tienda/producto
GET  /super-admin/notifications
POST /super-admin/notifications/read
```

### API REST (con middleware tenant)
```
GET  /api/productos              → lista paginada
GET  /api/productos/basicas      → datos mínimos
GET  /api/productos/destacados
GET  /api/productos/nuevos
GET  /api/productos/{id}
GET  /api/categorias
GET  /api/categorias/{id}/productos
POST /api/pedidos                → crear pedido
GET  /api/pedidos
GET  /api/pedidos/{id}
GET  /api/ofertas/activas
GET  /api/productos/{id}/resenas
POST /api/productos/{id}/resenas
GET  /api/bloques-home
GET  /api/plantillas/activa
GET  /api/banners
GET  /api/slides
```

---

## 7. Frontend — Páginas Vue (Inertia)

| Página | Ruta | Descripción |
|--------|------|-------------|
| `HomePage.vue` | `/` | Catálogo con filtros client-side (categoría, talla, precio, recencia, nueva colección), add-to-cart |
| `ProductPage.vue` | `/productos/{id}` | Detalle: galería, selección de talla, add-to-cart |
| `CartPage.vue` | `/carrito` | Lista items, ajuste qty/talla, modal checkout con datos de envío |
| `ThankYouPage.vue` | `/pedido/gracias/{id}` | Confirmación de pedido |
| `AboutPage.vue` | `/conocenos` | Info del store (nombre, descripción, email, teléfono) |
| `AdminDashboardPage.vue` | `/admin` | Panel admin: tabs (configuracion, productos, insumos, ofertas, bloques, noticias) |
| `SuperAdminDashboard.vue` | `/super-admin` | Panel super: tabs (tiendas, usuarios, pedidos recientes, reportes) |
| `AdminLoginPage.vue` | `/admin/login` | Login form |

### Componentes
- `AppLayout.vue` — Header sticky (logo+nav+carrito counter), Footer, logout
- `FilterSidebar.vue` — Filtros del catálogo (categoría, talla, precio, nueva colección, ordenamiento)

### Cómo funciona el carrito
El carrito es **anónimo por sesión**. No requiere login. El `AppLayout` escucha el evento `cart-updated` para actualizar el contador. Las operaciones van a `/api/carrito/*` que usa `CarritoController`.

---

## 8. Panel Admin — Tabs y funcionalidades

### Tab: Configuración
- Cambiar nombre de la tienda
- Subir logo (preview en tiempo real)

### Tab: Productos
- CRUD completo con: referencia (SKU), precio, categoría, estado, nueva_colección
- Gestión de stock por talla (tabla de tallas con checkbox + input de stock)
- Upload de hasta 4 imágenes por producto
- Lista paginada con búsqueda, toggle de nueva_colección desde la lista

### Tab: Insumos
- Inventario de materia prima
- Dos modos: PAQUETE (calcula costo unitario) y UNIDAD
- Campos: nombre, SKU, unidad, proveedor, stock_actual, stock_mínimo

### Tab: Ofertas/Promociones
- Crear por producto específico o por categoría completa
- Tres tipos de descuento: porcentaje, fijo, precio_especial
- Fechas de inicio/fin con activación manual

### Tab: Bloques Home
- 2 bloques configurables (posición 1 y 2)
- Tipo "banner": hasta 4 imágenes con URL de destino (carrusel en frontend)
- Tipo "texto": título + contenido + tamaño (normal/grande)

### Tab: Noticias
- Ticker scrolling de texto en la parte superior del home

---

## 9. Bugs y deudas técnicas detectadas

### Críticos (bloquean producción)

1. **Sin gestión de pedidos en admin de tienda** — El store admin no puede ver ni gestionar los pedidos que llegan. Solo el super admin los ve globalmente.

2. **Sin decremento de stock al crear pedido** — El `PedidoController` no descuenta `ProductoVariante.stock` cuando se confirma una compra. Se puede vender stock inexistente.

3. **Sin hashing explícito en `updateUserPassword`** — El método en `SuperAdminController` hace `$user->update(['password' => $validated['password']])` sin `Hash::make()`. Dependemos del casting del modelo; si falla, contraseñas en texto plano.

4. **Sin paginación en el storefront** — `StorefrontController::home()` carga TODOS los productos disponibles sin límite. Con 1000+ productos, la página explota.

5. **Footer hardcodeado** — `AppLayout.vue` muestra "NOVA COMMERCE BASE" en el footer. No usa el `store.nombre` del tenant.

6. **Referencia única rompe multi-tenant** — La migración original define `referencia` como `unique()` global. Dos tiendas no pueden tener el mismo SKU. La migración `2026_04_17_000004_fix_productos_referencia_unique.php` puede corregir esto, pero debe verificarse.

### Importantes (afectan calidad)

7. **Sin rate limiting en API** — Las rutas de API no tienen `throttle`. Expuesto a abuso.

8. **N+1 queries en SuperAdminController** — `Store::find($order->store_id)` dentro de loops en `dashboard()` y `notifications()`. Usar eager loading.

9. **Múltiples modelos en un archivo** — `app/Models/Oferta.php` contiene `Oferta`, `Wishlist`, `Resena`, `HistorialPrecio`. Esto viola la convención Laravel y confunde el autoloader en algunas versiones.

10. **Sin emails de confirmación** — Ni al cliente ni al admin cuando llega un pedido.

11. **Sin SEO** — Sin meta tags dinámicos, sin Open Graph, sin sitemap.xml, sin structured data de productos.

12. **Sin validación de disponibilidad en checkout** — Si un producto pasa a `NO_DISPONIBLE` entre que el usuario agrega al carrito y hace el pedido, igual pasa.

13. **`HasTenant` depende de `app('tenant.store')`** — Si el proveedor no ha corrido (seeders, artisan, etc.), el scope se rompe silenciosamente.

14. **Sin autenticación de cliente** — No existe registro/login para compradores. El carrito es anónimo. No se puede hacer seguimiento de pedidos.

15. **Productos: campo `nombre` ausente** — `referencia` actúa como nombre visible, siendo técnicamente un SKU. En el buscador del admin se busca en `nombre` que no existe como columna real.

---

## 10. Mejoras propuestas para producción

### Alta prioridad

#### A. Gestión de pedidos para el admin de tienda
Agregar tab "Pedidos" en `AdminDashboardPage` con:
- Lista paginada de pedidos (estado, cliente, total, fecha)
- Cambio de estado: `pendiente → procesando → enviado → entregado → cancelado`
- Vista de detalle del pedido con items
- Backend: rutas `GET /admin/pedidos`, `GET /admin/pedidos/{id}`, `PUT /admin/pedidos/{id}/estado`

#### B. Stock real — decremento al comprar
En `PedidoController::store()`, después de crear el pedido:
```php
foreach ($items as $item) {
    ProductoVariante::where('producto_id', $item['producto_id'])
        ->where('talla', $item['talla'])
        ->decrement('stock', $item['cantidad']);
}
```
Con validación previa de stock suficiente.

#### C. Paginación del catálogo
Cambiar `StorefrontController::home()` a `Producto::paginate(24)` + scroll infinito o paginación con Vue.

#### D. Campo `nombre` y `descripcion` en Producto
```php
// Migración nueva
$table->string('nombre')->nullable()->after('referencia');
$table->text('descripcion')->nullable()->after('nombre');
```
Actualizar el form del admin para ingresar nombre y descripción. El `referencia` queda como código interno.

#### E. Emails transaccionales
Usar `Mail` de Laravel o un proveedor (Mailgun, Resend):
- `PedidoConfirmadoMail` → cliente
- `NuevoPedidoAdminMail` → admin de la tienda
- `PedidoActualizadoMail` → al cambiar estado

### Media prioridad

#### F. WhatsApp como canal de checkout
Botón "Pedir por WhatsApp" que genera un mensaje preformateado con los productos del carrito. Muy común y efectivo en el mercado latinoamericano.

```vue
const whatsappUrl = computed(() => {
  const phone = store.telefono_whatsapp;
  const msg = items.value.map(i => `${i.producto.referencia} (x${i.cantidad})`).join('\n');
  return `https://wa.me/${phone}?text=${encodeURIComponent(msg)}`;
});
```

#### G. SEO básico por tienda
En el `HandleInertiaRequests` middleware, compartir meta tags por página:
- Title dinámico por producto/página
- Meta description
- Open Graph (og:image usando la foto del producto)
- `<link rel="canonical">`

#### H. Rate limiting en API
En `api.php`:
```php
Route::middleware(['tenant', 'throttle:60,1'])->group(function () { ... });
```
Y `throttle:10,1` para POST de pedidos.

#### I. Pasarela de pago
Integrar **MercadoPago** (Colombia/LATAM) o **PayU** para pagos en línea:
- Botón "Pagar ahora" en checkout
- Webhook para actualizar estado del pedido
- Actualmente solo funciona COD (pago contraentrega)

#### J. Separar modelos en archivos individuales
`app/Models/Oferta.php` contiene 4 clases. Separar:
- `app/Models/Resena.php`
- `app/Models/Wishlist.php`
- `app/Models/HistorialPrecio.php`

#### K. Footer y header dinámicos
En `AppLayout.vue`, el footer debe usar los datos del tenant:
```vue
<p>{{ appName }}</p>  <!-- ya está en el header, añadir al footer -->
```

#### L. Hash explícito en passwords
En `SuperAdminController::updateUserPassword()`:
```php
$user->update(['password' => Hash::make($validated['password'])]);
```

#### M. Rate limiting + HTTPS en producción
En `bootstrap/app.php` o middleware, forzar HTTPS en producción.

### Baja prioridad (mejoras de producto)

#### N. Sistema de cupones
Modelo `Cupon` con código, descuento porcentual/fijo, usos máximos, expiración. Campo en el checkout para aplicar.

#### O. Búsqueda en el catálogo (servidor)
Endpoint `GET /api/productos?q=camiseta` con full-text search o `LIKE`. Barra de búsqueda en el header.

#### P. Registro y login de clientes
Modelo `Cliente` ya existe. Agregar auth para clientes:
- Ver historial de pedidos
- Wishlist persistente
- Datos de envío guardados

#### Q. Variantes de color
`ProductoVariante` ya tiene campo `color`. El admin actualmente usa `color = 'STD'`. Activar la selección de color en el storefront.

#### R. Reseñas visibles en ProductPage
`Resena` y la relación existen. Solo falta mostrar las reseñas aprobadas en `ProductPage.vue` y el formulario de nueva reseña.

#### S. Dashboard con métricas del admin de tienda
En el tab inicio del admin: gráfico de ventas de los últimos 30 días, productos más vendidos, stock bajo.

#### T. Soft deletes en Producto y Pedido
Agregar `SoftDeletes` para no perder historial.

#### U. Lazy loading de imágenes
En las tarjetas del catálogo:
```html
<img loading="lazy" ...>
```

---

## 11. Checklist de producción

### Infraestructura
- [ ] Configurar dominio `vendex.app` con wildcard DNS (`*.vendex.app`)
- [ ] SSL wildcard para subdominios
- [ ] Configurar `VENDEX_DOMAIN=vendex.app` en `.env` de producción
- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] `php artisan config:cache && php artisan route:cache && php artisan view:cache`
- [ ] Storage link: `php artisan storage:link`
- [ ] Queue worker en producción (Supervisor)
- [ ] Scheduler en cron: `* * * * * php artisan schedule:run`

### Base de datos
- [ ] Verificar que `referencia` tiene índice compuesto `(store_id, referencia)` y no unique global
- [ ] Backup automático de la DB
- [ ] `php artisan migrate --force` en deploy

### Seguridad
- [ ] Rate limiting en rutas de API
- [ ] HTTPS forzado
- [ ] Verificar hashing de passwords en todas las actualizaciones
- [ ] Auditar que `admin.role` middleware no permite acceso cross-tenant
- [ ] Variables sensibles solo en `.env`, no en código

### Performance
- [ ] `npm run build` para assets optimizados
- [ ] Caché de configuración y rutas
- [ ] Paginación de productos en storefront
- [ ] Lazy loading en imágenes
- [ ] Optimizer de imágenes (Intervention Image o similar)

---

## 12. Flujo completo de una venta

```
1. Cliente visita https://tienda.vendex.app
   → ResolveTenant detecta "tienda" → activa HasTenant scope
   → StorefrontController::home() carga productos del store

2. Cliente filtra y ve producto → ProductPage
   → Selecciona talla → POST /api/carrito/agregar
   → CarritoController crea/actualiza Carrito de sesión

3. Cliente va a /carrito
   → Ve items, ajusta cantidades, abre modal checkout
   → Llena datos (nombre, email, dirección)
   → POST /api/pedidos → PedidoController::store()
   → Crea Cliente + Pedido + PedidoItems
   → Redirect a /pedido/gracias/{id}

4. Admin ve el pedido en /admin (TAB FALTANTE → debe crearse)
   → Actualiza estado
   → Cliente recibe email (PENDIENTE de implementar)
```

---

## 13. Variables de entorno relevantes

```env
APP_NAME=Vendex
APP_URL=https://vendex.app
VENDEX_DOMAIN=vendex.app

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=vendex_db
DB_USERNAME=root
DB_PASSWORD=

MAIL_MAILER=smtp  # o mailgun/resend para producción
MAIL_FROM_ADDRESS=noreply@vendex.app

BROADCAST_DRIVER=pusher  # si se activa tiempo real
PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
```

---

## 14. Estructura de archivos clave

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── AdminController.php         # CRUD admin de tienda
│   │   ├── AdminAuthController.php     # Login/logout
│   │   ├── StorefrontController.php    # Páginas públicas
│   │   ├── SuperAdminController.php    # Gestión global
│   │   └── Api/                       # Controllers REST
│   └── Middleware/
│       ├── ResolveTenant.php           # Detecta el store del request
│       └── EnsureAdminRole.php         # Verifica rol admin
├── Models/
│   ├── Store.php                       # Entidad tenant principal
│   ├── Producto.php                    # Con scopes, accessors de oferta/precio
│   ├── Oferta.php                      # También contiene Resena, Wishlist, HistorialPrecio (¡refactorizar!)
│   └── ...
├── Services/
│   └── TenantManager.php              # Singleton del store activo
└── Traits/
    └── HasTenant.php                   # Global scope por store

resources/js/
├── app.js                              # Bootstrap Inertia + Vue
├── pages/
│   ├── HomePage.vue                    # Catálogo con filtros
│   ├── ProductPage.vue                 # Detalle producto
│   ├── CartPage.vue                    # Carrito + checkout modal
│   ├── admin/
│   │   ├── AdminDashboardPage.vue      # Panel admin (tabs)
│   │   └── SuperAdminDashboard.vue    # Panel super admin
│   └── ...
├── layouts/
│   └── AppLayout.vue                   # Header + footer + cart counter
└── components/
    └── FilterSidebar.vue               # Sidebar de filtros

config/
└── vendex.php                          # Configuración dominio base

database/migrations/
├── 2026_04_17_000001_create_stores_table.php
├── 2026_04_17_000002_add_store_id_to_all_tables.php
└── ...
```

---

## 15. Comparación con el mercado

| Feature | Vendex actual | Shopify | WooCommerce | MercadoShops |
|---------|--------------|---------|-------------|--------------|
| Multi-tenant | ✓ | SaaS nativo | No | Parcial |
| Dominio personalizado | ✓ | ✓ ($) | ✓ | No |
| Panel admin | ✓ básico | ✓ completo | ✓ completo | ✓ limitado |
| Gestión de pedidos | ✗ en admin | ✓ | ✓ | ✓ |
| Pago en línea | ✗ | ✓ múltiple | ✓ múltiple | ✓ MercadoPago |
| WhatsApp checkout | ✗ | plugins | plugins | ✗ |
| Inventario insumos | ✓ (único) | ✗ | plugins | ✗ |
| SEO | ✗ | ✓ | ✓ | Básico |
| Reseñas | ✓ (sin UI) | ✓ | ✓ | ✓ |
| Emails transac. | ✗ | ✓ | ✓ | ✓ |
| Código abierto | ✓ | ✗ | ✓ | ✗ |

**Ventaja diferencial de Vendex**: Inventario de insumos integrado (único en su clase para PYMES manufactureras), multi-tenant propio (sin costos de plataforma por tienda).

---

---

## 16. Cambios implementados — 2026-04-30

### Bugs corregidos
| # | Archivo | Fix |
|---|---------|-----|
| 1 | `TenantServiceProvider.php` | Cambió `singleton` a `bind` para `tenant.store` — evita capturar `null` al registrar |
| 2 | `User.php` | Agregó `store_id` y `email_verified_at` a `$fillable` — el super admin ahora puede crear usuarios con store asignado |
| 3 | `SuperAdminController.php` | Eliminó N+1: usa `->with('store')` en usuarios y `->with(['cliente','store'])` en pedidos recientes |
| 4 | `SuperAdminController.php` | `updateUserPassword` usa `Hash::make()` explícito |
| 5 | `AdminController.php` | Búsqueda de productos: removió columnas inexistentes `nombre`/`codigo`, usa `descripcion` (que sí existe) |
| 6 | `HandleInertiaRequests.php` | Reemplazó `StoreSetting::first()` por `TenantManager::getStore()` — ahora el nombre y logo vienen del store correcto |
| 7 | `AppLayout.vue` | Footer muestra `appName` dinámico en lugar de "NOVA COMMERCE BASE" hardcodeado |
| 8 | `Oferta.php` | Separó los 4 modelos en archivos individuales: `Resena.php`, `Wishlist.php`, `HistorialPrecio.php` |
| 9 | `StorefrontController.php` | Limitó productos a 200 en el home para evitar cargar catálogos enormes en el cliente |
| 10 | `StorefrontController.php` | Corregió `nuevaColeccion` — usaba campo `nuevo` en vez de `nueva_coleccion` |
| 11 | `api.php` | Agregó `throttle:60,1` a todas las rutas y `throttle:5,1` extra a `POST /api/pedidos` |
| 12 | `PedidoController.php` | Valida stock disponible antes de crear el pedido |
| 13 | `PedidoController.php` | Decrementa `ProductoVariante.stock` al confirmar el pedido |
| 14 | `Producto.php` | Agregó `nombre`, `descripcion`, `destacado`, `nuevo`, `nueva_coleccion` al `$fillable` |

### Nuevas features implementadas
| Feature | Archivos |
|---------|---------|
| Tab "Pedidos" en admin | `AdminController.php` + `web.php` + `AdminDashboardPage.vue` |
| Campo `nombre` en productos | Migración `2026_04_30_000001` + `AdminController` + `AdminDashboardPage.vue` + `StorefrontController` |
| Campo `descripcion` funcional | `AdminController.php` + formulario admin + storefront |
| Botón WhatsApp en catálogo | `HomePage.vue` + `ProductPage.vue` + `AppLayout.vue` |
| WhatsApp en footer | `AppLayout.vue` |
| Teléfono del store en Inertia shared | `HandleInertiaRequests.php` — expone `app.whatsapp` |

### Pendiente de ejecutar al iniciar servidor
```bash
php artisan migrate   # Agrega columna nombre a productos
```

*Este documento debe actualizarse cada vez que se añadan features importantes, corrijan bugs críticos o cambie la arquitectura.*
