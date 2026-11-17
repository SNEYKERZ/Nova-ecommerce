<template>
  <div>
    <PageHeader title="Productos" description="Catálogo, precios, tallas e imágenes.">
      <button type="button" class="a-btn a-btn-primary" @click="openCreate"><AdminIcon name="plus" size="sm" />Agregar producto</button>
    </PageHeader>

    <div class="a-card overflow-hidden">
      <div class="flex flex-col gap-3 border-b border-[var(--a-border)] p-3 sm:flex-row sm:items-center">
        <div class="a-tabs" role="tablist" aria-label="Filtrar productos">
          <button v-for="tab in tabs" :key="tab.value" type="button" role="tab" class="a-tab" :aria-selected="filter === tab.value ? 'true' : 'false'" @click="filter = tab.value">{{ tab.label }}</button>
        </div>
        <SearchField :model-value="localSearch" class="sm:ml-auto sm:max-w-xs" placeholder="Buscar nombre, referencia o categoría" label="Buscar productos" @update:model-value="onSearchInput" />
      </div>

      <div v-if="filtered.length" class="overflow-x-auto">
        <table class="a-table">
          <thead>
            <tr>
              <th scope="col">Producto</th>
              <th scope="col">Estado</th>
              <th scope="col">Inventario</th>
              <th scope="col">Categoría</th>
              <th scope="col" class="num">Precio</th>
              <th scope="col">Nueva colección</th>
              <th scope="col"><span class="visually-hidden">Acciones</span></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in filtered" :key="item.id" class="is-clickable" @click="openEdit(item)">
              <td>
                <div class="flex items-center gap-3">
                  <img v-if="item.imagenes?.[0]?.url" :src="item.imagenes[0].url" alt="" class="a-thumb" loading="lazy" width="40" height="40" />
                  <span v-else class="a-thumb flex items-center justify-center text-[var(--a-muted)]"><AdminIcon name="image" size="sm" /></span>
                  <div class="min-w-0">
                    <button type="button" class="block max-w-[16rem] truncate text-left font-semibold hover:underline" @click.stop="openEdit(item)">{{ item.nombre || item.referencia }}</button>
                    <p class="text-xs text-[var(--a-muted)]">Ref. {{ item.referencia }}</p>
                  </div>
                </div>
              </td>
              <td><span class="a-badge" :class="item.estado === 'DISPONIBLE' ? 'tone-success' : ''">{{ item.estado === 'DISPONIBLE' ? 'Activo' : 'Oculto' }}</span></td>
              <td>
                <span v-if="!(item.stock_por_talla?.length)" class="text-[var(--a-muted)]">Sin tallas</span>
                <span v-else :class="item.stock_total <= 0 ? 'font-semibold text-[var(--a-critical)]' : ''">
                  {{ item.stock_total }} en stock<span class="text-[var(--a-muted)]"> · {{ item.stock_por_talla.length }} {{ item.stock_por_talla.length === 1 ? 'talla' : 'tallas' }}</span>
                </span>
              </td>
              <td class="text-[var(--a-ink-2)]">{{ item.categoria || '—' }}</td>
              <td class="num">{{ money(item.precio) }}</td>
              <td @click.stop>
                <button
                  type="button"
                  role="switch"
                  class="a-switch"
                  :aria-checked="item.nueva_coleccion ? 'true' : 'false'"
                  :aria-label="`Nueva colección: ${item.nombre || item.referencia}`"
                  :disabled="toggling[item.id]"
                  @click="toggleCollection(item)"
                ></button>
              </td>
              <td @click.stop>
                <div class="flex justify-end gap-1">
                  <a :href="`/productos/${item.id}`" target="_blank" rel="noopener" class="a-btn a-btn-plain a-btn-sm a-btn-icon" :aria-label="`Ver ${item.nombre || item.referencia} en la tienda`"><AdminIcon name="external" size="sm" /></a>
                  <button type="button" class="a-btn a-btn-plain-critical a-btn-sm a-btn-icon" :aria-label="`Eliminar ${item.nombre || item.referencia}`" @click="remove(item)"><AdminIcon name="trash" size="sm" /></button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <EmptyState v-else icon="tag" :title="localSearch ? `Sin resultados para “${localSearch}”` : list.length ? 'Ningún producto con este filtro' : 'Agrega tu primer producto'" :description="list.length || localSearch ? 'Prueba con otra búsqueda o filtro.' : 'Sube fotos, define precio y stock por talla.'">
        <button v-if="!list.length && !localSearch" type="button" class="a-btn a-btn-primary" @click="openCreate">Agregar producto</button>
      </EmptyState>

      <AdminPagination :meta="meta" :only="['productos']" section="productos" />
    </div>

    <!-- Formulario -->
    <Drawer :open="drawerOpen" :title="form.id ? 'Editar producto' : 'Nuevo producto'" :subtitle="form.id ? `Ref. ${form.referencia}` : ''" size="lg" :busy="saving" @close="close">
      <form id="product-form" novalidate class="space-y-4" @submit.prevent="save">
        <section class="a-card a-card-pad space-y-4">
          <FormField v-slot="f" label="Nombre visible en la tienda" :error="errors.nombre" help="Si lo dejas vacío se mostrará la referencia.">
            <input :id="f.id" v-model.trim="form.nombre" class="a-input" maxlength="255" placeholder="Ej. Gorra Trucker Negra" :aria-invalid="f.invalid" :aria-describedby="f.describedby" />
          </FormField>
          <FormField v-slot="f" label="Descripción" optional :error="errors.descripcion">
            <textarea :id="f.id" v-model.trim="form.descripcion" rows="3" class="a-input" placeholder="Materiales, ajuste, cuidados…" :aria-invalid="f.invalid" :aria-describedby="f.describedby"></textarea>
          </FormField>
          <div class="grid gap-4 sm:grid-cols-2">
            <FormField v-slot="f" label="Referencia (SKU)" required :error="errors.referencia" help="Código interno único.">
              <input :id="f.id" v-model.trim="form.referencia" class="a-input" maxlength="255" placeholder="GORRA-001" :aria-invalid="f.invalid" :aria-describedby="f.describedby" />
            </FormField>
            <FormField v-slot="f" label="Precio (COP)" required :error="errors.precio">
              <input :id="f.id" v-model="form.precio" type="number" inputmode="numeric" min="0" step="100" class="a-input" placeholder="59900" :aria-invalid="f.invalid" :aria-describedby="f.describedby" />
            </FormField>
            <FormField v-slot="f" label="Categoría" required :error="errors.categoria_id">
              <select :id="f.id" v-model="form.categoria_id" class="a-input" :aria-invalid="f.invalid" :aria-describedby="f.describedby">
                <option :value="null">Selecciona una categoría</option>
                <option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.categoria }}</option>
              </select>
            </FormField>
            <FormField v-slot="f" label="Estado">
              <select :id="f.id" v-model="form.estado" class="a-input">
                <option value="DISPONIBLE">Activo (visible en la tienda)</option>
                <option value="NO_DISPONIBLE">Oculto</option>
              </select>
            </FormField>
          </div>
          <label class="a-check">
            <input v-model="form.nueva_coleccion" type="checkbox" />
            Mostrar con la etiqueta “Nuevo” (nueva colección)
          </label>
        </section>

        <!-- Imágenes -->
        <section class="a-card a-card-pad" aria-labelledby="pf-img">
          <div class="flex items-center justify-between gap-3">
            <h3 id="pf-img" class="a-subtitle">Imágenes</h3>
            <span class="text-xs text-[var(--a-muted)]">Hasta 4 · JPG, PNG o WebP · máx. 10 MB</span>
          </div>
          <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
            <figure v-for="(img, index) in shownImages" :key="img.key" class="relative overflow-hidden rounded-lg border border-[var(--a-border)] bg-[var(--a-surface-2)]">
              <img :src="img.url" alt="" class="aspect-square w-full object-cover" />
              <span v-if="index === 0" class="absolute left-1.5 top-1.5 rounded bg-black/70 px-1.5 py-0.5 text-[10px] font-semibold text-white">Principal</span>
              <button v-if="img.isNew" type="button" class="absolute right-1.5 top-1.5 flex h-7 w-7 items-center justify-center rounded-full bg-white/95 text-[var(--a-critical)] shadow" :aria-label="`Quitar imagen ${index + 1}`" @click="removeNewImage(index)">
                <AdminIcon name="trash" size="sm" />
              </button>
            </figure>
            <label v-if="form.newImages.length < 4" class="flex aspect-square cursor-pointer flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed border-[var(--a-border-strong)] text-center text-xs font-medium text-[var(--a-ink-2)] hover:bg-[var(--a-surface-2)]">
              <AdminIcon name="upload" />
              {{ form.newImages.length ? 'Agregar otra' : 'Subir imágenes' }}
              <input type="file" class="visually-hidden" multiple accept="image/jpeg,image/png,image/webp" @change="onImages" />
            </label>
          </div>
          <p v-if="form.id && form.existingImages.length && form.newImages.length" class="mt-3 rounded-lg bg-[var(--tone-warning-bg)] px-3 py-2 text-xs font-medium text-[var(--tone-warning)]">
            Al guardar, las imágenes nuevas reemplazarán las {{ form.existingImages.length }} actuales.
          </p>
          <p v-if="errors.images" class="a-error">{{ errors.images }}</p>
        </section>

        <!-- Tallas y stock -->
        <section class="a-card a-card-pad" aria-labelledby="pf-var">
          <div class="flex flex-wrap items-center justify-between gap-2">
            <h3 id="pf-var" class="a-subtitle">Tallas y stock</h3>
            <span class="text-xs text-[var(--a-muted)] tabular">{{ enabledSizes.length }} tallas · {{ totalStock }} unidades</span>
          </div>
          <p class="mt-1 text-xs text-[var(--a-muted)]">Activa las tallas que vendes. Si el producto tiene tallas, el cliente deberá elegir una.</p>
          <div class="mt-3 flex flex-wrap gap-2" role="group" aria-label="Tallas">
            <button v-for="size in sizeKeys" :key="size" type="button" class="a-btn a-btn-sm" :class="form.sizeStock[size].enabled ? 'a-btn-primary' : 'a-btn-secondary'" :aria-pressed="form.sizeStock[size].enabled ? 'true' : 'false'" @click="toggleSize(size)">{{ size }}</button>
          </div>
          <div class="mt-3 flex items-end gap-2">
            <FormField v-slot="f" label="Otra talla" class="max-w-[10rem]">
              <input :id="f.id" v-model.trim="customSize" class="a-input uppercase" maxlength="40" placeholder="Ej. 7 1/4" @keydown.enter.prevent="addCustomSize" />
            </FormField>
            <button type="button" class="a-btn a-btn-secondary" :disabled="!customSize" @click="addCustomSize">Añadir</button>
          </div>
          <div v-if="enabledSizes.length" class="mt-4 overflow-hidden rounded-lg border border-[var(--a-border)]">
            <table class="a-table">
              <thead><tr><th scope="col">Talla</th><th scope="col">Stock disponible</th></tr></thead>
              <tbody>
                <tr v-for="size in enabledSizes" :key="size">
                  <td class="font-semibold">{{ size }}</td>
                  <td>
                    <label :for="`stock-${size}`" class="visually-hidden">Stock talla {{ size }}</label>
                    <input :id="`stock-${size}`" v-model.number="form.sizeStock[size].stock" type="number" min="0" step="1" class="a-input !w-28 !min-h-8 !py-1" :aria-invalid="!(form.sizeStock[size].stock >= 0) ? 'true' : 'false'" />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <p v-if="errors.stock_por_talla" class="a-error">{{ errors.stock_por_talla }}</p>
        </section>
      </form>

      <template #footer>
        <button v-if="form.id" type="button" class="a-btn a-btn-plain-critical mr-auto" :disabled="saving" @click="remove(form)">Eliminar</button>
        <button type="button" class="a-btn a-btn-secondary" :disabled="saving" @click="close">Cancelar</button>
        <button type="submit" form="product-form" class="a-btn a-btn-primary" :disabled="saving">
          <span v-if="saving" class="spinner" aria-hidden="true"></span>
          {{ saving ? 'Guardando…' : form.id ? 'Guardar cambios' : 'Crear producto' }}
        </button>
      </template>
    </Drawer>
  </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue';
