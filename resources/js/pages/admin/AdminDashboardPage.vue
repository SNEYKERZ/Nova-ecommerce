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
        <button v-for="tab in tabs" :key="tab.key" class="rounded-full border px-4 py-2 text-sm font-semibold" :class="activeTab === tab.key ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-300 bg-white text-slate-700'" @click="activeTab = tab.key">
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
            <input v-model="productForm.referencia" placeholder="Referencia" class="rounded-xl border border-slate-300 px-3 py-2.5" />
            <input v-model.number="productForm.precio" type="number" min="0" placeholder="Precio" class="rounded-xl border border-slate-300 px-3 py-2.5" />
            <select v-model.number="productForm.categoria_id" class="rounded-xl border border-slate-300 px-3 py-2.5"><option :value="null">Selecciona categoria</option><option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.categoria }}</option></select>
            <select v-model="productForm.estado" class="rounded-xl border border-slate-300 px-3 py-2.5"><option value="DISPONIBLE">DISPONIBLE</option><option value="NO_DISPONIBLE">NO_DISPONIBLE</option></select>

            <div class="lg:col-span-2">
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Imagenes del producto (max 4)</label>
              <input type="file" multiple accept="image/png,image/jpeg,image/jpg,image/webp" class="w-full rounded-xl border border-slate-300 px-3 py-2.5" @change="handleProductImages" />
            </div>

            <div v-if="productForm.existingImages.length" class="lg:col-span-2">
              <p class="mb-1 text-xs font-semibold uppercase text-slate-500">Imagenes actuales</p>
              <div class="grid grid-cols-2 gap-2 sm:grid-cols-4"> <img v-for="image in productForm.existingImages" :key="image.id" :src="image.url" class="h-20 w-full rounded-lg object-cover" alt="Imagen" /> </div>
            </div>

            <div v-if="productForm.newImageNames.length" class="lg:col-span-2 text-sm text-slate-600">
              {{ productForm.newImageNames.join(', ') }}
            </div>

            <div class="lg:col-span-2">
              <p class="mb-2 text-xs font-semibold uppercase text-slate-500">Tallas y stock por talla</p>
              <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                <label v-for="size in sizeOptions" :key="size" class="flex items-center gap-2 rounded-lg border border-slate-300 bg-slate-50 px-3 py-2">
                  <input v-model="productForm.sizeStock[size].enabled" type="checkbox" />
                  <span class="w-10 text-sm font-semibold text-slate-700">{{ size }}</span>
                  <input v-model.number="productForm.sizeStock[size].stock" type="number" min="0" :disabled="!productForm.sizeStock[size].enabled" class="w-full rounded-md border border-slate-300 px-2 py-1 text-sm" />
                </label>
              </div>
            </div>
          </div>

          <div class="mt-4 flex flex-wrap gap-2">
            <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white" @click="saveProduct">{{ productForm.id ? 'Actualizar' : 'Crear' }}</button>
            <button class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700" @click="resetProductForm">Limpiar</button>
          </div>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-100 text-left text-slate-600"><tr><th class="px-3 py-2">Ref</th><th class="px-3 py-2">Categoria</th><th class="px-3 py-2">Precio</th><th class="px-3 py-2">Tallas</th><th class="px-3 py-2">Stock</th><th class="px-3 py-2">Img</th><th class="px-3 py-2">Acciones</th></tr></thead>
            <tbody>
              <tr v-for="item in productos" :key="item.id" class="border-t border-slate-200">
                <td class="px-3 py-2">{{ item.referencia }}</td><td class="px-3 py-2">{{ item.categoria }}</td><td class="px-3 py-2">{{ money(item.precio) }}</td><td class="px-3 py-2">{{ item.tallas }}</td><td class="px-3 py-2">{{ item.stock_total }}</td><td class="px-3 py-2">{{ item.imagenes?.length || 0 }}</td>
                <td class="px-3 py-2"><div class="flex gap-2"><button class="rounded-md border border-slate-300 px-2 py-1" @click="editProduct(item)">Editar</button><button class="rounded-md border border-red-300 px-2 py-1 text-red-600" @click="deleteProduct(item.id)">Eliminar</button></div></td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section v-if="activeTab === 'promociones'" class="space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <h2 class="text-xl font-semibold text-slate-900">Crear / Editar Promocion</h2>
          <div class="mt-4 grid gap-3 md:grid-cols-2">
            <input v-model="offerForm.titulo" placeholder="Titulo" class="rounded-xl border border-slate-300 px-3 py-2.5" />
            <input v-model="offerForm.descripcion" placeholder="Descripcion" class="rounded-xl border border-slate-300 px-3 py-2.5" />
            <select v-model.number="offerForm.producto_id" class="rounded-xl border border-slate-300 px-3 py-2.5"><option :value="null">Aplicar a producto (opcional)</option><option v-for="p in productos" :key="p.id" :value="p.id">{{ p.referencia }}</option></select>
            <select v-model.number="offerForm.categoria_id" class="rounded-xl border border-slate-300 px-3 py-2.5"><option :value="null">Aplicar a categoria (opcional)</option><option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.categoria }}</option></select>
            <input v-model.number="offerForm.descuento_porcentaje" type="number" min="0" placeholder="% descuento" class="rounded-xl border border-slate-300 px-3 py-2.5" />
            <input v-model.number="offerForm.descuento_fijo" type="number" min="0" placeholder="Descuento fijo" class="rounded-xl border border-slate-300 px-3 py-2.5" />
            <input v-model.number="offerForm.precio_oferta" type="number" min="0" placeholder="Precio oferta" class="rounded-xl border border-slate-300 px-3 py-2.5" />
            <select v-model="offerForm.esta_activa" class="rounded-xl border border-slate-300 px-3 py-2.5"><option :value="true">Activa</option><option :value="false">Inactiva</option></select>
            <input v-model="offerForm.fecha_inicio" type="datetime-local" class="rounded-xl border border-slate-300 px-3 py-2.5" />
            <input v-model="offerForm.fecha_fin" type="datetime-local" class="rounded-xl border border-slate-300 px-3 py-2.5" />
          </div>
          <div class="mt-4 flex gap-2"><button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white" @click="saveOffer">{{ offerForm.id ? 'Actualizar' : 'Crear' }}</button><button class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold" @click="resetOfferForm">Limpiar</button></div>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-100 text-left text-slate-600"><tr><th class="px-3 py-2">Titulo</th><th class="px-3 py-2">Aplica a</th><th class="px-3 py-2">Vigencia</th><th class="px-3 py-2">Estado</th><th class="px-3 py-2">Acciones</th></tr></thead>
            <tbody>
              <tr v-for="item in ofertas" :key="item.id" class="border-t border-slate-200">
                <td class="px-3 py-2">{{ item.titulo }}</td>
                <td class="px-3 py-2">{{ item.producto || item.categoria }}</td>
                <td class="px-3 py-2">{{ item.fecha_inicio?.slice(0, 10) }} - {{ item.fecha_fin?.slice(0, 10) }}</td>
                <td class="px-3 py-2">{{ item.esta_activa ? 'Activa' : 'Inactiva' }}</td>
                <td class="px-3 py-2"><div class="flex gap-2"><button class="rounded-md border border-slate-300 px-2 py-1" @click="editOffer(item)">Editar</button><button class="rounded-md border border-red-300 px-2 py-1 text-red-600" @click="deleteOffer(item.id)">Eliminar</button></div></td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section v-if="activeTab === 'noticias'" class="space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <h2 class="text-xl font-semibold text-slate-900">Noticias / Promociones Activas</h2>
          <textarea v-model="newsText" rows="5" class="mt-3 w-full rounded-xl border border-slate-300 px-3 py-2.5"></textarea>
          <button class="mt-3 rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white" @click="saveNews">Guardar noticias</button>
        </div>
      </section>

      <section v-if="activeTab === 'insumos'" class="space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <h2 class="text-xl font-semibold text-slate-900">Crear / Editar Insumo</h2>
          <div class="mt-4 grid gap-3 md:grid-cols-2">
            <input v-model="supplyForm.nombre" placeholder="Nombre" class="rounded-xl border border-slate-300 px-3 py-2.5" />
            <input v-model="supplyForm.sku" placeholder="SKU" class="rounded-xl border border-slate-300 px-3 py-2.5" />
            <input v-model="supplyForm.unidad" placeholder="Unidad" class="rounded-xl border border-slate-300 px-3 py-2.5" />
            <select v-model="supplyForm.tipo_registro" class="rounded-xl border border-slate-300 px-3 py-2.5">
              <option value="UNIDAD">Compra por unidades</option>
              <option value="PAQUETE">Compra por paquete</option>
            </select>

            <input v-if="supplyForm.tipo_registro === 'PAQUETE'" v-model.number="supplyForm.unidades_por_paquete" type="number" min="1" placeholder="Unidades por paquete" class="rounded-xl border border-slate-300 px-3 py-2.5" />
            <input v-if="supplyForm.tipo_registro === 'UNIDAD'" v-model.number="supplyForm.cantidad_compra" type="number" min="1" placeholder="Cantidad comprada" class="rounded-xl border border-slate-300 px-3 py-2.5" />

            <input v-model.number="supplyForm.costo_total_compra" type="number" min="0" placeholder="Costo total de compra" class="rounded-xl border border-slate-300 px-3 py-2.5" />
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm">
              <p class="text-slate-500">Costo por unidad calculado</p>
              <p class="text-lg font-semibold text-slate-900">{{ money(calculatedSupplyUnitCost) }}</p>
            </div>

            <input v-model.number="supplyForm.stock_actual" type="number" min="0" placeholder="Stock actual" class="rounded-xl border border-slate-300 px-3 py-2.5" />
            <input v-model.number="supplyForm.stock_minimo" type="number" min="0" placeholder="Stock minimo" class="rounded-xl border border-slate-300 px-3 py-2.5" />
            <input v-model="supplyForm.proveedor" placeholder="Proveedor" class="rounded-xl border border-slate-300 px-3 py-2.5" />
            <select v-model="supplyForm.activo" class="rounded-xl border border-slate-300 px-3 py-2.5"><option :value="true">Activo</option><option :value="false">Inactivo</option></select>
          </div>

          <div class="mt-4 flex gap-2"><button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white" @click="saveSupply">{{ supplyForm.id ? 'Actualizar' : 'Crear' }}</button><button class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold" @click="resetSupplyForm">Limpiar</button></div>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-100 text-left text-slate-600"><tr><th class="px-3 py-2">SKU</th><th class="px-3 py-2">Nombre</th><th class="px-3 py-2">Tipo</th><th class="px-3 py-2">Costo total</th><th class="px-3 py-2">Costo unit.</th><th class="px-3 py-2">Stock</th><th class="px-3 py-2">Acciones</th></tr></thead>
            <tbody>
              <tr v-for="item in insumos" :key="item.id" class="border-t border-slate-200">
                <td class="px-3 py-2">{{ item.sku }}</td>
                <td class="px-3 py-2">{{ item.nombre }}</td>
                <td class="px-3 py-2">{{ item.tipo_registro }}</td>
                <td class="px-3 py-2">{{ money(item.costo_total_compra) }}</td>
                <td class="px-3 py-2">{{ money(item.costo_unitario) }}</td>
                <td class="px-3 py-2" :class="item.stock_actual <= item.stock_minimo ? 'text-red-600 font-semibold' : ''">{{ item.stock_actual }} {{ item.unidad }}</td>
                <td class="px-3 py-2"><div class="flex gap-2"><button class="rounded-md border border-slate-300 px-2 py-1" @click="editSupply(item)">Editar</button><button class="rounded-md border border-red-300 px-2 py-1 text-red-600" @click="deleteSupply(item.id)">Eliminar</button></div></td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <p v-if="feedback" class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-800">{{ feedback }}</p>
    </section>
  </AppLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '../../layouts/AppLayout.vue';

