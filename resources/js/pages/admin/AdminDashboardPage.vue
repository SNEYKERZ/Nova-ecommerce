<template>
  <AppLayout>

    <Head title="Admin" />
    <section class="mx-auto w-full max-w-[96rem] px-4 py-8 sm:px-6 lg:px-10">
      <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h1 class="text-3xl font-semibold tracking-tight text-slate-900">Panel Administrativo</h1>
        <p class="mt-2 text-sm text-slate-600">Vista corporativa para control operativo de tienda, fabrica, inventario y
          promociones.</p>
        <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs text-slate-500">Productos</p>
            <p class="text-2xl font-semibold text-slate-900">{{ stats.productos }}</p>
          </div>
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs text-slate-500">Insumos</p>
            <p class="text-2xl font-semibold text-slate-900">{{ stats.insumos }}</p>
          </div>
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs text-slate-500">Promociones</p>
            <p class="text-2xl font-semibold text-slate-900">{{ stats.ofertas }}</p>
          </div>
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs text-slate-500">Promociones activas</p>
            <p class="text-2xl font-semibold text-slate-900">{{ stats.ofertasActivas }}</p>
          </div>
        </div>
      </div>

      <div class="mb-5 flex flex-wrap gap-2">
        <button v-for="tab in tabs" :key="tab.key"
          class="rounded-full border px-4 py-2 text-sm font-semibold cursor-pointer"
          :class="activeTab === tab.key ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-300 bg-white text-slate-700'"
          @click="activeTab = tab.key">
          {{ tab.label }}
        </button>
      </div>

      <!-- ==================== PEDIDOS ==================== -->
      <section v-if="activeTab === 'pedidos'" class="space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-xl font-semibold text-slate-900">Pedidos recibidos</h2>
              <p class="mt-1 text-sm text-slate-500">Gestiona, edita y confirma pedidos de tu tienda.</p>
            </div>
            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">
              {{pedidosList.filter(p => p.estado === 'PENDIENTE').length}} pendientes
            </span>
          </div>
        </div>

        <!-- Detalle del pedido seleccionado (EDITABLE) -->
        <div v-if="pedidoDetalle" class="rounded-2xl border border-blue-200 bg-blue-50 p-5 shadow-sm">
          <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold text-blue-900">Pedido #{{ editForm.id }} — {{ editForm.nombre }} {{ editForm.apellidos }}</h3>
            <div class="flex gap-2">
              <button class="rounded-md bg-blue-600 px-3 py-1 text-xs font-semibold text-white cursor-pointer"
                @click="confirmarPedido(editForm.id)" v-if="editForm.estado === 'PENDIENTE'">
                ✓ Confirmar
              </button>
              <button class="rounded-md border border-slate-300 bg-white px-2 py-1 text-xs cursor-pointer"
                @click="savePedidoEdits">
                Guardar cambios
              </button>
              <button class="text-xs text-blue-600 underline cursor-pointer" @click="pedidoDetalle = null">Cerrar</button>
            </div>
          </div>

          <!-- Datos de contacto editables -->
          <div class="grid gap-2 text-sm sm:grid-cols-3 mb-4">
            <div>
              <label class="text-[10px] font-semibold uppercase text-blue-700">Nombre</label>
              <input v-model="editForm.nombre" class="w-full rounded-lg border border-blue-200 px-2 py-1 text-xs bg-white" />
            </div>
            <div>
              <label class="text-[10px] font-semibold uppercase text-blue-700">Apellidos</label>
              <input v-model="editForm.apellidos" class="w-full rounded-lg border border-blue-200 px-2 py-1 text-xs bg-white" />
            </div>
            <div>
              <label class="text-[10px] font-semibold uppercase text-blue-700">Teléfono</label>
              <input v-model="editForm.telefono" class="w-full rounded-lg border border-blue-200 px-2 py-1 text-xs bg-white" />
            </div>
            <div class="sm:col-span-2">
              <label class="text-[10px] font-semibold uppercase text-blue-700">Dirección</label>
              <input v-model="editForm.direccion" class="w-full rounded-lg border border-blue-200 px-2 py-1 text-xs bg-white" />
            </div>
          </div>

          <!-- Items editables -->
          <table class="mt-2 min-w-full text-xs">
            <thead>
              <tr class="text-left text-blue-700">
                <th class="py-1 pr-3">Producto</th>
                <th class="py-1 pr-3">Talla</th>
                <th class="py-1 pr-3">Precio</th>
                <th class="py-1 pr-3">Cant.</th>
                <th class="py-1 pr-3">Subtotal</th>
                <th class="py-1"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, i) in editForm.items" :key="item._key || i" class="border-t border-blue-200">
                <td class="py-1 pr-3">{{ item.producto_referencia }}</td>
                <td class="py-1 pr-3">
                  <input v-model="item.talla" class="w-16 rounded border border-blue-200 px-1 py-0.5 bg-white" />
                </td>
                <td class="py-1 pr-3">{{ money(item.precio_unitario) }}</td>
                <td class="py-1 pr-3">
                  <input v-model.number="item.cantidad" type="number" min="1"
                    class="w-14 rounded border border-blue-200 px-1 py-0.5 bg-white"
                    @input="recalcItemSubtotal(item)" />
                </td>
                <td class="py-1 pr-3">{{ money(item.subtotal) }}</td>
                <td class="py-1">
                  <button class="text-red-600 underline cursor-pointer bg-transparent border-none p-0" @click="deleteEditItem(i)">Eliminar</button>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Agregar item -->
          <div class="mt-2 flex gap-2 items-center">
            <select v-model="addItemProductoId" class="rounded-lg border border-blue-200 px-2 py-1 text-xs bg-white">
              <option :value="null">+ Agregar producto...</option>
              <option v-for="p in allProductos" :key="p.id" :value="p.id">{{ p.referencia }} — {{ money(p.precio) }}</option>
            </select>
            <input v-model.number="addItemCantidad" type="number" min="1" placeholder="Cant" class="w-16 rounded-lg border border-blue-200 px-2 py-1 text-xs bg-white" />
            <button class="rounded-md bg-blue-600 px-2 py-1 text-xs font-semibold text-white cursor-pointer" @click="addItemToEdit">Agregar</button>
          </div>

          <!-- Total editable -->
          <div class="mt-3 flex items-center gap-2 font-bold text-blue-900">
            <span>Total:</span>
            <input v-model.number="editForm.total" type="number" step="0.01" min="0"
              class="w-28 rounded-lg border border-blue-200 px-2 py-1 text-xs font-bold bg-white text-right" />
          </div>
          <div v-if="editForm.descuento_cupon > 0" class="mt-1 text-xs text-blue-700">
            Cupón aplicado: −{{ money(editForm.descuento_cupon) }}
          </div>
        </div>

        <!-- Tabla de pedidos -->
        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-100 text-left text-slate-600">
              <tr>
                <th class="px-3 py-3">#</th>
                <th class="px-3 py-3">Cliente</th>
                <th class="px-3 py-3">Origen</th>
                <th class="px-3 py-3">Total</th>
                <th class="px-3 py-3">Items</th>
                <th class="px-3 py-3">Fecha</th>
                <th class="px-3 py-3">Estado</th>
                <th class="px-3 py-3">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!pedidosList.length">
                <td colspan="8" class="px-4 py-6 text-center text-slate-400">No hay pedidos todavía.</td>
              </tr>
              <tr v-for="pedido in pedidosList" :key="pedido.id" class="border-t border-slate-200 hover:bg-slate-50">
                <td class="px-3 py-2 font-semibold text-slate-700">#{{ pedido.id }}</td>
                <td class="px-3 py-2">
                  <p class="font-semibold">{{ pedido.cliente?.nombre }} {{ pedido.cliente?.apellidos }}</p>
                  <p class="text-xs text-slate-400">{{ pedido.cliente?.email }}</p>
                </td>
                <td class="px-3 py-2">
                  <span class="rounded-full px-2 py-0.5 text-xs font-semibold"
                    :class="pedido.origen === 'whatsapp' ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-600'">
                    {{ pedido.origen === 'whatsapp' ? 'WhatsApp' : 'Web' }}
                  </span>
                </td>
                <td class="px-3 py-2 font-bold">{{ money(pedido.total) }}</td>
                <td class="px-3 py-2 text-slate-500">{{ pedido.items_count }} art.</td>
                <td class="px-3 py-2 text-slate-500 text-xs">{{ pedido.created_at }}</td>
                <td class="px-3 py-2">
                  <span class="rounded-full px-2 py-1 text-xs font-semibold"
                    :class="estadoColors[pedido.estado] || 'bg-slate-100 text-slate-700'">
                    {{ pedido.estado }}
                  </span>
                </td>
                <td class="px-3 py-2">
                  <div class="flex flex-wrap gap-1.5">
                    <button
                      class="rounded-md border border-slate-300 px-2 py-1 text-xs cursor-pointer hover:bg-slate-50"
                      @click="openPedidoEdit(pedido)">
                      Editar
                    </button>
                    <select class="rounded-md border border-slate-300 px-2 py-1 text-xs cursor-pointer bg-white"
                      :value="pedido.estado" @change="updateEstadoPedido(pedido.id, $event.target.value)">
                      <option value="PENDIENTE">Pendiente</option>
                      <option value="CONFIRMADO">Confirmado</option>
                      <option value="ENVIADO">Enviado</option>
                      <option value="ENTREGADO">Entregado</option>
                      <option value="CANCELADO">Cancelado</option>
                    </select>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación pedidos -->
        <div v-if="pedidosMeta" class="flex items-center justify-between">
          <p class="text-sm text-slate-600">{{ pedidosMeta.from }} - {{ pedidosMeta.to }} de {{ pedidosMeta.total }}
            pedidos</p>
          <div class="flex gap-1">
            <Link v-for="link in pedidosLinks" :key="link.label" :href="link.url"
              class="rounded-md border border-slate-300 px-3 py-1 text-sm"
              :class="link.active ? 'bg-slate-900 text-white' : 'bg-white text-slate-700 cursor-pointer'"
              :disabled="!link.url" v-html="link.label"></Link>
          </div>
        </div>
      </section>

      <section v-if="activeTab === 'configuracion'" class="space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <h2 class="text-xl font-semibold text-slate-900">Configuracion de Tienda</h2>
          <p class="mt-1 text-sm text-slate-600">Personaliza nombre, logo y colores del e-commerce.</p>
          <div class="mt-4 grid gap-4 lg:grid-cols-2">
            <div class="space-y-3">
              <div>
                <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Nombre de tienda</label>
                <input v-model="settingsForm.store_name" class="w-full rounded-xl border border-slate-300 px-3 py-2.5"
                  placeholder="Nombre tienda" />
              </div>
              <div>
                <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Logo</label>
                <input type="file" accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml"
                  class="w-full rounded-xl border border-slate-300 px-3 py-2.5" @change="handleSettingsLogo" />
              </div>

              <!-- Colores de la tienda -->
              <div>
                <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Color de Fondo</label>
                <div class="flex gap-2">
                  <input v-model="settingsForm.bg_color" type="color"
                    class="h-10 w-14 cursor-pointer rounded-lg border border-slate-300 p-0.5" />
                  <input v-model="settingsForm.bg_color" placeholder="#ffffff"
                    class="flex-1 rounded-xl border border-slate-300 px-3 py-2.5 font-mono text-sm cursor-pointer" />
                </div>
              </div>
              <div>
                <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Color Navbar</label>
                <div class="flex gap-2">
                  <input v-model="settingsForm.navbar_color" type="color"
                    class="h-10 w-14 cursor-pointer rounded-lg border border-slate-300 p-0.5" />
                  <input v-model="settingsForm.navbar_color" placeholder="#fff"
                    class="flex-1 rounded-xl border border-slate-300 px-3 py-2.5 font-mono text-sm cursor-pointer" />
                </div>
              </div>
              <div>
                <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Color Footer</label>
                <div class="flex gap-2">
                  <input v-model="settingsForm.footer_color" type="color"
                    class="h-10 w-14 cursor-pointer rounded-lg border border-slate-300 p-0.5" />
                  <input v-model="settingsForm.footer_color" placeholder="#fff"
                    class="flex-1 rounded-xl border border-slate-300 px-3 py-2.5 font-mono text-sm cursor-pointer" />
                </div>
              </div>

              <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white cursor-pointer"
                @click="saveSettings">Guardar configuracion</button>
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
              <p class="text-xs font-semibold uppercase text-slate-500">Vista previa</p>
              <div class="mt-3 flex items-center gap-3">
                <img v-if="settingsLogoPreview" :src="settingsLogoPreview" class="h-14 w-14 rounded-full object-cover"
                  alt="Logo" />
                <div class="text-lg font-semibold text-slate-900">{{ settingsForm.store_name || 'Nombre Tienda' }}</div>
              </div>
              <!-- Previsualización de colores -->
              <div class="mt-4 space-y-2">
                <div class="flex items-center gap-2">
                  <span class="inline-block h-4 w-4 rounded border border-slate-300"
                    :style="{ background: settingsForm.bg_color }"></span>
                  <span class="text-xs text-slate-500">Fondo</span>
                </div>
                <div class="flex items-center gap-2">
                  <span class="inline-block h-4 w-4 rounded border border-slate-300"
                    :style="{ background: settingsForm.navbar_color }"></span>
                  <span class="text-xs text-slate-500">Navbar</span>
                </div>
                <div class="flex items-center gap-2">
                  <span class="inline-block h-4 w-4 rounded border border-slate-300"
                    :style="{ background: settingsForm.footer_color }"></span>
                  <span class="text-xs text-slate-500">Footer</span>
                </div>
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
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Nombre del producto <span
                  class="normal-case font-normal text-slate-400">(visible en tienda)</span></label>
              <input v-model="productForm.nombre" placeholder="Ej: Camiseta Polo Azul Manga Corta"
                class="rounded-xl border border-slate-300 px-3 py-2.5 w-full" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Referencia / SKU <span
                  class="normal-case font-normal text-slate-400">(código interno)</span></label>
              <input v-model="productForm.referencia" placeholder="Ej: CAMISETA-001"
                class="rounded-xl border border-slate-300 px-3 py-2.5 w-full" />
            </div>
            <div class="lg:col-span-2">
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Descripción</label>
              <textarea v-model="productForm.descripcion" rows="2"
                placeholder="Descripción del producto para el catálogo..."
                class="rounded-xl border border-slate-300 px-3 py-2.5 w-full resize-none"></textarea>
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Precio (COP)</label>
              <input v-model.number="productForm.precio" type="number" min="0" placeholder="Ej: 50000"
                class="rounded-xl border border-slate-300 px-3 py-2.5 w-full" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Categoría</label>
              <select v-model.number="productForm.categoria_id"
                class="rounded-xl border border-slate-300 px-3 py-2.5 w-full">
                <option :value="null">Selecciona una categoría</option>
                <option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.categoria }}</option>
              </select>
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Estado del producto</label>
              <select v-model="productForm.estado" class="rounded-xl border border-slate-300 px-3 py-2.5 w-full">
                <option value="DISPONIBLE">Disponible</option>
                <option value="NO_DISPONIBLE">No disponible</option>
              </select>
            </div>
            <div class="lg:col-span-2">
              <label
                class="flex items-center gap-2 rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 cursor-pointer">
                <input type="checkbox" v-model="productForm.nueva_coleccion" class="rounded" />
                <span class="text-sm font-semibold text-slate-700">Marcar como Nueva Colección</span>
              </label>
            </div>

            <div class="lg:col-span-2">
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Imágenes del producto (máximo
                4)</label>
              <input type="file" multiple accept="image/png,image/jpeg,image/jpg,image/webp"
                class="w-full rounded-xl border border-slate-300 px-3 py-2.5" @change="handleProductImages" />
            </div>

            <div v-if="productForm.existingImages.length" class="lg:col-span-2">
              <p class="mb-1 text-xs font-semibold uppercase text-slate-500">Imágenes actuales</p>
              <div class="grid grid-cols-2 gap-2 sm:grid-cols-4"> <img v-for="image in productForm.existingImages"
                  :key="image.id" :src="image.url" class="h-20 w-full rounded-lg object-cover" alt="Imagen" /> </div>
            </div>

            <div v-if="productForm.newImageNames.length" class="lg:col-span-2 text-sm text-slate-600">
              {{ productForm.newImageNames.join(', ') }}
            </div>

            <div class="lg:col-span-2 rounded-xl border-2 border-blue-300 bg-blue-50 p-4">
              <p class="mb-3 text-sm font-bold uppercase text-blue-900">📦 Stock por talla</p>
              <p class="mb-3 text-xs text-blue-700">Selecciona las tallas disponibles e ingresa la cantidad en stock para cada una</p>
              <div class="grid gap-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                <label v-for="size in sizeOptions" :key="size"
                  class="flex flex-col gap-1 rounded-lg border border-blue-200 bg-white px-3 py-2.5 cursor-pointer hover:bg-blue-50 transition">
                  <div class="flex items-center gap-2">
                    <input v-model="productForm.sizeStock[size].enabled" type="checkbox" class="w-4 h-4 rounded" />
                    <span class="font-semibold text-slate-900">{{ size }}</span>
                  </div>
                  <input v-model.number="productForm.sizeStock[size].stock" type="number" min="0"
                    :disabled="!productForm.sizeStock[size].enabled" placeholder="Stock"
                    class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm disabled:bg-slate-100 disabled:text-slate-400" />
                </label>
              </div>
              <p class="mt-3 text-xs text-blue-600">
                ℹ️ Marcadas: {{ Object.values(productForm.sizeStock).filter(s => s.enabled).length }} |
                Stock total: {{ Object.values(productForm.sizeStock).reduce((sum, s) => sum + (s.enabled ? s.stock : 0), 0) }}
              </p>
            </div>
          </div>

          <div class="mt-4 flex flex-wrap gap-2">
            <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white cursor-pointer"
              @click="saveProduct">{{ productForm.id ? 'Actualizar' : 'Crear' }}</button>
            <button
              class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 cursor-pointer"
              @click="resetProductForm">Limpiar</button>
          </div>
        </div>

        <!-- Buscador y tabla de productos -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="mb-4 flex items-center gap-3">
            <input v-model="search" type="text" placeholder="Buscar por referencia, SKU, código o categoría..."
              class="flex-1 rounded-xl border border-slate-300 px-3 py-2.5" />
          </div>
          <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full text-sm">
              <thead class="bg-slate-100 text-left text-slate-600">
                <tr>
                  <th class="px-3 py-2">Nueva</th>
                  <th class="px-3 py-2">Ref</th>
                  <th class="px-3 py-2">Categoria</th>
                  <th class="px-3 py-2">Precio</th>
                  <th class="px-3 py-2">Tallas</th>
                  <th class="px-3 py-2">Stock</th>
                  <th class="px-3 py-2">Img</th>
                  <th class="px-3 py-2">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in productosList" :key="item.id" class="border-t border-slate-200">
                  <td class="px-3 py-2"><button @click="toggleNuevaColeccion(item.id)"
                      class="rounded-full px-2 py-1 text-xs font-semibold cursor-pointer"
                      :class="item.nueva_coleccion ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-400'">{{
                        item.nueva_coleccion ? 'NUEVA' : '--' }}</button></td>
                  <td class="px-3 py-2">{{ item.referencia }}</td>
                  <td class="px-3 py-2">{{ item.categoria }}</td>
                  <td class="px-3 py-2">{{ money(item.precio) }}</td>
                  <td class="px-3 py-2">{{ item.tallas }}</td>
                  <td class="px-3 py-2">{{ item.stock_total }}</td>
                  <td class="px-3 py-2">{{ item.imagenes?.length || 0 }}</td>
                  <td class="px-3 py-2">
                    <div class="flex gap-2"><button class="rounded-md border border-slate-300 px-2 py-1 cursor-pointer"
                        @click="editProduct(item)">Editar</button><button
                        class="rounded-md border border-red-300 px-2 py-1 text-red-600 cursor-pointer"
                        @click="deleteProduct(item.id)">Eliminar</button></div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <!-- Paginación productos -->
          <div v-if="productosMeta" class="mt-4 flex items-center justify-between">
            <p class="text-sm text-slate-600">Mostrando {{ productosMeta.from }} - {{ productosMeta.to }} de {{
              productosMeta.total }}</p>
            <div class="flex gap-1">
              <Link v-for="link in productosLinks" :key="link.label" :href="link.url"
                class="rounded-md border border-slate-300 px-3 py-1 text-sm cursor-pointer"
                :class="link.active ? 'bg-slate-900 text-white' : 'bg-white text-slate-700'" :disabled="!link.url"
                v-html="link.label"></Link>
            </div>
          </div>
        </div>
      </section>

      <!-- Galerías Tab -->
      <section v-if="activeTab === 'galerias'" class="space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-xl font-semibold text-slate-900">Gestión de Galerías</h2>
              <p class="mt-1 text-sm text-slate-600">Crea y organiza galerías de productos con imágenes masonry.</p>
            </div>
            <Link href="/admin/galleries"
              class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white cursor-pointer hover:bg-slate-800">
              Ir a Galerías
            </Link>
          </div>
        </div>
      </section>

      <section v-if="activeTab === 'promociones'" class="space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <h2 class="text-xl font-semibold text-slate-900">Crear / Editar Promoción</h2>
          <div class="mt-4 grid gap-3 md:grid-cols-2">
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Título de la promoción</label>
              <input v-model="offerForm.titulo" placeholder="Ej: Promo Verano 50%"
                class="rounded-xl border border-slate-300 px-3 py-2.5 w-full" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Descripción (opcional)</label>
              <input v-model="offerForm.descripcion" placeholder="Ej: Descuento especial en camisas"
                class="rounded-xl border border-slate-300 px-3 py-2.5 w-full" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Aplicar a producto específico
                (opcional)</label>
              <select v-model.number="offerForm.producto_id"
                class="rounded-xl border border-slate-300 px-3 py-2.5 w-full">
                <option :value="null">Selecciona un producto</option>
                <option v-for="p in productosList" :key="p.id" :value="p.id">{{ p.referencia }}</option>
              </select>
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Aplicar a categoría
                (opcional)</label>
              <select v-model.number="offerForm.categoria_id"
                class="rounded-xl border border-slate-300 px-3 py-2.5 w-full">
                <option :value="null">Selecciona una categoría</option>
                <option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.categoria }}</option>
              </select>
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Porcentaje de descuento
                (%)</label>
              <input v-model.number="offerForm.descuento_porcentaje" type="number" min="0" max="100"
                placeholder="Ej: 20" class="rounded-xl border border-slate-300 px-3 py-2.5 w-full" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Descuento fijo en COP
                (opcional)</label>
              <input v-model.number="offerForm.descuento_fijo" type="number" min="0" placeholder="Ej: 10000"
                class="rounded-xl border border-slate-300 px-3 py-2.5 w-full" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Precio especial oferta
                (opcional)</label>
              <input v-model.number="offerForm.precio_oferta" type="number" min="0" placeholder="Ej: 35000"
                class="rounded-xl border border-slate-300 px-3 py-2.5 w-full" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Estado de la promoción</label>
              <select v-model="offerForm.esta_activa" class="rounded-xl border border-slate-300 px-3 py-2.5 w-full">
                <option :value="true">Activa</option>
                <option :value="false">Inactiva</option>
              </select>
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Fecha de inicio</label>
              <input v-model="offerForm.fecha_inicio" type="datetime-local"
                class="rounded-xl border border-slate-300 px-3 py-2.5 w-full" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Fecha de fin</label>
              <input v-model="offerForm.fecha_fin" type="datetime-local"
                class="rounded-xl border border-slate-300 px-3 py-2.5 w-full" />
            </div>
          </div>
          <div class="mt-4 flex gap-2"><button
              class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white cursor-pointer"
              @click="saveOffer">{{ offerForm.id ? 'Actualizar' : 'Crear' }}</button><button
              class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold cursor-pointer"
              @click="resetOfferForm">Limpiar</button></div>
        </div>

        <!-- Buscador y tabla de ofertas -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="mb-4 flex items-center gap-3">
            <input v-model="search" type="text" placeholder="Buscar por título, descripción, producto o categoría..."
              class="flex-1 rounded-xl border border-slate-300 px-3 py-2.5" />
          </div>
          <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full text-sm">
              <thead class="bg-slate-100 text-left text-slate-600">
                <tr>
                  <th class="px-3 py-2">Titulo</th>
                  <th class="px-3 py-2">Aplica a</th>
                  <th class="px-3 py-2">Vigencia</th>
                  <th class="px-3 py-2">Estado</th>
                  <th class="px-3 py-2">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in ofertasList" :key="item.id" class="border-t border-slate-200">
                  <td class="px-3 py-2">{{ item.titulo }}</td>
                  <td class="px-3 py-2">{{ item.producto || item.categoria }}</td>
                  <td class="px-3 py-2">{{ item.fecha_inicio?.slice(0, 10) }} - {{ item.fecha_fin?.slice(0, 10) }}</td>
                  <td class="px-3 py-2">{{ item.esta_activa ? 'Activa' : 'Inactiva' }}</td>
                  <td class="px-3 py-2">
                    <div class="flex gap-2"><button class="rounded-md border border-slate-300 px-2 py-1 cursor-pointer"
                        @click="editOffer(item)">Editar</button><button
                        class="rounded-md border border-red-300 px-2 py-1 text-red-600 cursor-pointer"
                        @click="deleteOffer(item.id)">Eliminar</button></div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <!-- Paginación ofertas -->
          <div v-if="ofertasMeta" class="mt-4 flex items-center justify-between">
            <p class="text-sm text-slate-600">Mostrando {{ ofertasMeta.from }} - {{ ofertasMeta.to }} de {{
              ofertasMeta.total }}</p>
            <div class="flex gap-1">
              <Link v-for="link in ofertasLinks" :key="link.label" :href="link.url"
                class="rounded-md border border-slate-300 px-3 py-1 text-sm cursor-pointer"
                :class="link.active ? 'bg-slate-900 text-white' : 'bg-white text-slate-700'" :disabled="!link.url"
                v-html="link.label"></Link>
            </div>
          </div>
        </div>
      </section>

      <section v-if="activeTab === 'noticias'" class="space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <h2 class="text-xl font-semibold text-slate-900">Noticias / Promociones Activas</h2>
          <textarea v-model="newsText" rows="5"
            class="mt-3 w-full rounded-xl border border-slate-300 px-3 py-2.5"></textarea>
          <button class="mt-3 rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white cursor-pointer"
            @click="saveNews">Guardar noticias</button>
        </div>
      </section>

      <section v-if="activeTab === 'bloques'" class="space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <h2 class="text-xl font-semibold text-slate-900">Carrusel del Home</h2>
          <p class="mt-1 text-sm text-slate-600">Este carrusel reemplaza por completo los dos bloques anteriores. Puedes
            subir varias imágenes y el frontend las rota automáticamente cada 2 segundos.</p>

          <div class="mt-4 grid gap-4">
            <div class="flex items-center gap-2">
              <input type="checkbox" v-model="bloqueForm.activo" id="bloqueActivo" class="rounded border-slate-300" />
              <label for="bloqueActivo" class="text-sm font-semibold text-slate-700">Carrusel activo</label>
            </div>

            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Imagenes del carrusel</label>
              <input type="file" multiple accept="image/*" class="w-full rounded-xl border border-slate-300 px-3 py-2.5"
                @change="handleBloqueImagenes" />
              <p class="mt-1 text-xs text-slate-400">Puedes cargar varias imágenes. Se mostrarán en ancho completo.</p>
            </div>

            <div v-if="bloqueForm.nuevasImagenes?.length" class="space-y-2">
              <p class="text-xs font-semibold uppercase text-slate-500">Nuevas imagenes por guardar</p>
              <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div v-for="(img, index) in bloqueForm.nuevasImagenes" :key="`${img.name}-${index}`"
                  class="rounded-xl border border-dashed border-slate-300 p-3">
                  <img :src="img.preview" class="h-28 w-full rounded-lg object-cover" />
                  <p class="mt-2 truncate text-xs text-slate-500">{{ img.name }}</p>
                </div>
              </div>
            </div>

            <div v-if="bloqueForm.imagenes?.length" class="space-y-2">
              <p class="text-xs font-semibold uppercase text-slate-500">Slides actuales</p>
              <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div v-for="img in bloqueForm.imagenes" :key="img.id" class="rounded-xl border border-slate-200 p-3">
                  <img :src="img.imagen" class="h-28 w-full rounded-lg object-cover" />
                  <input v-model="img.nombre" placeholder="Nombre del slide"
                    class="mt-2 w-full rounded border border-slate-300 px-2 py-1.5 text-xs" />
                  <input :value="img.identificador || 'carrusel'" readonly
                    class="mt-2 w-full rounded border border-slate-200 bg-slate-50 px-2 py-1.5 text-xs text-slate-500" />
                  <input v-model="img.url_destino" placeholder="Link del slide (opcional)"
                    class="mt-2 w-full rounded border border-slate-300 px-2 py-1.5 text-xs" />
                  <button @click="deleteBloqueImagen(img.id)"
                    class="mt-2 w-full rounded bg-red-100 px-2 py-1.5 text-xs font-semibold text-red-600 cursor-pointer">Eliminar
                    slide</button>
                </div>
              </div>
            </div>
          </div>

        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <h2 class="text-xl font-semibold text-slate-900">Banners del Catalogo</h2>
          <p class="mt-1 text-sm text-slate-600">Estos dos banners aparecen debajo del bloque paginado de productos y
            cada uno puede llevar a una categoria, coleccion o filtro del catalogo.</p>

          <div class="mt-4 grid gap-4 lg:grid-cols-2">
            <div v-for="banner in [1, 2]" :key="banner" class="rounded-2xl border border-slate-200 p-4">
              <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-slate-900">Banner {{ banner === 1 ? 'Izquierdo' : 'Derecho' }}
                </h3>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                  <input v-model="catalogBannerForms[banner].activo" type="checkbox" class="rounded border-slate-300" />
                  Activo
                </label>
              </div>

              <div class="mt-3 space-y-3">
                <div v-if="catalogBannerForms[banner].imagen"
                  class="overflow-hidden rounded-xl border border-slate-200">
                  <img :src="catalogBannerForms[banner].imagen" class="h-32 w-full object-cover" />
                </div>

                <input type="file" accept="image/*" class="w-full rounded-xl border border-slate-300 px-3 py-2.5"
                  @change="handleCatalogBannerImage(banner, $event)" />
                <input v-model="catalogBannerForms[banner].nombre" placeholder="Nombre del banner"
                  class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" />
                <input :value="catalogBannerForms[banner].identificador" readonly
                  class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-500" />
                <input v-model="catalogBannerForms[banner].url_destino"
                  placeholder="/?categoria=chaquetas o enlace completo"
                  class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm" />
              </div>

              <div class="mt-3 flex gap-2">
                <button v-if="catalogBannerForms[banner].id"
                  class="rounded-xl border border-red-300 px-4 py-2 text-sm font-semibold text-red-600 cursor-pointer"
                  @click="deleteCatalogBanner(banner)">Eliminar</button>
              </div>
            </div>
          </div>

          <div class="mt-5 flex justify-end">
            <button class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white cursor-pointer"
              @click="saveVisualAssets">Guardar carrusel y banners</button>
          </div>
        </div>
      </section>

      <section v-if="activeTab === 'insumos'" class="space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <h2 class="text-xl font-semibold text-slate-900">Crear / Editar Insumo</h2>
          <div class="mt-4 grid gap-3 md:grid-cols-2">
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Nombre del insumo</label>
              <input v-model="supplyForm.nombre" placeholder="Ej: Hilo de algodón"
                class="rounded-xl border border-slate-300 px-3 py-2.5 w-full" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Código SKU</label>
              <input v-model="supplyForm.sku" placeholder="Ej: HIL-001"
                class="rounded-xl border border-slate-300 px-3 py-2.5 w-full" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Unidad de medida</label>
              <input v-model="supplyForm.unidad" placeholder="Ej: metro, kilo, unidad"
                class="rounded-xl border border-slate-300 px-3 py-2.5 w-full" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Tipo de registro de
                compra</label>
              <select v-model="supplyForm.tipo_registro" class="rounded-xl border border-slate-300 px-3 py-2.5 w-full">
                <option value="UNIDAD">Compra por unidades (cantidad fija)</option>
                <option value="PAQUETE">Compra por paquete (múltiplos)</option>
              </select>
            </div>

            <div v-if="supplyForm.tipo_registro === 'PAQUETE'">
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Unidades por paquete</label>
              <input v-model.number="supplyForm.unidades_por_paquete" type="number" min="1" placeholder="Ej: 100"
                class="rounded-xl border border-slate-300 px-3 py-2.5 w-full" />
            </div>
            <div v-if="supplyForm.tipo_registro === 'UNIDAD'">
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Cantidad comprada
                usualmente</label>
              <input v-model.number="supplyForm.cantidad_compra" type="number" min="1" placeholder="Ej: 50"
                class="rounded-xl border border-slate-300 px-3 py-2.5 w-full" />
            </div>

            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Costo total de la compra
                (COP)</label>
              <input v-model.number="supplyForm.costo_total_compra" type="number" min="0" placeholder="Ej: 50000"
                class="rounded-xl border border-slate-300 px-3 py-2.5 w-full" />
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm">
              <p class="text-slate-500">Costo por unidad calculado</p>
              <p class="text-lg font-semibold text-slate-900">{{ money(calculatedSupplyUnitCost) }} COP</p>
            </div>

            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Stock actual en
                inventario</label>
              <input v-model.number="supplyForm.stock_actual" type="number" min="0" placeholder="Ej: 100"
                class="rounded-xl border border-slate-300 px-3 py-2.5 w-full" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Stock mínimo (alerta de
                reorder)</label>
              <input v-model.number="supplyForm.stock_minimo" type="number" min="0" placeholder="Ej: 20"
                class="rounded-xl border border-slate-300 px-3 py-2.5 w-full" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Nombre del proveedor</label>
              <input v-model="supplyForm.proveedor" placeholder="Ej: Textilmax S.A.S"
                class="rounded-xl border border-slate-300 px-3 py-2.5 w-full" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Estado del insumo</label>
              <select v-model="supplyForm.activo" class="rounded-xl border border-slate-300 px-3 py-2.5 w-full">
                <option :value="true">Activo</option>
                <option :value="false">Inactivo</option>
              </select>
            </div>
          </div>

          <div class="mt-4 flex gap-2"><button
              class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white cursor-pointer"
              @click="saveSupply">{{ supplyForm.id ? 'Actualizar' : 'Crear' }}</button><button
              class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold cursor-pointer"
              @click="resetSupplyForm">Limpiar</button></div>
        </div>

        <!-- Buscador y tabla de insumos -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="mb-4 flex items-center gap-3">
            <input v-model="search" type="text" placeholder="Buscar por nombre, SKU, código o referencia..."
              class="flex-1 rounded-xl border border-slate-300 px-3 py-2.5" />
          </div>
          <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full text-sm">
              <thead class="bg-slate-100 text-left text-slate-600">
                <tr>
                  <th class="px-3 py-2">SKU</th>
                  <th class="px-3 py-2">Nombre</th>
                  <th class="px-3 py-2">Tipo</th>
                  <th class="px-3 py-2">Costo total</th>
                  <th class="px-3 py-2">Costo unit.</th>
                  <th class="px-3 py-2">Stock</th>
                  <th class="px-3 py-2">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in insumosList" :key="item.id" class="border-t border-slate-200">
                  <td class="px-3 py-2">{{ item.sku }}</td>
                  <td class="px-3 py-2">{{ item.nombre }}</td>
                  <td class="px-3 py-2">{{ item.tipo_registro }}</td>
                  <td class="px-3 py-2">{{ money(item.costo_total_compra) }}</td>
                  <td class="px-3 py-2">{{ money(item.costo_unitario) }}</td>
                  <td class="px-3 py-2"
                    :class="item.stock_actual <= item.stock_minimo ? 'text-red-600 font-semibold' : ''">{{
                      item.stock_actual }} {{ item.unidad }}</td>
                  <td class="px-3 py-2">
                    <div class="flex gap-2"><button class="rounded-md border border-slate-300 px-2 py-1 cursor-pointer"
                        @click="editSupply(item)">Editar</button><button
                        class="rounded-md border border-red-300 px-2 py-1 text-red-600 cursor-pointer"
                        @click="deleteSupply(item.id)">Eliminar</button></div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <!-- Paginación insumos -->
          <div v-if="insumosMeta" class="mt-4 flex items-center justify-between">
            <p class="text-sm text-slate-600">Mostrando {{ insumosMeta.from }} - {{ insumosMeta.to }} de {{
              insumosMeta.total }}</p>
            <div class="flex gap-1">
              <Link v-for="link in insumosLinks" :key="link.label" :href="link.url"
                class="rounded-md border border-slate-300 px-3 py-1 text-sm cursor-pointer"
                :class="link.active ? 'bg-slate-900 text-white' : 'bg-white text-slate-700'" :disabled="!link.url"
                v-html="link.label"></Link>
            </div>
          </div>
        </div>
      </section>

      <!-- ==================== CUPONES TAB ==================== -->
      <section v-if="activeTab === 'cupones'" class="space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-xl font-semibold text-slate-900">Cupones de descuento</h2>
              <p class="mt-1 text-sm text-slate-500">Crea y gestiona cupones de descuento para tu tienda.</p>
            </div>
            <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-800">
              {{ cuponesMeta?.total || 0 }} cupones
            </span>
          </div>
        </div>

        <!-- Formulario de cupón -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <h3 class="mb-3 text-lg font-semibold text-slate-800">{{ cuponForm.id ? 'Editar cupón' : 'Nuevo cupón' }}</h3>
          <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Código</label>
              <input v-model="cuponForm.codigo" type="text" placeholder="ej: BIENVENIDO10"
                class="w-full rounded-xl border border-slate-300 px-3 py-2.5" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Tipo</label>
              <select v-model="cuponForm.tipo" class="w-full rounded-xl border border-slate-300 px-3 py-2.5">
                <option value="porcentaje">Porcentaje (%)</option>
                <option value="fijo">Fijo ($)</option>
              </select>
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Valor descuento</label>
              <input v-model="cuponForm.valor_descuento" type="number" step="0.01" min="0"
                class="w-full rounded-xl border border-slate-300 px-3 py-2.5" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Monto mínimo (opcional)</label>
              <input v-model="cuponForm.monto_minimo" type="number" step="0.01" min="0" placeholder="Sin mínimo"
                class="w-full rounded-xl border border-slate-300 px-3 py-2.5" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Usos máximos (opcional)</label>
              <input v-model="cuponForm.max_usos" type="number" min="1" placeholder="Ilimitado"
                class="w-full rounded-xl border border-slate-300 px-3 py-2.5" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Fecha expiración (opcional)</label>
              <input v-model="cuponForm.fecha_expiracion" type="date"
                class="w-full rounded-xl border border-slate-300 px-3 py-2.5" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Activo</label>
              <select v-model="cuponForm.esta_activo" class="w-full rounded-xl border border-slate-300 px-3 py-2.5">
                <option :value="true">Sí</option>
                <option :value="false">No</option>
              </select>
            </div>
          </div>
          <div class="mt-4 flex gap-2">
            <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white cursor-pointer"
              @click="saveCupon">{{ cuponForm.id ? 'Actualizar' : 'Crear' }}</button>
            <button class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold cursor-pointer"
              @click="resetCuponForm">Limpiar</button>
          </div>
        </div>

        <!-- Tabla de cupones -->
        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-100 text-left text-slate-600">
              <tr>
                <th class="px-3 py-3">Código</th>
                <th class="px-3 py-3">Tipo</th>
                <th class="px-3 py-3">Valor</th>
                <th class="px-3 py-3">Monto mínimo</th>
                <th class="px-3 py-3">Usos</th>
                <th class="px-3 py-3">Expira</th>
                <th class="px-3 py-3">Estado</th>
                <th class="px-3 py-3">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="cupon in cuponesList" :key="cupon.id" class="border-t border-slate-200">
                <td class="px-3 py-3 font-semibold text-slate-900">{{ cupon.codigo }}</td>
                <td class="px-3 py-3">
                  <span class="rounded-full px-2 py-0.5 text-xs font-semibold"
                    :class="cupon.tipo === 'porcentaje' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'">
                    {{ cupon.tipo === 'porcentaje' ? '%' : '$' }}
                  </span>
                </td>
                <td class="px-3 py-3">{{ cupon.tipo === 'porcentaje' ? cupon.valor_descuento + '%' : money(cupon.valor_descuento) }}</td>
                <td class="px-3 py-3">{{ cupon.monto_minimo ? money(cupon.monto_minimo) : '—' }}</td>
                <td class="px-3 py-3">{{ cupon.usos_actuales }}{{ cupon.max_usos ? '/' + cupon.max_usos : '' }}</td>
                <td class="px-3 py-3">{{ cupon.fecha_expiracion ? formatDate(cupon.fecha_expiracion) : '—' }}</td>
                <td class="px-3 py-3">
                  <span class="rounded-full px-2 py-0.5 text-xs font-semibold"
                    :class="cupon.esta_activo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                    {{ cupon.esta_activo ? 'Activo' : 'Inactivo' }}
                  </span>
                </td>
                <td class="px-3 py-3">
                  <div class="flex gap-2">
                    <button class="rounded-md border border-slate-300 px-2 py-1 cursor-pointer"
                      @click="editCupon(cupon)">Editar</button>
                    <button class="rounded-md border border-red-300 px-2 py-1 text-red-600 cursor-pointer"
                      @click="deleteCupon(cupon.id)">Eliminar</button>
                  </div>
                </td>
              </tr>
              <tr v-if="!cuponesList.length">
                <td colspan="8" class="px-3 py-8 text-center text-sm text-slate-400">
                  No hay cupones creados aún.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación cupones -->
        <div v-if="cuponesMeta" class="flex items-center justify-between">
          <p class="text-sm text-slate-600">Mostrando {{ cuponesMeta.from }} - {{ cuponesMeta.to }} de {{ cuponesMeta.total }}</p>
          <div class="flex gap-1">
            <Link v-for="link in cuponesLinks" :key="link.label" :href="link.url"
              class="rounded-md border border-slate-300 px-3 py-1 text-sm cursor-pointer"
              :class="link.active ? 'bg-slate-900 text-white' : 'bg-white text-slate-700'" :disabled="!link.url"
              v-html="link.label"></Link>
          </div>
        </div>
      </section>

      <p v-if="feedback"
        class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-800">
        {{ feedback }}</p>
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
  pedidos: { type: [Object, Array], default: () => [] },
  cupones: { type: [Object, Array], default: () => [] },
  cupones: { type: [Object, Array], default: () => [] },
  noticia: { type: String, default: '' },
  bloques: { type: Object, default: () => ({}) },
  catalogBanners: { type: Object, default: () => ({}) },
  settings: { type: Object, default: () => ({ store_name: 'Vendex', logo_url: null }) },
  filters: { type: Object, default: () => ({ search: '' }) },
});

