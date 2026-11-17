<template>
  <article class="frame group" :class="{ 'is-soldout': soldOut }">
    <div class="frame-media" :class="{ 'has-alt': product.fotoAlt }">
      <Link :href="href" class="absolute inset-0 block" tabindex="-1" aria-hidden="true">
        <img
          :src="product.foto"
          :alt="displayName"
          :loading="priority ? 'eager' : 'lazy'"
          :fetchpriority="priority ? 'high' : 'auto'"
          decoding="async"
          width="600"
          height="750"
          :style="{ opacity: loaded ? undefined : 0 }"
          @load="loaded = true"
          @error="onImageError"
        />
        <img v-if="product.fotoAlt" :src="product.fotoAlt" alt="" class="frame-alt" loading="lazy" decoding="async" width="600" height="750" />
      </Link>

      <div class="frame-flags">
        <span v-if="!soldOut && product.tieneOferta" class="frame-flag is-sale sf-num">−{{ product.descuentoPct }}%</span>
        <span v-if="!soldOut && (product.nuevo || product.nuevaColeccion)" class="frame-flag">{{ rubro.preset.value.nuevo }}</span>
      </div>
      <span v-if="soldOut" class="frame-stamp">Agotado</span>

      <span class="frame-added" aria-hidden="true">
        <PencilMark shape="tick" :active="justAdded" />
      </span>

      <template v-if="!soldOut">
        <!-- Escritorio: agregar rápido al pasar el cursor o con teclado -->
        <div class="frame-quick">
          <div v-if="sizes.length" class="flex flex-wrap gap-1.5">
            <button
              v-for="talla in sizes"
              :key="talla"
              type="button"
              class="sf-option"
              :disabled="cart.isPending(product.id, talla)"
              :aria-label="`Agregar ${displayName}, ${rubro.preset.value.variante.toLowerCase()} ${talla}, al carrito`"
              @click="add(talla)"
            >{{ talla }}</button>
          </div>
          <button v-else type="button" class="sf-btn w-full !min-h-10" :disabled="cart.isPending(product.id)" @click="add(null)">
            <span v-if="cart.isPending(product.id)" class="spinner" aria-hidden="true"></span>
            Agregar al carrito
          </button>
        </div>

        <!-- Táctil: botón visible que abre el selector -->
        <button type="button" class="frame-fab" :aria-label="`Agregar ${displayName} al carrito`" @click="onFab">
          <span v-if="cart.isPending(product.id, sizes.length === 1 ? sizes[0] : null)" class="spinner" aria-hidden="true"></span>
          <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
        </button>
      </template>
    </div>

    <div class="frame-edge">
      <span class="frame-no sf-num" aria-hidden="true">Nº {{ number }}</span>
      <p class="sf-num flex flex-wrap items-baseline justify-end gap-x-2">
        <template v-if="product.tieneOferta">
          <span class="frame-price-was"><span class="visually-hidden">Antes </span>{{ money(product.precio) }}</span>
          <span class="frame-price text-[var(--pencil)]">{{ money(product.precioOferta) }}</span>
        </template>
        <span v-else class="frame-price">{{ money(product.precio) }}</span>
      </p>
    </div>
    <h3 class="frame-name">
      <Link :href="href" class="line-clamp-2 underline-offset-4 hover:underline">{{ displayName }}</Link>
    </h3>
    <p v-if="rubro.preset.value.mostrarDescripcion && product.descripcion" class="mt-1 line-clamp-2 text-[0.82rem] leading-snug text-[var(--sf-mute)]">{{ product.descripcion }}</p>
    <p class="sf-meta mt-1 truncate">
      <template v-if="rubro.preset.value.mostrarSku && product.sku">Ref. {{ product.sku }} · </template>{{ titleCase(product.categoria) }}<template v-if="sizes.length > 1"> · {{ sizes.length }} {{ rubro.key.value === 'moda' ? 'tallas' : 'opciones' }}</template>
    </p>

    <SizeSheet :open="sheetOpen" :product="product" :busy="anyPending" @select="onSheetSelect" @close="sheetOpen = false" />
  </article>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref } from 'vue';
import PencilMark from './PencilMark.vue';
import SizeSheet from './SizeSheet.vue';
import { useCart } from '../../composables/useCart';
import { useRubro } from '../../composables/useRubro';
import { money } from '../../utils/format';

const props = defineProps({
  product: { type: Object, required: true },
  priority: { type: Boolean, default: false },
  index: { type: Number, default: 0 },
});

const cart = useCart();
const rubro = useRubro();
const loaded = ref(false);
const sheetOpen = ref(false);
const justAdded = ref(false);
let addedTimer = null;

const href = computed(() => `/productos/${props.product.id}`);
const displayName = computed(() => props.product.nombre || props.product.sku);
const number = computed(() => String(props.index + 1).padStart(3, '0'));
const sizes = computed(() => props.product.tallas || []);
const soldOut = computed(() => props.product.tieneStock === false);
const anyPending = computed(() => Object.keys(cart.state.pending).some((k) => k.startsWith(`${props.product.id}-`)));
const titleCase = (value) => String(value || '').toLowerCase().replace(/(^|\s)\S/g, (c) => c.toUpperCase());

const add = async (talla) => {
  const ok = await cart.add(props.product, { talla });
  if (ok) {
    justAdded.value = true;
    clearTimeout(addedTimer);
    addedTimer = setTimeout(() => { justAdded.value = false; }, 2600);
  }
  return ok;
};

const onFab = () => {
  if (sizes.value.length > 1) sheetOpen.value = true;
  else add(sizes.value[0] ?? null);
};

const onSheetSelect = async (talla) => {
  const ok = await add(talla);
  if (ok) sheetOpen.value = false;
};

const onImageError = (event) => {
  if (!event.target.src.endsWith('/images/sinfoto.jpg')) event.target.src = '/images/sinfoto.jpg';
  loaded.value = true;
};

onBeforeUnmount(() => clearTimeout(addedTimer));
</script>
