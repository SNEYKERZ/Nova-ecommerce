<template>
  <AppLayout>
    <Head title="Admin" />
    <section class="mx-auto w-full max-w-[96rem] px-4 py-8 sm:px-6 lg:px-10">
      <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h1 class="text-3xl font-semibold tracking-tight text-slate-900">Panel Administrativo</h1>
        <p class="mt-2 text-sm text-slate-600">Vista corporativa para control operativo de tienda, fabrica, inventario y promociones.</p>
        <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Productos</p><p class="text-2xl font-semibold text-slate-900">{{ stats.productos }}</p></div>
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Insumos</p><p class="text-2xl font-semibold text-slate-900">{{ stats.insumos }}</p></div>
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Promociones</p><p class="text-2xl font-semibold text-slate-900">{{ stats.ofertas }}</p></div>
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="text-xs text-slate-500">Promociones activas</p><p class="text-2xl font-semibold text-slate-900">{{ stats.ofertasActivas }}</p></div>
        </div>
      </div>

      <div class="mb-5 flex flex-wrap gap-2">
        <button v-for="tab in tabs" :key="tab.key" class="rounded-full border px-4 py-2 text-sm font-semibold cursor-pointer" :class="activeTab === tab.key ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-300 bg-white text-slate-700'" @click="activeTab = tab.key">
          {{ tab.label }}
        </button>
      </div>

      <section v-if="activeTab === 'configuracion'" class="space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <h2 class="text-xl font-semibold text-slate-900">Configuracion de Tienda</h2>
          <p class="mt-1 text-sm text-slate-600">Personaliza nombre y logo global del e-commerce.</p>
          <div class="mt-4 grid gap-4 lg:grid-cols-2">
            <div class="space-y-3">
              <div>
                <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Nombre de tienda</label>
                <input v-model="settingsForm.store_name" class="w-full rounded-xl border border-slate-300 px-3 py-2.5" placeholder="Nombre tienda" />
              </div>
              <div>
                <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Logo</label>
                <input type="file" accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml" class="w-full rounded-xl border border-slate-300 px-3 py-2.5" @change="handleSettingsLogo" />
              </div>
              <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white" @click="saveSettings">Guardar configuracion</button>
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
              <p class="text-xs font-semibold uppercase text-slate-500">Vista previa</p>
              <div class="mt-3 flex items-center gap-3">
                <img v-if="settingsLogoPreview" :src="settingsLogoPreview" class="h-14 w-14 rounded-full object-cover" alt="Logo" />
                <div class="text-lg font-semibold text-slate-900">{{ settingsForm.store_name || 'Nombre Tienda' }}</div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section v-if="activeTab === 'productos'" class="space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <h2 class="text-xl font-semibold text-slate-900">Crear / Editar Producto</h2>
<div class="mt-4 grid gap-3 lg:grid-cols-2">
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Referencia del producto</label>
              <input v-model="productForm.referencia" placeholder="Ej: CAMISETA-001" class="rounded-xl border border-slate-300 px-3 py-2.5 w-full" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Precio (COP)</label>
              <input v-model.number="productForm.precio" type="number" min="0" placeholder="Ej: 50000" class="rounded-xl border border-slate-300 px-3 py-2.5 w-full" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Categoría</label>
              <select v-model.number="productForm.categoria_id" class="rounded-xl border border-slate-300 px-3 py-2.5 w-full"><option :value="null">Selecciona una categoría</option><option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.categoria }}</option></select>
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Estado del producto</label>
              <select v-model="productForm.estado" class="rounded-xl border border-slate-300 px-3 py-2.5 w-full"><option value="DISPONIBLE">Disponible</option><option value="NO_DISPONIBLE">No disponible</option></select>
            </div>
            <div class="lg:col-span-2">
              <label class="flex items-center gap-2 rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 cursor-pointer">
                <input type="checkbox" v-model="productForm.nueva_coleccion" class="rounded" />
                <span class="text-sm font-semibold text-slate-700">Marcar como Nueva Colección</span>
              </label>
            </div>

