<template>
  <AppLayout>
    <Head title="Galerías" />

    <!-- Header Section (contained) -->
    <section class="mx-auto w-full max-w-7xl px-4 py-12 sm:px-6 lg:px-10">
      <div class="mb-12 text-center">
        <h1 class="text-4xl font-bold tracking-tight text-slate-900">Galerías</h1>
        <p class="mt-4 text-lg text-slate-600">Descubre nuestras últimas colecciones</p>
      </div>
    </section>

    <!-- Galleries (Full-width) -->
    <div class="space-y-16 w-full">
      <div v-for="gallery in galleries" :key="gallery.id" class="w-full">
        <!-- Title and Description (contained) -->
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-10 mb-6">
          <h2 class="mb-2 text-2xl font-semibold text-slate-900">{{ gallery.nombre }}</h2>
          <p v-if="gallery.descripcion" class="text-slate-600">{{ gallery.descripcion }}</p>
        </div>

        <!-- Full-width Grid with minimal gap -->
        <div class="w-full px-1 sm:px-2">
          <div class="grid grid-cols-1 gap-0.5 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
            <div
              v-for="imagen in gallery.imagenes"
              :key="imagen.id"
              class="overflow-hidden border border-slate-200 bg-slate-100 cursor-pointer transition-transform hover:scale-102 group"
              :style="{ aspectRatio: imagen.aspect_ratio }"
              @click="openModal(gallery, imagen)"
            >
              <img
                :src="imagen.imagen_url"
                :alt="gallery.nombre"
                class="h-full w-full object-cover group-hover:opacity-90 transition-opacity"
                loading="lazy"
                decoding="async"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="galleries.length === 0" class="rounded-lg border border-slate-200 bg-slate-50 py-12 text-center mx-auto max-w-7xl px-4">
      <p class="text-slate-600">No hay galerías disponibles en este momento.</p>
    </div>

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
