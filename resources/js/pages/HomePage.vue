<template>
  <AppLayout>
    <Head :title="`${storeName} — ${labels.catalogo}`" />

    <!-- Portada: copia ampliada (carrusel) con el nombre de la tienda -->
    <section
      v-if="slides.length"
      class="relative bg-[var(--sf-ink)]"
      aria-roledescription="carrusel"
      :aria-label="`Destacados de ${storeName}`"
      @mouseenter="pauseAutoplay"
      @mouseleave="startAutoplay"
      @focusin="pauseAutoplay"
      @focusout="startAutoplay"
    >
      <div class="relative h-[72svh] min-h-[440px] max-h-[900px] overflow-hidden">
        <Transition name="develop">
          <component
            :is="activeSlide.url_destino ? 'a' : 'div'"
            :key="activeSlide.id || currentSlide"
            :href="activeSlide.url_destino || undefined"
            class="absolute inset-0 block"
          >
            <img
              :src="activeSlide.imagen"
              :alt="slideCaption || `Imagen destacada ${currentSlide + 1} de ${storeName}`"
              class="h-full w-full object-cover"
              :loading="currentSlide === 0 ? 'eager' : 'lazy'"
              fetchpriority="high"
              decoding="async"
            />
          </component>
        </Transition>
        <span class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/70 via-black/10 to-black/0" aria-hidden="true"></span>

        <div class="pointer-events-none absolute inset-x-0 bottom-0 z-10">
          <div class="mx-auto max-w-[110rem] px-4 pb-6 sm:px-6 sm:pb-8 lg:px-10">
            <h1 class="sf-display sf-masthead hero-title text-white">{{ storeName }}</h1>
            <div class="pointer-events-auto mt-5 flex flex-wrap items-center justify-between gap-4 border-t border-white/30 pt-4 text-white">
              <div class="flex min-w-0 items-center gap-5">
                <a href="#catalogo" class="sf-btn hero-cta">Ver {{ labels.catalogo.toLowerCase() }}</a>
                <p v-if="slideCaption" class="hidden truncate text-sm text-white/85 sm:block">{{ slideCaption }}</p>
              </div>
              <div v-if="slides.length > 1" class="flex items-center gap-3">
                <p class="sf-num text-sm text-white/85" aria-live="polite">
                  <span class="text-white">{{ pad2(currentSlide + 1) }}</span> / {{ pad2(slides.length) }}
                </p>
                <button type="button" class="hero-arrow" aria-label="Imagen anterior" @click="go(-1)">
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
                </button>
                <button type="button" class="hero-arrow" aria-label="Imagen siguiente" @click="go(1)">
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
                </button>
              </div>
            </div>
          </div>
        </div>
        <div v-if="slides.length > 1 && !reducedMotion" class="absolute inset-x-0 bottom-0 z-10 h-[3px] bg-white/15" aria-hidden="true">
          <span :key="`p-${currentSlide}-${autoplayOn}`" class="hero-progress block h-full bg-[var(--pencil)]" :class="{ 'is-running': autoplayOn }"></span>
        </div>
      </div>
    </section>

    <!-- Portada sin carrusel: el nombre en la hoja -->
    <section v-else class="mx-auto max-w-[110rem] px-4 pt-10 sm:px-6 sm:pt-14 lg:px-10">
      <h1 class="sf-display sf-masthead hero-title">{{ storeName }}</h1>
      <div class="mt-6 grid gap-6 border-t border-[var(--sf-rule)] pt-5 md:grid-cols-12">
        <p class="max-w-xl text-lg leading-relaxed text-[var(--sf-ink-2)] md:col-span-7">{{ intro }}</p>
        <div class="flex items-start gap-4 md:col-span-5 md:justify-end">
          <p class="sf-num pt-3 text-sm text-[var(--sf-mute)]">{{ rubro.plural(totalProducts) }} · {{ categorias.length }} {{ categorias.length === 1 ? 'categoría' : 'categorías' }}</p>
          <a href="#catalogo" class="sf-btn">Ver {{ labels.catalogo.toLowerCase() }}</a>
        </div>
      </div>
    </section>

    <!-- Promociones activas -->
    <section v-if="promos.length" class="sf-ticker mt-0" :class="{ 'mt-10': !slides.length }" aria-label="Promociones">
      <ul class="visually-hidden"><li v-for="(promo, index) in promos" :key="index">{{ promo }}</li></ul>
      <div class="sf-ticker-track" aria-hidden="true">
        <span v-for="(promo, index) in tickerItems" :key="index">{{ promo }}</span>
      </div>
    </section>

    <!-- Catálogo -->
    <section id="catalogo" class="mx-auto w-full max-w-[110rem] scroll-mt-16 px-4 pb-12 pt-12 sm:px-6 sm:pt-16 lg:px-10">
      <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-2">
        <h2 class="sf-display text-5xl sm:text-7xl">{{ labels.catalogo }}</h2>
        <p class="sf-num pb-1 text-sm text-[var(--sf-mute)]" aria-live="polite">
          {{ hasActiveFilters ? `${rubro.plural(filteredProducts.length)} de ${totalProducts}` : rubro.plural(totalProducts) }}
        </p>
      </div>

      <!-- Riel de filtros: todo a la vista -->
      <div class="sf-rail -mx-4 mt-6 px-4 sm:-mx-6 sm:px-6 lg:-mx-10 lg:px-10">
        <div class="flex flex-col gap-1 lg:flex-row lg:items-center lg:gap-8">
          <label class="sf-search mt-2 w-full lg:mt-0 lg:w-72 lg:shrink-0">
            <span class="visually-hidden">{{ labels.buscar }}</span>
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path stroke-linecap="round" d="m20 20-4.2-4.2"/></svg>
            <input v-model="search" type="search" :placeholder="labels.buscar" autocomplete="off" enterkeyhint="search" />
            <button v-if="search" type="button" class="sf-search-clear" aria-label="Borrar búsqueda" @click="search = ''">
              <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.3 5.3a1 1 0 0 0-1.4 1.4L8.6 10l-3.7 3.3a1 1 0 1 0 1.4 1.4L10 11.4l3.3 3.3a1 1 0 0 0 1.4-1.4L11.4 10l3.3-3.3a1 1 0 0 0-1.4-1.4L10 8.6 6.3 5.3Z"/></svg>
            </button>
          </label>

          <div class="sf-cats min-w-0 flex-1" role="group" aria-label="Categorías">
            <button type="button" class="sf-cat" :aria-pressed="selectedCategory === 'ALL'" @click="selectedCategory = 'ALL'">
              Todo<sup v-if="countsReady">{{ allProducts.length }}</sup>
              <PencilMark :active="selectedCategory === 'ALL'" />
            </button>
            <button
              v-for="cat in categorias"
              :key="cat.id"
              type="button"
              class="sf-cat"
              :aria-pressed="selectedCategory === cat.nombre"
              @click="selectedCategory = selectedCategory === cat.nombre ? 'ALL' : cat.nombre"
            >
              {{ titleCase(cat.nombre) }}<sup v-if="countsReady">{{ categoryCounts[cat.nombre] || 0 }}</sup>
              <PencilMark :active="selectedCategory === cat.nombre" />
            </button>
          </div>
        </div>

        <div class="flex items-center gap-2 border-t border-[var(--sf-rule)] py-2.5">
          <!-- Escritorio: listas cortas en el riel -->
          <div class="hidden flex-1 flex-wrap items-center gap-2 md:flex">
            <RailMenu v-if="availableSizes.length" :label="labels.variante" :value="selectedSize !== 'ALL' ? selectedSize : ''" :is-set="selectedSize !== 'ALL'">
              <template #default="{ close }">
                <div class="flex max-w-[18rem] flex-wrap gap-1.5">
                  <button type="button" class="sf-option" :aria-pressed="selectedSize === 'ALL'" @click="selectedSize = 'ALL'; close()">Todas</button>
                  <button v-for="size in availableSizes" :key="size" type="button" class="sf-option" :aria-pressed="selectedSize === size" @click="selectedSize = selectedSize === size ? 'ALL' : size; close()">{{ size }}</button>
                </div>
              </template>
            </RailMenu>

            <RailMenu label="Precio" :value="priceLabel" :is-set="minPrice !== '' || maxPrice !== ''">
              <div class="sf-field grid w-64 grid-cols-2 gap-3">
                <div>
                  <label for="r-min" class="mb-1 block text-xs font-semibold text-[var(--sf-mute)]">Desde</label>
                  <input id="r-min" v-model="minPrice" type="number" inputmode="numeric" min="0" step="1000" placeholder="0" />
                </div>
                <div>
                  <label for="r-max" class="mb-1 block text-xs font-semibold text-[var(--sf-mute)]">Hasta</label>
                  <input id="r-max" v-model="maxPrice" type="number" inputmode="numeric" min="0" step="1000" placeholder="Sin límite" />
                </div>
              </div>
              <p v-if="priceError" class="mt-2 text-xs font-semibold text-[var(--pencil)]">{{ priceError }}</p>
              <div class="mt-3 flex flex-wrap gap-1.5">
                <button v-for="range in priceRanges" :key="range.label" type="button" class="sf-option" :aria-pressed="minPrice === range.min && maxPrice === range.max" @click="minPrice = range.min; maxPrice = range.max">{{ range.label }}</button>
              </div>
            </RailMenu>

            <RailMenu label="Publicados" :value="selectedRecency !== 'ALL' ? recencyLabel[selectedRecency] : ''" :is-set="selectedRecency !== 'ALL'">
              <template #default="{ close }">
                <div class="flex flex-col items-stretch gap-1.5">
                  <button v-for="option in recencyOptions" :key="option.value" type="button" class="sf-option !justify-start" :aria-pressed="selectedRecency === option.value" @click="selectedRecency = option.value; close()">{{ option.label }}</button>
                </div>
              </template>
            </RailMenu>

            <button type="button" class="sf-toggle" :class="{ 'is-set': onlyNewCollection }" :aria-pressed="onlyNewCollection ? 'true' : 'false'" @click="onlyNewCollection = !onlyNewCollection">
              {{ labels.nuevaColeccion }}
            </button>

            <button v-if="hasActiveFilters" type="button" class="sf-btn-ghost ml-1 text-sm" @click="resetFilters">Limpiar todo</button>
          </div>

          <!-- Móvil: un solo botón abre el panel -->
          <button type="button" class="sf-toggle md:hidden" aria-haspopup="dialog" @click="filtersOpen = true">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M4 7h10M18 7h2M4 12h4M12 12h8M4 17h12"/><circle cx="16" cy="7" r="2"/><circle cx="10" cy="12" r="2"/></svg>
            Filtros
            <span v-if="advancedCount" class="sf-toggle-count">{{ advancedCount }}</span>
          </button>
          <span class="flex-1 md:hidden"></span>

          <RailMenu label="Ordenar" :value="currentSortLabel" align="right">
            <template #default="{ close }">
              <div class="flex flex-col items-stretch gap-1.5">
                <button v-for="option in sortOptions" :key="option.value" type="button" class="sf-option !justify-start" :aria-pressed="sortBy === option.value" @click="sortBy = option.value; close()">{{ option.label }}</button>
              </div>
            </template>
          </RailMenu>
        </div>

        <div v-if="activeFilters.length" class="flex flex-wrap items-center gap-x-4 gap-y-1 pb-3 md:hidden">
          <button v-for="filter in activeFilters" :key="filter.key" type="button" class="inline-flex min-h-9 items-center gap-1.5 text-sm font-semibold" :aria-label="`Quitar filtro ${filter.label}`" @click="filter.clear()">
            {{ filter.label }}
            <svg class="h-3 w-3 text-[var(--pencil)]" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.3 5.3a1 1 0 0 0-1.4 1.4L8.6 10l-3.7 3.3a1 1 0 1 0 1.4 1.4L10 11.4l3.3 3.3a1 1 0 0 0 1.4-1.4L11.4 10l3.3-3.3a1 1 0 0 0-1.4-1.4L10 8.6 6.3 5.3Z"/></svg>
          </button>
          <button type="button" class="sf-btn-ghost text-sm" @click="resetFilters">Limpiar</button>
        </div>
      </div>

      <!-- Hoja de contactos -->
      <div v-if="filteredProducts.length || loadingAll" class="sheet mt-8">
        <ProductCard
          v-for="(item, index) in filteredProducts"
          :key="item.id"
          :product="item"
          :index="index"
          :priority="index < 4 && !slides.length"
          class="frame-enter"
          :style="{ '--i': Math.min(index % 20, 12) }"
        />
        <template v-if="loadingMore || loadingAll">
          <ProductCardSkeleton v-for="n in 4" :key="`sk-${n}`" />
        </template>
      </div>

      <!-- Vacío -->
      <div v-else class="mt-8 border border-dashed border-[var(--sf-rule-strong)] px-6 py-16 text-center">
        <p class="sf-display text-4xl">{{ allProducts.length ? 'Nada con estos filtros' : 'Pronto habrá novedades' }}</p>
        <p class="mx-auto mt-3 max-w-md text-base text-[var(--sf-mute)]">
          {{ allProducts.length ? (search ? `No encontramos “${search}”. Prueba con otra palabra o quita algún filtro.` : 'Quita algún filtro o amplía el rango de precio para ver más opciones.') : 'Esta tienda todavía no tiene productos publicados. Vuelve pronto.' }}
        </p>
        <button v-if="hasActiveFilters" type="button" class="sf-btn mt-7" @click="resetFilters">Ver todo</button>
      </div>

      <!-- Cargar más -->
      <div v-if="hasMore && !hasActiveFilters" class="mt-12 flex flex-col items-center gap-3">
        <p class="sf-num text-sm text-[var(--sf-mute)]">Mostrando {{ allProducts.length }} de {{ totalProducts }}</p>
        <button type="button" class="sf-btn-line min-w-64" :disabled="loadingMore" @click="loadMore">
          <span v-if="loadingMore" class="spinner" aria-hidden="true"></span>
          {{ loadingMore ? 'Cargando…' : `Ver más ${rubro.preset.value.item[1]}` }}
        </button>
      </div>

      <!-- Banners inferiores -->
      <div v-if="bottomCatalogBanners.length" class="mt-20 grid gap-4 md:grid-cols-2">
        <a
          v-for="banner in bottomCatalogBanners"
          :key="banner.id || banner.identificador"
          :href="banner.url_destino || '#catalogo'"
          class="group relative block overflow-hidden bg-[var(--sf-wash-2)]"
          :aria-label="banner.nombre || 'Ver colección'"
        >
          <img :src="banner.imagen" alt="" class="aspect-[16/10] w-full object-cover transition duration-[1200ms] ease-[cubic-bezier(.16,1,.3,1)] group-hover:scale-[1.03]" loading="lazy" decoding="async" width="960" height="600" />
          <span v-if="banner.nombre" class="absolute bottom-0 left-0 bg-[var(--sf-paper)] px-4 py-2.5 text-sm font-semibold text-[var(--sf-ink)]">{{ banner.nombre }}</span>
        </a>
      </div>
    </section>

    <!-- Panel de filtros (móvil) -->
    <Teleport to="#contenido" defer>
      <Transition name="fade">
        <div v-if="filtersOpen" class="overlay" aria-hidden="true" @click="filtersOpen = false"></div>
      </Transition>
      <Transition name="sheet">
        <aside
          v-if="filtersOpen"
          ref="filterPanel"
          class="fixed inset-x-0 bottom-0 z-[61] flex max-h-[88vh] flex-col bg-[var(--sf-paper)] text-[var(--sf-ink)] shadow-2xl"
          role="dialog"
          aria-modal="true"
          aria-labelledby="filters-title"
          @keydown.esc="filtersOpen = false"
        >
          <div class="flex items-center justify-between border-b border-[var(--sf-rule)] px-5 py-4">
            <h2 id="filters-title" class="sf-display text-3xl">Filtros</h2>
            <button type="button" class="icon-btn -mr-2" aria-label="Cerrar filtros" @click="filtersOpen = false">
              <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.3 5.3a1 1 0 0 0-1.4 1.4L8.6 10l-3.7 3.3a1 1 0 1 0 1.4 1.4L10 11.4l3.3 3.3a1 1 0 0 0 1.4-1.4L11.4 10l3.3-3.3a1 1 0 0 0-1.4-1.4L10 8.6 6.3 5.3Z"/></svg>
            </button>
          </div>
          <div class="flex-1 overflow-y-auto px-5 py-6">
            <FilterSidebar
              :available-sizes="availableSizes"
              :size-label="labels.variante"
              :new-label="labels.nuevaColeccion"
              v-model:selected-size="selectedSize"
              v-model:selected-recency="selectedRecency"
              v-model:only-new-collection="onlyNewCollection"
              v-model:sort-by="sortBy"
              v-model:min-price="minPrice"
              v-model:max-price="maxPrice"
            />
          </div>
          <div class="grid grid-cols-2 gap-3 border-t border-[var(--sf-rule)] px-5 py-4 pb-[max(1rem,env(safe-area-inset-bottom))]">
            <button type="button" class="sf-btn-line" @click="resetFilters">Limpiar</button>
            <button type="button" class="sf-btn" @click="filtersOpen = false">Ver {{ filteredProducts.length }}</button>
          </div>
        </aside>
      </Transition>
    </Teleport>
  </AppLayout>