const tabs = [
  { key: 'pedidos', label: 'Pedidos' },
  { key: 'productos', label: 'Productos y Stock' },
  { key: 'galerias', label: 'Galerías' },
  { key: 'promociones', label: 'Promociones' },
  { key: 'insumos', label: 'Insumos Fabrica' },
  { key: 'bloques', label: 'Bloques Home' },
  { key: 'noticias', label: 'Noticias Activas' },
  { key: 'cupones', label: 'Cupones' },
  { key: 'configuracion', label: 'Config Tienda' },
];

const sizeOptions = ['XS', 'S', 'M', 'L', 'XL', 'XXL', '28', '30', '32', '34', '36', 'UNICA'];
const activeTab = ref('pedidos');
const feedback = ref('');
const newsText = ref(props.noticia || '');
const search = ref(props.filters.search || '');

const settingsForm = ref({ store_name: props.settings.store_name || import.meta.env.VITE_APP_NAME || 'Vendex', logoFile: null, bg_color: props.settings.bg_color || '#ffffff', navbar_color: props.settings.navbar_color || '#fff', footer_color: props.settings.footer_color || '#fff' });
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

// Cupones
const cuponesList = computed(() => resolvePagedData(props.cupones));
const cuponesMeta = computed(() => resolveMeta(props.cupones));
const cuponesLinks = computed(() => {
  const meta = resolveMeta(props.cupones);
  return meta?.links || [];
});

