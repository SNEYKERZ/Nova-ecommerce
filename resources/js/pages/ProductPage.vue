<template>
  <AppLayout>
    <Head title="Producto" />
    <section class="mx-auto grid w-full max-w-7xl gap-8 px-4 pt-10 sm:px-6 lg:grid-cols-2 lg:px-8">
      <div class="space-y-4">
        <div class="overflow-hidden rounded-3xl border border-black/5 bg-[color:var(--surface)]">
          <img :src="activeImage" :alt="producto.sku" class="h-[30rem] w-full object-cover" />
        </div>

        <div v-if="gallery.length" class="grid grid-cols-4 gap-3">
          <button
            v-for="(img, index) in gallery"
            :key="index"
            class="overflow-hidden rounded-2xl border border-[color:var(--line)]"
            @click="activeImage = img"
          >
            <img :src="img" alt="Galeria" class="h-24 w-full object-cover" />
          </button>
        </div>
      </div>

      <div class="space-y-6 rounded-3xl border border-black/5 bg-[color:var(--surface)] p-7">
        <p class="chip inline-flex bg-[color:var(--sand)]">{{ producto.categoria }}</p>
        <h1 class="font-display text-4xl leading-tight font-bold">{{ producto.sku }}</h1>
        <p class="text-base text-[color:var(--muted)]">{{ producto.descripcion }}</p>
        <p class="text-3xl font-extrabold">{{ money(producto.precio) }}</p>

        <div v-if="producto.tallas.length" class="space-y-2">
          <p class="text-sm font-semibold">Selecciona una variante</p>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="size in producto.tallas"
              :key="size"
              class="chip cursor-pointer"
              :class="selectedSize === size ? 'bg-[color:var(--ink)] text-white' : 'bg-[color:var(--surface)]'"
              @click="selectedSize = size"
            >
              {{ size }}
            </button>
          </div>
        </div>

        <div class="flex flex-wrap gap-3">
          <button class="btn-main px-6 py-3 text-sm font-bold uppercase" @click="addToCart" :disabled="loading">
            {{ loading ? 'Agregando...' : 'Agregar al carrito' }}
          </button>
          <Link href="/carrito" class="btn-soft px-6 py-3 text-sm font-semibold">Ir al carrito</Link>
        </div>

        <p v-if="message" class="rounded-xl bg-[color:var(--sand)] px-4 py-3 text-sm font-semibold">
          {{ message }}
        </p>
      </div>
    </section>
  </AppLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';

const props = defineProps({
  producto: { type: Object, required: true },
});

const gallery = props.producto.galeria?.length ? props.producto.galeria : [props.producto.foto];
const activeImage = ref(gallery[0]);
const selectedSize = ref(props.producto.tallas?.[0] ?? null);
const loading = ref(false);
const message = ref('');

const money = (value) =>
  new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(value || 0);

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

const addToCart = async () => {
  loading.value = true;
  message.value = '';

  try {
    const res = await fetch('/api/carrito/agregar', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrf(),
      },
      body: JSON.stringify({
        producto_id: props.producto.id,
        cantidad: 1,
        talla: selectedSize.value,
      }),
    });

    const payload = await res.json();

    if (!payload.success) {
      throw new Error(payload.message || 'No se pudo agregar al carrito');
    }

    message.value = 'Producto agregado correctamente';
    window.dispatchEvent(new Event('cart-updated'));
  } catch {
    message.value = 'No fue posible agregar este producto';
  } finally {
    loading.value = false;
  }
};
</script>