import AdminIcon from '../AdminIcon.vue';
import AdminPagination from '../AdminPagination.vue';
import EmptyState from '../EmptyState.vue';
import FormField from '../FormField.vue';
import PageHeader from '../PageHeader.vue';
import SearchField from '../SearchField.vue';
import Drawer from '../../ui/Drawer.vue';
import { useConfirm } from '../../../composables/useConfirm';
import { useToast } from '../../../composables/useToast';
import { api } from '../../../utils/http';
import { debounce, money } from '../../../utils/format';

const props = defineProps({
  productos: { type: [Object, Array], default: () => ({ data: [] }) },
  categorias: { type: Array, default: () => [] },
  search: { type: String, default: '' },
});
const emit = defineEmits(['search']);

const toast = useToast();
const { confirm } = useConfirm();

const PRESET_SIZES = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'UNICA', '28', '30', '32', '34', '36'];
const MAX_IMAGE_BYTES = 10 * 1024 * 1024;

const list = computed(() => (Array.isArray(props.productos) ? props.productos : props.productos?.data || []));
const meta = computed(() => (Array.isArray(props.productos) ? null : props.productos));

/* Filtros */
const filter = ref('ALL');
const tabs = [
  { value: 'ALL', label: 'Todos' },
  { value: 'DISPONIBLE', label: 'Activos' },
  { value: 'NO_DISPONIBLE', label: 'Ocultos' },
  { value: 'SIN_STOCK', label: 'Sin stock' },
];
const filtered = computed(() => list.value.filter((p) => {
  if (filter.value === 'SIN_STOCK') return (p.stock_por_talla?.length ?? 0) > 0 && p.stock_total <= 0;
  if (filter.value !== 'ALL') return p.estado === filter.value;
  return true;
}));