const cuponForm = ref(emptyCupon());

function emptyCupon() {
  return {
    id: null,
    codigo: '',
    tipo: 'porcentaje',
    valor_descuento: null,
    monto_minimo: null,
    max_usos: null,
    fecha_expiracion: null,
    esta_activo: true,
  };
}

const formatDate = (dateStr) => {
  if (!dateStr) return '—';
  const d = new Date(dateStr);
  return d.toLocaleDateString('es-CO', { year: 'numeric', month: 'short', day: 'numeric' });
};

const saveCupon = async () => {
  await requestJson(
    cuponForm.value.id ? `/admin/cupones/${cuponForm.value.id}` : '/admin/cupones',
    cuponForm.value.id ? 'PUT' : 'POST',
    cuponForm.value
  );
  resetCuponForm();
  refresh();
};

const editCupon = (cupon) => {
  cuponForm.value = {
    id: cupon.id,
    codigo: cupon.codigo,
    tipo: cupon.tipo,
    valor_descuento: cupon.valor_descuento,
    monto_minimo: cupon.monto_minimo,
    max_usos: cupon.max_usos,
    fecha_expiracion: cupon.fecha_expiracion ? cupon.fecha_expiracion.slice(0, 10) : null,
    esta_activo: cupon.esta_activo,
  };
  activeTab.value = 'cupones';
};