<div class="lg:col-span-2">
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Imágenes del producto (máximo 4)</label>
              <input type="file" multiple accept="image/png,image/jpeg,image/jpg,image/webp" class="w-full rounded-xl border border-slate-300 px-3 py-2.5" @change="handleProductImages" />
            </div>

            <div v-if="productForm.existingImages.length" class="lg:col-span-2">
              <p class="mb-1 text-xs font-semibold uppercase text-slate-500">Imágenes actuales</p>
              <div class="grid grid-cols-2 gap-2 sm:grid-cols-4"> <img v-for="image in productForm.existingImages" :key="image.id" :src="image.url" class="h-20 w-full rounded-lg object-cover" alt="Imagen" /> </div>
            </div>

            <div v-if="productForm.newImageNames.length" class="lg:col-span-2 text-sm text-slate-600">
              {{ productForm.newImageNames.join(', ') }}
            </div>

            <div class="lg:col-span-2">
              <p class="mb-2 text-xs font-semibold uppercase text-slate-500">Tallas disponibles y stock por talla</p>
              <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                <label v-for="size in sizeOptions" :key="size" class="flex items-center gap-2 rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 cursor-pointer">
                  <input v-model="productForm.sizeStock[size].enabled" type="checkbox" />
                  <span class="w-10 text-sm font-semibold text-slate-700">{{ size }}</span>
                  <input v-model.number="productForm.sizeStock[size].stock" type="number" min="0" :disabled="!productForm.sizeStock[size].enabled" placeholder="Stock" class="w-full rounded-md border border-slate-300 px-2 py-1 text-sm" />
                </label>
              </div>
            </div>
          </div>

          <div class="mt-4 flex flex-wrap gap-2">
<button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white cursor-pointer" @click="saveProduct">{{ productForm.id ? 'Actualizar' : 'Crear' }}</button>
            <button class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 cursor-pointer" @click="resetProductForm">Limpiar</button>
          </div>
</div>

        <!-- Buscador y tabla de productos -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="mb-4 flex items-center gap-3">
            <input v-model="search" type="text" placeholder="Buscar por referencia, SKU, código o categoría..." class="flex-1 rounded-xl border border-slate-300 px-3 py-2.5" />
          </div>
          <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full text-sm">
              <thead class="bg-slate-100 text-left text-slate-600"><tr><th class="px-3 py-2">Nueva</th><th class="px-3 py-2">Ref</th><th class="px-3 py-2">Categoria</th><th class="px-3 py-2">Precio</th><th class="px-3 py-2">Tallas</th><th class="px-3 py-2">Stock</th><th class="px-3 py-2">Img</th><th class="px-3 py-2">Acciones</th></tr></thead>
              <tbody>
                <tr v-for="item in productosList" :key="item.id" class="border-t border-slate-200">
                  <td class="px-3 py-2"><button @click="toggleNuevaColeccion(item.id)" class="rounded-full px-2 py-1 text-xs font-semibold cursor-pointer" :class="item.nueva_coleccion ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-400'">{{ item.nueva_coleccion ? 'NUEVA' : '--' }}</button></td>
                  <td class="px-3 py-2">{{ item.referencia }}</td><td class="px-3 py-2">{{ item.categoria }}</td><td class="px-3 py-2">{{ money(item.precio) }}</td><td class="px-3 py-2">{{ item.tallas }}</td><td class="px-3 py-2">{{ item.stock_total }}</td><td class="px-3 py-2">{{ item.imagenes?.length || 0 }}</td>
                  <td class="px-3 py-2"><div class="flex gap-2"><button class="rounded-md border border-slate-300 px-2 py-1 cursor-pointer" @click="editProduct(item)">Editar</button><button class="rounded-md border border-red-300 px-2 py-1 text-red-600 cursor-pointer" @click="deleteProduct(item.id)">Eliminar</button></div></td>
                </tr>
              </tbody>
            </table>
          </div>
          <!-- Paginación productos -->
          <div v-if="productosMeta" class="mt-4 flex items-center justify-between">
            <p class="text-sm text-slate-600">Mostrando {{ productosMeta.from }} - {{ productosMeta.to }} de {{ productosMeta.total }}</p>
            <div class="flex gap-1">
              <Link v-for="link in productosLinks" :key="link.label" :href="link.url" class="rounded-md border border-slate-300 px-3 py-1 text-sm cursor-pointer" :class="link.active ? 'bg-slate-900 text-white' : 'bg-white text-slate-700'" :disabled="!link.url" v-html="link.label"></Link>
            </div>
          </div>
        </div>
      </section>

