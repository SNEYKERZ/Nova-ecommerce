<template>
  <AppLayout>
    <Head :title="displayName" />

    <div class="mx-auto w-full max-w-[110rem] px-4 pt-5 sm:px-6 lg:px-10">
      <nav aria-label="Ruta de navegación" class="text-sm text-[var(--sf-mute)]">
        <ol class="flex flex-wrap items-center gap-2">
          <li><Link href="/" class="sf-link">{{ labels.catalogo }}</Link></li>
          <li aria-hidden="true">/</li>
          <li><Link :href="`/?categoria=${encodeURIComponent(producto.categoria)}`" class="sf-link">{{ titleCase(producto.categoria) }}</Link></li>
          <li aria-hidden="true">/</li>
          <li aria-current="page" class="max-w-[50vw] truncate text-[var(--sf-ink)]">{{ displayName }}</li>
        </ol>
      </nav>
    </div>

    <section class="mx-auto grid w-full max-w-[110rem] gap-8 px-4 pb-32 pt-6 sm:px-6 lg:grid-cols-12 lg:gap-12 lg:px-10 lg:pb-10">
      <!-- Fotos: tira de contactos + ampliación -->
      <div class="lg:col-span-7 lg:self-start" :class="gallery.length > 1 ? 'lg:grid lg:grid-cols-[5.5rem_minmax(0,1fr)] lg:gap-4' : ''">
        <div v-if="gallery.length > 1" class="order-2 mt-3 grid grid-cols-5 gap-2 lg:order-1 lg:mt-0 lg:flex lg:flex-col lg:gap-3" role="group" aria-label="Fotos del producto">
          <button
            v-for="(img, index) in gallery"
            :key="index"
            type="button"
            class="strip-thumb"
            :aria-label="`Ver foto ${index + 1} de ${gallery.length}`"
            :aria-current="index === activeIndex ? 'true' : undefined"
            @click="activeIndex = index"
          >
            <img :src="img" alt="" class="aspect-square w-full object-cover" loading="lazy" width="120" height="120" />
            <span class="sf-num mt-1 block text-left text-[0.7rem]">{{ String(index + 1).padStart(2, '0') }}</span>
          </button>
        </div>

        <div class="relative order-1 overflow-hidden bg-[var(--sf-wash-2)] lg:sticky lg:top-20" :class="gallery.length > 1 ? 'lg:order-2 lg:col-start-2' : ''">
          <Transition name="swap" mode="out-in">
            <img
              :key="activeImage"
              :src="activeImage"
              :alt="`${displayName}, foto ${activeIndex + 1} de ${gallery.length}`"
              class="aspect-[4/5] w-full object-cover"
              width="900"
              height="1125"
              fetchpriority="high"
              @error="onImageError"
            />
          </Transition>
          <div class="frame-flags !left-3 !top-3">
            <span v-if="!soldOut && producto.tieneOferta" class="frame-flag is-sale sf-num">−{{ producto.descuentoPct }}%</span>
            <span v-if="producto.nuevaColeccion && !soldOut" class="frame-flag">{{ labels.nuevo }}</span>
          </div>
          <span v-if="soldOut" class="frame-stamp">Agotado</span>
          <template v-if="gallery.length > 1">
            <button type="button" class="photo-arrow left-3" aria-label="Foto anterior" @click="step(-1)">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
            </button>
            <button type="button" class="photo-arrow right-3" aria-label="Foto siguiente" @click="step(1)">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
            </button>
          </template>
        </div>
      </div>

      <!-- Información y compra -->
      <div class="lg:col-span-5 lg:pt-2">
        <p class="text-sm text-[var(--sf-mute)]">{{ titleCase(producto.categoria) }}<template v-if="producto.sku"> · Ref. {{ producto.sku }}</template></p>
        <h1 class="sf-display mt-3 text-5xl sm:text-6xl xl:text-7xl">{{ displayName }}</h1>

        <div class="mt-6 flex flex-wrap items-baseline gap-x-4 gap-y-1 border-t border-[var(--sf-rule)] pt-5 sf-num">
          <template v-if="producto.tieneOferta">
            <p class="text-3xl font-extrabold text-[var(--pencil)]">{{ money(producto.precioOferta) }}</p>
            <p class="text-lg text-[var(--sf-mute)] line-through"><span class="visually-hidden">Precio anterior </span>{{ money(producto.precio) }}</p>
            <p class="relative text-sm font-semibold">
              Ahorras {{ money(producto.precio - producto.precioOferta) }}
              <PencilMark shape="underline" :active="true" />
            </p>
          </template>
          <p v-else class="text-3xl font-extrabold">{{ money(producto.precio) }}</p>
        </div>

        <div v-if="soldOut" class="mt-6 border border-[var(--pencil)] bg-[var(--sf-pencil-wash)] px-4 py-3 text-base">
          <p class="font-bold">Producto agotado</p>
          <p class="mt-0.5 text-[var(--sf-ink-2)]">
            Escríbenos<template v-if="whatsappHref"> por <a :href="whatsappHref" target="_blank" rel="noopener" class="sf-link font-semibold">WhatsApp</a></template> y te avisamos cuando vuelva.
          </p>
        </div>

        <template v-else>
          <!-- Variante (talla, tamaño…) -->
          <fieldset v-if="sizes.length" class="mt-8">
            <div class="flex items-center justify-between">
              <legend class="text-base font-semibold">{{ labels.variante }}<span v-if="selectedSize" class="font-normal text-[var(--sf-mute)]">: {{ selectedSize }}</span></legend>
              <span v-if="lowStockText" class="text-sm font-semibold text-[var(--pencil)]">{{ lowStockText }}</span>
            </div>
            <div class="mt-3 flex flex-wrap gap-2" role="radiogroup" :aria-label="labels.variante" :aria-describedby="sizeError ? 'size-error' : undefined">
              <button
                v-for="size in sizes"
                :key="size"
                type="button"
                role="radio"
                class="sf-option !min-h-12 !min-w-12 !text-sm"
                :aria-checked="selectedSize === size ? 'true' : 'false'"
                :disabled="unavailableSizes.includes(size)"
                :aria-label="unavailableSizes.includes(size) ? `${size}, agotada` : size"
                @click="selectSize(size)"
              >{{ size }}</button>
            </div>
            <p v-if="sizeError" id="size-error" class="mt-2 text-sm font-semibold text-[var(--pencil)]" role="alert">{{ sizeError }}</p>
          </fieldset>

          <!-- Cantidad + CTA -->
          <div class="mt-8 flex flex-wrap items-end gap-3">
            <div>
              <p id="qty-label" class="mb-2 text-base font-semibold">Cantidad</p>
              <div class="qty-sf" role="group" aria-labelledby="qty-label">
                <button type="button" aria-label="Quitar uno" :disabled="cantidad <= 1" @click="cantidad--">−</button>
                <output class="sf-num" aria-live="polite">{{ cantidad }}</output>
                <button type="button" aria-label="Agregar uno" :disabled="cantidad >= maxQty" @click="cantidad++">+</button>
              </div>
            </div>
            <button type="button" class="sf-btn hidden !min-h-[3.25rem] flex-1 lg:inline-flex" :disabled="adding" @click="addToCart">
              <span v-if="adding" class="spinner" aria-hidden="true"></span>
              <svg v-else-if="justAdded" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4L8 12.6l7.3-7.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
              {{ ctaLabel }}
              <span v-if="!adding && !justAdded" class="sf-num font-semibold opacity-80">· {{ money(unitPrice * cantidad) }}</span>
            </button>
          </div>
          <a v-if="consultHref" :href="consultHref" target="_blank" rel="noopener" class="mt-3 hidden min-h-11 items-center gap-2 text-sm font-semibold underline-offset-4 hover:underline lg:inline-flex">
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18a8 8 0 0 1-4.1-1.1l-.3-.2-3 .8.8-2.9-.2-.3A8 8 0 1 1 12 20Z"/></svg>
            Preguntar por este producto en WhatsApp
          </a>
        </template>

        <div v-if="producto.descripcion" class="mt-10 border-t border-[var(--sf-rule)] pt-6">
          <h2 class="text-base font-semibold">Descripción</h2>
          <p class="mt-3 max-w-prose whitespace-pre-line text-base leading-relaxed text-[var(--sf-ink-2)]">{{ producto.descripcion }}</p>
        </div>

        <dl class="mt-8 grid gap-4 border-t border-[var(--sf-rule)] pt-6 text-sm sm:grid-cols-2">
          <div>
            <dt class="font-semibold">Envío</dt>
            <dd class="mt-1 text-[var(--sf-ink-2)]">Se coordina contigo después de confirmar el pedido.</dd>
          </div>
          <div>
            <dt class="font-semibold">Cómo pedir</dt>
            <dd class="mt-1 text-[var(--sf-ink-2)]">Termina tu compra en la web o por WhatsApp.</dd>
          </div>
        </dl>
      </div>
    </section>

    <!-- Barra fija de compra en móvil -->
    <div v-if="!soldOut" class="fixed inset-x-0 bottom-0 z-40 border-t border-[var(--sf-rule)] bg-[var(--sf-paper)] px-4 py-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] lg:hidden">
      <div class="flex items-center gap-3">
        <div class="min-w-0">
          <p class="truncate text-xs text-[var(--sf-mute)]">{{ selectedSize ? `${labels.variante} ${selectedSize}` : sizes.length ? labels.elegirVariante : displayName }}</p>
          <p class="sf-num text-lg font-extrabold">{{ money(unitPrice * cantidad) }}</p>
        </div>
        <button type="button" class="sf-btn min-h-12 flex-1" :disabled="adding" @click="addToCart">
          <span v-if="adding" class="spinner" aria-hidden="true"></span>
          {{ ctaLabel }}
        </button>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';
