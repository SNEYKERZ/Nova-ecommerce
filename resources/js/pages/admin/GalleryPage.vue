<template>
  <AppLayout>
    <Head title="Galerías - Admin" />
    <section class="mx-auto w-full max-w-6xl px-4 py-8 sm:px-6 lg:px-10">
      <!-- Header -->
      <div class="mb-6">
        <h1 class="text-3xl font-semibold tracking-tight text-slate-900">Gestión de Galerías</h1>
        <p class="mt-2 text-sm text-slate-600">Crea y organiza galerías de productos con imágenes masonry.</p>
      </div>

      <!-- Create Gallery Form -->
      <div v-if="!showGalleryForm" class="mb-6">
        <button class="rounded-lg border border-indigo-600 bg-indigo-600 px-4 py-2 text-sm font-semibold text-white cursor-pointer" @click="showGalleryForm = true">
          + Nueva Galería
        </button>
      </div>

      <div v-if="showGalleryForm" class="mb-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">{{ editingGallery ? 'Editar Galería' : 'Crear Nueva Galería' }}</h2>
        <div class="mt-4 grid gap-3 md:grid-cols-2">
          <div>
            <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Nombre</label>
            <input v-model="galleryForm.nombre" placeholder="ej: Verano 2026" class="w-full rounded-lg border border-slate-300 px-3 py-2.5" />
          </div>
          <div>
            <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Descripción</label>
            <input v-model="galleryForm.descripcion" placeholder="Descripción opcional" class="w-full rounded-lg border border-slate-300 px-3 py-2.5" />
          </div>
        </div>
        <div v-if="editingGallery" class="mt-3 flex items-center gap-2">
          <label class="text-sm font-semibold text-slate-700">
            <input v-model="galleryForm.activo" type="checkbox" class="h-4 w-4" />
            Activa
          </label>
        </div>
        <div class="mt-4 flex gap-2">
          <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white cursor-pointer" @click="saveGallery">
            {{ editingGallery ? 'Actualizar' : 'Crear' }}
          </button>
          <button class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 cursor-pointer" @click="cancelGalleryForm">
            Cancelar
          </button>
        </div>
      </div>

      <!-- Galleries List -->
      <div class="space-y-4">
        <div v-for="gallery in galleries" :key="gallery.id" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <h3 class="text-lg font-semibold text-slate-900">{{ gallery.nombre }}</h3>
              <p v-if="gallery.descripcion" class="mt-1 text-sm text-slate-600">{{ gallery.descripcion }}</p>
              <div class="mt-2 flex items-center gap-3">
                <span class="text-xs font-semibold" :class="gallery.activo ? 'text-emerald-600' : 'text-red-600'">
                  {{ gallery.activo ? '● Activa' : '● Inactiva' }}
                </span>
                <span class="text-xs text-slate-500">{{ gallery.imagenes?.length || 0 }} / 8 imágenes</span>
              </div>
            </div>
            <div class="flex gap-2">
              <button class="rounded-md border border-indigo-300 px-3 py-1.5 text-sm text-indigo-600 cursor-pointer" @click="editGallery(gallery)">
                Editar
              </button>
              <button class="rounded-md border border-red-300 px-3 py-1.5 text-sm text-red-600 cursor-pointer" @click="deleteGallery(gallery.id)">
                Eliminar
              </button>
            </div>
          </div>

          <!-- Gallery Images -->
          <div class="mt-4 border-t border-slate-200 pt-4">
            <h4 class="mb-3 text-sm font-semibold text-slate-700">Imágenes</h4>

            <!-- Image Grid -->
            <div v-if="gallery.imagenes?.length > 0" class="mb-4 grid gap-3 sm:grid-cols-2 md:grid-cols-4">
              <div v-for="imagen in gallery.imagenes" :key="imagen.id" class="overflow-hidden rounded-lg border border-slate-200 bg-slate-100">
                <img :src="imagen.imagen_url" :alt="'Imagen ' + imagen.id" class="h-32 w-full object-cover" loading="lazy" />
                <div class="flex items-center justify-between border-t border-slate-200 bg-white px-2 py-1.5">
                  <span class="text-xs text-slate-500">{{ imagen.productos?.length || 0 }} prod.</span>
                  <div class="flex gap-1">
                    <button class="rounded bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700 cursor-pointer hover:bg-indigo-100" @click="editImage(gallery, imagen)">
                      {{ editingImage?.id === imagen.id ? 'Cerrar' : 'Productos' }}
                    </button>
                    <button class="rounded bg-red-50 px-2 py-0.5 text-xs font-medium text-red-600 cursor-pointer hover:bg-red-100" @click="deleteImage(gallery.id, imagen.id)">
                      ✕
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Add Image with Preview (multiselección) -->
            <div v-if="!gallery.imagenes || gallery.imagenes.length < 8" class="flex flex-wrap items-end gap-3">
              <div class="flex flex-col gap-1">
                <label class="text-xs font-semibold uppercase text-slate-500">Nuevas imágenes</label>
                <input
                  type="file"
                  multiple
                  @change="(e) => prepareImageUpload(gallery, e)"
                  accept="image/jpeg,image/png,image.webp"
                  class="block w-72 text-sm text-slate-600 file:mr-2 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100"
                />
              </div>

              <!-- File Queue Previews -->
              <div v-if="pendingFiles.length > 0 && pendingGalleryId === gallery.id" class="flex flex-wrap items-end gap-3">
                <div v-for="(pf, idx) in pendingFiles" :key="idx" class="relative">
                  <img :src="pf.preview" class="h-14 w-14 rounded border border-slate-200 object-cover" />
                  <span class="absolute -right-1.5 -top-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-xs text-white">{{ pf.uploaded ? '✓' : idx + 1 }}</span>
                </div>
                <div class="flex flex-col gap-1">
                  <button
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white cursor-pointer disabled:opacity-40"
                    :disabled="uploading || pendingFiles.length === 0"
                    @click="uploadNextImage(gallery)"
                  >
                    {{ uploading ? `Subiendo ${uploadProgress}/${pendingFiles.length}...` : `Subir ${pendingFiles.length} imagen(es)` }}
                  </button>
                  <button class="text-xs text-slate-500 cursor-pointer hover:text-red-600" @click="clearPendingFiles">
                    Cancelar todo
                  </button>
                </div>
              </div>
              <p v-else-if="pendingGalleryId !== gallery.id" class="text-xs text-slate-400">Seleccioná una o más imágenes para subir</p>
            </div>
            <p v-else class="text-sm font-medium text-amber-600">Límite de 8 imágenes alcanzado</p>
          </div>

          <!-- Image Editor (expand/collapse individual) -->
          <div v-if="editingImage && editingImage.gallery_id === gallery.id" class="mt-4 rounded-lg border border-indigo-200 bg-indigo-50 p-4">
            <div class="mb-3 flex items-start justify-between">
              <div class="flex items-center gap-3">
                <img :src="editingImage.imagen_url" class="h-16 w-16 rounded border border-white object-cover shadow-sm" />
                <div>
                  <h5 class="text-sm font-semibold text-slate-900">Editando imagen #{{ editingImage.id }}</h5>
                  <p class="text-xs text-slate-500">{{ editingImage.productos?.length || 0 }} productos asociados</p>
                </div>
              </div>
              <button class="rounded-lg border border-slate-300 bg-white px-2 py-1 text-xs text-slate-700 cursor-pointer" @click="editingImage = null">Cerrar ✕</button>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
              <!-- Aspect Ratio -->
              <div>
                <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Aspect Ratio</label>
                <select v-model="editingImage.aspect_ratio" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm">
                  <option value="1/1">Cuadrada (1:1)</option>
                  <option value="2/3">Vertical (2:3)</option>
                  <option value="3/2">Horizontal (3:2)</option>
                </select>
              </div>

              <!-- Save -->
              <div class="flex items-end gap-2">
                <button class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white cursor-pointer" @click="saveImage">
                  Guardar Cambios
                </button>
              </div>
            </div>

            <!-- Product Association -->
            <div class="mt-4 border-t border-indigo-200 pt-4">
              <h6 class="mb-3 text-xs font-semibold uppercase text-slate-500">Productos Asociados</h6>

              <!-- Current Products -->
              <div v-if="editingImage.productos?.length > 0" class="mb-3 space-y-2">
                <div v-for="(prod, idx) in editingImage.productos" :key="prod.id" class="flex items-center justify-between rounded-lg border border-slate-200 bg-white px-3 py-2">
                  <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-slate-900">{{ prod.nombre }}</span>
                    <span class="text-xs text-slate-400">${{ formatPrice(prod.precio) }}</span>
                  </div>
                  <button class="rounded-md bg-red-50 px-2 py-1 text-xs font-semibold text-red-600 cursor-pointer hover:bg-red-100" @click="removeProduct(idx)">
                    ✕ Quitar
                  </button>
                </div>
              </div>
              <p v-else class="mb-3 text-sm text-slate-500">Sin productos asociados todavía.</p>

              <!-- Searchable Product Selector -->
              <div>
                <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Agregar Producto</label>
                <div class="relative">
                  <input
                    v-model="productSearch"
                    type="text"
                    placeholder="Buscá por nombre o referencia..."
                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 pr-10 text-sm"
                    @input="filterProducts"
                  />
                  <span v-if="productSearch" class="absolute right-3 top-2.5 text-xs text-slate-400 cursor-pointer" @click="productSearch = ''; filteredProducts = allProducts">✕</span>
                </div>
                <div v-if="productSearch && filteredProducts.length > 0" class="mt-1 max-h-40 overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-sm">
                  <button
                    v-for="prod in filteredProducts"
                    :key="prod.id"
                    class="flex w-full items-center justify-between px-3 py-2 text-left text-sm hover:bg-indigo-50 cursor-pointer border-b border-slate-100 last:border-0"
                    @click="addProduct(prod)"
                  >
                    <span class="font-medium text-slate-900">{{ prod.nombre }}</span>
                    <span class="text-xs text-slate-400">{{ prod.referencia }} — ${{ formatPrice(prod.precio) }}</span>
                  </button>
                </div>
                <div v-if="productSearch && filteredProducts.length === 0" class="mt-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-500">
                  Sin resultados para "{{ productSearch }}"
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Feedback -->
      <p v-if="feedback" class="mt-4 rounded-lg border px-4 py-2 text-sm font-semibold" :class="feedbackType === 'error' ? 'border-red-200 bg-red-50 text-red-800' : 'border-emerald-200 bg-emerald-50 text-emerald-800'">{{ feedback }}</p>
    </section>
  </AppLayout>