const localSearch = ref(props.search);
watch(() => props.search, (v) => { localSearch.value = v; });
const emitSearch = debounce((v) => emit('search', v), 350);
const onSearchInput = (v) => { localSearch.value = v; emitSearch(v.trim()); };

const reload = () => new Promise((resolve) => router.reload({ only: ['productos', 'stats'], onFinish: resolve }));

/* Nueva colección (optimista) */
const toggling = reactive({});
const toggleCollection = async (item) => {
  toggling[item.id] = true;
  item.nueva_coleccion = !item.nueva_coleccion;
  try {
    const payload = await api(`/admin/productos/${item.id}/toggle-coleccion`, { method: 'POST' });
    item.nueva_coleccion = Boolean(payload.nueva_coleccion ?? item.nueva_coleccion);
    toast.success(payload.message || 'Producto actualizado');
  } catch (error) {
    item.nueva_coleccion = !item.nueva_coleccion;
    toast.error(error.message);
  } finally {
    toggling[item.id] = false;
  }
};

/* Formulario */
const buildSizeStock = (entries = []) => {
  const state = {};
  PRESET_SIZES.forEach((size) => { state[size] = { enabled: false, stock: 0 }; });
  entries.forEach((entry) => { state[entry.talla] = { enabled: true, stock: Number(entry.stock) || 0 }; });
  return state;
};
const emptyForm = () => ({
  id: null, referencia: '', nombre: '', descripcion: '', precio: '', categoria_id: null,
  estado: 'DISPONIBLE', nueva_coleccion: false, sizeStock: buildSizeStock(),
  newImages: [], existingImages: [],
});