const resetCuponForm = () => {
  cuponForm.value = emptyCupon();
};

const deleteCupon = async (id) => {
  if (!confirm('Eliminar cupón?')) return;
  await requestJson(`/admin/cupones/${id}`, 'DELETE');
  refresh();
};

// Pedidos
const pedidosList = computed(() => resolvePagedData(props.pedidos));
const pedidosMeta = computed(() => resolveMeta(props.pedidos));
const pedidosLinks = computed(() => resolveMeta(props.pedidos)?.links || []);

const estadoColors = {
  PENDIENTE: 'bg-amber-100 text-amber-800',
  CONFIRMADO: 'bg-blue-100 text-blue-800',
  ENVIADO: 'bg-indigo-100 text-indigo-800',
  ENTREGADO: 'bg-green-100 text-green-800',
  CANCELADO: 'bg-red-100 text-red-800',
};

const pedidoDetalle = ref(null);

// Edición de pedidos
const editForm = ref({
  id: null, nombre: '', apellidos: '', telefono: '', direccion: '',
  total: 0, descuento_cupon: 0, estado: '', items: [],
});
const addItemProductoId = ref(null);
const addItemCantidad = ref(1);

const allProductos = computed(() => {
  const data = props.productos;
  if (!data) return [];
  if (Array.isArray(data)) return data;
  return data.data || [];
});

