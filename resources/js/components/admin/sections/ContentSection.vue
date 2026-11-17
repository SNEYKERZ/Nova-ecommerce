<template>
  <div>
    <PageHeader title="Contenido del home" description="Carrusel principal, banners bajo el catálogo y la barra de promociones.">
      <a href="/" target="_blank" rel="noopener" class="a-btn a-btn-secondary">Ver home <AdminIcon name="external" size="sm" /></a>
    </PageHeader>

    <!-- Carrusel -->
    <section class="a-card a-card-pad" aria-labelledby="ct-carousel">
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h2 id="ct-carousel" class="a-subtitle">Carrusel principal</h2>
          <p class="mt-0.5 text-xs text-[var(--a-muted)]">Hasta {{ MAX_SLIDES }} imágenes horizontales (recomendado 1920×1080). Rotan cada 6 segundos.</p>
        </div>
        <label class="a-check">
          <button type="button" role="switch" class="a-switch" :aria-checked="carousel.activo ? 'true' : 'false'" aria-label="Mostrar carrusel en el home" @click="carousel.activo = !carousel.activo"></button>
          {{ carousel.activo ? 'Visible' : 'Oculto' }}
        </label>
      </div>

      <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <article v-for="(img, index) in carousel.imagenes" :key="img.id" class="overflow-hidden rounded-xl border border-[var(--a-border)]">
          <div class="relative">
            <img :src="img.imagen" alt="" class="aspect-video w-full object-cover" loading="lazy" />
            <span class="absolute left-2 top-2 rounded bg-black/70 px-1.5 py-0.5 text-[10px] font-semibold text-white">{{ index + 1 }}</span>
          </div>
          <div class="space-y-2 p-3">
            <FormField v-slot="f" label="Texto alternativo" help="Describe la imagen para lectores de pantalla.">
              <input :id="f.id" v-model.trim="img.nombre" class="a-input" maxlength="255" placeholder="Ej. Modelo con gorra negra" />
            </FormField>
            <FormField v-slot="f" label="Enlace al tocar" optional :error="linkError(img.url_destino)">
              <input :id="f.id" v-model.trim="img.url_destino" class="a-input" maxlength="2048" placeholder="/productos/12 o https://…" :aria-invalid="f.invalid" :aria-describedby="f.describedby" />
            </FormField>
            <button type="button" class="a-btn a-btn-plain-critical a-btn-sm" :disabled="deleting[img.id]" @click="deleteSlide(img)">
              <AdminIcon name="trash" size="sm" />{{ deleting[img.id] ? 'Eliminando…' : 'Eliminar imagen' }}
            </button>
          </div>
        </article>

        <article v-for="(img, index) in carousel.nuevas" :key="img.preview" class="overflow-hidden rounded-xl border border-dashed border-[var(--a-border-strong)]">
          <div class="relative">
            <img :src="img.preview" alt="" class="aspect-video w-full object-cover" />
            <span class="absolute left-2 top-2 rounded bg-[var(--tone-info)] px-1.5 py-0.5 text-[10px] font-semibold text-white">Sin guardar</span>
          </div>
          <div class="flex items-center justify-between gap-2 p-3 text-xs">
            <span class="truncate text-[var(--a-ink-2)]">{{ img.name }}</span>
            <button type="button" class="a-btn a-btn-plain a-btn-sm" @click="removeNew(index)">Quitar</button>
          </div>
        </article>

        <label v-if="slideCount < MAX_SLIDES" class="flex aspect-video cursor-pointer flex-col items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-[var(--a-border-strong)] text-sm font-medium text-[var(--a-ink-2)] hover:bg-[var(--a-surface-2)]">
          <AdminIcon name="upload" />
          Agregar imágenes
          <span class="text-xs text-[var(--a-muted)]">JPG, PNG o WebP · máx. 10 MB</span>
          <input type="file" class="visually-hidden" multiple accept="image/jpeg,image/png,image/webp" @change="onSlides" />
        </label>
      </div>
      <p v-if="carouselError" class="a-error">{{ carouselError }}</p>
    </section>

    <!-- Banners -->
    <section class="a-card a-card-pad mt-5" aria-labelledby="ct-banners">
      <h2 id="ct-banners" class="a-subtitle">Banners bajo el catálogo</h2>
      <p class="mt-0.5 text-xs text-[var(--a-muted)]">Dos imágenes lado a lado (recomendado 1600×900) que llevan a una colección o página.</p>
      <div class="mt-4 grid gap-4 lg:grid-cols-2">
        <article v-for="pos in [1, 2]" :key="pos" class="rounded-xl border border-[var(--a-border)] p-3">
          <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold">{{ pos === 1 ? 'Banner izquierdo' : 'Banner derecho' }}</h3>
            <label class="a-check text-xs">
              <button type="button" role="switch" class="a-switch" :aria-checked="banners[pos].activo ? 'true' : 'false'" :aria-label="`Mostrar ${pos === 1 ? 'banner izquierdo' : 'banner derecho'}`" @click="banners[pos].activo = !banners[pos].activo"></button>
              {{ banners[pos].activo ? 'Visible' : 'Oculto' }}
            </label>
          </div>
          <label class="mt-3 block cursor-pointer overflow-hidden rounded-lg border border-dashed border-[var(--a-border-strong)] hover:bg-[var(--a-surface-2)]">
            <img v-if="banners[pos].preview || banners[pos].imagen" :src="banners[pos].preview || banners[pos].imagen" alt="" class="aspect-video w-full object-cover" />
            <span v-else class="flex aspect-video flex-col items-center justify-center gap-1 text-sm text-[var(--a-ink-2)]"><AdminIcon name="upload" />Subir imagen</span>
            <span class="block border-t border-[var(--a-border)] px-3 py-2 text-center text-xs font-semibold text-[var(--a-ink-2)]">{{ banners[pos].imagen || banners[pos].preview ? 'Cambiar imagen' : 'Elegir archivo' }}</span>
            <input type="file" class="visually-hidden" accept="image/jpeg,image/png,image/webp" @change="onBanner(pos, $event)" />
          </label>
          <div class="mt-3 space-y-2">
            <FormField v-slot="f" label="Texto alternativo">
              <input :id="f.id" v-model.trim="banners[pos].nombre" class="a-input" maxlength="255" />
            </FormField>
            <FormField v-slot="f" label="Enlace" optional :error="linkError(banners[pos].url_destino)" help="Ruta como /galerias o una URL completa https://…">
              <input :id="f.id" v-model.trim="banners[pos].url_destino" class="a-input" maxlength="2048" placeholder="/galerias" :aria-invalid="f.invalid" :aria-describedby="f.describedby" />
            </FormField>
            <button v-if="banners[pos].id" type="button" class="a-btn a-btn-plain-critical a-btn-sm" @click="deleteBanner(pos)"><AdminIcon name="trash" size="sm" />Eliminar banner</button>
          </div>
        </article>
      </div>
    </section>

    <div class="sticky bottom-0 z-10 -mx-4 mt-4 flex justify-end gap-2 border-t border-[var(--a-border)] bg-[var(--a-bg)]/95 px-4 py-3 backdrop-blur sm:mx-0 sm:rounded-xl sm:border sm:bg-white">
      <span v-if="visualsDirty" class="mr-auto self-center text-xs font-medium text-[var(--tone-attention)]">Cambios sin guardar</span>
      <button type="button" class="a-btn a-btn-secondary" :disabled="savingVisuals || !visualsDirty" @click="resetVisuals">Descartar</button>
      <button type="button" class="a-btn a-btn-primary" :disabled="savingVisuals || !visualsDirty" @click="saveVisuals">
        <span v-if="savingVisuals" class="spinner" aria-hidden="true"></span>
        {{ savingVisuals ? 'Guardando…' : 'Guardar carrusel y banners' }}
      </button>
    </div>

    <!-- Promociones -->
    <section class="a-card a-card-pad mt-5" aria-labelledby="ct-news">
      <h2 id="ct-news" class="a-subtitle">Barra de promociones</h2>
      <p class="mt-0.5 text-xs text-[var(--a-muted)]">Mensajes cortos que aparecen bajo el carrusel. Escribe uno por línea, sin comas.</p>
      <div class="mt-3 space-y-2">
        <div v-for="(msg, index) in news" :key="index" class="flex items-center gap-2">
          <label :for="`news-${index}`" class="visually-hidden">Mensaje {{ index + 1 }}</label>
          <input :id="`news-${index}`" v-model="news[index]" class="a-input" maxlength="120" placeholder="Ej. Envío gratis desde $180.000" />
          <button type="button" class="a-btn a-btn-plain a-btn-icon" :aria-label="`Quitar mensaje ${index + 1}`" @click="news.splice(index, 1)"><AdminIcon name="trash" size="sm" /></button>
        </div>
        <button v-if="news.length < 6" type="button" class="a-btn a-btn-secondary a-btn-sm" @click="news.push('')"><AdminIcon name="plus" size="sm" />Agregar mensaje</button>
      </div>
      <p v-if="newsHasComma" class="a-error">Las comas separan mensajes: usa otro signo dentro de un mensaje (por ejemplo “y”).</p>
      <div class="mt-4 flex items-center justify-between gap-3 rounded-lg bg-[var(--a-topbar)] px-3 py-2 text-[11px] font-bold uppercase tracking-[0.14em] text-white">
        <span class="truncate">{{ cleanNews.join(' • ') || 'Vista previa de la barra' }}</span>
      </div>
      <div class="mt-4 flex justify-end">
        <button type="button" class="a-btn a-btn-primary" :disabled="savingNews || newsHasComma || !newsDirty" @click="saveNews">
          <span v-if="savingNews" class="spinner" aria-hidden="true"></span>
          {{ savingNews ? 'Guardando…' : 'Guardar promociones' }}
        </button>
      </div>
    </section>
  </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue';