</template>

<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '../../layouts/AppLayout.vue';

const props = defineProps({
  galleries: { type: Array, default: () => [] },
  productos: { type: Array, default: () => [] },
});

const page = usePage();

const galleryForm = ref({ nombre: '', descripcion: '', activo: true });
const editingGallery = ref(null);
const editingImage = ref(null);
const pendingFiles = ref([]);        // { file, preview, uploaded }
const pendingGalleryId = ref(null);
const uploading = ref(false);
const uploadProgress = ref(0);       // cuántas van subidas del lote
const showGalleryForm = ref(false);
const feedback = ref('');
const feedbackType = ref('success');
const galleries = ref(props.galleries);
const allProducts = ref(props.productos);
const filteredProducts = ref(props.productos);
const productSearch = ref('');

// CSRF token desde Inertia shared props (Siempre actualizado post-login)
const csrf = () => page.props.csrf_token || '';

const formatPrice = (price) => {
  return parseFloat(price).toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const notify = (message, type = 'success') => {
  feedback.value = message;
  feedbackType.value = type;
  setTimeout(() => { feedback.value = ''; }, 4000);
};

const requestJson = async (url, method, body) => {
  const res = await fetch(url, {
    method,
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrf(),
      'X-Requested-With': 'XMLHttpRequest',
      'Accept': 'application/json',
    },
    body: body ? JSON.stringify(body) : null,
  });
  const data = await res.json();
  if (!res.ok) throw new Error(data.message || 'Error del servidor');
  return data;
};