</template>

<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';
import FilterSidebar from '../components/FilterSidebar.vue';
import PencilMark from '../components/store/PencilMark.vue';
import ProductCard from '../components/store/ProductCard.vue';
import ProductCardSkeleton from '../components/store/ProductCardSkeleton.vue';
import RailMenu from '../components/store/RailMenu.vue';
import { useRubro } from '../composables/useRubro';
import { effectivePrice, sortOptions } from '../utils/catalog';
import { money } from '../utils/format';

const props = defineProps({
  productos: { type: Object, default: () => ({ data: [] }) },
  categorias: { type: Array, default: () => [] },
  promociones: { type: Array, default: () => [] },
  carousel: { type: Object, default: null },
  catalogBanners: { type: Array, default: () => [] },
  store: { type: Object, default: null },
});

const page = usePage();
const rubro = useRubro();
const labels = computed(() => rubro.preset.value);
const storeName = computed(() => props.store?.nombre || page.props.app?.name || 'Tienda');
const titleCase = (value) => String(value || '').toLowerCase().replace(/(^|\s)\S/g, (c) => c.toUpperCase());
const pad2 = (n) => String(n).padStart(2, '0');
const intro = computed(() => {
  const text = (page.props.app?.descripcion || '').trim();
  if (text) return text.length > 200 ? `${text.slice(0, 197).trimEnd()}…` : text;
  return labels.value.about;
});