<section v-if="activeTab === 'promociones'" class="space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <h2 class="text-xl font-semibold text-slate-900">Crear / Editar Promoción</h2>
          <div class="mt-4 grid gap-3 md:grid-cols-2">
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Título de la promoción</label>
              <input v-model="offerForm.titulo" placeholder="Ej: Promo Verano 50%" class="rounded-xl border border-slate-300 px-3 py-2.5 w-full" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Descripción (opcional)</label>
              <input v-model="offerForm.descripcion" placeholder="Ej: Descuento especial en camisas" class="rounded-xl border border-slate-300 px-3 py-2.5 w-full" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Aplicar a producto específico (opcional)</label>
              <select v-model.number="offerForm.producto_id" class="rounded-xl border border-slate-300 px-3 py-2.5 w-full"><option :value="null">Selecciona un producto</option><option v-for="p in productosList" :key="p.id" :value="p.id">{{ p.referencia }}</option></select>
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Aplicar a categoría (opcional)</label>
              <select v-model.number="offerForm.categoria_id" class="rounded-xl border border-slate-300 px-3 py-2.5 w-full"><option :value="null">Selecciona una categoría</option><option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.categoria }}</option></select>
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Porcentaje de descuento (%)</label>
              <input v-model.number="offerForm.descuento_porcentaje" type="number" min="0" max="100" placeholder="Ej: 20" class="rounded-xl border border-slate-300 px-3 py-2.5 w-full" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Descuento fijo en COP (opcional)</label>
              <input v-model.number="offerForm.descuento_fijo" type="number" min="0" placeholder="Ej: 10000" class="rounded-xl border border-slate-300 px-3 py-2.5 w-full" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Precio especial oferta (opcional)</label>
              <input v-model.number="offerForm.precio_oferta" type="number" min="0" placeholder="Ej: 35000" class="rounded-xl border border-slate-300 px-3 py-2.5 w-full" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Estado de la promoción</label>
              <select v-model="offerForm.esta_activa" class="rounded-xl border border-slate-300 px-3 py-2.5 w-full"><option :value="true">Activa</option><option :value="false">Inactiva</option></select>
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Fecha de inicio</label>
              <input v-model="offerForm.fecha_inicio" type="datetime-local" class="rounded-xl border border-slate-300 px-3 py-2.5 w-full" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Fecha de fin</label>
              <input v-model="offerForm.fecha_fin" type="datetime-local" class="rounded-xl border border-slate-300 px-3 py-2.5 w-full" />
            </div>
          </div>
<div class="mt-4 flex gap-2"><button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white cursor-pointer" @click="saveOffer">{{ offerForm.id ? 'Actualizar' : 'Crear' }}</button><button class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold cursor-pointer" @click="resetOfferForm">Limpiar</button></div>
        </div>

        <!-- Buscador y tabla de ofertas -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="mb-4 flex items-center gap-3">
            <input v-model="search" type="text" placeholder="Buscar por título, descripción, producto o categoría..." class="flex-1 rounded-xl border border-slate-300 px-3 py-2.5" />
          </div>
          <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full text-sm">
              <thead class="bg-slate-100 text-left text-slate-600"><tr><th class="px-3 py-2">Titulo</th><th class="px-3 py-2">Aplica a</th><th class="px-3 py-2">Vigencia</th><th class="px-3 py-2">Estado</th><th class="px-3 py-2">Acciones</th></tr></thead>
              <tbody>
                <tr v-for="item in ofertasList" :key="item.id" class="border-t border-slate-200">
                  <td class="px-3 py-2">{{ item.titulo }}</td>
                  <td class="px-3 py-2">{{ item.producto || item.categoria }}</td>
                  <td class="px-3 py-2">{{ item.fecha_inicio?.slice(0, 10) }} - {{ item.fecha_fin?.slice(0, 10) }}</td>
                  <td class="px-3 py-2">{{ item.esta_activa ? 'Activa' : 'Inactiva' }}</td>
                  <td class="px-3 py-2"><div class="flex gap-2"><button class="rounded-md border border-slate-300 px-2 py-1 cursor-pointer" @click="editOffer(item)">Editar</button><button class="rounded-md border border-red-300 px-2 py-1 text-red-600 cursor-pointer" @click="deleteOffer(item.id)">Eliminar</button></div></td>
                </tr>
              </tbody>
            </table>
          </div>
          <!-- Paginación ofertas -->
          <div v-if="ofertasMeta" class="mt-4 flex items-center justify-between">
            <p class="text-sm text-slate-600">Mostrando {{ ofertasMeta.from }} - {{ ofertasMeta.to }} de {{ ofertasMeta.total }}</p>
            <div class="flex gap-1">
              <Link v-for="link in ofertasLinks" :key="link.label" :href="link.url" class="rounded-md border border-slate-300 px-3 py-1 text-sm cursor-pointer" :class="link.active ? 'bg-slate-900 text-white' : 'bg-white text-slate-700'" :disabled="!link.url" v-html="link.label"></Link>
            </div>
          </div>
        </div>
      </section>