const openPedidoEdit = (pedido) => {
  editForm.value = {
    id: pedido.id,
    nombre: pedido.cliente?.nombre || '',
    apellidos: pedido.cliente?.apellidos || '',
    telefono: pedido.cliente?.telefono || '',
    direccion: pedido.direccion || '',
    total: pedido.total,
    descuento_cupon: pedido.descuento_cupon || 0,
    estado: pedido.estado,
    items: (pedido.items || []).map(item => ({ ...item, _key: Date.now() + Math.random() })),
  };
  pedidoDetalle.value = pedido;
};

const recalcItemSubtotal = (item) => {
  item.subtotal = (item.precio_unitario || 0) * (item.cantidad || 1);
  editForm.value.total = editForm.value.items.reduce((s, i) => s + i.subtotal, 0);
  if (editForm.value.descuento_cupon > 0) {
    editForm.value.total = Math.max(0, editForm.value.total - editForm.value.descuento_cupon);
  }
};

const deleteEditItem = (index) => {
  editForm.value.items.splice(index, 1);
  recalcItemSubtotal(null);
};

const addItemToEdit = async () => {
  const prodId = addItemProductoId.value;
  const cant = addItemCantidad.value || 1;
  if (!prodId) return;

  // Buscar producto en allProductos
  const prod = allProductos.value.find(p => p.id === prodId);
  if (!prod) return;

  const subtotal = prod.precio * cant;
  editForm.value.items.push({
    id: null,
    producto_id: prod.id,
    producto_referencia: prod.referencia,
    precio_unitario: prod.precio,
    cantidad: cant,
    talla: '',
    subtotal: subtotal,
    _key: Date.now() + Math.random(),
  });

  recalcItemSubtotal(null);
  addItemProductoId.value = null;
  addItemCantidad.value = 1;
};