const drawerOpen = ref(false);
const form = ref(emptyForm());
const errors = reactive({});
const saving = ref(false);
const customSize = ref('');
let initialSnapshot = '';

const sizeKeys = computed(() => Object.keys(form.value.sizeStock));
const enabledSizes = computed(() => sizeKeys.value.filter((s) => form.value.sizeStock[s].enabled));
const totalStock = computed(() => enabledSizes.value.reduce((sum, s) => sum + (Number(form.value.sizeStock[s].stock) || 0), 0));

const shownImages = computed(() => (form.value.newImages.length
  ? form.value.newImages.map((img, i) => ({ key: `n${i}-${img.name}`, url: img.preview, isNew: true }))
  : form.value.existingImages.map((img) => ({ key: `e${img.id}`, url: img.url, isNew: false }))));

const snapshot = () => JSON.stringify({ ...form.value, newImages: form.value.newImages.map((i) => i.name) });
const clearErrors = () => Object.keys(errors).forEach((k) => delete errors[k]);

const openCreate = () => {
  clearErrors();
  form.value = emptyForm();
  initialSnapshot = snapshot();
  drawerOpen.value = true;
};

const openEdit = (item) => {
  clearErrors();
  form.value = {
    id: item.id,
    referencia: item.referencia || '',
    nombre: item.nombre || '',
    descripcion: item.descripcion || '',
    precio: item.precio ?? '',
    categoria_id: item.categoria_id ?? null,
    estado: item.estado || 'DISPONIBLE',
    nueva_coleccion: Boolean(item.nueva_coleccion),
    sizeStock: buildSizeStock(item.stock_por_talla || []),
    newImages: [],
    existingImages: item.imagenes || [],
  };
  initialSnapshot = snapshot();
  drawerOpen.value = true;
};

const revokePreviews = () => form.value.newImages.forEach((img) => URL.revokeObjectURL(img.preview));

const close = async () => {
  if (snapshot() !== initialSnapshot) {
    const ok = await confirm({ title: '¿Descartar cambios?', message: 'Hay cambios sin guardar en este producto.', confirmLabel: 'Descartar', cancelLabel: 'Seguir editando' });
    if (!ok) return;
  }
  revokePreviews();
  drawerOpen.value = false;
};