import AdminIcon from '../AdminIcon.vue';
import FormField from '../FormField.vue';
import PageHeader from '../PageHeader.vue';
import { LINK_PATTERN } from '../adminNav';
import { useConfirm } from '../../../composables/useConfirm';
import { useToast } from '../../../composables/useToast';
import { api } from '../../../utils/http';

const props = defineProps({
  bloques: { type: Object, default: () => ({}) },
  catalogBanners: { type: Object, default: () => ({}) },
  noticia: { type: String, default: '' },
});

const toast = useToast();
const { confirm } = useConfirm();
const MAX_SLIDES = 4;
const MAX_BYTES = 10 * 1024 * 1024;
const ACCEPTED = ['image/jpeg', 'image/png', 'image/webp'];

const linkError = (value) => (value && !LINK_PATTERN.test(value) ? 'Usa una ruta que empiece por / o una URL https:// completa.' : '');

/* ── Carrusel + banners (un solo guardado: /admin/visual-assets) ── */
const carousel = reactive({ id: null, activo: true, imagenes: [], nuevas: [] });
const banners = reactive({ 1: {}, 2: {} });
const carouselError = ref('');
const deleting = reactive({});
const savingVisuals = ref(false);
let visualsInitial = '';

const bannerForm = (pos, data) => ({
  id: data?.id || null,
  nombre: data?.nombre || (pos === 1 ? 'Banner izquierdo' : 'Banner derecho'),
  imagen: data?.imagen || null,
  url_destino: data?.url_destino || '',
  activo: data?.activo ?? true,
  file: null,
  preview: null,
});