<section v-if="activeTab === 'noticias'" class="space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <h2 class="text-xl font-semibold text-slate-900">Noticias / Promociones Activas</h2>
          <textarea v-model="newsText" rows="5" class="mt-3 w-full rounded-xl border border-slate-300 px-3 py-2.5"></textarea>
          <button class="mt-3 rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white" @click="saveNews">Guardar noticias</button>
        </div>
      </section>

      <section v-if="activeTab === 'bloques'" class="space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <h2 class="text-xl font-semibold text-slate-900">Bloques del Home</h2>
          <p class="mt-1 text-sm text-slate-600">Configura los dos bloques superiores del home (banner o texto).</p>
          
          <!-- Selector de Bloque -->
          <div class="mt-4 flex gap-2">
            <button v-for="n in [1, 2]" :key="n" class="rounded-xl px-4 py-2 text-sm font-semibold cursor-pointer" :class="bloqueActivo === n ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700'" @click="bloqueActivo = n">
              Bloque {{ n }}
            </button>
          </div>

          <!-- Formulario del Bloque -->
          <div class="mt-4 grid gap-4">
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Tipo</label>
              <select v-model="bloqueForm.tipo" class="w-full rounded-xl border border-slate-300 px-3 py-2.5">
                <option value="banner">Banner (carrusel de imagenes)</option>
                <option value="texto">Texto</option>
              </select>
            </div>
            
            <div class="flex items-center gap-2">
              <input type="checkbox" v-model="bloqueForm.activo" id="bloqueActivo" class="rounded border-slate-300" />
              <label for="bloqueActivo" class="text-sm font-semibold text-slate-700">Bloque activo</label>
            </div>

            <!-- Campos de Texto -->
            <template v-if="bloqueForm.tipo === 'texto'">
              <div>
                <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Titulo</label>
                <input v-model="bloqueForm.titulo" placeholder="Titulo del bloque" class="w-full rounded-xl border border-slate-300 px-3 py-2.5" />
              </div>
              <div>
                <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Contenido</label>
                <textarea v-model="bloqueForm.contenido" rows="3" placeholder="Contenido del bloque" class="w-full rounded-xl border border-slate-300 px-3 py-2.5"></textarea>
              </div>
              <div>
                <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Tamano de texto</label>
                <select v-model="bloqueForm.tamano_texto" class="w-full rounded-xl border border-slate-300 px-3 py-2.5">
                  <option value="normal">Normal</option>
                  <option value="grande">Grande</option>
                </select>
              </div>
            </template>

            <!-- Imagenes para Banner -->
            <template v-if="bloqueForm.tipo === 'banner'">
              <div>
                <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Imagenes (max 4)</label>
                <input type="file" multiple accept="image/*" class="w-full rounded-xl border border-slate-300 px-3 py-2.5" @change="handleBloqueImagenes" />
              </div>
              
              <!-- Lista de imagenes actuales -->
              <div v-if="bloqueForm.imagenes?.length" class="space-y-2">
                <p class="text-xs font-semibold uppercase text-slate-500">Imagenes actuales</p>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                  <div v-for="img in bloqueForm.imagenes" :key="img.id" class="relative rounded-lg border border-slate-200 p-2">
                    <img :src="img.imagen" class="h-20 w-full rounded object-cover" />
                    <input v-model="img.url_destino" placeholder="URL destino (opcional)" class="mt-1 w-full rounded border border-slate-300 px-2 py-1 text-xs" />
                    <button @click="deleteBloqueImagen(img.id)" class="mt-1 w-full rounded bg-red-100 px-2 py-1 text-xs text-red-600">Eliminar</button>
                  </div>
                </div>
              </div>
            </template>
          </div>

          <div class="mt-4 flex gap-2">
            <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white" @click="saveBloque">Guardar Bloque {{ bloqueActivo }}</button>
          </div>
        </div>
      </section>

<section v-if="activeTab === 'insumos'" class="space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <h2 class="text-xl font-semibold text-slate-900">Crear / Editar Insumo</h2>
          <div class="mt-4 grid gap-3 md:grid-cols-2">
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Nombre del insumo</label>
              <input v-model="supplyForm.nombre" placeholder="Ej: Hilo de algodón" class="rounded-xl border border-slate-300 px-3 py-2.5 w-full" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Código SKU</label>
              <input v-model="supplyForm.sku" placeholder="Ej: HIL-001" class="rounded-xl border border-slate-300 px-3 py-2.5 w-full" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Unidad de medida</label>
              <input v-model="supplyForm.unidad" placeholder="Ej: metro, kilo, unidad" class="rounded-xl border border-slate-300 px-3 py-2.5 w-full" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Tipo de registro de compra</label>
              <select v-model="supplyForm.tipo_registro" class="rounded-xl border border-slate-300 px-3 py-2.5 w-full">
                <option value="UNIDAD">Compra por unidades (cantidad fija)</option>
                <option value="PAQUETE">Compra por paquete (múltiplos)</option>
              </select>
            </div>

            <div v-if="supplyForm.tipo_registro === 'PAQUETE'">
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Unidades por paquete</label>
              <input v-model.number="supplyForm.unidades_por_paquete" type="number" min="1" placeholder="Ej: 100" class="rounded-xl border border-slate-300 px-3 py-2.5 w-full" />
            </div>
            <div v-if="supplyForm.tipo_registro === 'UNIDAD'">
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Cantidad comprada usualmente</label>
              <input v-model.number="supplyForm.cantidad_compra" type="number" min="1" placeholder="Ej: 50" class="rounded-xl border border-slate-300 px-3 py-2.5 w-full" />
            </div>

            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Costo total de la compra (COP)</label>
              <input v-model.number="supplyForm.costo_total_compra" type="number" min="0" placeholder="Ej: 50000" class="rounded-xl border border-slate-300 px-3 py-2.5 w-full" />
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm">
              <p class="text-slate-500">Costo por unidad calculado</p>
              <p class="text-lg font-semibold text-slate-900">{{ money(calculatedSupplyUnitCost) }} COP</p>
            </div>

            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Stock actual en inventario</label>
              <input v-model.number="supplyForm.stock_actual" type="number" min="0" placeholder="Ej: 100" class="rounded-xl border border-slate-300 px-3 py-2.5 w-full" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Stock mínimo (alerta de reorder)</label>
              <input v-model.number="supplyForm.stock_minimo" type="number" min="0" placeholder="Ej: 20" class="rounded-xl border border-slate-300 px-3 py-2.5 w-full" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Nombre del proveedor</label>
              <input v-model="supplyForm.proveedor" placeholder="Ej: Textilmax S.A.S" class="rounded-xl border border-slate-300 px-3 py-2.5 w-full" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Estado del insumo</label>
              <select v-model="supplyForm.activo" class="rounded-xl border border-slate-300 px-3 py-2.5 w-full"><option :value="true">Activo</option><option :value="false">Inactivo</option></select>
            </div>
          </div>