/* ── Carrusel ── */
const slides = computed(() => (props.carousel?.activo === false ? [] : props.carousel?.imagenes || []));
const currentSlide = ref(0);
const activeSlide = computed(() => slides.value[currentSlide.value] || {});
const slideCaption = computed(() => {
  const name = activeSlide.value.nombre;
  return name && !/^Slide \d+$/i.test(name) ? name : '';
});
const autoplayOn = ref(false);
let autoplay = null;
const reducedMotion = typeof window !== 'undefined' && window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

const setSlide = (index) => { currentSlide.value = (index + slides.value.length) % slides.value.length; };
const go = (delta) => setSlide(currentSlide.value + delta);
const pauseAutoplay = () => { clearInterval(autoplay); autoplay = null; autoplayOn.value = false; };
const startAutoplay = () => {
  pauseAutoplay();
  if (!reducedMotion && slides.value.length > 1) {
    autoplay = setInterval(() => go(1), 6500);
    autoplayOn.value = true;
  }
};
watch(currentSlide, () => { if (autoplay) startAutoplay(); });
watch(slides, () => { currentSlide.value = 0; startAutoplay(); });

/* ── Banners bajo el catálogo ── */
const bannerOrder = { 'banner-izq': 1, 'banner-der': 2 };
const bottomCatalogBanners = computed(() =>
  (props.catalogBanners || [])
    .filter((b) => b.imagen && ['banner-izq', 'banner-der'].includes(b.identificador))
    .sort((a, b) => (a.posicion || bannerOrder[a.identificador] || 99) - (b.posicion || bannerOrder[b.identificador] || 99)));