import PencilMark from '../components/store/PencilMark.vue';
import { useRubro } from '../composables/useRubro';
import { useCart } from '../composables/useCart';
import { money } from '../utils/format';

const props = defineProps({
  producto: { type: Object, required: true },
});

const page = usePage();
const cart = useCart();
const rubro = useRubro();
const labels = computed(() => rubro.preset.value);

const titleCase = (value) => String(value || '').toLowerCase().replace(/(^|\s)\S/g, (c) => c.toUpperCase());
const displayName = computed(() => props.producto.nombre || props.producto.sku);
const soldOut = computed(() => props.producto.tieneStock === false);
const unitPrice = computed(() => (props.producto.tieneOferta ? props.producto.precioOferta : props.producto.precio));

const gallery = computed(() => (props.producto.galeria?.length ? props.producto.galeria : [props.producto.foto]));
const activeIndex = ref(0);
const activeImage = computed(() => gallery.value[activeIndex.value] || props.producto.foto);
const step = (delta) => { activeIndex.value = (activeIndex.value + delta + gallery.value.length) % gallery.value.length; };
const onImageError = (event) => { if (!event.target.src.endsWith('/images/sinfoto.jpg')) event.target.src = '/images/sinfoto.jpg'; };

/**
 * Stock por talla: si el backend envía `stockPorTalla` ([{talla, stock}] o {talla: stock})
 * se deshabilitan las tallas agotadas y se avisa cuando quedan pocas unidades.
 */