<div class="mt-4 flex gap-2"><button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white cursor-pointer" @click="saveSupply">{{ supplyForm.id ? 'Actualizar' : 'Crear' }}</button><button class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold cursor-pointer" @click="resetSupplyForm">Limpiar</button></div>
        </div>

        <!-- Buscador y tabla de insumos -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="mb-4 flex items-center gap-3">
            <input v-model="search" type="text" placeholder="Buscar por nombre, SKU, código o referencia..." class="flex-1 rounded-xl border border-slate-300 px-3 py-2.5" />
          </div>
          <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full text-sm">
              <thead class="bg-slate-100 text-left text-slate-600"><tr><th class="px-3 py-2">SKU</th><th class="px-3 py-2">Nombre</th><th class="px-3 py-2">Tipo</th><th class="px-3 py-2">Costo total</th><th class="px-3 py-2">Costo unit.</th><th class="px-3 py-2">Stock</th><th class="px-3 py-2">Acciones</th></tr></thead>
              <tbody>
                <tr v-for="item in insumosList" :key="item.id" class="border-t border-slate-200">
                  <td class="px-3 py-2">{{ item.sku }}</td>
                  <td class="px-3 py-2">{{ item.nombre }}</td>
                  <td class="px-3 py-2">{{ item.tipo_registro }}</td>
                  <td class="px-3 py-2">{{ money(item.costo_total_compra) }}</td>
                  <td class="px-3 py-2">{{ money(item.costo_unitario) }}</td>
                  <td class="px-3 py-2" :class="item.stock_actual <= item.stock_minimo ? 'text-red-600 font-semibold' : ''">{{ item.stock_actual }} {{ item.unidad }}</td>
                  <td class="px-3 py-2"><div class="flex gap-2"><button class="rounded-md border border-slate-300 px-2 py-1 cursor-pointer" @click="editSupply(item)">Editar</button><button class="rounded-md border border-red-300 px-2 py-1 text-red-600 cursor-pointer" @click="deleteSupply(item.id)">Eliminar</button></div></td>
                </tr>
              </tbody>
            </table>
          </div>
          <!-- Paginación insumos -->
          <div v-if="insumosMeta" class="mt-4 flex items-center justify-between">
            <p class="text-sm text-slate-600">Mostrando {{ insumosMeta.from }} - {{ insumosMeta.to }} de {{ insumosMeta.total }}</p>
            <div class="flex gap-1">
              <Link v-for="link in insumosLinks" :key="link.label" :href="link.url" class="rounded-md border border-slate-300 px-3 py-1 text-sm cursor-pointer" :class="link.active ? 'bg-slate-900 text-white' : 'bg-white text-slate-700'" :disabled="!link.url" v-html="link.label"></Link>
            </div>
          </div>
        </div>
      </section>

      <p v-if="feedback" class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-800">{{ feedback }}</p>
    </section>
  </AppLayout>
</template>

<script setup>
import { Head, router, Link } from '@inertiajs/vue3';
import { computed, ref, watch, onMounted } from 'vue';
import AppLayout from '../../layouts/AppLayout.vue';

