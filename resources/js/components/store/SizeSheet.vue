<template>
  <Teleport to="#contenido" defer>
    <Transition name="fade">
      <div v-if="open" class="overlay !z-[85]" aria-hidden="true" @click="$emit('close')"></div>
    </Transition>
    <Transition name="sheet">
      <div
        v-if="open && product"
        ref="panel"
        class="fixed inset-x-0 bottom-0 z-[86] bg-[var(--sf-paper)] p-5 pb-[max(1.25rem,env(safe-area-inset-bottom))] text-[var(--sf-ink)] shadow-2xl sm:inset-x-auto sm:bottom-auto sm:left-1/2 sm:top-1/2 sm:w-[26rem] sm:-translate-x-1/2 sm:-translate-y-1/2"
        role="dialog"
        aria-modal="true"
        aria-labelledby="size-sheet-title"
        @keydown.esc="$emit('close')"
      >
        <div class="flex items-start gap-3">
          <img v-if="product.foto" :src="product.foto" alt="" class="h-16 w-12 shrink-0 bg-[var(--sf-wash-2)] object-cover" width="48" height="64" />
          <div class="min-w-0 flex-1">
            <p id="size-sheet-title" class="sf-display text-3xl">{{ rubro.preset.value.elegirVariante }}</p>
            <p class="mt-0.5 truncate text-sm text-[var(--muted)]">{{ product.nombre || product.sku || product.referencia }}</p>
          </div>
          <button type="button" class="icon-btn -m-2" aria-label="Cerrar" @click="$emit('close')">
            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.3 5.3a1 1 0 0 0-1.4 1.4L8.6 10l-3.7 3.3a1 1 0 1 0 1.4 1.4L10 11.4l3.3 3.3a1 1 0 0 0 1.4-1.4L11.4 10l3.3-3.3a1 1 0 0 0-1.4-1.4L10 8.6 6.3 5.3Z"/></svg>
          </button>
        </div>
        <div class="mt-5 flex flex-wrap gap-2" role="group" aria-label="Tallas disponibles">
          <button
            v-for="talla in product.tallas"
            :key="talla"
            type="button"
            class="sf-option !min-h-12 !min-w-12"
            :disabled="busy || unavailable.includes(talla)"
            @click="$emit('select', talla)"
          >{{ talla }}</button>
        </div>
        <p class="mt-4 text-sm text-[var(--sf-mute)]">Toca una opción para agregarla al carrito.</p>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { nextTick, ref, watch } from 'vue';
import { useRubro } from '../../composables/useRubro';

const props = defineProps({
  open: { type: Boolean, default: false },
  product: { type: Object, default: null },
  unavailable: { type: Array, default: () => [] },
  busy: { type: Boolean, default: false },
});
defineEmits(['select', 'close']);

const rubro = useRubro();
const panel = ref(null);
watch(() => props.open, async (open) => {
  if (!open) return;
  await nextTick();
  panel.value?.querySelector('.sf-option:not(:disabled)')?.focus();
});
</script>
