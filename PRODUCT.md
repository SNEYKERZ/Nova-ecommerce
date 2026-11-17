# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

- **Comprador final** (primario en el storefront): llega a una tienda concreta desde redes sociales, WhatsApp o un link directo, casi siempre en el celular. Su trabajo: encontrar rápido lo que busca (filtrar por categoría, talla, precio), ver bien el producto y comprar o pedir por WhatsApp.
- **Dueño de la tienda** (pyme colombiana): configura su tienda (logo, colores, carrusel, galerías, productos) desde `/admin`. Necesita que su tienda se vea profesional sin saber de diseño.
- **Super admin de Vendex**: crea y gestiona tiendas desde `/super-admin`.

## Product Purpose

Vendex es un SaaS de e-commerce multi-tenant: una sola plataforma sirve muchas tiendas, cada una con su subdominio o dominio propio, catálogo, inventario, pedidos y configuración visual aislados. Éxito = que cualquier tienda, sea del rubro que sea, se vea profesional y venda sin que el dueño tenga que diseñar nada.

## Positioning

Una sola base sirve rubros muy distintos (ropa, restaurante, autopartes/motos, general) y cada tienda debe sentirse propia: colores configurables por tienda y, desde este rediseño, un preset por rubro que adapta tono, densidad y vocabulario. Galerías tipo lookbook con productos etiquetados en cada foto ("compra la foto").

## Operating Context

- Mercado colombiano: precios en COP, checkout por la web o por WhatsApp.
- Tráfico mayoritariamente móvil (asumido por canal de adquisición: redes y WhatsApp).
- El dueño sube fotos de galería eligiendo una proporción (1:1, 2:3, 3:2); la galería debe acomodarlas sin espacios en blanco.

## Capabilities and Constraints

- Stack: Laravel 12, Vue 3 + Inertia 3, Tailwind v4, Vite 6. PHP máximo 8.3 en producción.
- Colores por tienda: `bg_color`, `navbar_color`, `footer_color`, `navbar_text_color`, `footer_text_color`; `configuracion` JSON en `stores`.
- Storefront: catálogo con carrusel, promociones, filtros (categoría, talla, precio, fecha, nueva colección, orden), paginación "ver más"; producto con variantes por talla; carrito; galerías con productos etiquetados; página Nosotros; página de gracias.
- El rediseño cubre solo el storefront público; el panel admin no cambia de aspecto (salvo el selector de rubro).

## Brand Commitments

- Cada tienda es la marca protagonista; Vendex aparece solo como "Hecho con Vendex" en el footer.
- La identidad visual debe respetar los colores que el dueño configura y seguir siendo legible (contraste automático ya existente).

## Evidence on Hand

- Contenido real: productos, fotos y galerías que suben las tiendas. No hay testimonios, reseñas verificadas ni métricas de ventas; no inventarlas.

## Product Principles

1. La tienda primero: el diseño enmarca el producto y la marca del dueño, no compite con ellos.
2. Encontrar rápido: filtros visibles y de un toque, sin esconder lo esencial detrás de paneles.
3. Funciona para cualquier rubro: nada de texto ni iconografía atada a ropa cuando la tienda es otra cosa.
4. Móvil primero, con escritorio que aproveche el espacio.

## Accessibility & Inclusion

WCAG AA: contraste garantizado sobre colores configurados por el dueño, navegación por teclado, `prefers-reduced-motion` respetado (ya hay base en el código).
