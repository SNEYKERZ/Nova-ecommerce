<template>
  <AdminLayout :nav="storeNav" active="galerias" variant="store" @navigate="goSection">
    <Head title="Galerías" />

    <PageHeader title="Galerías" description="Lookbooks con fotos en las que el cliente puede comprar los productos que aparecen.">
      <a href="/galerias" target="_blank" rel="noopener" class="a-btn a-btn-secondary">Ver en la tienda <AdminIcon name="external" size="sm" /></a>
      <button type="button" class="a-btn a-btn-primary" @click="openCreate"><AdminIcon name="plus" size="sm" />Nueva galería</button>
    </PageHeader>

    <div v-if="galleries.length" class="space-y-5">
      <section v-for="gallery in galleries" :key="gallery.id" class="a-card overflow-hidden" :aria-labelledby="`g-${gallery.id}`">
        <header class="flex flex-wrap items-start justify-between gap-3 border-b border-[var(--a-border)] px-4 py-3">
          <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
              <h2 :id="`g-${gallery.id}`" class="a-subtitle">{{ gallery.nombre }}</h2>
              <span class="a-badge" :class="gallery.activo ? 'tone-success' : ''">{{ gallery.activo ? 'Publicada' : 'Oculta' }}</span>
              <span class="text-xs text-[var(--a-muted)] tabular">{{ gallery.imagenes?.length || 0 }}/{{ MAX_IMAGES }} fotos</span>
            </div>
            <p v-if="gallery.descripcion" class="mt-0.5 text-sm text-[var(--a-ink-2)]">{{ gallery.descripcion }}</p>
          </div>
          <div class="flex gap-1">
            <button type="button" class="a-btn a-btn-secondary a-btn-sm" @click="openEdit(gallery)"><AdminIcon name="edit" size="sm" />Editar</button>
            <button type="button" class="a-btn a-btn-plain-critical a-btn-sm a-btn-icon" :aria-label="`Eliminar galería ${gallery.nombre}`" @click="deleteGallery(gallery)"><AdminIcon name="trash" size="sm" /></button>
          </div>
        </header>

        <div class="grid grid-cols-2 gap-3 p-4 sm:grid-cols-3 lg:grid-cols-4">
          <article v-for="imagen in gallery.imagenes" :key="imagen.id" class="overflow-hidden rounded-xl border border-[var(--a-border)]" :class="editing?.id === imagen.id ? 'ring-2 ring-[var(--focus-ring)]' : ''">
            <button type="button" class="block w-full" :aria-label="`Editar foto ${imagen.id}: ${imagen.productos?.length || 0} productos`" @click="openImage(gallery, imagen)">
              <img :src="imagen.imagen_url" alt="" class="aspect-square w-full object-cover" loading="lazy" />
            </button>
            <div class="flex items-center justify-between gap-1 px-2 py-1.5">
              <span class="text-xs text-[var(--a-ink-2)]">{{ imagen.productos?.length || 0 }} prod. · {{ ratioLabel(imagen.aspect_ratio) }}</span>
              <button type="button" class="a-btn a-btn-plain-critical a-btn-sm a-btn-icon" :aria-label="`Eliminar foto ${imagen.id}`" @click="deleteImage(gallery, imagen)"><AdminIcon name="trash" size="sm" /></button>
            </div>
          </article>

          <label v-if="(gallery.imagenes?.length || 0) < MAX_IMAGES" class="flex aspect-square cursor-pointer flex-col items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-[var(--a-border-strong)] p-2 text-center text-xs font-medium text-[var(--a-ink-2)] hover:bg-[var(--a-surface-2)]" :class="uploading[gallery.id] ? 'pointer-events-none opacity-60' : ''">
            <span v-if="uploading[gallery.id]" class="spinner" aria-hidden="true"></span>
            <AdminIcon v-else name="upload" />
            {{ uploading[gallery.id] ? `Subiendo ${uploading[gallery.id]}` : 'Subir fotos' }}
            <span class="text-[11px] text-[var(--a-muted)]">JPG, PNG o WebP · máx. 20 MB</span>
            <input type="file" class="visually-hidden" multiple accept="image/jpeg,image/png,image/webp" :disabled="!!uploading[gallery.id]" @change="uploadImages(gallery, $event)" />
          </label>
        </div>
      </section>
    </div>

    <div v-else class="a-card">
      <EmptyState icon="gallery" title="Crea tu primera galería" description="Sube fotos de tu colección y vincula los productos que aparecen en cada una.">
        <button type="button" class="a-btn a-btn-primary" @click="openCreate">Nueva galería</button>
      </EmptyState>
    </div>

    <!-- Crear / editar galería -->
    <Drawer :open="galleryDrawer" :title="galleryForm.id ? 'Editar galería' : 'Nueva galería'" :busy="savingGallery" @close="galleryDrawer = false">
      <form id="gallery-form" class="a-card a-card-pad space-y-4" novalidate @submit.prevent="saveGallery">
        <FormField v-slot="f" label="Nombre" required :error="galleryErrors.nombre">
          <input :id="f.id" v-model.trim="galleryForm.nombre" class="a-input" maxlength="255" placeholder="Ej. Verano 2026" :aria-invalid="f.invalid" :aria-describedby="f.describedby" />
        </FormField>
        <FormField v-slot="f" label="Descripción" optional>
          <textarea :id="f.id" v-model.trim="galleryForm.descripcion" rows="3" class="a-input"></textarea>
        </FormField>
        <label class="a-check"><input v-model="galleryForm.activo" type="checkbox" />Publicada en la tienda</label>
      </form>
      <template #footer>
        <button type="button" class="a-btn a-btn-secondary" :disabled="savingGallery" @click="galleryDrawer = false">Cancelar</button>
        <button type="submit" form="gallery-form" class="a-btn a-btn-primary" :disabled="savingGallery">
          <span v-if="savingGallery" class="spinner" aria-hidden="true"></span>
          {{ galleryForm.id ? 'Guardar cambios' : 'Crear galería' }}
        </button>
      </template>
    </Drawer>

    <!-- Editar foto: formato y productos -->
    <Drawer :open="!!editing" :title="editing ? `Foto de ${editing.galleryName}` : ''" subtitle="Formato y productos que aparecen" :busy="savingImage" @close="editing = null">
      <template v-if="editing">
        <img :src="editing.imagen_url" alt="" class="mx-auto max-h-64 rounded-xl object-contain" />
        <section class="a-card a-card-pad mt-4">
          <FormField v-slot="f" label="Formato en la cuadrícula">
            <div class="flex gap-2">
              <select :id="f.id" v-model="editing.aspect_ratio" class="a-input">
                <option value="1/1">Cuadrada (1:1)</option>
                <option value="2/3">Vertical (2:3)</option>
                <option value="3/2">Horizontal (3:2)</option>
              </select>
              <button type="button" class="a-btn a-btn-secondary" :disabled="savingImage" @click="saveImage">
                <span v-if="savingImage" class="spinner" aria-hidden="true"></span>Guardar
              </button>
            </div>
          </FormField>
        </section>

        <section class="a-card a-card-pad mt-4" aria-labelledby="img-products">
          <h3 id="img-products" class="a-subtitle">Productos en esta foto</h3>
          <ul v-if="editing.productos?.length" class="mt-3 divide-y divide-[var(--a-border)]">
            <li v-for="(prod, idx) in editing.productos" :key="prod.id" class="flex items-center justify-between gap-3 py-2 text-sm">
              <div class="min-w-0">
                <p class="truncate font-medium">{{ prod.nombre || prod.referencia }}</p>
                <p class="text-xs text-[var(--a-muted)]">{{ prod.referencia }} · {{ money(prod.precio) }}</p>
              </div>
              <button type="button" class="a-btn a-btn-plain-critical a-btn-sm" @click="removeProduct(idx)">Quitar</button>
            </li>
          </ul>
          <p v-else class="mt-2 text-sm text-[var(--a-muted)]">Todavía no hay productos vinculados.</p>

          <div class="mt-4">
            <SearchField v-model="productSearch" placeholder="Buscar producto por nombre o referencia" label="Buscar producto para vincular" />
            <ul v-if="productSearch" class="mt-2 max-h-60 overflow-y-auto rounded-lg border border-[var(--a-border)]">
              <li v-for="prod in productMatches" :key="prod.id">
                <button type="button" class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm hover:bg-[var(--a-surface-2)]" @click="addProduct(prod)">
                  <span class="truncate font-medium">{{ prod.nombre || prod.referencia }}</span>
                  <span class="shrink-0 text-xs text-[var(--a-muted)]">{{ prod.referencia }} · {{ money(prod.precio) }}</span>
                </button>
              </li>
              <li v-if="!productMatches.length" class="px-3 py-2 text-sm text-[var(--a-muted)]">Sin resultados para “{{ productSearch }}”.</li>
            </ul>
          </div>
        </section>
      </template>
    </Drawer>
  </AdminLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import AdminLayout from '../../layouts/AdminLayout.vue';