const visualsSnapshot = () => JSON.stringify({
  activo: carousel.activo,
  imagenes: carousel.imagenes.map((i) => [i.id, i.nombre, i.url_destino]),
  nuevas: carousel.nuevas.length,
  banners: [1, 2].map((p) => [banners[p].nombre, banners[p].url_destino, banners[p].activo, Boolean(banners[p].file)]),
});

const revoke = () => {
  carousel.nuevas.forEach((n) => URL.revokeObjectURL(n.preview));
  [1, 2].forEach((p) => banners[p].preview && URL.revokeObjectURL(banners[p].preview));
};

const resetVisuals = () => {
  revoke();
  const bloque = props.bloques?.[1] || props.bloques?.['1'] || {};
  carousel.id = bloque.id || null;
  carousel.activo = bloque.activo ?? true;
  carousel.imagenes = (bloque.imagenes || []).map((img) => ({ ...img, nombre: img.nombre || '', url_destino: img.url_destino || '' }));
  carousel.nuevas = [];
  carouselError.value = '';
  banners[1] = bannerForm(1, props.catalogBanners?.[1] || props.catalogBanners?.['1']);
  banners[2] = bannerForm(2, props.catalogBanners?.[2] || props.catalogBanners?.['2']);
  visualsInitial = visualsSnapshot();
};
resetVisuals();
watch(() => [props.bloques, props.catalogBanners], resetVisuals);

const visualsDirty = computed(() => visualsSnapshot() !== visualsInitial);
const slideCount = computed(() => carousel.imagenes.length + carousel.nuevas.length);

const checkFile = (file) => {
  if (!ACCEPTED.includes(file.type)) return `“${file.name}” no es JPG, PNG o WebP.`;
  if (file.size > MAX_BYTES) return `“${file.name}” pesa más de 10 MB.`;
  return '';
};

const onSlides = (event) => {
  carouselError.value = '';
  const files = Array.from(event.target.files || []);
  event.target.value = '';
  const room = MAX_SLIDES - slideCount.value;
  files.forEach((file, i) => {
    const problem = checkFile(file);
    if (problem) carouselError.value = problem;
    else if (i >= room) carouselError.value = `El carrusel admite máximo ${MAX_SLIDES} imágenes.`;
    else carousel.nuevas.push({ file, name: file.name, preview: URL.createObjectURL(file) });
  });
};
const removeNew = (index) => {
  const [img] = carousel.nuevas.splice(index, 1);
  if (img) URL.revokeObjectURL(img.preview);
};

