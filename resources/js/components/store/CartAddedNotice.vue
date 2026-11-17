<template>
  <Transition name="slide-up">
    <div
      v-if="item"
      class="fixed inset-x-3 bottom-3 z-[80] sm:inset-x-auto sm:bottom-auto sm:right-5 sm:top-20 sm:w-[22rem]"
      role="status"
      aria-live="polite"
      @mouseenter="pause"
      @mouseleave="resume"
      @focusin="pause"
      @focusout="resume"
    >
      <div class="border border-[var(--sf-ink)] bg-[var(--sf-paper)] p-4 text-[var(--sf-ink)] shadow-[0_18px_40px_-18px_rgba(0,0,0,.45)]">
        <div class="flex items-center justify-between gap-2">
          <p class="flex items-center gap-2 text-sm font-bold">
            <svg class="h-4 w-4 text-[var(--pencil)]" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4L8 12.6l7.3-7.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
            Agregado al carrito
          </p>
          <button type="button" class="icon-btn -m-2 !h-9 !w-9" aria-label="Cerrar aviso" @click="dismiss">
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.3 5.3a1 1 0 0 0-1.4 1.4L8.6 10l-3.7 3.3a1 1 0 1 0 1.4 1.4L10 11.4l3.3 3.3a1 1 0 0 0 1.4-1.4L11.4 10l3.3-3.3a1 1 0 0 0-1.4-1.4L10 8.6 6.3 5.3Z"/></svg>
          </button>
        </div>
        <div class="mt-3 flex gap-3">
          <img v-if="item.foto" :src="item.foto" alt="" class="h-20 w-16 shrink-0 bg-[var(--sf-wash-2)] object-cover" width="64" height="80" />
          <div class="min-w-0 text-sm">
            <p class="truncate font-semibold">{{ item.nombre }}</p>
            <p class="mt-0.5 text-xs text-[var(--muted)]">
              <span v-if="item.talla">{{ item.talla }} · </span>Cantidad {{ item.cantidad }}
            </p>
            <p v-if="item.precio" class="sf-num mt-1 font-bold">{{ money(item.precio * item.cantidad) }}</p>
          </div>
        </div>
        <div class="mt-4 grid grid-cols-2 gap-2">
          <button type="button" class="sf-btn-line !min-h-11 !px-2" @click="dismiss">Seguir comprando</button>
          <Link href="/carrito" class="sf-btn !min-h-11 !px-2" @click="dismiss">Ver carrito ({{ cart.state.count }})</Link>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, watch } from 'vue';
import { useCart } from '../../composables/useCart';
import { money } from '../../utils/format';

const cart = useCart();
const item = computed(() => cart.state.lastAdded);
let timer = null;

const dismiss = () => {
  clearTimeout(timer);
  cart.dismissAdded();
};
const resume = () => {
  clearTimeout(timer);
  timer = setTimeout(dismiss, 5000);
};
const pause = () => clearTimeout(timer);

watch(() => cart.state.lastAdded?.at, (value) => { if (value) resume(); });
onBeforeUnmount(() => clearTimeout(timer));
</script>