import AdminIcon from '../../components/admin/AdminIcon.vue';
import EmptyState from '../../components/admin/EmptyState.vue';
import FormField from '../../components/admin/FormField.vue';
import PageHeader from '../../components/admin/PageHeader.vue';
import SearchField from '../../components/admin/SearchField.vue';
import Drawer from '../../components/ui/Drawer.vue';
import { storeNav } from '../../components/admin/adminNav';
import { useConfirm } from '../../composables/useConfirm';
import { useToast } from '../../composables/useToast';
import { api } from '../../utils/http';
import { money } from '../../utils/format';

const props = defineProps({
  galleries: { type: Array, default: () => [] },
  productos: { type: Array, default: () => [] },
});

const toast = useToast();
const { confirm } = useConfirm();
const MAX_IMAGES = 8;
const MAX_BYTES = 20 * 1024 * 1024;

const galleries = ref(props.galleries.map((g) => ({ ...g, imagenes: [...(g.imagenes || [])] })));
const goSection = (key) => router.visit(`/admin?seccion=${key}`);
const ratioLabel = (r) => ({ '1/1': '1:1', '2/3': '2:3', '3/2': '3:2' }[r] || r || '1:1');

/* ── Galerías ── */
const galleryDrawer = ref(false);
const galleryForm = reactive({ id: null, nombre: '', descripcion: '', activo: true });
const galleryErrors = reactive({});
const savingGallery = ref(false);