const onBanner = (pos, event) => {
  const file = event.target.files?.[0];
  event.target.value = '';
  if (!file) return;
  const problem = checkFile(file);
  if (problem) {
    toast.error(problem);
    return;
  }
  if (banners[pos].preview) URL.revokeObjectURL(banners[pos].preview);
  banners[pos].file = file;
  banners[pos].preview = URL.createObjectURL(file);
};

const deleteSlide = async (img) => {
  const ok = await confirm({ title: '¿Eliminar esta imagen del carrusel?', message: 'Se borra de inmediato del home.', confirmLabel: 'Eliminar imagen' });
  if (!ok) return;
  if (!carousel.id) return;
  deleting[img.id] = true;
  try {
    const payload = await api(`/admin/bloques/${carousel.id}/imagenes/${img.id}`, { method: 'DELETE' });
    carousel.imagenes = carousel.imagenes.filter((i) => i.id !== img.id);
    visualsInitial = visualsSnapshot();
    toast.success(payload.message || 'Imagen eliminada');
    router.reload({ only: ['bloques'] });
  } catch (error) {
    toast.error(error.message);
  } finally {
    deleting[img.id] = false;
  }
};

const deleteBanner = async (pos) => {
  const ok = await confirm({ title: `¿Eliminar el ${pos === 1 ? 'banner izquierdo' : 'banner derecho'}?`, confirmLabel: 'Eliminar banner' });
  if (!ok) return;
  try {
    const payload = await api(`/admin/catalog-banners/${pos}`, { method: 'DELETE' });
    toast.success(payload.message || 'Banner eliminado');
    router.reload({ only: ['catalogBanners'] });
  } catch (error) {
    toast.error(error.message);
  }
};

const saveVisuals = async () => {
  const badLink = carousel.imagenes.some((i) => linkError(i.url_destino)) || [1, 2].some((p) => linkError(banners[p].url_destino));
  if (badLink) {
    toast.error('Revisa los enlaces marcados.');
    return;
  }
  const data = new FormData();
  data.append('carousel_active', carousel.activo ? '1' : '0');
  carousel.imagenes.forEach((img) => {
    data.append(`carousel_existing_names[${img.id}]`, img.nombre || '');
    data.append(`carousel_existing_links[${img.id}]`, img.url_destino || '');
  });
  carousel.nuevas.forEach((n) => data.append('carousel_images[]', n.file));
  [[1, 'left'], [2, 'right']].forEach(([pos, side]) => {
    data.append(`${side}_nombre`, banners[pos].nombre || '');
    data.append(`${side}_url_destino`, banners[pos].url_destino || '');
    data.append(`${side}_activo`, banners[pos].activo ? '1' : '0');
    if (banners[pos].file) data.append(`${side}_imagen`, banners[pos].file);
  });

  savingVisuals.value = true;
  try {
    const payload = await api('/admin/visual-assets', { method: 'POST', body: data });
    toast.success(payload.message || 'Contenido guardado');
    router.reload({ only: ['bloques', 'catalogBanners'] });
  } catch (error) {
    toast.error(error.message);
  } finally {
    savingVisuals.value = false;
  }
};

/* ── Promociones (texto separado por comas en el backend) ── */
const splitNews = (text) => String(text || '').split(',').map((s) => s.trim()).filter(Boolean);
const news = ref(splitNews(props.noticia));
let newsInitial = JSON.stringify(news.value);
watch(() => props.noticia, (value) => { news.value = splitNews(value); newsInitial = JSON.stringify(news.value); });
const cleanNews = computed(() => news.value.map((s) => s.trim()).filter(Boolean));
const newsHasComma = computed(() => news.value.some((s) => s.includes(',')));
const newsDirty = computed(() => JSON.stringify(cleanNews.value) !== newsInitial);
const savingNews = ref(false);

const saveNews = async () => {
  savingNews.value = true;
  try {
    const payload = await api('/admin/noticias', { method: 'PUT', body: { campos_adicionales: cleanNews.value.join(', ') } });
    toast.success(payload.message || 'Promociones guardadas');
    newsInitial = JSON.stringify(cleanNews.value);
    router.reload({ only: ['noticia'] });
  } catch (error) {
    toast.error(error.message);
  } finally {
    savingNews.value = false;
  }
};

onBeforeUnmount(revoke);
</script>
