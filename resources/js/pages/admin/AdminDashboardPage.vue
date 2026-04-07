<template>
  <AppLayout>
    <Head title="Admin" />
    <section class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <div class="mb-6 rounded-2xl border border-[color:var(--line)] bg-[color:var(--surface)] p-5">
        <h1 class="font-display text-3xl font-bold">Panel Administrativo</h1>
        <p class="mt-2 text-sm text-[color:var(--muted)]">Gestion de fabrica: productos, stock, promociones, noticias e insumos.</p>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
          <div class="rounded-xl bg-white p-3"><p class="text-xs text-[color:var(--muted)]">Productos</p><p class="text-xl font-bold">{{ stats.productos }}</p></div>
          <div class="rounded-xl bg-white p-3"><p class="text-xs text-[color:var(--muted)]">Insumos</p><p class="text-xl font-bold">{{ stats.insumos }}</p></div>
          <div class="rounded-xl bg-white p-3"><p class="text-xs text-[color:var(--muted)]">Promociones</p><p class="text-xl font-bold">{{ stats.ofertas }}</p></div>
          <div class="rounded-xl bg-white p-3"><p class="text-xs text-[color:var(--muted)]">Promociones activas</p><p class="text-xl font-bold">{{ stats.ofertasActivas }}</p></div>
        </div>
      </div>

      <div class="flex flex-wrap gap-2">
        <button v-for="tab in tabs" :key="tab.key" class="chip cursor-pointer" :class="activeTab === tab.key ? 'bg-[color:var(--ink)] text-white' : 'bg-white'" @click="activeTab = tab.key">
          {{ tab.label }}
        </button>
      </div>

      <section v-if="activeTab === 'productos'" class="mt-5 space-y-4">
        <div class="rounded-2xl border border-[color:var(--line)] bg-[color:var(--surface)] p-4">
          <h2 class="font-display text-xl font-bold">Crear / Editar Producto</h2>
          <div class="mt-3 grid gap-3 md:grid-cols-2">
            <input v-model="productForm.referencia" placeholder="Referencia" class="rounded-xl border border-[color:var(--line)] bg-white px-3 py-2.5" />
            <input v-model.number="productForm.precio" type="number" min="0" placeholder="Precio" class="rounded-xl border border-[color:var(--line)] bg-white px-3 py-2.5" />

            <select v-model.number="productForm.categoria_id" class="rounded-xl border border-[color:var(--line)] bg-white px-3 py-2.5">
              <option :value="null">Selecciona categoria</option>
              <option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.categoria }}</option>
            </select>
            <select v-model="productForm.estado" class="rounded-xl border border-[color:var(--line)] bg-white px-3 py-2.5">
              <option value="DISPONIBLE">DISPONIBLE</option>
              <option value="NO_DISPONIBLE">NO_DISPONIBLE</option>
            </select>

            <div class="md:col-span-2">
              <label class="mb-1 block text-xs font-semibold uppercase text-[color:var(--muted)]">Imagenes del producto (max 4)</label>
              <input type="file" multiple accept="image/png,image/jpeg,image/jpg,image/webp" class="w-full rounded-xl border border-[color:var(--line)] bg-white px-3 py-2.5" @change="handleProductImages" />
              <p class="mt-1 text-xs text-[color:var(--muted)]">Si editas y subes nuevas, se reemplazan las existentes.</p>
            </div>

            <div v-if="productForm.existingImages.length" class="md:col-span-2">
              <p class="mb-2 text-xs font-semibold uppercase text-[color:var(--muted)]">Imagenes actuales</p>
              <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                <img v-for="image in productForm.existingImages" :key="image.id" :src="image.url" alt="Imagen producto" class="h-20 w-full rounded-xl object-cover" />
              </div>
            </div>

            <div v-if="productForm.newImageNames.length" class="md:col-span-2">
              <p class="mb-1 text-xs font-semibold uppercase text-[color:var(--muted)]">Nuevas imagenes seleccionadas</p>
              <ul class="text-sm text-[color:var(--muted)]">
                <li v-for="name in productForm.newImageNames" :key="name">{{ name }}</li>
              </ul>
            </div>

            <div class="md:col-span-2">
              <p class="mb-2 text-xs font-semibold uppercase text-[color:var(--muted)]">Tallas y stock por talla</p>
              <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                <label v-for="size in sizeOptions" :key="size" class="flex items-center gap-2 rounded-xl border border-[color:var(--line)] bg-white px-3 py-2">
                  <input v-model="productForm.sizeStock[size].enabled" type="checkbox" class="h-4 w-4" />
                  <span class="min-w-10 text-sm font-semibold">{{ size }}</span>
                  <input
                    v-model.number="productForm.sizeStock[size].stock"
                    type="number"
                    min="0"
                    class="w-full rounded-lg border border-[color:var(--line)] px-2 py-1 text-sm"
                    :disabled="!productForm.sizeStock[size].enabled"
                    placeholder="0"
                  />
                </label>
              </div>
            </div>
          </div>

          <div class="mt-3 flex flex-wrap gap-2">
            <button class="btn-main px-4 py-2 text-sm font-bold" @click="saveProduct">{{ productForm.id ? 'Actualizar' : 'Crear' }}</button>
            <button class="btn-soft px-4 py-2 text-sm font-semibold" @click="resetProductForm">Limpiar</button>
          </div>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-[color:var(--line)] bg-white">
          <table class="min-w-full text-sm">
            <thead class="bg-[color:var(--sand)]/60 text-left">
              <tr><th class="px-3 py-2">Ref</th><th class="px-3 py-2">Categoria</th><th class="px-3 py-2">Precio</th><th class="px-3 py-2">Tallas</th><th class="px-3 py-2">Stock</th><th class="px-3 py-2">Img</th><th class="px-3 py-2">Acciones</th></tr>
            </thead>
            <tbody>
              <tr v-for="item in productos" :key="item.id" class="border-t border-[color:var(--line)]">
                <td class="px-3 py-2">{{ item.referencia }}</td>
                <td class="px-3 py-2">{{ item.categoria }}</td>
                <td class="px-3 py-2">{{ money(item.precio) }}</td>
                <td class="px-3 py-2">{{ item.tallas }}</td>
                <td class="px-3 py-2">{{ item.stock_total }}</td>
                <td class="px-3 py-2">{{ item.imagenes?.length || 0 }}</td>
                <td class="px-3 py-2">
                  <div class="flex gap-2">
                    <button class="chip" @click="editProduct(item)">Editar</button>
                    <button class="chip text-red-600" @click="deleteProduct(item.id)">Eliminar</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section v-if="activeTab === 'promociones'" class="mt-5 space-y-4">
        <div class="rounded-2xl border border-[color:var(--line)] bg-[color:var(--surface)] p-4">
          <h2 class="font-display text-xl font-bold">Crear / Editar Promocion</h2>
          <div class="mt-3 grid gap-3 md:grid-cols-2">
            <input v-model="offerForm.titulo" placeholder="Titulo" class="rounded-xl border border-[color:var(--line)] bg-white px-3 py-2.5" />
            <input v-model="offerForm.descripcion" placeholder="Descripcion" class="rounded-xl border border-[color:var(--line)] bg-white px-3 py-2.5" />
            <select v-model.number="offerForm.producto_id" class="rounded-xl border border-[color:var(--line)] bg-white px-3 py-2.5">
              <option :value="null">Aplicar a producto (opcional)</option>
              <option v-for="p in productos" :key="p.id" :value="p.id">{{ p.referencia }}</option>
            </select>
            <select v-model.number="offerForm.categoria_id" class="rounded-xl border border-[color:var(--line)] bg-white px-3 py-2.5">
              <option :value="null">Aplicar a categoria (opcional)</option>
              <option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.categoria }}</option>
            </select>
            <input v-model.number="offerForm.descuento_porcentaje" type="number" min="0" placeholder="% descuento" class="rounded-xl border border-[color:var(--line)] bg-white px-3 py-2.5" />
            <input v-model.number="offerForm.descuento_fijo" type="number" min="0" placeholder="Descuento fijo" class="rounded-xl border border-[color:var(--line)] bg-white px-3 py-2.5" />
            <input v-model.number="offerForm.precio_oferta" type="number" min="0" placeholder="Precio oferta" class="rounded-xl border border-[color:var(--line)] bg-white px-3 py-2.5" />
            <select v-model="offerForm.esta_activa" class="rounded-xl border border-[color:var(--line)] bg-white px-3 py-2.5"><option :value="true">Activa</option><option :value="false">Inactiva</option></select>
            <input v-model="offerForm.fecha_inicio" type="datetime-local" class="rounded-xl border border-[color:var(--line)] bg-white px-3 py-2.5" />
            <input v-model="offerForm.fecha_fin" type="datetime-local" class="rounded-xl border border-[color:var(--line)] bg-white px-3 py-2.5" />
          </div>
          <div class="mt-3 flex gap-2"><button class="btn-main px-4 py-2 text-sm font-bold" @click="saveOffer">{{ offerForm.id ? 'Actualizar' : 'Crear' }}</button><button class="btn-soft px-4 py-2 text-sm font-semibold" @click="resetOfferForm">Limpiar</button></div>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-[color:var(--line)] bg-white">
          <table class="min-w-full text-sm">
            <thead class="bg-[color:var(--sand)]/60 text-left"><tr><th class="px-3 py-2">Titulo</th><th class="px-3 py-2">Aplicacion</th><th class="px-3 py-2">Vigencia</th><th class="px-3 py-2">Estado</th><th class="px-3 py-2">Acciones</th></tr></thead>
            <tbody>
              <tr v-for="item in ofertas" :key="item.id" class="border-t border-[color:var(--line)]">
                <td class="px-3 py-2">{{ item.titulo }}</td>
                <td class="px-3 py-2">{{ item.producto || item.categoria }}</td>
                <td class="px-3 py-2">{{ item.fecha_inicio?.slice(0, 10) }} a {{ item.fecha_fin?.slice(0, 10) }}</td>
                <td class="px-3 py-2">{{ item.esta_activa ? 'Activa' : 'Inactiva' }}</td>
                <td class="px-3 py-2"><div class="flex gap-2"><button class="chip" @click="editOffer(item)">Editar</button><button class="chip text-red-600" @click="deleteOffer(item.id)">Eliminar</button></div></td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section v-if="activeTab === 'noticias'" class="mt-5">
        <div class="rounded-2xl border border-[color:var(--line)] bg-[color:var(--surface)] p-4">
          <h2 class="font-display text-xl font-bold">Noticias / Promociones Activas</h2>
          <p class="mt-1 text-sm text-[color:var(--muted)]">Separa cada mensaje con coma para mostrarlos en la barra promocional.</p>
          <textarea v-model="newsText" rows="5" class="mt-3 w-full rounded-xl border border-[color:var(--line)] bg-white px-3 py-2.5"></textarea>
          <button class="btn-main mt-3 px-4 py-2 text-sm font-bold" @click="saveNews">Guardar noticias</button>
        </div>
      </section>

      <section v-if="activeTab === 'insumos'" class="mt-5 space-y-4">
        <div class="rounded-2xl border border-[color:var(--line)] bg-[color:var(--surface)] p-4">
          <h2 class="font-display text-xl font-bold">Crear / Editar Insumo</h2>
          <div class="mt-3 grid gap-3 md:grid-cols-2">
            <input v-model="supplyForm.nombre" placeholder="Nombre" class="rounded-xl border border-[color:var(--line)] bg-white px-3 py-2.5" />
            <input v-model="supplyForm.sku" placeholder="SKU" class="rounded-xl border border-[color:var(--line)] bg-white px-3 py-2.5" />
            <input v-model="supplyForm.unidad" placeholder="Unidad" class="rounded-xl border border-[color:var(--line)] bg-white px-3 py-2.5" />
            <input v-model.number="supplyForm.stock_actual" type="number" min="0" placeholder="Stock actual" class="rounded-xl border border-[color:var(--line)] bg-white px-3 py-2.5" />
            <input v-model.number="supplyForm.stock_minimo" type="number" min="0" placeholder="Stock minimo" class="rounded-xl border border-[color:var(--line)] bg-white px-3 py-2.5" />
            <input v-model.number="supplyForm.costo_unitario" type="number" min="0" placeholder="Costo unitario" class="rounded-xl border border-[color:var(--line)] bg-white px-3 py-2.5" />
            <input v-model="supplyForm.proveedor" placeholder="Proveedor" class="rounded-xl border border-[color:var(--line)] bg-white px-3 py-2.5" />
            <select v-model="supplyForm.activo" class="rounded-xl border border-[color:var(--line)] bg-white px-3 py-2.5"><option :value="true">Activo</option><option :value="false">Inactivo</option></select>
          </div>
          <div class="mt-3 flex gap-2"><button class="btn-main px-4 py-2 text-sm font-bold" @click="saveSupply">{{ supplyForm.id ? 'Actualizar' : 'Crear' }}</button><button class="btn-soft px-4 py-2 text-sm font-semibold" @click="resetSupplyForm">Limpiar</button></div>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-[color:var(--line)] bg-white">
          <table class="min-w-full text-sm">
            <thead class="bg-[color:var(--sand)]/60 text-left"><tr><th class="px-3 py-2">SKU</th><th class="px-3 py-2">Nombre</th><th class="px-3 py-2">Stock</th><th class="px-3 py-2">Minimo</th><th class="px-3 py-2">Costo</th><th class="px-3 py-2">Acciones</th></tr></thead>
            <tbody>
              <tr v-for="item in insumos" :key="item.id" class="border-t border-[color:var(--line)]">
                <td class="px-3 py-2">{{ item.sku }}</td>
                <td class="px-3 py-2">{{ item.nombre }}</td>
                <td class="px-3 py-2" :class="item.stock_actual <= item.stock_minimo ? 'text-red-600 font-bold' : ''">{{ item.stock_actual }} {{ item.unidad }}</td>
                <td class="px-3 py-2">{{ item.stock_minimo }}</td>
                <td class="px-3 py-2">{{ money(item.costo_unitario) }}</td>
                <td class="px-3 py-2"><div class="flex gap-2"><button class="chip" @click="editSupply(item)">Editar</button><button class="chip text-red-600" @click="deleteSupply(item.id)">Eliminar</button></div></td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <p v-if="feedback" class="mt-4 rounded-xl bg-[color:var(--sand)] px-4 py-2 text-sm font-semibold">{{ feedback }}</p>
    </section>
  </AppLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '../../layouts/AppLayout.vue';

