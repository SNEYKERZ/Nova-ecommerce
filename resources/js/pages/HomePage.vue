<template>
  <AppLayout>
    <Head title="Inicio" />

    <section v-if="carouselImages.length" class="w-full">
      <div class="relative overflow-hidden bg-black">
        <div class="relative h-[40vh] min-h-[280px] sm:h-[55vh] lg:h-[85vh]">
          <transition name="fade" mode="out-in">
            <button
              :key="activeSlide.id || currentSlide"
              type="button"
              class="absolute inset-0 block h-full w-full cursor-default"
              :class="activeSlide.url_destino ? 'cursor-pointer' : ''"
              @click="openSlide(activeSlide.url_destino)"
            >
              <img
                :src="activeSlide.imagen"
                :alt="`Slide ${currentSlide + 1}`"
                class="h-full w-full object-cover"
                loading="eager"
              />
              <div class="absolute inset-0 bg-gradient-to-t from-black/45 via-black/10 to-black/20"></div>
            </button>
          </transition>

          <div v-if="carouselImages.length > 1" class="pointer-events-none absolute inset-x-0 bottom-5 flex justify-center">
            <div class="pointer-events-auto flex items-center gap-2 rounded-full bg-black/30 px-3 py-2 backdrop-blur-sm">
              <button
                v-for="(slide, index) in carouselImages"
                :key="slide.id || index"
                type="button"
                class="h-2.5 rounded-full transition-all duration-300"
                :class="currentSlide === index ? 'w-8 bg-white' : 'w-2.5 bg-white/45'"
                @click="setSlide(index)"
              />
            </div>
          </div>
        </div>
      </div>
    </section>

    <section v-if="promociones.length" class="w-full mt-8 px-0">
      <div class="overflow-hidden rounded-xl border-2 border-amber-300 bg-gradient-to-r from-amber-50 to-orange-50 shadow-md">
        <div class="px-6 py-4 sm:px-8 sm:py-5">
          <p class="text-xs font-bold text-amber-700 uppercase tracking-widest mb-2">📢 Promociones Activas</p>
          <div class="animate-pulse text-sm sm:text-base md:text-lg font-bold text-amber-900 leading-relaxed">
            {{ promociones.join(' • ') }}
          </div>
        </div>
      </div>
    </section>

    <section id="catalogo" class="mt-12 w-full px-4 pb-12 sm:px-6 lg:px-8">
      <div class="mb-5 flex flex-wrap items-end justify-between gap-4 border-b border-black/10 pb-4">
        <div>
          <p class="text-[10px] font-bold tracking-[0.22em] text-[color:var(--muted)] uppercase sm:text-[11px]">Catalogo</p>
          <h2 class="font-display mt-1 text-2xl font-bold sm:mt-2 sm:text-3xl md:text-4xl">Productos listos para vender</h2>
        </div>

        <div class="flex items-center gap-3">
          <p class="text-sm font-semibold text-[color:var(--muted)]">{{ filteredProducts.length }} resultados</p>
          <button class="btn-soft px-4 py-2 text-xs font-bold tracking-[0.12em] uppercase" @click="filtersOpen = !filtersOpen">
            {{ filtersOpen ? 'Ocultar filtros' : 'Mostrar filtros' }}
          </button>
        </div>
      </div>

      <transition name="slide-up">
        <div v-if="filtersOpen" class="mb-6 rounded-2xl border border-black/10 bg-[color:var(--surface)] p-4 sm:p-5">
          <FilterSidebar
            :categorias="categorias"
            :available-sizes="availableSizes"
            v-model:selected-category="selectedCategory"
            v-model:selected-size="selectedSize"
            v-model:selected-recency="selectedRecency"
            v-model:only-new-collection="onlyNewCollection"
            v-model:sort-by="sortBy"
            v-model:min-price="minPrice"
            v-model:max-price="maxPrice"
            @reset="resetFilters"
          />
        </div>
      </transition>

      <div class="grid grid-cols-2 gap-x-1.5 gap-y-5 sm:grid-cols-3 sm:gap-x-3 sm:gap-y-7 md:grid-cols-4 md:gap-x-4 md:gap-y-8 lg:grid-cols-5 xl:gap-x-5">
        <article v-for="item in paginatedProducts" :key="item.id" class="group">
          <Link :href="`/productos/${item.id}`" class="relative block overflow-hidden bg-[#efefee]">
            <img :src="item.foto" :alt="item.sku" loading="lazy" class="aspect-[3/4] w-full object-cover transition duration-500 group-hover:scale-[1.03]" />

            <div class="pointer-events-none absolute inset-x-0 bottom-0 flex translate-y-full items-center gap-2 bg-black/90 p-2 text-white opacity-0 transition duration-300 group-hover:translate-y-0 group-hover:opacity-100">
              <button v-if="whatsappPhone" :href="whatsappLink(item)" @click.prevent="openWhatsapp(item)" class="pointer-events-auto btn-soft !border-white/20 !bg-white/10 px-3 py-2 text-[10px] font-bold tracking-[0.08em] !text-white uppercase" title="Pedir por WhatsApp">
                WhatsApp
              </button>
              <button class="pointer-events-auto btn-main flex-1 px-3 py-2 text-[10px] font-bold tracking-[0.08em] uppercase" @click.prevent="addToCart(item)">Agregar</button>
            </div>
          </Link>

          <div class="pt-3">
            <h3 class="truncate text-[15px] font-semibold text-[color:var(--ink)]">{{ item.nombre || item.sku }}</h3>
            <p class="mt-1 text-[11px] tracking-[0.09em] text-[color:var(--muted)] uppercase">{{ item.categoria }} · Ref {{ item.sku }}</p>
            <p class="mt-2 text-base font-bold">{{ money(item.precio) }}</p>
          </div>
        </article>
      </div>

      <div v-if="bottomCatalogBanners.length" class="mt-8 -mx-4 sm:-mx-6 lg:-mx-8">
        <div class="grid gap-0 md:grid-cols-2">
          <a
            v-for="banner in bottomCatalogBanners"
            :key="banner.id || banner.identificador"
            :href="banner.url_destino || '#catalogo'"
            class="group relative block overflow-hidden"
          >
            <img :src="banner.imagen" :alt="banner.nombre || banner.identificador" class="h-[260px] w-full object-cover transition duration-500 group-hover:scale-[1.03] sm:h-[340px]" loading="lazy" />
            <div class="absolute inset-0 bg-gradient-to-r from-black/35 to-black/10"></div>
          </a>
        </div>
      </div>

      <div v-if="totalPages > 1" class="mt-8 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-[color:var(--muted)]">Pagina {{ currentPage }} de {{ totalPages }}</p>
        <div class="flex flex-wrap gap-2">
          <button class="btn-soft px-4 py-2 text-xs font-bold uppercase" :disabled="currentPage === 1" @click="goToPage(currentPage - 1)">Anterior</button>
          <button
            v-for="pageNumber in visiblePages"
            :key="pageNumber"
            class="rounded-full px-4 py-2 text-xs font-bold uppercase transition"
            :class="pageNumber === currentPage ? 'bg-[color:var(--ink)] text-white' : 'bg-white text-[color:var(--ink)] border border-black/10'"
            @click="goToPage(pageNumber)"
          >
            {{ pageNumber }}
          </button>
          <button class="btn-soft px-4 py-2 text-xs font-bold uppercase" :disabled="currentPage === totalPages" @click="goToPage(currentPage + 1)">Siguiente</button>
        </div>
      </div>

      <div v-if="!filteredProducts.length" class="mt-10 rounded-2xl border border-dashed border-[color:var(--line)] bg-[color:var(--surface)] p-10 text-center text-[color:var(--muted)]">
        No encontramos productos con esos filtros. Prueba limpiarlos o ajustar el rango de precio.
      </div>
    </section>

    <div v-if="toast" class="fixed right-5 bottom-5 z-50 rounded-full bg-[color:var(--surface-dark)] px-4 py-2 text-sm font-semibold text-white shadow-2xl">{{ toast }}</div>
  </AppLayout>
