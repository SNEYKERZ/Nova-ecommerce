# Guía de Optimización de Imágenes - WebCaps Laravel

## Resumen de Optimizaciones Implementadas

### 1. **Lazy Loading**
✅ Todas las imágenes de galerías ahora usan `loading="lazy"` y `decoding="async"`
- Las imágenes se cargan solo cuando entran en el viewport
- La decodificación es asincrónica para no bloquear el hilo principal

### 2. **Estructura Multi-tenant para Banners**
✅ Implementado filtrado por `store_id` en:
- `StorefrontController::getCatalogBanners()` - Filtra banners por tenant
- `AdminController::getCatalogBanners()` - Filtra en admin
- `AdminController::destroyCatalogBanner()` - Elimina solo banners del store actual
- `AdminController::upsertCatalogBannerRecord()` - Asigna store_id al guardar

### 3. **Optimización de Queries (N+1)**
✅ `StorefrontController::galleries()` - Eager loading de productos
- Antes: N+1 queries por imagen (1 query por imagen para productos)
- Ahora: 2 queries totales con eager loading

### 4. **Diseño de Galería Pública**
✅ GalleryPage.vue rediseñado:
- Full-width sin restricción de ancho
- Gap mínimo entre imágenes (0.5rem = 2px)
- Bordes rectos (sin `border-radius`)
- Grid responsivo (1 columna móvil, 2 tablets, 3 escritorio, 4 pantallas grandes)

---

## Recomendaciones Pendientes de Conversión a WebP

### **Por qué WebP?**
- 25-35% de compresión adicional vs JPEG/PNG
- Soportado por todos los navegadores modernos (>97%)
- Tamaño promedio: 50-60KB vs 80-100KB en JPEG

### **Implementación en tu Proyecto**

#### **Opción 1: Conversión Manual (Simple)**
```bash
# Instalar ImageMagick
# En Windows (Laragon): ya viene incluido

# Convertir todas las imágenes
for /R %%f in (public\images\*.jpg public\images\*.png) do (
  magick convert "%%f" "%%~dpnf.webp"
)
```

#### **Opción 2: Middleware de Servidor (Recomendado)**
En `.htaccess` (si usas Apache):
```apache
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteCond %{HTTP_ACCEPT} image/webp
  RewriteCond %{REQUEST_FILENAME}.webp -f
  RewriteRule ^(.*)$ $1.webp [T=image/webp,L]
  
  Header add Vary Accept env=REDIRECT_webp
</IfModule>
```

#### **Opción 3: Picture Tags en Vue (Más Control)**
```vue
<picture>
  <source :srcset="imagen.imagen_url.replace(/\.\w+$/, '.webp')" type="image/webp">
  <img :src="imagen.imagen_url" :alt="title" loading="lazy" decoding="async">
</picture>
```

---

## Rutas de Almacenamiento de Imágenes

### **Banners (CatalogBanner)**
- **Ruta física**: `public/images/carrusel/`
- **Ruta DB**: `images/carrusel/visual_XXX.jpg`
- **URL pública**: `asset('storage/images/carrusel/visual_XXX.jpg')`

### **Galerías (Gallery)**
- **Ruta física**: `storage/app/public/galleries/`
- **Ruta DB**: `galleries/XXXXX.jpg`
- **URL pública**: `asset('storage/galleries/XXXXX.jpg')`

### **Productos (ProductoImagen)**
- **Ruta física**: `storage/app/public/productos/`
- **Ruta DB**: `productos/XXXXX.jpg`
- **URL pública**: `asset('storage/productos/XXXXX.jpg')`

---

## Checklist de Optimización Completado

- ✅ Lazy loading en todas las imágenes
- ✅ Eager loading en queries (eliminado N+1)
- ✅ Filtrado multi-tenant en banners
- ✅ Diseño full-width de galería
- ✅ Bordes rectos en imágenes
- ✅ Gap mínimo entre elementos
- ✅ Atributos de decoding="async"
- ⏳ Conversión a WebP (ver instrucciones arriba)
- ⏳ CDN/Cache headers (opcional)

---

## Testing de Rendimiento

### Medir mejoras con Chrome DevTools:
1. Abre Developer Tools (F12)
2. Performance tab → Click "Record"
3. Navega por la galería
4. Observa:
   - **Métrica clave**: Largest Contentful Paint (LCP)
   - **Métrica clave**: First Input Delay (FID)
   - **Métrica clave**: Cumulative Layout Shift (CLS)

### Lighthouse Score (debería mejorar 20-30 puntos):
1. Lighthouse tab
2. Generate report
3. Busca sección "Opportunities" para más optimizaciones

---

## Próximos Pasos (Opcionales)

1. **AVIF Format** (aún más compresión que WebP)
2. **Responsive Images** (srcset con diferentes tamaños)
3. **Image CDN** (Cloudflare, Bunny, imgix)
4. **Asset Preloading** (preload críticas imágenes above-the-fold)