const savePedidoEdits = async () => {
  const form = editForm.value;
  try {
    // Guardar datos del pedido (total, direccion, nombre, telefono)
    await requestJson(`/admin/pedidos/${form.id}`, 'PUT', {
      total: form.total,
      direccion: form.direccion,
      nombre: form.nombre,
      telefono: form.telefono,
    });

    // Sincronizar items: los existentes se actualizan, nuevos se crean, eliminados se borran
    for (const item of form.items) {
      if (item.id) {
        await requestJson(`/admin/pedidos/${form.id}/items/${item.id}`, 'PUT', {
          cantidad: item.cantidad,
          talla: item.talla || '',
        });
      } else {
        await requestJson(`/admin/pedidos/${form.id}/items`, 'POST', {
          producto_id: item.producto_id,
          cantidad: item.cantidad,
          talla: item.talla || null,
        });
      }
    }

    notify('Pedido actualizado correctamente');
    refresh();
  } catch (e) {
    notify(e.message);
  }
};

const confirmarPedido = async (id) => {
  if (!confirm('¿Confirmar este pedido? Se descontará el stock de los productos.')) return;
  try {
    await requestJson(`/admin/pedidos/${id}/estado`, 'PUT', { estado: 'CONFIRMADO' });
    notify('Pedido confirmado — stock descontado');
    pedidoDetalle.value = null;
    refresh();
  } catch (e) {
    notify(e.message);
  }
};