const saveGallery = async () => {
  try {
    if (editingGallery.value) {
      await requestJson(`/admin/galleries/${editingGallery.value.id}`, 'PUT', galleryForm.value);
      Object.assign(editingGallery.value, galleryForm.value);
    } else {
      const result = await requestJson('/admin/galleries', 'POST', galleryForm.value);
      galleries.value.push(result.data);
    }
    cancelGalleryForm();
    notify('Galería guardada correctamente');
  } catch (e) {
    notify(e.message, 'error');
  }
};

const editGallery = (gallery) => {
  editingGallery.value = gallery;
  galleryForm.value = { nombre: gallery.nombre, descripcion: gallery.descripcion, activo: gallery.activo };
  showGalleryForm.value = true;
};

const cancelGalleryForm = () => {
  showGalleryForm.value = false;
  editingGallery.value = null;
  galleryForm.value = { nombre: '', descripcion: '', activo: true };
};

const deleteGallery = async (id) => {
  if (!confirm('¿Eliminar esta galería y todas sus imágenes?')) return;
  try {
    await requestJson(`/admin/galleries/${id}`, 'DELETE', {});
    galleries.value = galleries.value.filter(g => g.id !== id);
    notify('Galería eliminada');
  } catch (e) {
    notify(e.message, 'error');
  }
};