</template>

<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';
import FilterSidebar from '../components/FilterSidebar.vue';

const PRODUCTS_PER_PAGE = 10;

const props = defineProps({
  productos: { type: Array, default: () => [] },
  categorias: { type: Array, default: () => [] },
  promociones: { type: Array, default: () => [] },
  carousel: { type: Object, default: null },
  catalogBanners: { type: Array, default: () => [] },
  store: { type: Object, default: null },
});

const page = usePage();
const whatsappPhone = computed(() => page.props.app?.whatsapp || props.store?.telefono || null);
const selectedCategory = ref('ALL');
const selectedSize = ref('ALL');
const selectedRecency = ref('ALL');
const onlyNewCollection = ref(false);
const sortBy = ref('recentes');
const minPrice = ref('');
const maxPrice = ref('');
const toast = ref('');
const filtersOpen = ref(false);
const currentSlide = ref(0);
const currentPage = ref(1);
let autoplayInterval = null;
const bannerOrder = { 'banner-izq': 1, 'banner-der': 2 };

const carouselImages = computed(() => props.carousel?.imagenes || []);
const activeSlide = computed(() => carouselImages.value[currentSlide.value] || {});
const bottomCatalogBanners = computed(() =>
  [...props.catalogBanners]
    .filter((banner) => ['banner-izq', 'banner-der'].includes(banner.identificador))
    .sort((left, right) => (left.posicion || bannerOrder[left.identificador] || 99) - (right.posicion || bannerOrder[right.identificador] || 99)),
);

const whatsappLink = (item) => {
  const phone = whatsappPhone.value?.replace(/\D/g, '');
  if (!phone) return '#';
  const msg = encodeURIComponent(`Hola! Me interesa el producto: ${item.nombre || item.sku} - ${money(item.precio)}`);
  return `https://wa.me/${phone}?text=${msg}`;
};

