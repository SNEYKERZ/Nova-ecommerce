# Análisis Completo del Sistema de Galerías

> Documento de referencia para sesiones futuras.
> Fecha: 2026-05-20

---

## Arquitectura Actual

```
Gallery ──┬── GalleryImage ──┬── GalleryImageProduct ── Producto
          │                  │    (tabla pivote)
          │                  │      gallery_image_id
          │                  │      producto_id
          │                  │      orden
          │                  │
          │                  └── (imagen, aspect_ratio, orden)
          │
          └── (nombre, descripcion, orden, activo)
```

### Lo que YA existe y funciona

**Modelos:**
- `app/Models/Gallery.php` — Relaciones: `imagenes()`, `store()`, scope `activas()`
- `app/Models/GalleryImage.php` — Relaciones: `gallery()`, `productos()`, accessor `imagen_url`
- `app/Models/GalleryImageProduct.php` (tabla pivote) — `gallery_image_id`, `producto_id`, `orden`

**Backend (`app/Http/Controllers/GalleryController.php`):**
- `index()` — Renderiza Inertia `admin/GalleryPage` con galerías (con imágenes y productos asociados) y productos disponibles
- `show()` — JSON detalle de galería
- `store()` — Crear galería (JSON 201)
- `update()` — Actualizar galería (JSON) — SOLO nombre/descripción/activo
- `destroy()` — Eliminar galería con imágenes del storage (JSON)
- `addImage()` — Subir imagen a galería (JSON 201)
- `deleteImage()` — Eliminar imagen (JSON)
- `associateProduct()` — Asociar producto a imagen (JSON)
- `disassociateProduct()` — Desasociar producto de imagen (JSON)

**Frontend Admin (`resources/js/pages/admin/GalleryPage.vue`):**
- CRUD de galerías mediante `fetch()` con JSON (sin Inertia form submission)
- Subida de imágenes con FormData
- Asociación/desasociación de productos a imágenes
- Edición de aspect_ratio

**Frontend Público (`resources/js/pages/GalleryPage.vue`):**
- Grid masonry de galerías activas con imágenes
- Modal (`resources/js/components/GalleryModal.vue`) con:
  - Imagen a la izquierda
  - Productos asociados a la derecha
  - Selector de talle/color (si aplica)
  - Control de cantidad
  - Botón "Agregar al Carrito" conectado a `/api/carrito/agregar`
  - Dispara evento `cart-updated` en window

**Rutas admin (bajo middleware `['auth', 'admin.role']` y prefix `admin`):**
```
GET    /admin/galleries                          → index     (Inertia)
POST   /admin/galleries                          → store     (JSON)
GET    /admin/galleries/{gallery}                → show      (JSON)
PUT    /admin/galleries/{gallery}                → update    (JSON)
DELETE /admin/galleries/{gallery}                → destroy   (JSON)
POST   /admin/galleries/{gallery}/images         → addImage  (JSON)
DELETE /admin/galleries/{gallery}/images/{imagen} → deleteImage (JSON)
POST   /admin/galleries/{gallery}/images/{imagen}/products   → associateProduct (JSON)
DELETE /admin/galleries/{gallery}/images/{imagen}/products/{gip} → disassociateProduct (JSON)
```

---

## 🔥 Problemas Identificados

### ⚠️ CSRF Token Stale Post-Login (CORREGIDO 2026-05-20)

**Problema:** Todas las llamadas `fetch()` a endpoints POST/PUT/DELETE desde el admin fallaban porque el `<meta csrf-token>` del DOM contenía el token de la sesión ANTERIOR al login. Al loguearse, Laravel regenera la sesión (seguridad), pero Inertia navega del lado del cliente sin recargar el layout Blade. El meta tag nunca se actualiza.

**Solución:** 
1. Se agregó `'csrf_token' => csrf_token()` a los shared props de Inertia en `HandleInertiaRequests.php`
2. Se cambió `csrf()` en `GalleryPage.vue` para leer de `usePage().props.csrf_token` en vez del DOM

**Por qué funciona:** Cada navegación Inertia envía los shared props actualizados. El token siempre está sincronizado con la sesión actual.

**⚠️ Pendiente:** Revisar TODAS las demás páginas admin que usen `fetch()` con CSRF desde el DOM.

---

### 1. Redirección Externa / Panel en Blanco

**Síntoma:** Al gestionar galerías, el sistema redirige fuera del panel o la página se ve en blanco.