const MAX_IMAGE_SIZE = 20 * 1024 * 1024; // 20MB
const MAX_FILES_PER_BATCH = 8;

const prepareImageUpload = (gallery, event) => {
  const files = Array.from(event.target.files);
  if (files.length === 0) return;

  pendingGalleryId.value = gallery.id;

  // Calcular cuántas se pueden agregar (respetando límite de 8)
  const currentCount = gallery.imagenes?.length || 0;
  const available = MAX_FILES_PER_BATCH - currentCount;
  const toAdd = files.slice(0, available);

  if (toAdd.length < files.length) {
    notify(`Solo podés subir ${available} imagen(es) más (máx 8 por galería)`, 'error');
  }

  // Crear previews con FileReader
  let loaded = 0;
  toAdd.forEach((file) => {
    if (file.size > MAX_IMAGE_SIZE) {
      notify(`"${file.name}" excede los 20MB, se saltea`, 'error');
      return;
    }
    const reader = new FileReader();
    reader.onload = (e) => {
      pendingFiles.value.push({ file, preview: e.target.result, uploaded: false });
    };
    reader.readAsDataURL(file);
  });
};

const clearPendingFiles = () => {
  pendingFiles.value = [];
  pendingGalleryId.value = null;
  uploadProgress.value = 0;
};

const uploadSingleFile = async (gallery, fileEntry) => {
  const formData = new FormData();
  formData.append('imagen', fileEntry.file);

  const res = await fetch(`/admin/galleries/${gallery.id}/images`, {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': csrf(),
      'X-Requested-With': 'XMLHttpRequest',
      'Accept': 'application/json',
    },
    body: formData,
  });

  const responseText = await res.text();
  let data;
  try {
    data = JSON.parse(responseText);
  } catch {
    throw new Error(`El servidor respondió con HTML en vez de JSON (status ${res.status}). Probablemente la sesión expiró.`);
  }

  if (!res.ok) throw new Error(data.message || `Error HTTP ${res.status}`);

  if (!gallery.imagenes) gallery.imagenes = [];
  gallery.imagenes.push(data.data);
  fileEntry.uploaded = true;

  return data.data;
};

