---
version: 1
slug: "resources-js-pages-homepage-vue"
primary_target: "resources/js/pages/HomePage.vue"
related_targets: ["resources/js/pages/GalleryPage.vue","resources/js/pages/ProductPage.vue","resources/js/pages/CartPage.vue","resources/js/layouts/AppLayout.vue"]
---

## Scope

Storefront público multi-tenant: layout (header/footer), catálogo (home), galería, producto, carrito, nosotros, gracias. Modo: Persuade (catálogo/producto) con la galería en Experience. El panel admin queda fuera.

Audiencia: comprador móvil que llega desde redes/WhatsApp; debe encontrar, filtrar y agregar al carrito en segundos. Debe servir para moda, restaurante, autopartes y general (preset por `rubro`).

## Direction contract

THESIS: Cada tienda es una hoja de contactos de sus productos: cuadros numerados en una rejilla apretada y lo que el comprador elige queda marcado a lápiz graso. Rechaza la rejilla de tarjetas redondeadas con sombra y el filtro escondido en un panel lateral.

OWN-WORLD: Papel fotográfico mate (fondo de la tienda) con medianiles finos de tinta; cuadros sin radio ni sombra; numeración Nº 001 en cifras tabulares; grotesca condensada para display, sans de trabajo para texto. El color de la tienda es el lápiz graso: sólo marca lo activo (filtro elegido, en carrito, oferta, paso actual). Trazo de lápiz = SVG dibujado alrededor del elemento activo.

STORY: El visitante ve de inmediato la hoja completa de la tienda, entiende que puede marcar categorías y filtros sin abrir nada, y agrega al carrito desde el cuadro; la galería es la ampliación de la hoja: fotos que encajan sin huecos y que se compran.

FIRST VIEWPORT: Banda con el nombre de la tienda en display condensado enorme (≈ 9–12vw) a la izquierda y el conteo de cuadros a la derecha; si hay carrusel, ocupa una tira ancha tipo copia ampliada debajo. Riel de filtros fijo: buscador + todas las categorías visibles + talla/precio/orden como listas desplegables cortas. Rejilla numerada inicia dentro del primer viewport en escritorio.

FORM: Hoja de contactos de estudio fotográfico, posición 7 de la lista; seed:b41e477b9703c4fe. Raises: riel con todas las opciones visibles (Tubo nixie), color reservado para lo activo (Autómata), numeración estricta (Grulla).

Signature interaction: marca de lápiz graso que se dibuja (stroke-dashoffset) alrededor de la categoría elegida y del cuadro recién agregado al carrito. Motion: un único trazo dibujado; entradas de la rejilla con fundido corto escalonado sólo al cargar.

FINISH: unreviewed and undocumented is unfinished; this build ends with the finish review, the verdict, DESIGN.md, and every shipping raster carrying its provenance

## Rubro presets

- general: grotesca condensada + sans; densidad media.
- moda: display más condensado y grande, rejilla 3:4, más aire.
- restaurante: display redondeado/cálido, cuadros 1:1, textos "Menú", "Platos", sin tallas.
- autopartes: densidad alta, cuadros 1:1, referencia (SKU) visible en el cuadro, textos "Repuestos", "Referencia".