const props = defineProps({
  stats: { type: Object, required: true },
  categorias: { type: Array, default: () => [] },
  productos: { type: [Object, Array], default: () => [] },
  insumos: { type: [Object, Array], default: () => [] },
  ofertas: { type: [Object, Array], default: () => [] },
  noticia: { type: String, default: '' },
  bloques: { type: Object, default: () => ({}) },
  settings: { type: Object, default: () => ({ store_name: 'Nova Commerce', logo_url: null }) },
  filters: { type: Object, default: () => ({ search: '' }) },
});

const tabs = [
  { key: 'configuracion', label: 'Configuracion Tienda' },
  { key: 'productos', label: 'Productos y Stock' },
  { key: 'promociones', label: 'Promociones' },
  { key: 'noticias', label: 'Noticias Activas' },
  { key: 'bloques', label: 'Bloques Home' },
  { key: 'insumos', label: 'Insumos Fabrica' },
];

const sizeOptions = ['XS', 'S', 'M', 'L', 'XL', 'XXL', '28', '30', '32', '34', '36', 'UNICA'];
const activeTab = ref('configuracion');
const feedback = ref('');
const newsText = ref(props.noticia || '');
const search = ref(props.filters.search || '');

const settingsForm = ref({ store_name: props.settings.store_name || 'Nova Commerce', logoFile: null });
const settingsLogoPreview = ref(props.settings.logo_url || null);

const productForm = ref(emptyProduct());
const offerForm = ref(emptyOffer());
const supplyForm = ref(emptySupply());

// Helper para manejar datos paginados (array o objeto con .data)
const resolvePagedData = (data) => {
  if (!data) return [];
  if (Array.isArray(data)) return data;
  if (typeof data !== 'object') return [];
  return data.data || [];
};

// Helper para metadata de paginación
const resolveMeta = (data) => {
  if (!data || typeof data !== 'object' || Array.isArray(data)) return null;
  return { from: data.from, to: data.to, total: data.total, links: data.links };
};

onMounted(() => {
  console.log('=== MOUNTED ===');
  console.log('productos:', props.productos);
  console.log('ofertas:', props.ofertas);
  console.log('insumos:', props.insumos);
});

// Productos
const productosList = computed(() => resolvePagedData(props.productos));
const productosMeta = computed(() => resolveMeta(props.productos));
const productosLinks = computed(() => {
  const meta = resolveMeta(props.productos);
  return meta?.links || [];
});

// Ofertas
const ofertasList = computed(() => resolvePagedData(props.ofertas));
const ofertasMeta = computed(() => resolveMeta(props.ofertas));
const ofertasLinks = computed(() => {
  const meta = resolveMeta(props.ofertas);
  return meta?.links || [];
});

// Insumos
const insumosList = computed(() => resolvePagedData(props.insumos));
const insumosMeta = computed(() => resolveMeta(props.insumos));
const insumosLinks = computed(() => {
  const meta = resolveMeta(props.insumos);
  return meta?.links || [];
});

// Paginación y búsqueda
const performSearch = () => {
  router.get('/admin', { search: search.value }, { replace: true, only: ['productos', 'insumos', 'ofertas'] });
};

watch(search, (val) => {
  performSearch();
});

// Bloques Home
const bloqueActivo = ref(1);
const bloqueForm = ref({ tipo: 'banner', activo: true, titulo: '', contenido: '', tamano_texto: 'normal', imagenes: [], nuevasImagenes: [] });

const initBloqueForm = (bloque) => {
  bloqueForm.value = {
    id: bloque?.id || null,
    tipo: bloque?.tipo || 'banner',
    activo: bloque?.activo ?? true,
    titulo: bloque?.titulo || '',
    contenido: bloque?.contenido || '',
    tamano_texto: bloque?.tamano_texto || 'normal',
    imagenes: bloque?.imagenes || [],
    nuevasImagenes: [],
  };
};

// Cargar bloques desde props
const propsBloques = props.bloques || {};
if (propsBloques[1]) initBloqueForm(propsBloques[1]);
else if (propsBloques[2]) initBloqueForm(propsBloques[2]);

const handleBloqueImagenes = (event) => {
  const files = Array.from(event.target.files || []).slice(0, 4 - (bloqueForm.value.imagenes?.length || 0));
  bloqueForm.value.nuevasImagenes = files;
};

const saveBloque = async () => {
  const formData = new FormData();
  formData.append('posicion', String(bloqueActivo.value));
  formData.append('tipo', bloqueForm.value.tipo);
  formData.append('activo', bloqueForm.value.activo ? '1' : '0');
  if (bloqueForm.value.tipo === 'texto') {
    formData.append('titulo', bloqueForm.value.titulo || '');
    formData.append('contenido', bloqueForm.value.contenido || '');
    formData.append('tamano_texto', bloqueForm.value.tamano_texto);
  }
  
  // Si tiene nuevas imagenes
  bloqueForm.value.nuevasImagenes.forEach(file => formData.append('imagen', file));

  try {
    const res = await fetch('/admin/bloques', {
      method: 'POST',
      headers: { 
        'X-CSRF-TOKEN': csrf(),
        'Accept': 'application/json',
      },
      body: formData,
    });
    const payload = await res.json();
    if (!res.ok || !payload.success) throw new Error(payload.message || 'Error al guardar');
    notify('Bloque guardado');
    refresh();
  } catch (e) {
    console.error('Error saveBloque:', e);
    notify('Error al guardar bloque');
  }
};