const toggleSize = (size) => { form.value.sizeStock[size].enabled = !form.value.sizeStock[size].enabled; };
const addCustomSize = () => {
  const size = customSize.value.trim().toUpperCase();
  if (!size) return;
  if (!form.value.sizeStock[size]) form.value.sizeStock[size] = { enabled: true, stock: 0 };
  else form.value.sizeStock[size].enabled = true;
  customSize.value = '';
};

const onImages = (event) => {
  delete errors.images;
  const files = Array.from(event.target.files || []);
  event.target.value = '';
  const room = 4 - form.value.newImages.length;
  const accepted = [];
  files.forEach((file) => {
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) errors.images = `“${file.name}” no es JPG, PNG o WebP.`;
    else if (file.size > MAX_IMAGE_BYTES) errors.images = `“${file.name}” pesa más de 10 MB.`;
    else accepted.push(file);
  });
  if (accepted.length > room) errors.images = 'Máximo 4 imágenes por producto.';
  accepted.slice(0, room).forEach((file) => form.value.newImages.push({ file, name: file.name, preview: URL.createObjectURL(file) }));
};
const removeNewImage = (index) => {
  const [img] = form.value.newImages.splice(index, 1);
  if (img) URL.revokeObjectURL(img.preview);
};

const validate = () => {
  clearErrors();
  const f = form.value;
  if (!f.referencia) errors.referencia = 'Escribe la referencia.';
  if (f.precio === '' || f.precio === null) errors.precio = 'Escribe el precio.';
  else if (!(Number(f.precio) >= 0)) errors.precio = 'El precio no puede ser negativo.';
  if (!f.categoria_id) errors.categoria_id = 'Elige una categoría.';
  if (enabledSizes.value.some((s) => !(Number(f.sizeStock[s].stock) >= 0) || !Number.isInteger(Number(f.sizeStock[s].stock)))) {
    errors.stock_por_talla = 'El stock de cada talla debe ser un número entero de 0 o más.';
  }
  return !Object.keys(errors).length;
};

const save = async () => {
  if (!validate()) {
    toast.error('Revisa los campos marcados.');
    return;
  }
  const f = form.value;
  const data = new FormData();
  data.append('referencia', f.referencia);
  data.append('nombre', f.nombre || '');
  data.append('descripcion', f.descripcion || '');
  data.append('precio', String(Number(f.precio)));
  data.append('categoria_id', String(f.categoria_id));
  data.append('estado', f.estado);
  data.append('nueva_coleccion', f.nueva_coleccion ? '1' : '0');
  data.append('tallas', enabledSizes.value.join(','));
  enabledSizes.value.forEach((size, index) => {
    data.append(`stock_por_talla[${index}][talla]`, size);
    data.append(`stock_por_talla[${index}][stock]`, String(Number(f.sizeStock[size].stock) || 0));
  });
  f.newImages.forEach((img) => data.append('images[]', img.file));

  saving.value = true;
  try {
    const payload = await api(f.id ? `/admin/productos/${f.id}` : '/admin/productos', { method: f.id ? 'PUT' : 'POST', body: data });
    toast.success(payload.message || (f.id ? 'Producto actualizado' : 'Producto creado'));
    revokePreviews();
    drawerOpen.value = false;
    await reload();
  } catch (error) {
    Object.entries(error.errors || {}).forEach(([key, message]) => {
      const field = key.startsWith('images') ? 'images' : key.startsWith('stock_por_talla') ? 'stock_por_talla' : key;
      errors[field] = message;
    });
    toast.error(error.message || 'No se pudo guardar el producto.');
  } finally {
    saving.value = false;
  }
};

const remove = async (item) => {
  const name = item.nombre || item.referencia;
  const ok = await confirm({
    title: `¿Eliminar “${name}”?`,
    message: 'Se borrarán sus imágenes y su stock. Si tiene pedidos, se ocultará de la tienda en lugar de borrarse.',
    confirmLabel: 'Eliminar producto',
  });
  if (!ok) return;
  try {
    const payload = await api(`/admin/productos/${item.id}`, { method: 'DELETE' });
    if (payload.archived) {
      toast.info(payload.message || 'El producto tiene pedidos: se ocultó de la tienda.', { duration: 7000 });
      const row = list.value.find((p) => p.id === item.id);
      if (row) row.estado = 'NO_DISPONIBLE';
    } else {
      toast.success(payload.message || 'Producto eliminado');
    }
    if (drawerOpen.value && form.value.id === item.id) {
      revokePreviews();
      drawerOpen.value = false;
    }
    await reload();
  } catch (error) {
    toast.error(error.message);
  }
};

onBeforeUnmount(revokePreviews);
</script>