const openCreate = () => {
  Object.assign(galleryForm, { id: null, nombre: '', descripcion: '', activo: true });
  Object.keys(galleryErrors).forEach((k) => delete galleryErrors[k]);
  galleryDrawer.value = true;
};
const openEdit = (gallery) => {
  Object.assign(galleryForm, { id: gallery.id, nombre: gallery.nombre, descripcion: gallery.descripcion || '', activo: Boolean(gallery.activo) });
  Object.keys(galleryErrors).forEach((k) => delete galleryErrors[k]);
  galleryDrawer.value = true;
};

const saveGallery = async () => {
  Object.keys(galleryErrors).forEach((k) => delete galleryErrors[k]);
  if (!galleryForm.nombre) {
    galleryErrors.nombre = 'Escribe el nombre de la galería.';
    return;
  }
  savingGallery.value = true;
  const body = { nombre: galleryForm.nombre, descripcion: galleryForm.descripcion || null, activo: galleryForm.activo };
  try {
    if (galleryForm.id) {
      await api(`/admin/galleries/${galleryForm.id}`, { method: 'PUT', body });
      const target = galleries.value.find((g) => g.id === galleryForm.id);
      if (target) Object.assign(target, body);
      toast.success('Galería actualizada');
    } else {
      const result = await api('/admin/galleries', { method: 'POST', body });
      galleries.value.push({ ...result.data, imagenes: [] });
      toast.success('Galería creada. Ahora sube sus fotos.');
    }
    galleryDrawer.value = false;
  } catch (error) {
    Object.assign(galleryErrors, error.errors || {});
    toast.error(error.message);
  } finally {
    savingGallery.value = false;
  }
};

const deleteGallery = async (gallery) => {
  const ok = await confirm({ title: `¿Eliminar la galería “${gallery.nombre}”?`, message: `Se borrarán sus ${gallery.imagenes?.length || 0} fotos.`, confirmLabel: 'Eliminar galería' });
  if (!ok) return;
  try {
    await api(`/admin/galleries/${gallery.id}`, { method: 'DELETE' });
    galleries.value = galleries.value.filter((g) => g.id !== gallery.id);
    toast.success('Galería eliminada');
  } catch (error) {
    toast.error(error.message);
  }
};

