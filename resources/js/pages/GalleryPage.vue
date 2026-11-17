<template>
  <AppLayout>
    <Head :title="labels.galeria" />

    <section class="mx-auto w-full max-w-[110rem] px-4 pt-10 sm:px-6 sm:pt-14 lg:px-10">
      <h1 class="sf-display sf-masthead">{{ labels.galeria }}</h1>
      <div class="mt-6 flex flex-col gap-4 border-t border-[var(--sf-rule)] pt-5 md:flex-row md:items-start md:justify-between">
        <p class="max-w-xl text-lg leading-relaxed text-[var(--sf-ink-2)]">Toca una foto para ver los productos que aparecen en ella y agregarlos al carrito.</p>
        <p v-if="galleries.length" class="sf-num shrink-0 pt-1 text-sm text-[var(--sf-mute)]">{{ totalPhotos }} {{ totalPhotos === 1 ? 'foto' : 'fotos' }} · {{ galleries.length }} {{ galleries.length === 1 ? 'galería' : 'galerías' }}</p>
      </div>

      <!-- Índice de galerías -->
      <nav v-if="galleries.length > 1" class="sf-rail -mx-4 mt-8 px-4 sm:-mx-6 sm:px-6 lg:-mx-10 lg:px-10" aria-label="Galerías">
        <div class="sf-cats">
          <a
            v-for="gallery in galleries"
            :key="gallery.id"
            :href="`#gal-${gallery.id}`"
            class="sf-cat"
            :aria-current="activeId === gallery.id ? 'true' : undefined"
            @click="activeId = gallery.id"
          >
            {{ gallery.nombre }}<sup>{{ gallery.imagenes.length }}</sup>
            <PencilMark :active="activeId === gallery.id" />
          </a>
        </div>
      </nav>

      <div v-if="galleries.length" class="mt-12 space-y-24">
        <section
          v-for="gallery in galleries"
          :id="`gal-${gallery.id}`"
          :key="gallery.id"
          :ref="(el) => registerSection(el, gallery.id)"
          class="scroll-mt-36"
          :aria-labelledby="`gal-title-${gallery.id}`"
        >
          <div class="mb-6 grid gap-3 md:grid-cols-12 md:items-end">
            <h2 :id="`gal-title-${gallery.id}`" class="sf-display text-5xl sm:text-6xl md:col-span-7">{{ gallery.nombre }}</h2>
            <div class="md:col-span-5 md:text-right">
              <p v-if="gallery.descripcion" class="text-base leading-relaxed text-[var(--sf-ink-2)] md:ml-auto md:max-w-md">{{ gallery.descripcion }}</p>
              <p class="sf-num mt-1 text-sm text-[var(--sf-mute)]">{{ gallery.imagenes.length }} {{ gallery.imagenes.length === 1 ? 'foto' : 'fotos' }} · {{ taggedCount(gallery) }} con productos</p>
            </div>
          </div>

          <MosaicGallery :imagenes="gallery.imagenes" :title="gallery.nombre" @open="(imagen) => openModal(gallery, imagen)" />
        </section>
      </div>

      <div v-else class="mt-12 border border-dashed border-[var(--sf-rule-strong)] px-6 py-16 text-center">
        <p class="sf-display text-4xl">Pronto habrá nuevas fotos</p>
        <p class="mx-auto mt-3 max-w-md text-base text-[var(--sf-mute)]">Mientras tanto, mira todo lo disponible en {{ labels.catalogo.toLowerCase() === 'menú' ? 'el menú' : 'el catálogo' }}.</p>
        <Link href="/" class="sf-btn mt-7">Ir {{ labels.catalogo === 'Menú' ? 'al menú' : labels.catalogo === 'Repuestos' ? 'a repuestos' : 'al catálogo' }}</Link>
      </div>
    </section>

    <GalleryModal v-if="selectedGallery && selectedImage" :gallery="selectedGallery" :imagen="selectedImage" @close="closeModal" @navigate="navigate" />
  </AppLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';
import GalleryModal from '../components/GalleryModal.vue';
import MosaicGallery from '../components/store/MosaicGallery.vue';
import PencilMark from '../components/store/PencilMark.vue';
import { useRubro } from '../composables/useRubro';

const props = defineProps({
  galleries: { type: Array, default: () => [] },
});

const rubro = useRubro();
const labels = computed(() => rubro.preset.value);
const totalPhotos = computed(() => props.galleries.reduce((n, g) => n + (g.imagenes?.length || 0), 0));
const taggedCount = (gallery) => gallery.imagenes.filter((i) => i.productos?.length).length;

const selectedGallery = ref(null);
const selectedImage = ref(null);

const openModal = (gallery, imagen) => {
  selectedGallery.value = gallery;
  selectedImage.value = imagen;
};
const closeModal = () => {
  selectedImage.value = null;
  selectedGallery.value = null;
};
const navigate = (delta) => {
  const list = selectedGallery.value?.imagenes || [];
  const index = list.findIndex((i) => i.id === selectedImage.value?.id);
  if (index < 0 || !list.length) return;
  selectedImage.value = list[(index + delta + list.length) % list.length];
};

/* Marca en el índice la galería que está en pantalla */
const activeId = ref(props.galleries[0]?.id ?? null);
const sections = new Map();
let observer = null;
const registerSection = (el, id) => {
  if (el) sections.set(el, id);
};
onMounted(() => {
  if (typeof IntersectionObserver === 'undefined' || props.galleries.length < 2) return;
  observer = new IntersectionObserver((entries) => {
    const visible = entries.filter((e) => e.isIntersecting).sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);
    if (visible[0]) activeId.value = sections.get(visible[0].target);
  }, { rootMargin: '-30% 0px -55% 0px' });
  sections.forEach((_, el) => observer.observe(el));
});
onBeforeUnmount(() => observer?.disconnect());
</script>