/* ── Promociones ── */
const promos = computed(() => (props.promociones || []).map((p) => String(p).trim()).filter(Boolean));
const tickerItems = computed(() => {
  if (!promos.value.length) return [];
  if (reducedMotion) return promos.value;
  const base = [];
  while (base.length < 6) base.push(...promos.value);
  return [...base, ...base];
});

/* ── Productos (acumulados con "Ver más") ── */
const allProducts = ref([...(props.productos?.data || [])]);
const loadingMore = ref(false);
const loadingAll = ref(false);
const currentServerPage = computed(() => props.productos?.current_page || 1);
const lastServerPage = computed(() => props.productos?.last_page || 1);
const totalProducts = computed(() => props.productos?.total ?? allProducts.value.length);
const hasMore = computed(() => currentServerPage.value < lastServerPage.value);
const countsReady = computed(() => !hasMore.value && allProducts.value.length > 0);
const categoryCounts = computed(() => allProducts.value.reduce((acc, p) => {
  acc[p.categoria] = (acc[p.categoria] || 0) + 1;
  return acc;
}, {}));

watch(() => props.productos, (value) => {
  const incoming = value?.data || [];
  if ((value?.current_page || 1) <= 1) {
    allProducts.value = [...incoming];
    return;
  }
  const known = new Set(allProducts.value.map((p) => p.id));
  allProducts.value.push(...incoming.filter((p) => !known.has(p.id)));
});

