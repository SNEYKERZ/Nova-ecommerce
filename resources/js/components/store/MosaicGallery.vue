<template>
  <div ref="root" class="mosaic" :style="{ height: layout.height ? `${layout.height}px` : undefined }">
    <!-- Antes de medir (SSR / primer pintado) se muestra una rejilla simple para no saltar -->
    <div v-if="!width" class="grid grid-cols-2 gap-3 sm:grid-cols-4">
      <div v-for="imagen in imagenes" :key="imagen.id" class="bg-[var(--sf-wash-2)]" :style="{ aspectRatio: imagen.aspect_ratio || '1/1' }"></div>
    </div>
    <button
      v-for="(imagen, index) in imagenes"
      v-else
      :key="imagen.id"
      type="button"
      class="mosaic-tile"
      :style="boxStyle(index)"
      :aria-label="`Abrir foto ${index + 1} de ${title}${imagen.productos?.length ? `, ${imagen.productos.length} ${imagen.productos.length === 1 ? 'producto' : 'productos'}` : ''}`"
      @click="$emit('open', imagen)"
    >
      <img
        :src="imagen.imagen_url"
        alt=""
        :loading="index < 6 ? 'eager' : 'lazy'"
        decoding="async"
        :class="loaded[imagen.id] ? 'opacity-100' : 'opacity-0'"
        @load="loaded[imagen.id] = true"
        @error="loaded[imagen.id] = true"
      />
      <span class="mosaic-no sf-num" aria-hidden="true">Nº {{ String(index + 1).padStart(2, '0') }}</span>
      <span v-if="imagen.productos?.length" class="mosaic-count sf-num" aria-hidden="true">
        <i></i>{{ imagen.productos.length }} {{ imagen.productos.length === 1 ? 'producto' : 'productos' }}
      </span>
    </button>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { layoutMosaic } from '../../utils/mosaic';

const props = defineProps({
  imagenes: { type: Array, default: () => [] },
  title: { type: String, default: '' },
});
defineEmits(['open']);

const root = ref(null);
const width = ref(0);
const loaded = reactive({});
let observer = null;

const gap = computed(() => (width.value < 640 ? 6 : 12));
const layout = computed(() => (width.value
  ? layoutMosaic(props.imagenes, width.value, { gap: gap.value })
  : { boxes: [], height: 0 }));

const boxStyle = (index) => {
  const box = layout.value.boxes[index];
  if (!box) return { display: 'none' };
  return {
    left: `${box.x}px`,
    top: `${box.y}px`,
    width: `${box.w}px`,
    height: `${box.h}px`,
  };
};

onMounted(() => {
  const measure = () => { width.value = Math.floor(root.value?.clientWidth || 0); };
  measure();
  if (typeof ResizeObserver !== 'undefined') {
    let frame = 0;
    observer = new ResizeObserver(() => {
      cancelAnimationFrame(frame);
      frame = requestAnimationFrame(measure);
    });
    observer.observe(root.value);
  }
});

onBeforeUnmount(() => observer?.disconnect());
</script>