const openWhatsapp = (item) => {
  const url = whatsappLink(item);
  if (url !== '#') window.open(url, '_blank', 'noopener');
};

const openSlide = (url) => {
  if (url) window.location.href = url;
};

const setSlide = (index) => {
  currentSlide.value = index;
};

const nextSlide = () => {
  if (carouselImages.value.length <= 1) return;
  currentSlide.value = (currentSlide.value + 1) % carouselImages.value.length;
};

const startAutoplay = () => {
  if (autoplayInterval) clearInterval(autoplayInterval);
  if (carouselImages.value.length > 1) {
    autoplayInterval = setInterval(nextSlide, 2000);
  }
};

onMounted(startAutoplay);
onUnmounted(() => {
  if (autoplayInterval) clearInterval(autoplayInterval);
});

watch(carouselImages, () => {
  currentSlide.value = 0;
  startAutoplay();
});

const availableSizes = computed(() => {
  const sizes = new Set();
  props.productos.forEach((product) => (product.tallas || []).forEach((size) => sizes.add(size)));
  return [...sizes];
});

const filteredProducts = computed(() => {
  let list = [...props.productos];

  if (selectedCategory.value !== 'ALL') list = list.filter((p) => p.categoria === selectedCategory.value);
  if (selectedSize.value !== 'ALL') list = list.filter((p) => (p.tallas || []).includes(selectedSize.value));
  if (onlyNewCollection.value) list = list.filter((p) => p.nuevaColeccion);

  if (selectedRecency.value !== 'ALL') {
    const days = Number(selectedRecency.value);
    const threshold = new Date();
    threshold.setDate(threshold.getDate() - days);
    list = list.filter((p) => p.createdAt && new Date(p.createdAt) >= threshold);
  }

  if (minPrice.value !== '') list = list.filter((p) => Number(p.precio) >= Number(minPrice.value));
  if (maxPrice.value !== '') list = list.filter((p) => Number(p.precio) <= Number(maxPrice.value));

  if (sortBy.value === 'precio_menor') list.sort((a, b) => Number(a.precio) - Number(b.precio));
  else if (sortBy.value === 'precio_mayor') list.sort((a, b) => Number(b.precio) - Number(a.precio));
  else if (sortBy.value === 'alfabetico_asc') list.sort((a, b) => a.sku.localeCompare(b.sku));
  else if (sortBy.value === 'alfabetico_desc') list.sort((a, b) => b.sku.localeCompare(a.sku));
  else list.sort((a, b) => new Date(b.createdAt || 0) - new Date(a.createdAt || 0));

  return list;
});

const totalPages = computed(() => Math.max(1, Math.ceil(filteredProducts.value.length / PRODUCTS_PER_PAGE)));
const paginatedProducts = computed(() => {
  const start = (currentPage.value - 1) * PRODUCTS_PER_PAGE;
  return filteredProducts.value.slice(start, start + PRODUCTS_PER_PAGE);
});

const visiblePages = computed(() => {
  const pages = [];
  const start = Math.max(1, currentPage.value - 2);
  const end = Math.min(totalPages.value, start + 4);

  for (let pageNumber = start; pageNumber <= end; pageNumber += 1) {
    pages.push(pageNumber);
  }

  return pages;
});

const goToPage = (pageNumber) => {
  currentPage.value = Math.min(Math.max(pageNumber, 1), totalPages.value);
  document.getElementById('catalogo')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

watch([selectedCategory, selectedSize, selectedRecency, onlyNewCollection, sortBy, minPrice, maxPrice], () => {
  currentPage.value = 1;
});

watch(totalPages, (value) => {
  if (currentPage.value > value) currentPage.value = value;
});

const resetFilters = () => {
  selectedCategory.value = 'ALL';
  selectedSize.value = 'ALL';
  selectedRecency.value = 'ALL';
  onlyNewCollection.value = false;
  sortBy.value = 'recentes';
  minPrice.value = '';
  maxPrice.value = '';
  currentPage.value = 1;
};

const money = (value) => new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(value || 0);
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

const addToCart = async (product) => {
  try {
    const res = await fetch('/api/carrito/agregar', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrf(),
      },
      body: JSON.stringify({
        producto_id: product.id,
        cantidad: 1,
        talla: product.tallas?.[0] ?? null,
      }),
    });

    const payload = await res.json();
    if (!payload.success) throw new Error(payload.message || 'No se pudo agregar');

    toast.value = 'Producto agregado al carrito';
    window.dispatchEvent(new Event('cart-updated'));
    setTimeout(() => {
      toast.value = '';
    }, 1800);
  } catch {
    toast.value = 'No fue posible agregar el producto';
    setTimeout(() => {
      toast.value = '';
    }, 1800);
  }
};
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.6s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