const props = defineProps({
  stats: { type: Object, required: true },
  categorias: { type: Array, default: () => [] },
  productos: { type: Array, default: () => [] },
  insumos: { type: Array, default: () => [] },
  ofertas: { type: Array, default: () => [] },
  noticia: { type: String, default: '' },
});

const tabs = [
  { key: 'productos', label: 'Productos y Stock' },
  { key: 'promociones', label: 'Promociones' },
  { key: 'noticias', label: 'Noticias Activas' },
  { key: 'insumos', label: 'Insumos Fabrica' },
];

const sizeOptions = ['XS', 'S', 'M', 'L', 'XL', 'XXL', '28', '30', '32', '34', '36', 'UNICA'];
const activeTab = ref('productos');
const feedback = ref('');
const newsText = ref(props.noticia || '');

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
    stock_actual: 0,
    stock_minimo: 0,
    costo_unitario: 0,
    proveedor: '',
    activo: true,
  };
}

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
const money = (value) => new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(value || 0);
const refresh = () => router.reload({ only: ['stats', 'productos', 'categorias', 'insumos', 'ofertas', 'noticia'] });

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

  feedback.value = payload.message || 'Proceso exitoso';
  setTimeout(() => {
    feedback.value = '';
  }, 1800);
};

const requestFormData = async (url, method, formData) => {
  const res = await fetch(url, {
    method,
    headers: {
      Accept: 'application/json',
      'X-CSRF-TOKEN': csrf(),
    },
    body: formData,
  });

  const payload = await res.json();
  if (!res.ok || !payload.success) throw new Error(payload.message || 'No se pudo completar la accion');

  feedback.value = payload.message || 'Proceso exitoso';
  setTimeout(() => {
    feedback.value = '';
  }, 1800);
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
    if (item?.enabled) {
      entries.push({ talla: size, stock: Number(item.stock || 0) });
    }
  }
  return entries;
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

  productForm.value.newImages.forEach((file) => {
    formData.append('images[]', file);
  });

  const url = productForm.value.id ? `/admin/productos/${productForm.value.id}` : '/admin/productos';
  await requestFormData(url, productForm.value.id ? 'POST' : 'POST', withMethod(formData, productForm.value.id ? 'PUT' : 'POST'));

  resetProductForm();
  refresh();
};

const withMethod = (formData, method) => {
  if (method !== 'POST') formData.append('_method', method);
  return formData;
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

const resetOfferForm = () => {
  offerForm.value = emptyOffer();
};

const deleteOffer = async (id) => {
  if (!confirm('Eliminar promocion?')) return;
  await requestJson(`/admin/ofertas/${id}`, 'DELETE');
  refresh();
};

const saveNews = async () => {
  await requestJson('/admin/noticias', 'PUT', { campos_adicionales: newsText.value });
  refresh();
};

const saveSupply = async () => {
  await requestJson(supplyForm.value.id ? `/admin/insumos/${supplyForm.value.id}` : '/admin/insumos', supplyForm.value.id ? 'PUT' : 'POST', supplyForm.value);
  resetSupplyForm();
  refresh();
};

const editSupply = (item) => {
  supplyForm.value = { ...item };
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