const fetchPage = (pageNumber) => new Promise((resolve) => {
  router.reload({
    data: { page: pageNumber },
    only: ['productos'],
    preserveScroll: true,
    preserveState: true,
    replace: true,
    onFinish: resolve,
  });
});

const loadMore = async () => {
  if (loadingMore.value || !hasMore.value) return;
  loadingMore.value = true;
  await fetchPage(currentServerPage.value + 1);
  loadingMore.value = false;
};

/** Con filtros activos se cargan las páginas restantes para filtrar sobre todo el catálogo */
const loadAll = async () => {
  if (loadingAll.value) return;
  loadingAll.value = true;
  let guard = 0;
  while (hasMore.value && guard < 20) {
    await fetchPage(currentServerPage.value + 1);
    guard += 1;
  }
  loadingAll.value = false;
};

/* ── Filtros ── */
const search = ref('');
const selectedCategory = ref('ALL');
const selectedSize = ref('ALL');
const selectedRecency = ref('ALL');
const onlyNewCollection = ref(false);
const sortBy = ref('recentes');
const minPrice = ref('');
const maxPrice = ref('');
const filtersOpen = ref(false);
const filterPanel = ref(null);

const recencyOptions = [
  { value: 'ALL', label: 'Cualquier fecha' },
  { value: '7', label: 'Últimos 7 días' },
  { value: '30', label: 'Últimos 30 días' },
  { value: '90', label: 'Últimos 90 días' },
];
const recencyLabel = { 7: 'Últimos 7 días', 30: 'Últimos 30 días', 90: 'Últimos 90 días' };