const stockMap = computed(() => {
  const raw = props.producto.stockPorTalla;
  if (!raw) return null;
  if (Array.isArray(raw)) return Object.fromEntries(raw.map((v) => [v.talla, Number(v.stock)]));
  return raw;
});
const sizes = computed(() => props.producto.tallas || []);
const unavailableSizes = computed(() => (stockMap.value ? sizes.value.filter((s) => (stockMap.value[s] ?? 0) <= 0) : []));
const availableSizes = computed(() => sizes.value.filter((s) => !unavailableSizes.value.includes(s)));

const selectedSize = ref(availableSizes.value.length === 1 ? availableSizes.value[0] : null);
const sizeError = ref('');
const cantidad = ref(1);
const adding = ref(false);
const justAdded = ref(false);

const maxQty = computed(() => {
  if (stockMap.value && selectedSize.value) return Math.max(1, Math.min(99, stockMap.value[selectedSize.value] ?? 99));
  return 99;
});
const lowStockText = computed(() => {
  if (!stockMap.value || !selectedSize.value) return '';
  const stock = stockMap.value[selectedSize.value];
  return stock > 0 && stock <= 3 ? `Quedan ${stock}` : '';
});

const ctaLabel = computed(() => {
  if (adding.value) return 'Agregando…';
  if (justAdded.value) return 'Agregado';
  return 'Agregar al carrito';
});

