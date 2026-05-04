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
        <h1 class="font-display text-4xl leading-tight font-bold">{{ producto.nombre || producto.sku }}</h1>
        <p class="text-xs text-[color:var(--muted)]/60 -mt-1">Ref: {{ producto.sku }}</p>
        <p v-if="producto.descripcion" class="text-base text-[color:var(--muted)]">{{ producto.descripcion }}</p>
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
          <a v-if="whatsappUrl" :href="whatsappUrl" target="_blank" rel="noopener" class="btn-soft px-6 py-3 text-sm font-semibold inline-flex items-center gap-2">
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            Pedir por WhatsApp
          </a>
        </div>

        <p v-if="message" class="rounded-xl bg-[color:var(--sand)] px-4 py-3 text-sm font-semibold">
          {{ message }}
        </p>
      </div>
    </section>
  </AppLayout>
</template>

<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';

const props = defineProps({
  producto: { type: Object, required: true },
});

const page = usePage();
const whatsappUrl = computed(() => {
  const phone = (page.props.app?.whatsapp || '').replace(/\D/g, '');
  if (!phone) return null;
  const msg = encodeURIComponent(`Hola! Me interesa: ${props.producto.nombre || props.producto.sku} - ${money(props.producto.precio)}`);
  return `https://wa.me/${phone}?text=${msg}`;
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