/* ── Fotos ── */
const uploading = reactive({});
const uploadImages = async (gallery, event) => {
  const files = Array.from(event.target.files || []);
  event.target.value = '';
  if (!files.length) return;
  const room = MAX_IMAGES - (gallery.imagenes?.length || 0);
  const valid = [];
  files.forEach((file) => {
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) toast.error(`“${file.name}” no es JPG, PNG o WebP.`);
    else if (file.size > MAX_BYTES) toast.error(`“${file.name}” pesa más de 20 MB.`);
    else valid.push(file);
  });
  if (valid.length > room) toast.error(`Sólo caben ${room} foto(s) más en esta galería (máx. ${MAX_IMAGES}).`);
  const batch = valid.slice(0, room);
  let done = 0;
  for (const file of batch) {
    uploading[gallery.id] = `${done + 1}/${batch.length}`;
    try {
      const data = new FormData();
      data.append('imagen', file);
      const result = await api(`/admin/galleries/${gallery.id}/images`, { method: 'POST', body: data });
      gallery.imagenes.push({ productos: [], ...result.data });
      done += 1;
    } catch (error) {
      toast.error(`${file.name}: ${error.message}`);
    }
  }
  delete uploading[gallery.id];
  if (done) toast.success(`${done} foto(s) subida(s). Toca una foto para vincular productos.`);
};

const deleteImage = async (gallery, imagen) => {
  const ok = await confirm({ title: '¿Eliminar esta foto?', message: 'También se quitan los productos vinculados a ella.', confirmLabel: 'Eliminar foto' });
  if (!ok) return;
  try {
    await api(`/admin/galleries/${gallery.id}/images/${imagen.id}`, { method: 'DELETE' });
    gallery.imagenes = gallery.imagenes.filter((i) => i.id !== imagen.id);
    if (editing.value?.id === imagen.id) editing.value = null;
    toast.success('Foto eliminada');
  } catch (error) {
    toast.error(error.message);
  }
};

/* ── Edición de una foto ── */
const editing = ref(null);
const savingImage = ref(false);
const productSearch = ref('');

const openImage = (gallery, imagen) => {
  productSearch.value = '';
  // Se edita el objeto real para que la cuadrícula refleje los cambios
  imagen.productos = imagen.productos || [];
  imagen.aspect_ratio = imagen.aspect_ratio || '1/1';
  editing.value = Object.assign(imagen, { gallery_id: gallery.id, galleryName: gallery.nombre });
};

const productMatches = computed(() => {
  const q = productSearch.value.trim().toLowerCase();
  if (!q) return [];
  const linked = new Set((editing.value?.productos || []).map((p) => p.producto_id));
  return props.productos
    .filter((p) => !linked.has(p.id))
    .filter((p) => (p.nombre || '').toLowerCase().includes(q) || (p.referencia || '').toLowerCase().includes(q))
    .slice(0, 30);
});

const saveImage = async () => {
  savingImage.value = true;
  try {
    await api(`/admin/galleries/${editing.value.gallery_id}/images/${editing.value.id}`, { method: 'PUT', body: { aspect_ratio: editing.value.aspect_ratio } });
    toast.success('Formato actualizado');
  } catch (error) {
    toast.error(error.message);
  } finally {
    savingImage.value = false;
  }
};

const addProduct = async (producto) => {
  try {
    const result = await api(`/admin/galleries/${editing.value.gallery_id}/images/${editing.value.id}/products`, { method: 'POST', body: { producto_id: producto.id } });
    const linked = result.producto || {};
    editing.value.productos.push({
      id: linked.id,
      producto_id: linked.producto_id ?? producto.id,
      referencia: linked.referencia ?? producto.referencia,
      nombre: linked.nombre ?? producto.nombre,
      precio: linked.precio ?? producto.precio,
    });
    productSearch.value = '';
    toast.success(`${producto.nombre || producto.referencia} vinculado`);
  } catch (error) {
    toast.error(error.message);
  }
};

const removeProduct = async (index) => {
  const prod = editing.value.productos[index];
  try {
    await api(`/admin/galleries/${editing.value.gallery_id}/images/${editing.value.id}/products/${prod.id}`, { method: 'DELETE' });
    editing.value.productos.splice(index, 1);
    toast.success('Producto desvinculado');
  } catch (error) {
    toast.error(error.message);
  }
};
</script>
