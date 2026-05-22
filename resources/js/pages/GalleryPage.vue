<template>
  <AppLayout>
    <Head title="Galerías" />
    <section class="mx-auto w-full max-w-7xl px-4 py-12 sm:px-6 lg:px-10">
      <!-- Header -->
      <div class="mb-12 text-center">
        <h1 class="text-4xl font-bold tracking-tight text-slate-900">Galerías</h1>
        <p class="mt-4 text-lg text-slate-600">Descubre nuestras últimas colecciones</p>
      </div>

      <!-- Galleries -->
      <div class="space-y-16">
        <div v-for="gallery in galleries" :key="gallery.id">
          <h2 class="mb-6 text-2xl font-semibold text-slate-900">{{ gallery.nombre }}</h2>
          <p v-if="gallery.descripcion" class="mb-8 text-slate-600">{{ gallery.descripcion }}</p>

          <!-- Masonry Grid -->
          <div class="columns-1 gap-6 sm:columns-2 lg:columns-4">
            <div
              v-for="imagen in gallery.imagenes"
              :key="imagen.id"
              class="mb-6 break-inside-avoid overflow-hidden rounded-lg border border-slate-200 bg-slate-100 shadow-sm cursor-pointer transition-transform hover:scale-105"
              :style="{ aspectRatio: imagen.aspect_ratio }"
              @click="openModal(gallery, imagen)"
            >
              <img :src="imagen.imagen_url" :alt="gallery.nombre" class="h-full w-full object-cover" loading="lazy" />
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="galleries.length === 0" class="rounded-lg border border-slate-200 bg-slate-50 py-12 text-center">
        <p class="text-slate-600">No hay galerías disponibles en este momento.</p>
      </div>
    </section>

    <!-- Gallery Modal -->
    <GalleryModal
      v-if="selectedGallery && selectedImage"
      :gallery="selectedGallery"
      :imagen="selectedImage"
      @close="selectedImage = null"
    />
  </AppLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';
import GalleryModal from '../components/GalleryModal.vue';

const props = defineProps({
  galleries: { type: Array, default: () => [] },
});

const selectedGallery = ref(null);
const selectedImage = ref(null);

const openModal = (gallery, imagen) => {
  selectedGallery.value = gallery;
  selectedImage.value = imagen;
};
</script>