const availableSizes = computed(() => {
  const sizes = new Set();
  allProducts.value.forEach((p) => (p.tallas || []).forEach((s) => sizes.add(s)));
  return [...sizes];
});

/** Rangos rápidos de precio a partir de los precios reales de la tienda */
const priceRanges = computed(() => {
  const prices = allProducts.value.map(effectivePrice).filter((n) => n > 0).sort((a, b) => a - b);
  if (prices.length < 4) return [];
  const round = (n) => {
    const step = n >= 100000 ? 10000 : n >= 10000 ? 5000 : 1000;
    return Math.max(step, Math.round(n / step) * step);
  };
  const a = round(prices[Math.floor(prices.length / 3)]);
  const b = round(prices[Math.floor((prices.length * 2) / 3)]);
  if (b <= a) return [{ label: `Hasta ${money(a)}`, min: '', max: String(a) }, { label: `Más de ${money(a)}`, min: String(a), max: '' }];
  return [
    { label: `Hasta ${money(a)}`, min: '', max: String(a) },
    { label: `${money(a)} – ${money(b)}`, min: String(a), max: String(b) },
    { label: `Más de ${money(b)}`, min: String(b), max: '' },
  ];
});

const priceError = computed(() => {
  if (minPrice.value === '' || maxPrice.value === '') return '';
  return Number(minPrice.value) > Number(maxPrice.value) ? 'El precio “desde” es mayor que “hasta”.' : '';
});
const priceLabel = computed(() => {
  if (minPrice.value !== '' && maxPrice.value !== '') return `${money(minPrice.value)} – ${money(maxPrice.value)}`;
  if (minPrice.value !== '') return `desde ${money(minPrice.value)}`;
  if (maxPrice.value !== '') return `hasta ${money(maxPrice.value)}`;
  return '';
});
const currentSortLabel = computed(() => (sortBy.value === 'recentes' ? '' : sortOptions.find((o) => o.value === sortBy.value)?.label || ''));