const whatsappHref = computed(() => {
  const digits = (page.props.app?.whatsapp || '').replace(/\D/g, '');
  if (!digits) return null;
  const msg = `Hola, me interesa ${displayName.value} (Ref. ${props.producto.sku}). ¿Cuándo vuelve a estar disponible?`;
  return `https://wa.me/${digits}?text=${encodeURIComponent(msg)}`;
});

const consultHref = computed(() => {
  const digits = (page.props.app?.whatsapp || '').replace(/\D/g, '');
  if (!digits) return null;
  const msg = `Hola, tengo una pregunta sobre ${displayName.value} (Ref. ${props.producto.sku}).`;
  return `https://wa.me/${digits}?text=${encodeURIComponent(msg)}`;
});

const selectSize = (size) => {
  selectedSize.value = size;
  sizeError.value = '';
  if (cantidad.value > maxQty.value) cantidad.value = maxQty.value;
};

const addToCart = async () => {
  if (sizes.value.length && !selectedSize.value) {
    sizeError.value = `${labels.value.elegirVariante} para continuar.`;
    document.querySelector('[role="radiogroup"] .sf-option:not(:disabled)')?.focus();
    return;
  }
  adding.value = true;
  const ok = await cart.add(props.producto, { talla: selectedSize.value, cantidad: cantidad.value });
  adding.value = false;
  if (ok) {
    cantidad.value = 1;
    justAdded.value = true;
    setTimeout(() => { justAdded.value = false; }, 2200);
  }
};
</script>

<style scoped>
.strip-thumb { display: block; padding: 0; background: none; border: 0; color: var(--sf-mute); opacity: 0.7; transition: opacity 160ms ease; }
.strip-thumb:hover { opacity: 1; }
.strip-thumb[aria-current="true"] { opacity: 1; color: var(--sf-ink); }
.strip-thumb[aria-current="true"] img { outline: 2px solid var(--pencil); outline-offset: 2px; }
.photo-arrow {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  display: inline-flex;
  width: 2.75rem;
  height: 2.75rem;
  align-items: center;
  justify-content: center;
  background: color-mix(in srgb, var(--sf-paper) 90%, transparent);
  color: var(--sf-ink);
  border: 0;
}
.qty-sf { display: inline-flex; align-items: center; border: 1px solid var(--sf-rule-strong); }
.qty-sf button { width: 3rem; height: 3.1rem; background: none; border: 0; color: var(--sf-ink); font-size: 1.2rem; }
.qty-sf button:hover:not(:disabled) { background: var(--sf-wash); }
.qty-sf button:disabled { opacity: 0.35; }
.qty-sf output { min-width: 2.5rem; text-align: center; font-weight: 700; }
.swap-enter-active, .swap-leave-active { transition: opacity 200ms ease; }
.swap-enter-from, .swap-leave-to { opacity: 0; }
</style>