const updateEstadoPedido = async (pedidoId, estado) => {
  try {
    await requestJson(`/admin/pedidos/${pedidoId}/estado`, 'PUT', { estado });
    refresh();
  } catch (e) {
    notify(e.message);
  }
};

// Paginación y búsqueda
const performSearch = () => {
  router.get('/admin', { search: search.value }, { replace: true, only: ['productos', 'insumos', 'ofertas'] });
};

watch(search, (val) => {
  performSearch();
});

// Bloques Home
const bloqueForm = ref({ tipo: 'banner', activo: true, titulo: '', contenido: '', tamano_texto: 'normal', imagenes: [], nuevasImagenes: [] });
const catalogBannerForms = ref({
  1: createCatalogBannerForm(1, props.catalogBanners?.[1] || null),
  2: createCatalogBannerForm(2, props.catalogBanners?.[2] || null),
});

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

const propsBloques = props.bloques || {};
if (propsBloques[1]) initBloqueForm(propsBloques[1]);

watch(() => props.bloques, (value) => {
  if (value?.[1]) initBloqueForm(value[1]);
}, { deep: true });

watch(() => props.catalogBanners, (value) => {
  catalogBannerForms.value = {
    1: createCatalogBannerForm(1, value?.[1] || null),
    2: createCatalogBannerForm(2, value?.[2] || null),
  };
}, { deep: true });