const uploadNextImage = async (gallery) => {
  const toUpload = pendingFiles.value.filter(f => !f.uploaded);
  if (toUpload.length === 0) {
    notify('No hay imágenes pendientes', 'error');
    return;
  }

  uploading.value = true;
  let lastUploadedData = null;

  try {
    for (const fileEntry of toUpload) {
      uploadProgress.value = pendingFiles.value.filter(f => f.uploaded).length + 1;

      if (fileEntry.file.size > MAX_IMAGE_SIZE) {
        notify(`"${fileEntry.file.name}" excede los 20MB, se saltea`, 'error');
        fileEntry.uploaded = true; // marca como "procesada" aunque falle
        continue;
      }

      lastUploadedData = await uploadSingleFile(gallery, fileEntry);
    }

    // Auto-abrir el editor para la última imagen subida
    if (lastUploadedData) {
      editingImage.value = { ...lastUploadedData, gallery_id: gallery.id };
      productSearch.value = '';
      filteredProducts.value = allProducts.value;
    }

    // Limpiar si ya se subieron todas
    const remaining = pendingFiles.value.filter(f => !f.uploaded);
    if (remaining.length === 0) {
      notify(`${toUpload.length} imagen(es) subida(s) correctamente`);
      clearPendingFiles();
    } else {
      notify(`${toUpload.length - remaining.length} subida(s), ${remaining.length} pendiente(s)`);
    }
  } catch (e) {
    notify(e.message, 'error');
  } finally {
    uploading.value = false;
  }
};

const editImage = (gallery, imagen) => {
  if (editingImage.value?.id === imagen.id) {
    editingImage.value = null;
  } else {
    editingImage.value = { ...imagen, gallery_id: gallery.id };
    productSearch.value = '';
    filteredProducts.value = allProducts.value;
  }
};

const deleteImage = async (galleryId, imagenId) => {
  if (!confirm('¿Eliminar esta imagen?')) return;
  try {
    await requestJson(`/admin/galleries/${galleryId}/images/${imagenId}`, 'DELETE', {});
    const gallery = galleries.value.find(g => g.id === galleryId);
    if (gallery) {
      gallery.imagenes = gallery.imagenes.filter(i => i.id !== imagenId);
    }
    if (editingImage.value?.id === imagenId) editingImage.value = null;
    notify('Imagen eliminada');
  } catch (e) {
    notify(e.message, 'error');
  }
};

const filterProducts = () => {
  const q = productSearch.value.toLowerCase().trim();
  if (!q) {
    filteredProducts.value = allProducts.value;
    return;
  }
  filteredProducts.value = allProducts.value.filter(p =>
    p.nombre.toLowerCase().includes(q) ||
    (p.referencia && p.referencia.toLowerCase().includes(q))
  );
};

const addProduct = async (producto) => {
  try {
    const result = await requestJson(
      `/admin/galleries/${editingImage.value.gallery_id}/images/${editingImage.value.id}/products`,
      'POST',
      { producto_id: producto.id }
    );

    if (result.producto) {
      if (!editingImage.value.productos) editingImage.value.productos = [];
      editingImage.value.productos.push({
        id: result.producto.id,
        producto_id: result.producto.producto_id,
        referencia: result.producto.referencia,
        nombre: result.producto.nombre,
        precio: result.producto.precio,
      });
    }

    productSearch.value = '';
    filteredProducts.value = allProducts.value;
    notify(`"${producto.nombre}" asociado a la imagen`);
  } catch (e) {
    notify(e.message, 'error');
  }
};

const removeProduct = async (idx) => {
  const prod = editingImage.value.productos[idx];
  try {
    await requestJson(
      `/admin/galleries/${editingImage.value.gallery_id}/images/${editingImage.value.id}/products/${prod.id}`,
      'DELETE',
      {}
    );
    editingImage.value.productos.splice(idx, 1);
    notify('Producto removido');
  } catch (e) {
    notify(e.message, 'error');
  }
};

const saveImage = async () => {
  try {
    const result = await requestJson(
      `/admin/galleries/${editingImage.value.gallery_id}/images/${editingImage.value.id}`,
      'PUT',
      { aspect_ratio: editingImage.value.aspect_ratio }
    );
    notify('Imagen actualizada correctamente');
  } catch (e) {
    notify(e.message, 'error');
  }
};
</script>