const props = defineProps({
  stats: { type: Object, required: true },
  categorias: { type: Array, default: () => [] },
  productos: { type: Array, default: () => [] },
  insumos: { type: Array, default: () => [] },
  ofertas: { type: Array, default: () => [] },
  noticia: { type: String, default: '' },
  settings: { type: Object, default: () => ({ store_name: 'Nova Commerce', logo_url: null }) },
});

const tabs = [
  { key: 'configuracion', label: 'Configuracion Tienda' },
  { key: 'productos', label: 'Productos y Stock' },
  { key: 'promociones', label: 'Promociones' },
  { key: 'noticias', label: 'Noticias Activas' },
  { key: 'insumos', label: 'Insumos Fabrica' },
];

const sizeOptions = ['XS', 'S', 'M', 'L', 'XL', 'XXL', '28', '30', '32', '34', '36', 'UNICA'];
const activeTab = ref('configuracion');
const feedback = ref('');
const newsText = ref(props.noticia || '');
const settingsForm = ref({ store_name: props.settings.store_name || 'Nova Commerce', logoFile: null });
const settingsLogoPreview = ref(props.settings.logo_url || null);

const productForm = ref(emptyProduct());
const offerForm = ref(emptyOffer());
const supplyForm = ref(emptySupply());

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
const refresh = () => router.reload({ only: ['stats', 'productos', 'categorias', 'insumos', 'ofertas', 'noticia', 'settings'] });

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
