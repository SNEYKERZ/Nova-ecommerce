<template>
  <AdminLayout :nav="navItems" :active="section" variant="store" @navigate="setSection">
    <Head :title="currentLabel" />

    <OverviewSection v-if="section === 'resumen'" :stats="stats" :pedidos="pedidosList" :insumos="insumosList" :productos="productosList" :settings="settings" @navigate="setSection" />
    <OrdersSection v-else-if="section === 'pedidos'" :pedidos="pedidos" :product-options="productOptions" />
    <ProductsSection v-else-if="section === 'productos'" :productos="productos" :categorias="categorias" :search="search" @search="onSearch" />
    <OffersSection v-else-if="section === 'ofertas'" :ofertas="ofertas" :categorias="categorias" :product-options="productOptions" :search="search" @search="onSearch" />
    <CouponsSection v-else-if="section === 'cupones'" :cupones="cupones" />
    <SuppliesSection v-else-if="section === 'insumos'" :insumos="insumos" :search="search" @search="onSearch" />
    <ContentSection v-else-if="section === 'contenido'" :bloques="bloques" :catalog-banners="catalogBanners" :noticia="noticia" />
    <SettingsSection v-else-if="section === 'ajustes'" :settings="settings" />
  </AdminLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import AdminLayout from '../../layouts/AdminLayout.vue';
import { storeNav } from '../../components/admin/adminNav';
import OverviewSection from '../../components/admin/sections/OverviewSection.vue';
import OrdersSection from '../../components/admin/sections/OrdersSection.vue';
import ProductsSection from '../../components/admin/sections/ProductsSection.vue';
import OffersSection from '../../components/admin/sections/OffersSection.vue';
import CouponsSection from '../../components/admin/sections/CouponsSection.vue';
import SuppliesSection from '../../components/admin/sections/SuppliesSection.vue';
import ContentSection from '../../components/admin/sections/ContentSection.vue';
import SettingsSection from '../../components/admin/sections/SettingsSection.vue';

/* Mismas props que entrega AdminController@dashboard (sin cambios de contrato) */
const props = defineProps({
  stats: { type: Object, required: true },
  categorias: { type: Array, default: () => [] },
  productos: { type: [Object, Array], default: () => ({ data: [] }) },
  insumos: { type: [Object, Array], default: () => ({ data: [] }) },
  ofertas: { type: [Object, Array], default: () => ({ data: [] }) },
  pedidos: { type: [Object, Array], default: () => ({ data: [] }) },
  cupones: { type: [Object, Array], default: () => ({ data: [] }) },
  noticia: { type: String, default: '' },
  bloques: { type: Object, default: () => ({}) },
  catalogBanners: { type: Object, default: () => ({}) },
  settings: { type: Object, default: () => ({ store_name: 'Mi tienda', logo_url: null }) },
  currentStore: { type: Object, default: null },
  /** Opcional: listado completo de productos para selectores (si el backend lo envía) */
  productosOptions: { type: Array, default: null },
  filters: { type: Object, default: () => ({ search: '' }) },
});

const SECTIONS = storeNav.filter((item) => !item.href).map((item) => item.key);
const list = (paged) => (Array.isArray(paged) ? paged : paged?.data || []);

const pedidosList = computed(() => list(props.pedidos));
const productosList = computed(() => list(props.productos));
const insumosList = computed(() => list(props.insumos));

/** Para selectores de producto (pedidos y ofertas): lista completa si existe; si no, la página actual */
const productOptions = computed(() => props.productosOptions || productosList.value);

const pendingCount = computed(() => props.stats?.pedidosPendientes ?? pedidosList.value.filter((p) => p.estado === 'PENDIENTE').length);
const navItems = computed(() => storeNav.map((item) => (item.key === 'pedidos' && pendingCount.value ? { ...item, badge: pendingCount.value } : item)));

const readSection = () => {
  const value = new URLSearchParams(window.location.search).get('seccion');
  return SECTIONS.includes(value) ? value : 'resumen';
};
const section = ref('resumen');
const currentLabel = computed(() => storeNav.find((i) => i.key === section.value)?.label || 'Panel');

const search = ref(props.filters?.search || new URLSearchParams(window.location.search).get('search') || '');

/** Cambia de sección sin recargar: la URL guarda ?seccion= para volver al mismo lugar tras recargar */
const setSection = (key) => {
  if (!SECTIONS.includes(key)) return;
  section.value = key;
  const url = new URL(window.location.href);
  url.searchParams.set('seccion', key);
  window.history.replaceState(window.history.state, '', url.pathname + url.search);
  window.scrollTo({ top: 0 });
};

/** Búsqueda del servidor (el backend aplica ?search a productos, insumos y ofertas) */
const onSearch = (value) => {
  search.value = value;
  const data = { seccion: section.value };
  if (value) data.search = value;
  router.get('/admin', data, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
    only: ['productos', 'insumos', 'ofertas'],
  });
};

onMounted(() => { section.value = readSection(); });
</script>
