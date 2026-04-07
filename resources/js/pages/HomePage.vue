<template>
  <AppLayout>
    <Head title="Inicio" />
    <section class="mx-auto grid w-full max-w-7xl gap-8 px-4 pt-10 sm:px-6 lg:grid-cols-5 lg:px-8">
      <div class="glass rounded-3xl p-8 lg:col-span-3">
        <p class="chip inline-flex bg-[color:var(--sand)]">E-commerce base</p>
        <h1 class="font-display mt-5 text-4xl leading-tight font-bold sm:text-5xl">
          Vende cualquier producto con una vitrina moderna y fluida.
        </h1>
        <p class="mt-4 max-w-2xl text-base text-[color:var(--muted)] sm:text-lg">
          Arquitectura Laravel + Vue + Inertia para una experiencia SPA sin recargas, diseñada para escalar de moda a tecnología, hogar o belleza.
        </p>
        <div class="mt-7 flex flex-wrap gap-3">
          <a href="#catalogo" class="btn-main px-6 py-3 text-sm font-bold">Explorar catalogo</a>
          <Link href="/conocenos" class="btn-soft px-6 py-3 text-sm font-semibold">Conocer la plataforma</Link>
        </div>
      </div>

      <div class="relative overflow-hidden rounded-3xl bg-[color:var(--surface-dark)] p-8 text-white lg:col-span-2">
        <div class="absolute -right-16 -top-14 h-44 w-44 rounded-full bg-[color:var(--brand)]/40 blur-2xl"></div>
        <p class="text-sm text-white/70">Coleccion destacada</p>
        <h2 class="font-display mt-2 text-2xl font-bold">Lanzamientos de temporada</h2>
        <p class="mt-3 text-sm text-white/80">Visual premium, cards limpias y enfoque mobile first.</p>
        <div class="mt-6 space-y-3 text-sm text-white/90">
          <p>• Navegacion instantanea con Inertia</p>
          <p>• Filtros listos para escalar</p>
          <p>• Checkout conectado a API existente</p>
        </div>
      </div>
    </section>

    <section v-if="promociones.length" class="mx-auto mt-8 w-full max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="overflow-hidden rounded-2xl border border-[color:var(--line)] bg-[color:var(--surface)]">
        <div class="animate-pulse px-5 py-3 text-sm font-semibold text-[color:var(--ink)]/90 sm:text-base">
          {{ promociones.join(' • ') }}
        </div>
      </div>
    </section>

    <section id="catalogo" class="mx-auto mt-12 w-full max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
          <p class="text-xs font-semibold tracking-[0.2em] text-[color:var(--muted)] uppercase">Catalogo</p>
          <h2 class="font-display mt-2 text-3xl font-bold">Productos listos para vender</h2>
        </div>
        <div class="flex flex-wrap gap-2">
          <button
            class="chip cursor-pointer"
            :class="selectedCategory === 'ALL' ? 'bg-[color:var(--ink)] text-white' : 'bg-[color:var(--surface)]'"
            @click="selectedCategory = 'ALL'"
          >
            Todo
          </button>
          <button
            v-for="cat in categorias"
            :key="cat.id"
            class="chip cursor-pointer"
            :class="selectedCategory === cat.nombre ? 'bg-[color:var(--ink)] text-white' : 'bg-[color:var(--surface)]'"
            @click="selectedCategory = cat.nombre"
          >
            {{ cat.nombre }}
          </button>
        </div>
      </div>

      <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article
          v-for="item in filteredProducts"
          :key="item.id"
          class="group overflow-hidden rounded-3xl border border-black/5 bg-[color:var(--surface)] shadow-[0_10px_40px_rgba(0,0,0,0.04)]"
        >
          <Link :href="`/productos/${item.id}`" class="block overflow-hidden">
            <img :src="item.foto" :alt="item.sku" class="h-64 w-full object-cover transition duration-500 group-hover:scale-105" />
          </Link>

          <div class="space-y-3 p-4">
            <div class="flex items-center justify-between gap-2">
              <p class="truncate text-sm font-semibold text-[color:var(--muted)]">{{ item.categoria }}</p>
              <p class="chip bg-[color:var(--sand)]">{{ item.nuevo ? 'Nuevo' : 'Stock' }}</p>
            </div>

            <div>
              <h3 class="font-display truncate text-lg font-semibold">{{ item.sku }}</h3>
              <p class="mt-1 text-sm text-[color:var(--muted)] line-clamp-2">{{ item.descripcion }}</p>
            </div>

            <div class="flex items-center justify-between">
              <p class="text-xl font-bold">{{ money(item.precio) }}</p>
              <button class="btn-main px-4 py-2 text-xs font-bold tracking-wide uppercase" @click="addToCart(item)">
                Agregar
              </button>
            </div>
          </div>
        </article>
      </div>

      <div v-if="!filteredProducts.length" class="mt-10 rounded-2xl border border-dashed border-[color:var(--line)] bg-[color:var(--surface)] p-10 text-center text-[color:var(--muted)]">
        No hay productos en esta categoria por ahora.
      </div>
    </section>

    <div
      v-if="toast"
      class="fixed right-5 bottom-5 z-50 rounded-full bg-[color:var(--surface-dark)] px-4 py-2 text-sm font-semibold text-white shadow-2xl"
    >
      {{ toast }}
    </div>
  </AppLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';

const props = defineProps({
  productos: { type: Array, default: () => [] },
  categorias: { type: Array, default: () => [] },
  promociones: { type: Array, default: () => [] },
});

const selectedCategory = ref('ALL');
const toast = ref('');

const filteredProducts = computed(() => {
  if (selectedCategory.value === 'ALL') return props.productos;
  return props.productos.filter((p) => p.categoria === selectedCategory.value);
});

const money = (value) =>
  new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(value || 0);

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

    if (!payload.success) {
      throw new Error(payload.message || 'No se pudo agregar al carrito');
    }

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