**Causa raíz:** El dashboard admin (`/admin`) se renderiza en blanco (problema no resuelto de Inertia v3 + componentes Vue). Cuando el usuario navega al panel de galerías, puede encontrar el mismo problema de renderizado.

**Factor agravante:** Las llamadas `fetch()` desde el admin NO envían el header `X-Inertia`. Si la sesión expira, el servidor responde con 302 a `/admin/login`, `fetch()` sigue la redirección automáticamente, y `res.json()` sobre HTML falla.

**Ruta de test creada:** `/admin/test-inertia` — renderiza `TestPage.vue`, página Vue mínima para aislar si el problema es de Inertia o del componente.

### 2. 🐛 `saveImage()` No Guarda el Aspect Ratio

**Archivo:** `resources/js/pages/admin/GalleryPage.vue:344`
```javascript
await requestJson(
  `/admin/galleries/${editingImage.value.gallery_id}/images/${editingImage.value.id}`,
  'PUT',
  { aspect_ratio: editingImage.value.aspect_ratio }
);
```

**Problema:** No existe una ruta `PUT /galleries/{gallery}/images/{imagen}`. La única ruta PUT:
```php
Route::put('/galleries/{gallery}', [GalleryController::class, 'update']);
```
...solo actualiza `nombre`, `descripcion`, `activo` del modelo Gallery, NO los campos de GalleryImage.

**Consecuencia:** El cambio de `aspect_ratio` se pierde silenciosamente.

### 3. 🐛 Backend No Valida Límite de 8 Imágenes

**Archivo:** `app/Http/Controllers/GalleryController.php:171`

El frontend oculta el botón de subida con `v-if="gallery.imagenes.length < 8"`, pero `addImage()` no valida:
```php
// FALTA:
if ($galeria->imagenes()->count() >= 8) {
    return response()->json(['message' => 'Máximo 8 imágenes por galería'], 422);
}
```

### 4. UX: Selector de Productos No Tiene Búsqueda

**Archivo:** `resources/js/pages/admin/GalleryPage.vue:126`

Actualmente es un `<select>` plano con `v-for` sobre todos los productos. Con catálogos grandes (>50 productos), es inusable. El usuario solicita un buscador/selector dinámico con filtro mientras se tipea.

### 5. UX: Sin Preview de Imagen Antes de Subir

El `<input type="file">` no muestra preview de la imagen seleccionada antes de hacer clic en "Subir Imagen".

---

## 📋 Checklist de Correcciones

### ✅ Completadas (2026-05-20)

- [x] Agregar ruta `PUT /galleries/{gallery}/images/{imagen}` y método `updateImage()` en GalleryController
- [x] Validar límite de 8 imágenes en `addImage()` (backend)
- [x] Agregar preview de imagen antes de subir (FileReader)
- [x] Reemplazar `<select>` de productos con buscador dinámico con filtro (búsqueda por nombre + referencia)
- [x] Modal público: agregar `tallas_array` y `colores` al response de `StorefrontController::galleries()` con eager loading de `producto.variantes`
- [x] Fix `saveImage()` endpoint: ahora PUT correctamente a `/galleries/{gallery}/images/{imagen}` en vez de `/galleries/{gallery}`
- [x] Agregar estado `uploading` para feedback visual durante subida
- [x] Agregar `feedbackType` para diferenciar errores (rojo) de éxitos (verde)

### ⏳ Pendientes

- [ ] Revisar /admin/test-inertia para debuggear renderizado Inertia (dashboard en blanco)
- [ ] Verificar modal público con productos asociados (prueba funcional)

---

## Modal Público (lo que ya funciona)

`GalleryModal.vue` recibe `gallery` e `imagen` como props. El objeto `imagen.productos` contiene los productos asociados desde el admin. Cada producto tiene:
- `id` / `producto_id` — ID del producto
- `nombre`, `referencia`, `precio`
- `tallas[]`, `colores[]` (opcional, desde la BD)
- Botón "Agregar al Carrito" que POST a `/api/carrito/agregar`

---

## Estado del Panel Admin en Blanco

El dashboard `/admin` se renderiza completamente en blanco. Este problema NO está resuelto y afecta potencialmente a TODAS las páginas admin renderizadas por Inertia. Ver notas en sesión anterior sobre:
- Versiones: inertia-laravel v3.0.2, @inertiajs/vue3 v3.0.3
- SSR Gateway intenta conectar a servidor SSR y falla silenciosamente
- app.js agregó `errorHandler` de Vue y listeners de `window.onerror` y `unhandledrejection`