const normalize = (value) => String(value || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
const searchTerms = computed(() => normalize(search.value).split(/\s+/).filter(Boolean));

const hasActiveFilters = computed(() =>
  searchTerms.value.length > 0 || selectedCategory.value !== 'ALL' || selectedSize.value !== 'ALL' || selectedRecency.value !== 'ALL'
  || onlyNewCollection.value || minPrice.value !== '' || maxPrice.value !== '');

const advancedCount = computed(() => [
  selectedSize.value !== 'ALL', selectedRecency.value !== 'ALL', onlyNewCollection.value,
  minPrice.value !== '' || maxPrice.value !== '',
].filter(Boolean).length);

const activeFilters = computed(() => {
  const list = [];
  if (selectedSize.value !== 'ALL') list.push({ key: 'size', label: `${labels.value.variante} ${selectedSize.value}`, clear: () => { selectedSize.value = 'ALL'; } });
  if (priceLabel.value) list.push({ key: 'price', label: priceLabel.value.replace(/^./, (c) => c.toUpperCase()), clear: () => { minPrice.value = ''; maxPrice.value = ''; } });
  if (selectedRecency.value !== 'ALL') list.push({ key: 'rec', label: recencyLabel[selectedRecency.value], clear: () => { selectedRecency.value = 'ALL'; } });
  if (onlyNewCollection.value) list.push({ key: 'new', label: labels.value.nuevaColeccion, clear: () => { onlyNewCollection.value = false; } });
  return list;
});

const filteredProducts = computed(() => {
  let list = [...allProducts.value];
  if (searchTerms.value.length) {
    list = list.filter((p) => {
      const haystack = normalize(`${p.nombre} ${p.sku} ${p.categoria} ${p.descripcion || ''}`);
      return searchTerms.value.every((term) => haystack.includes(term));
    });
  }
  if (selectedCategory.value !== 'ALL') list = list.filter((p) => p.categoria === selectedCategory.value);
  if (selectedSize.value !== 'ALL') list = list.filter((p) => (p.tallas || []).includes(selectedSize.value));
  if (onlyNewCollection.value) list = list.filter((p) => p.nuevaColeccion);
  if (selectedRecency.value !== 'ALL') {
    const threshold = Date.now() - Number(selectedRecency.value) * 86400000;
    list = list.filter((p) => p.createdAt && new Date(p.createdAt).getTime() >= threshold);
  }
  if (minPrice.value !== '') list = list.filter((p) => effectivePrice(p) >= Number(minPrice.value));
  if (maxPrice.value !== '') list = list.filter((p) => effectivePrice(p) <= Number(maxPrice.value));

  const byName = (p) => (p.nombre || p.sku || '').toString();
  if (sortBy.value === 'precio_menor') list.sort((a, b) => effectivePrice(a) - effectivePrice(b));
  else if (sortBy.value === 'precio_mayor') list.sort((a, b) => effectivePrice(b) - effectivePrice(a));
  else if (sortBy.value === 'alfabetico_asc') list.sort((a, b) => byName(a).localeCompare(byName(b), 'es'));
  else if (sortBy.value === 'alfabetico_desc') list.sort((a, b) => byName(b).localeCompare(byName(a), 'es'));
  else list.sort((a, b) => new Date(b.createdAt || 0) - new Date(a.createdAt || 0));
  return list;
});

watch(hasActiveFilters, (active) => { if (active && hasMore.value) loadAll(); });

const resetFilters = () => {
  search.value = '';
  selectedCategory.value = 'ALL';
  selectedSize.value = 'ALL';
  selectedRecency.value = 'ALL';
  onlyNewCollection.value = false;
  sortBy.value = 'recentes';
  minPrice.value = '';
  maxPrice.value = '';
};

watch(filtersOpen, async (open) => {
  document.documentElement.style.overflow = open ? 'hidden' : '';
  if (open) {
    await nextTick();
    filterPanel.value?.querySelector('button, input, select')?.focus();
  }
});

onMounted(() => {
  startAutoplay();
  // Enlaces desde banners: /?categoria=gorras
  const param = new URLSearchParams(window.location.search).get('categoria');
  if (param) {
    const match = props.categorias.find((c) => c.nombre?.toLowerCase() === param.toLowerCase());
    if (match) selectedCategory.value = match.nombre;
  }
});

onUnmounted(() => {
  pauseAutoplay();
  document.documentElement.style.overflow = '';
});
</script>

<style scoped>
.hero-title { max-width: 100%; overflow-wrap: anywhere; }
.hero-cta { background: #fff; color: #141414; border-color: #fff; }
.hero-cta:hover:not(:disabled) { background: var(--pencil); border-color: var(--pencil); color: var(--pencil-ink); }
.hero-arrow {
  display: inline-flex;
  width: 2.75rem;
  height: 2.75rem;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255, 255, 255, 0.45);
  background: transparent;
  color: #fff;
  transition: background 160ms ease, color 160ms ease;
}
.hero-arrow:hover { background: #fff; color: #141414; }
.hero-progress { width: 0; }
.hero-progress.is-running { animation: hero-progress 6.5s linear forwards; }
@keyframes hero-progress { to { width: 100%; } }

/* Cambio de foto: la nueva se "revela" como en la cubeta */
.develop-enter-active { transition: opacity 900ms ease, filter 1200ms cubic-bezier(0.16, 1, 0.3, 1), transform 1600ms cubic-bezier(0.16, 1, 0.3, 1); }
.develop-leave-active { transition: opacity 700ms ease; }
.develop-enter-from { opacity: 0; filter: contrast(0.6) brightness(1.25); transform: scale(1.04); }
.develop-leave-to { opacity: 0; }
</style>