const deleteBloqueImagen = async (imgId) => {
  if (!confirm('Eliminar imagen?')) return;
  const bloqueId = bloqueForm.value.id;
  if (!bloqueId) return;
  try {
    const res = await fetch(`/admin/bloques/${bloqueId}/imagenes/${imgId}`, {
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': csrf() },
    });
    const payload = await res.json();
    if (!res.ok || !payload.success) throw new Error(payload.message || 'Error');
    notify('Imagen eliminada');
    refresh();
  } catch (e) {
    notify(e.message);
  }
};

// Cuando cambia el bloque activo, actualizar el formulario
watch(bloqueActivo, (newVal) => {
  const bloque = props.bloques?.[newVal] || null;
  initBloqueForm(bloque);
});

function buildSizeStock() {
  const state = {};
  sizeOptions.forEach((size) => {
    state[size] = { enabled: false, stock: 0 };
  });
  return state;
}

function emptyProduct() {
  return {
    id: null,
    referencia: '',
    precio: null,
    categoria_id: null,
    estado: 'DISPONIBLE',
    nueva_coleccion: false,
    sizeStock: buildSizeStock(),
    newImages: [],
    newImageNames: [],
    existingImages: [],
  };
}

function emptyOffer() {
  const now = new Date();
  const inSeven = new Date(now.getTime() + 7 * 86400000);
  const toLocal = (d) => new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
  return {
    id: null,
    titulo: '',
    descripcion: '',
    producto_id: null,
    categoria_id: null,
    descuento_porcentaje: null,
    descuento_fijo: null,
    precio_oferta: null,
    fecha_inicio: toLocal(now),
    fecha_fin: toLocal(inSeven),
    esta_activa: true,
  };
}

function emptySupply() {
  return {
    id: null,
    nombre: '',
    sku: '',
    unidad: 'unidad',
    tipo_registro: 'UNIDAD',
    unidades_por_paquete: null,
    cantidad_compra: null,
    costo_total_compra: 0,
    stock_actual: 0,
    stock_minimo: 0,
    proveedor: '',
    activo: true,
  };
}

const calculatedSupplyUnitCost = computed(() => {
  const total = Number(supplyForm.value.costo_total_compra || 0);
  if (supplyForm.value.tipo_registro === 'PAQUETE') {
    const units = Number(supplyForm.value.unidades_por_paquete || 0);
    return units > 0 ? total / units : 0;
  }

  const qty = Number(supplyForm.value.cantidad_compra || 0);
  return qty > 0 ? total / qty : 0;
});

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
const money = (value) => new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 2 }).format(value || 0);
const refresh = () => router.reload({ 
  only: ['stats', 'productos', 'categorias', 'insumos', 'ofertas', 'noticia', 'settings'],
  data: search.value ? { search: search.value } : {}
});

const notify = (message) => {
  feedback.value = message;
  setTimeout(() => {
    feedback.value = '';
  }, 2200);
};

const requestJson = async (url, method, body) => {
  const res = await fetch(url, {
    method,
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      'X-CSRF-TOKEN': csrf(),
    },
    body: body ? JSON.stringify(body) : null,
  });

  const payload = await res.json();
  if (!res.ok || !payload.success) throw new Error(payload.message || 'No se pudo completar la accion');
  notify(payload.message || 'Proceso exitoso');
};

const requestFormData = async (url, method, formData) => {
  const res = await fetch(url, {
    method,
    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
    body: formData,
  });

  const payload = await res.json();
  if (!res.ok || !payload.success) throw new Error(payload.message || 'No se pudo completar la accion');
  notify(payload.message || 'Proceso exitoso');
};

const handleSettingsLogo = (event) => {
  const file = event.target.files?.[0] || null;
  settingsForm.value.logoFile = file;
  if (file) settingsLogoPreview.value = URL.createObjectURL(file);
};

const saveSettings = async () => {
  const formData = new FormData();
  formData.append('store_name', settingsForm.value.store_name || 'Nova Commerce');
  if (settingsForm.value.logoFile) formData.append('logo', settingsForm.value.logoFile);
  await requestFormData('/admin/settings', 'POST', withMethod(formData, 'POST'));
  refresh();
};

const handleProductImages = (event) => {
  const files = Array.from(event.target.files || []).slice(0, 4);
  productForm.value.newImages = files;
  productForm.value.newImageNames = files.map((file) => file.name);
};

