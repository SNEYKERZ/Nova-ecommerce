<template>
  <AppLayout>
    <Head title="Producto" />
    <section class="mx-auto grid w-full max-w-7xl gap-6 px-4 pt-8 sm:gap-8 sm:px-6 sm:pt-10 lg:grid-cols-2 lg:px-8">
      <div class="space-y-3 sm:space-y-4">
        <div class="rounded-2xl border border-black/5 bg-[color:var(--surface)] sm:rounded-3xl product-image-zoom">
          <img :src="activeImage" :alt="producto.sku" class="h-72 w-full object-cover sm:h-[30rem]" />
        </div>

        <div v-if="gallery.length" class="grid grid-cols-4 gap-3">
          <button
            v-for="(img, index) in gallery"
            :key="index"
            class="overflow-hidden rounded-xl border border-[color:var(--line)] sm:rounded-2xl"
            @click="activeImage = img"
          >
            <img :src="img" alt="Galeria" class="h-16 w-full object-cover sm:h-24" />
          </button>
        </div>
      </div>

      <div class="space-y-4 rounded-2xl border border-black/5 bg-[color:var(--surface)] p-5 sm:space-y-6 sm:rounded-3xl sm:p-7 md:sticky md:top-6 md:h-fit">
        <p class="chip inline-flex bg-[color:var(--sand)]">{{ producto.categoria }}</p>
        <h1 class="font-display text-2xl leading-tight font-bold sm:text-4xl">{{ producto.nombre || producto.sku }}</h1>
        <p class="text-xs text-[color:var(--muted)]/60 -mt-1">Ref: {{ producto.sku }}</p>
        <p v-if="producto.descripcion" class="text-sm text-[color:var(--muted)] sm:text-base">{{ producto.descripcion }}</p>

        <!-- Precio con oferta -->
        <div class="space-y-1">
          <div v-if="producto.tieneOferta" class="flex items-baseline gap-3">
            <p class="text-sm line-through text-[color:var(--muted)]">{{ money(producto.precio) }}</p>
            <p class="text-2xl font-extrabold sm:text-3xl" style="color: var(--accent)">{{ money(producto.precioOferta) }}</p>
            <span class="chip-sale text-xs">−{{ producto.descuentoPct }}%</span>
          </div>
          <div v-else class="text-2xl font-extrabold sm:text-3xl">{{ money(producto.precio) }}</div>
        </div>

        <!-- Stock -->
        <div v-if="!producto.tieneStock" class="rounded-lg border border-[color:var(--accent)] bg-[color:var(--accent)]/10 px-4 py-3">
          <p class="text-sm font-bold" style="color: var(--accent)">Producto agotado</p>
        </div>

        <!-- Selector de talla -->
        <div v-if="producto.tallas.length" class="space-y-2">
          <p class="text-sm font-semibold">Selecciona una variante</p>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="size in producto.tallas"
              :key="size"
              class="size-btn"
              :class="selectedSize === size ? 'active' : ''"
              @click="selectedSize = size"
            >
              {{ size }}
            </button>
          </div>
        </div>

        <!-- Cantidad -->
        <div class="space-y-2">
          <p class="text-sm font-semibold">Cantidad</p>
          <div class="flex items-center gap-3">
            <button class="qty-btn" @click="decrementarCantidad" :disabled="cantidad === 1">−</button>
            <span class="qty-display">{{ cantidad }}</span>
            <button class="qty-btn" @click="incrementarCantidad" :disabled="cantidad >= 99">+</button>
          </div>
        </div>

        <!-- CTA -->
        <div class="flex flex-wrap gap-3">
          <button class="btn-main px-6 py-3 text-sm font-bold uppercase flex-1" @click="addToCart" :disabled="loading || !producto.tieneStock">
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
import { computed, ref } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';

const props = defineProps({
  producto: { type: Object, required: true },
});

const gallery = props.producto.galeria?.length ? props.producto.galeria : [props.producto.foto];
const activeImage = ref(gallery[0]);
const selectedSize = ref(props.producto.tallas?.[0] ?? null);
const cantidad = ref(1);
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
        cantidad: cantidad.value,
        talla: selectedSize.value,
      }),
    });

    const payload = await res.json();

    if (!payload.success) {
      throw new Error(payload.message || 'No se pudo agregar al carrito');
    }

    message.value = 'Producto agregado correctamente';
    cantidad.value = 1;
    window.dispatchEvent(new Event('cart-updated'));
  } catch {
    message.value = 'No fue posible agregar este producto';
  } finally {
    loading.value = false;
  }
};

const decrementarCantidad = () => {
  if (cantidad.value > 1) cantidad.value--;
};

const incrementarCantidad = () => {
  if (cantidad.value < 99) cantidad.value++;
};
</script>