const handleBloqueImagenes = (event) => {
  const files = Array.from(event.target.files || []).slice(0, 4 - (bloqueForm.value.imagenes?.length || 0));
  bloqueForm.value.nuevasImagenes = files.map((file) => ({
    file,
    name: file.name,
    preview: URL.createObjectURL(file),
  }));
};

const saveBloque = async () => {
  const formData = new FormData();
  formData.append('posicion', '1');
  formData.append('tipo', 'banner');
  formData.append('activo', bloqueForm.value.activo ? '1' : '0');
  bloqueForm.value.imagenes.forEach((image) => {
    formData.append(`image_names[${image.id}]`, image.nombre || '');
  });
  bloqueForm.value.imagenes.forEach((image) => {
    formData.append(`image_links[${image.id}]`, image.url_destino || '');
  });
  bloqueForm.value.nuevasImagenes.forEach((item) => formData.append('imagen[]', item.file));

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
  } catch (e) {
    console.error('Error saveBloque:', e);
    throw e;
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

function createCatalogBannerForm(position, banner) {
  return {
    id: banner?.id || null,
    nombre: banner?.nombre || '',
    identificador: banner?.identificador || (position === 1 ? 'banner-izq' : 'banner-der'),
    imagen: banner?.imagen || null,
    url_destino: banner?.url_destino || '',
    activo: banner?.activo ?? true,
    imageFile: null,
  };
}

const handleCatalogBannerImage = (position, event) => {
  const file = event.target.files?.[0] || null;
  catalogBannerForms.value[position].imageFile = file;

  if (file) {
    catalogBannerForms.value[position].imagen = URL.createObjectURL(file);
  }
};

const saveCatalogBanner = async (position) => {
  const banner = catalogBannerForms.value[position];
  const formData = new FormData();
  formData.append('posicion', String(position));
  formData.append('nombre', banner.nombre || '');
  formData.append('url_destino', banner.url_destino || '');
  formData.append('activo', banner.activo ? '1' : '0');
  if (banner.imageFile) formData.append('imagen', banner.imageFile);

  await requestFormData('/admin/catalog-banners', 'POST', formData);
};

const saveVisualAssets = async () => {
  try {
    const formData = new FormData();
    formData.append('carousel_active', bloqueForm.value.activo ? '1' : '0');
    bloqueForm.value.imagenes.forEach((image) => {
      formData.append(`carousel_existing_names[${image.id}]`, image.nombre || '');
      formData.append(`carousel_existing_links[${image.id}]`, image.url_destino || '');
    });
    bloqueForm.value.nuevasImagenes.forEach((item) => formData.append('carousel_images[]', item.file));

    formData.append('left_nombre', catalogBannerForms.value[1].nombre || '');
    formData.append('left_url_destino', catalogBannerForms.value[1].url_destino || '');
    formData.append('left_activo', catalogBannerForms.value[1].activo ? '1' : '0');
    if (catalogBannerForms.value[1].imageFile) {
      formData.append('left_imagen', catalogBannerForms.value[1].imageFile);
    }

    formData.append('right_nombre', catalogBannerForms.value[2].nombre || '');
    formData.append('right_url_destino', catalogBannerForms.value[2].url_destino || '');
    formData.append('right_activo', catalogBannerForms.value[2].activo ? '1' : '0');
    if (catalogBannerForms.value[2].imageFile) {
      formData.append('right_imagen', catalogBannerForms.value[2].imageFile);
    }

    await requestFormData('/admin/visual-assets', 'POST', formData);

    // requestFormData ya muestra el notify, refresh con delay para que se vea
    setTimeout(() => refresh(), 2000);
  } catch (e) {
    notify(e.message || 'No se pudieron guardar los visuales');
  }
};

const deleteCatalogBanner = async (position) => {
  if (!confirm('Eliminar banner del catalogo?')) return;
  await requestJson(`/admin/catalog-banners/${position}`, 'DELETE');
  refresh();
};

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
    nombre: '',
    descripcion: '',
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
  only: ['stats', 'productos', 'categorias', 'insumos', 'ofertas', 'pedidos', 'cupones', 'noticia', 'settings', 'bloques', 'catalogBanners'],
  data: search.value ? { search: search.value } : {},
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
  formData.append('store_name', settingsForm.value.store_name || import.meta.env.VITE_APP_NAME || 'Vendex');
  if (settingsForm.value.logoFile) formData.append('logo', settingsForm.value.logoFile);
  formData.append('bg_color', settingsForm.value.bg_color || '#ffffff');
  formData.append('navbar_color', settingsForm.value.navbar_color || '#fff');
  formData.append('footer_color', settingsForm.value.footer_color || '#fff');
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
  formData.append('nombre', productForm.value.nombre || '');
  formData.append('descripcion', productForm.value.descripcion || '');
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
    nombre: item.nombre || '',
    descripcion: item.descripcion || '',
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