const selectedStockBySize = () => {
  const entries = [];
  for (const size of sizeOptions) {
    const item = productForm.value.sizeStock[size];
    if (item?.enabled) entries.push({ talla: size, stock: Number(item.stock || 0) });
  }
  return entries;
};

const withMethod = (formData, method) => {
  if (method !== 'POST') formData.append('_method', method);
  return formData;
};

const saveProduct = async () => {
  const stockBySize = selectedStockBySize();
  const formData = new FormData();
  formData.append('referencia', productForm.value.referencia || '');
  formData.append('precio', String(Number(productForm.value.precio || 0)));
  formData.append('categoria_id', String(productForm.value.categoria_id || ''));
  formData.append('estado', productForm.value.estado || 'DISPONIBLE');
  formData.append('nueva_coleccion', productForm.value.nueva_coleccion ? '1' : '0');
  formData.append('tallas', stockBySize.map((item) => item.talla).join(','));
  stockBySize.forEach((item, index) => {
    formData.append(`stock_por_talla[${index}][talla]`, item.talla);
    formData.append(`stock_por_talla[${index}][stock]`, String(item.stock));
  });
  productForm.value.newImages.forEach((file) => formData.append('images[]', file));

  const url = productForm.value.id ? `/admin/productos/${productForm.value.id}` : '/admin/productos';
  await requestFormData(url, 'POST', withMethod(formData, productForm.value.id ? 'PUT' : 'POST'));
  resetProductForm();
  refresh();
};

const editProduct = (item) => {
  const sizeStock = buildSizeStock();
  (item.stock_por_talla || []).forEach((entry) => {
    if (sizeStock[entry.talla]) {
      sizeStock[entry.talla].enabled = true;
      sizeStock[entry.talla].stock = Number(entry.stock || 0);
    }
  });

productForm.value = {
    id: item.id,
    referencia: item.referencia,
    precio: item.precio,
    categoria_id: item.categoria_id,
    estado: item.estado,
    nueva_coleccion: item.nueva_coleccion || false,
    sizeStock,
    newImages: [],
    newImageNames: [],
    existingImages: item.imagenes || [],
  };
  activeTab.value = 'productos';
};

const resetProductForm = () => {
  productForm.value = emptyProduct();
};

const toggleNuevaColeccion = async (id) => {
  try {
    const res = await fetch(`/admin/productos/${id}/toggle-coleccion`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrf() },
    });
    const payload = await res.json();
    if (payload.success) {
      refresh();
    }
  } catch (e) {
    console.error('Error toggle:', e);
  }
};

const deleteProduct = async (id) => {
  if (!confirm('Eliminar producto?')) return;
  await requestJson(`/admin/productos/${id}`, 'DELETE');
  refresh();
};

const saveOffer = async () => {
  await requestJson(offerForm.value.id ? `/admin/ofertas/${offerForm.value.id}` : '/admin/ofertas', offerForm.value.id ? 'PUT' : 'POST', offerForm.value);
  resetOfferForm();
  refresh();
};

const editOffer = (item) => {
  offerForm.value = { ...item };
  activeTab.value = 'promociones';
};

const deleteOffer = async (id) => {
  if (!confirm('Eliminar promocion?')) return;
  await requestJson(`/admin/ofertas/${id}`, 'DELETE');
  refresh();
};

const resetOfferForm = () => {
  offerForm.value = emptyOffer();
};

const saveNews = async () => {
  await requestJson('/admin/noticias', 'PUT', { campos_adicionales: newsText.value });
  refresh();
};

const saveSupply = async () => {
  const body = {
    ...supplyForm.value,
    costo_total_compra: Number(supplyForm.value.costo_total_compra || 0),
    unidades_por_paquete: supplyForm.value.tipo_registro === 'PAQUETE' ? Number(supplyForm.value.unidades_por_paquete || 0) : null,
    cantidad_compra: supplyForm.value.tipo_registro === 'UNIDAD' ? Number(supplyForm.value.cantidad_compra || 0) : null,
  };

  await requestJson(supplyForm.value.id ? `/admin/insumos/${supplyForm.value.id}` : '/admin/insumos', supplyForm.value.id ? 'PUT' : 'POST', body);
  resetSupplyForm();
  refresh();
};

const editSupply = (item) => {
  supplyForm.value = {
    ...item,
    tipo_registro: item.tipo_registro || 'UNIDAD',
  };
  activeTab.value = 'insumos';
};

const resetSupplyForm = () => {
  supplyForm.value = emptySupply();
};

const deleteSupply = async (id) => {
  if (!confirm('Eliminar insumo?')) return;
  await requestJson(`/admin/insumos/${id}`, 'DELETE');
  refresh();
};
</script>
