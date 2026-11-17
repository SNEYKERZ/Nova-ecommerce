<template>
  <Teleport to="#contenido">
    <div class="fixed inset-0 z-[70] flex items-end justify-center bg-black/75 backdrop-blur-[2px] sm:items-center sm:p-6" @click.self="$emit('close')">
      <div
        ref="panel"
        class="relative flex max-h-[94vh] w-full max-w-6xl flex-col overflow-hidden bg-[var(--sf-paper)] text-[var(--sf-ink)] shadow-2xl md:flex-row"
        role="dialog"
        aria-modal="true"
        :aria-label="`Foto ${position} de ${gallery.nombre}`"
        tabindex="-1"
        @keydown.esc="onEsc"
        @keydown.left="$emit('navigate', -1)"
        @keydown.right="$emit('navigate', 1)"
      >
        <!-- Foto -->
        <div class="relative flex shrink-0 items-center justify-center bg-[#0d0d0d] md:w-[60%]">
          <Transition name="swap" mode="out-in">
            <img :key="imagen.id" :src="imagen.imagen_url" :alt="`Foto ${position} de la galería ${gallery.nombre}`" class="max-h-[46vh] w-full object-contain md:h-full md:max-h-[94vh]" decoding="async" />
          </Transition>
          <template v-if="total > 1">
            <button type="button" class="lens-arrow left-3" aria-label="Foto anterior" @click="$emit('navigate', -1)">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
            </button>
            <button type="button" class="lens-arrow right-3" aria-label="Foto siguiente" @click="$emit('navigate', 1)">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
            </button>
          </template>
        </div>

        <!-- Productos en la foto -->
        <div class="flex min-h-0 flex-1 flex-col">
          <div class="flex items-start justify-between gap-4 border-b border-[var(--sf-rule)] px-5 pb-4 pt-5 sm:px-6">
            <div class="min-w-0">
              <p class="sf-num text-sm text-[var(--sf-mute)]">{{ gallery.nombre }} · Nº {{ String(position).padStart(2, '0') }} de {{ String(total).padStart(2, '0') }}</p>
              <h2 class="sf-display mt-1 text-4xl">En esta foto</h2>
            </div>
            <button type="button" class="icon-btn -mr-2 -mt-1 shrink-0" aria-label="Cerrar" @click="$emit('close')">
              <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.3 5.3a1 1 0 0 0-1.4 1.4L8.6 10l-3.7 3.3a1 1 0 1 0 1.4 1.4L10 11.4l3.3 3.3a1 1 0 0 0 1.4-1.4L11.4 10l3.3-3.3a1 1 0 0 0-1.4-1.4L10 8.6 6.3 5.3Z"/></svg>
            </button>
          </div>

          <div class="min-h-0 flex-1 overflow-y-auto px-5 sm:px-6">
            <ol v-if="imagen.productos?.length" class="divide-y divide-[var(--sf-rule)]">
              <li v-for="(producto, index) in imagen.productos" :key="producto.id" class="flex gap-4 py-4">
                <span class="sf-num w-6 shrink-0 pt-0.5 text-sm text-[var(--sf-mute)]" aria-hidden="true">{{ index + 1 }}</span>
                <Link :href="`/productos/${producto.producto_id}`" class="h-24 w-20 shrink-0 overflow-hidden bg-[var(--sf-wash-2)]" tabindex="-1" aria-hidden="true">
                  <img v-if="producto.foto" :src="producto.foto" alt="" class="h-full w-full object-cover" loading="lazy" />
                  <span v-else class="flex h-full items-center justify-center text-xs font-bold uppercase text-[var(--sf-mute)]">{{ initials(producto) }}</span>
                </Link>
                <div class="flex min-w-0 flex-1 flex-col">
                  <Link :href="`/productos/${producto.producto_id}`" class="line-clamp-2 font-semibold underline-offset-4 hover:underline">{{ name(producto) }}</Link>
                  <p class="sf-meta mt-0.5">Ref. {{ producto.referencia }}</p>
                  <div class="mt-auto flex items-end justify-between gap-3 pt-2">
                    <p class="sf-num text-base font-extrabold">{{ money(producto.precio) }}</p>
                    <button type="button" class="sf-btn !min-h-10 !px-4" :disabled="pendingFor(producto)" @click="startAdd(producto)">
                      <span v-if="pendingFor(producto)" class="spinner" aria-hidden="true"></span>
                      Agregar
                    </button>
                  </div>
                </div>
              </li>
            </ol>
            <p v-else class="my-6 border border-dashed border-[var(--sf-rule-strong)] p-5 text-sm text-[var(--sf-mute)]">
              Esta foto no tiene productos asociados. Explora el <Link href="/" class="sf-link font-semibold text-[var(--sf-ink)]">catálogo</Link>.
            </p>
          </div>
        </div>
      </div>
    </div>

    <SizeSheet :open="!!sizeProduct" :product="sheetProduct" :busy="sizeProduct ? pendingFor(sizeProduct) : false" @select="onSize" @close="sizeProduct = null" />
  </Teleport>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';
import SizeSheet from './store/SizeSheet.vue';
import { useCart } from '../composables/useCart';
import { money } from '../utils/format';

const props = defineProps({
  gallery: { type: Object, required: true },
  imagen: { type: Object, required: true },
});
const emit = defineEmits(['close', 'navigate']);

const cart = useCart();
const panel = ref(null);
const sizeProduct = ref(null);
let lastFocus = null;

const total = computed(() => props.gallery.imagenes?.length || 1);
const position = computed(() => Math.max(1, (props.gallery.imagenes || []).findIndex((i) => i.id === props.imagen.id) + 1));

const name = (p) => p.nombre || p.referencia;
const initials = (p) => name(p).slice(0, 2);
const toCartProduct = (p) => ({ id: p.producto_id, nombre: name(p), foto: p.foto || null, precio: p.precio });
const sheetProduct = computed(() => (sizeProduct.value ? { ...toCartProduct(sizeProduct.value), tallas: sizeProduct.value.tallas || [] } : null));
const pendingFor = (p) => Object.keys(cart.state.pending).some((k) => k.startsWith(`${p.producto_id}-`));

const startAdd = (producto) => {
  const tallas = producto.tallas || [];
  if (tallas.length > 1) sizeProduct.value = producto;
  else cart.add(toCartProduct(producto), { talla: tallas[0] ?? null });
};

const onSize = async (talla) => {
  const ok = await cart.add(toCartProduct(sizeProduct.value), { talla });
  if (ok) sizeProduct.value = null;
};

const onEsc = () => {
  if (sizeProduct.value) sizeProduct.value = null;
  else emit('close');
};

onMounted(async () => {
  lastFocus = document.activeElement;
  document.documentElement.style.overflow = 'hidden';
  await nextTick();
  panel.value?.focus();
});
onUnmounted(() => {
  document.documentElement.style.overflow = '';
  lastFocus?.focus?.();
});
</script>

<style scoped>
.lens-arrow {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  display: inline-flex;
  width: 2.75rem;
  height: 2.75rem;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.92);
  color: #141414;
  border: 0;
  transition: background 160ms ease;
}
.lens-arrow:hover { background: #fff; }
.swap-enter-active, .swap-leave-active { transition: opacity 220ms ease; }
.swap-enter-from, .swap-leave-to { opacity: 0; }
</style>
