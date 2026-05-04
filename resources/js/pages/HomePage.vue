<template>
  <AppLayout>
    <Head title="Inicio" />
    <section class="mx-auto grid w-full max-w-7xl gap-8 px-4 pt-10 sm:px-6 lg:grid-cols-5 lg:px-8">
      <div class="glass rounded-3xl p-8 lg:col-span-3">
        <p class="chip inline-flex bg-[color:var(--sand)]">E-commerce base</p>
        <h1 class="font-display mt-5 text-4xl leading-tight font-bold sm:text-5xl">Vende cualquier producto con una vitrina moderna y fluida.</h1>
        <p class="mt-4 max-w-2xl text-base text-[color:var(--muted)] sm:text-lg">Arquitectura Laravel + Vue + Inertia para una experiencia SPA sin recargas, disenada para escalar por categorias, colecciones y temporadas.</p>
        <div class="mt-7 flex flex-wrap gap-3">
          <a href="#catalogo" class="btn-main px-6 py-3 text-sm font-bold">Explorar catalogo</a>
          <Link href="/conocenos" class="btn-soft px-6 py-3 text-sm font-semibold">Conocer la plataforma</Link>
        </div>
      </div>

      <div class="relative overflow-hidden rounded-3xl bg-[color:var(--surface-dark)] p-8 text-white lg:col-span-2">
        <div class="absolute -right-16 -top-14 h-44 w-44 rounded-full bg-[color:var(--brand)]/40 blur-2xl"></div>
        <p class="text-sm text-white/70">Coleccion destacada</p>
        <h2 class="font-display mt-2 text-2xl font-bold">Lanzamientos de temporada</h2>
        <p class="mt-3 text-sm text-white/80">Visual premium, navegacion fluida y control total desde el panel admin.</p>
      </div>
    </section>

    <section v-if="promociones.length" class="mx-auto mt-8 w-full max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="overflow-hidden rounded-2xl border border-[color:var(--line)] bg-[color:var(--surface)]">
        <div class="animate-pulse px-5 py-3 text-sm font-semibold text-[color:var(--ink)]/90 sm:text-base">{{ promociones.join(' • ') }}</div>
      </div>
    </section>

    <section id="catalogo" class="mt-12 w-full px-4 pb-12 sm:px-6 lg:px-8">
      <div class="mb-5 flex flex-wrap items-end justify-between gap-4 border-b border-black/10 pb-4">
        <div>
          <p class="text-[11px] font-bold tracking-[0.22em] text-[color:var(--muted)] uppercase">Catalogo</p>
          <h2 class="font-display mt-2 text-3xl font-bold sm:text-4xl">Productos listos para vender</h2>
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

      <div class="grid grid-cols-2 gap-x-3 gap-y-8 md:grid-cols-3 md:gap-x-4 xl:grid-cols-4">
        <article v-for="item in filteredProducts" :key="item.id" class="group">
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

      <div v-if="!filteredProducts.length" class="mt-10 rounded-2xl border border-dashed border-[color:var(--line)] bg-[color:var(--surface)] p-10 text-center text-[color:var(--muted)]">
        No encontramos productos con esos filtros. Prueba limpiarlos o ajustar el rango de precio.
      </div>
    </section>

    <div v-if="toast" class="fixed right-5 bottom-5 z-50 rounded-full bg-[color:var(--surface-dark)] px-4 py-2 text-sm font-semibold text-white shadow-2xl">{{ toast }}</div>
  </AppLayout>
</template>

<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';
import FilterSidebar from '../components/FilterSidebar.vue';

const props = defineProps({
  productos: { type: Array, default: () => [] },
  categorias: { type: Array, default: () => [] },
  promociones: { type: Array, default: () => [] },
  store: { type: Object, default: null },
});

const page = usePage();
const whatsappPhone = computed(() => page.props.app?.whatsapp || props.store?.telefono || null);

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

const selectedCategory = ref('ALL');
const selectedSize = ref('ALL');
const selectedRecency = ref('ALL');
const onlyNewCollection = ref(false);
const sortBy = ref('recentes');
const minPrice = ref('');
const maxPrice = ref('');
const toast = ref('');
const filtersOpen = ref(false);

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

const resetFilters = () => {
  selectedCategory.value = 'ALL';
  selectedSize.value = 'ALL';
  selectedRecency.value = 'ALL';
  onlyNewCollection.value = false;
  sortBy.value = 'recentes';
  minPrice.value = '';
  maxPrice.value = '';
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
    setTimeout(() => (toast.value = ''), 1800);
  } catch {
    toast.value = 'No fue posible agregar el producto';
    setTimeout(() => (toast.value = ''), 1800);
  }
};
</script>
