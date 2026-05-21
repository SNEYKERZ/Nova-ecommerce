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

    <!-- Galleries (Full-width Pinterest Layout) -->
    <div class="w-full space-y-20">
      <div v-for="gallery in galleries" :key="gallery.id" class="w-full">
        <!-- Title and Description (contained) -->
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-10 mb-8">
          <h2 class="mb-2 text-2xl font-semibold text-slate-900">{{ gallery.nombre }}</h2>
          <p v-if="gallery.descripcion" class="text-slate-600">{{ gallery.descripcion }}</p>
        </div>

        <!-- Pinterest-style Grid with row-dense flow (Max 4 columns) -->
        <div class="w-full px-1 sm:px-2 lg:px-4">
          <div
            class="grid gap-0.5 grid-flow-row-dense grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6"
            style="grid-auto-rows: 240px; gap: 0.125rem;"
          >
            <div
              v-for="(imagen, idx) in getOptimizedImages(gallery.imagenes)"
              :key="imagen.id"
              :class="getGridClasses(imagen._orientation)"
              class="group overflow-hidden rounded-lg border border-slate-200 bg-slate-100 cursor-pointer transition-all duration-300 hover:shadow-lg hover:z-10"
              @click="openModal(gallery, imagen)"
            >
              <img
                :src="imagen.imagen_url"
                :alt="`${gallery.nombre} - imagen ${idx + 1}`"
                class="h-full w-full object-cover transition-opacity duration-300 group-hover:opacity-85"
                loading="lazy"
                decoding="async"
              />
              <!-- Overlay subtle on hover -->
              <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors duration-300" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="galleries.length === 0" class="mx-auto w-full max-w-7xl rounded-lg border border-slate-200 bg-slate-50 px-4 py-12 text-center">
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
import { ref, computed } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';
import GalleryModal from '../components/GalleryModal.vue';

const props = defineProps({
  galleries: { type: Array, default: () => [] },
});

const selectedGallery = ref(null);
const selectedImage = ref(null);

/**
 * Detecta la orientación de una imagen basada en su aspect_ratio
 * Retorna: 'horizontal' | 'vertical' | 'square'
 */
const detectOrientation = (aspectRatio) => {
  if (!aspectRatio) return 'square';

  const [width, height] = aspectRatio.split('/').map(Number);
  if (!width || !height) return 'square';

  const ratio = width / height;

  // Horizontal: ratio > 1.2 (ancho es 20% mayor que alto)
  if (ratio > 1.2) return 'horizontal';

  // Vertical: ratio < 0.8 (alto es 20% mayor que ancho)
  if (ratio < 0.8) return 'vertical';

  // Cuadrada: entre 0.8 y 1.2
  return 'square';
};

/**
 * Retorna las clases de Tailwind CSS para el grid basadas en orientación
 */
const getGridClasses = (orientation) => {
  const baseClasses = 'relative';

  switch (orientation) {
    // Horizontales: ocupan 2 columnas × 2 filas para mejor visibilidad
    case 'horizontal':
      return `${baseClasses} col-span-2 row-span-2`;
    // Verticales: ocupan 1 columna × 2 filas
    case 'vertical':
      return `${baseClasses} col-span-1 row-span-2`;
    // Cuadradas: ocupan 2 columnas × 2 filas
    case 'square':
      return `${baseClasses} col-span-2 row-span-2`;
    default:
      return `${baseClasses} col-span-1 row-span-1`;
  }
};

/**
 * Algoritmo de ordenamiento estético: intercala inteligentemente
 * horizontales, verticales y cuadradas para optimizar el empaquetado
 * del grid denso y minimizar espacios vacíos.
 */
const getOptimizedImages = (imagenes) => {
  if (!imagenes || imagenes.length === 0) return [];

  // 1. Enriquecer cada imagen con su orientación detectada
  const imagenConOrientacion = imagenes.map((img) => ({
    ...img,
    _orientation: detectOrientation(img.aspect_ratio),
  }));

  // 2. Separar por orientación
  const horizontal = imagenConOrientacion.filter((img) => img._orientation === 'horizontal');
  const vertical = imagenConOrientacion.filter((img) => img._orientation === 'vertical');
  const square = imagenConOrientacion.filter((img) => img._orientation === 'square');

  // 3. Crear array ordenado intercalando de forma estética
  const result = [];
  let h = 0,
    v = 0,
    s = 0;

  // Patrón: 1 horizontal, 1 vertical, 1 cuadrada (si existen)
  // Si se agota algún tipo, continúa con los demás
  while (h < horizontal.length || v < vertical.length || s < square.length) {
    // Agregar horizontal
    if (h < horizontal.length) {
      result.push(horizontal[h++]);
    }

    // Agregar vertical
    if (v < vertical.length) {
      result.push(vertical[v++]);
    }

    // Agregar cuadrada
    if (s < square.length) {
      result.push(square[s++]);
    }

    // Patrón alternativo: si hay verticales sobrantes, agregar 2 verticales seguidas
    if (v < vertical.length && h >= horizontal.length) {
      result.push(vertical[v++]);
      if (v < vertical.length) {
        result.push(vertical[v++]);
      }
    }
  }

  return result;
};

</script>

<style scoped>
/* Animación suave en hover */
.group:hover {
  transition: all 300ms cubic-bezier(0.4, 0, 0.2, 1);
}
</style>
