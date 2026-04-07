<template>
  <AppLayout>
    <Head title="Carrito" />
    <section class="mx-auto w-full max-w-7xl px-4 pt-10 sm:px-6 lg:px-8">
      <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
          <p class="text-xs font-semibold tracking-[0.2em] text-[color:var(--muted)] uppercase">Checkout</p>
          <h1 class="font-display mt-2 text-4xl font-bold">Carrito de compras</h1>
        </div>
        <Link href="/" class="btn-soft px-5 py-2.5 text-sm font-semibold">Seguir comprando</Link>
      </div>

      <div v-if="loading" class="rounded-2xl border border-[color:var(--line)] bg-[color:var(--surface)] p-8 text-center text-[color:var(--muted)]">
        Cargando carrito...
      </div>

      <div v-else-if="!items.length" class="rounded-2xl border border-dashed border-[color:var(--line)] bg-[color:var(--surface)] p-10 text-center">
        <p class="text-lg font-semibold">Tu carrito esta vacio</p>
        <Link href="/" class="btn-main mt-4 inline-flex px-5 py-2.5 text-sm font-bold">Ir al catalogo</Link>
      </div>

      <div v-else class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
          <article v-for="item in items" :key="item.id" class="grid gap-3 rounded-2xl border border-black/5 bg-[color:var(--surface)] p-4 sm:grid-cols-[96px_1fr]">
            <img :src="productImage(item)" :alt="item.producto.referencia" class="h-24 w-24 rounded-xl object-cover" />
            <div class="grid gap-3 sm:grid-cols-[1fr_auto] sm:items-center">
              <div>
                <h3 class="font-display text-lg font-semibold">{{ item.producto.referencia }}</h3>
                <p class="text-sm text-[color:var(--muted)]">{{ item.producto.categoria }}</p>
                <p class="mt-1 text-sm font-semibold">{{ money(item.producto.precio) }}</p>
              </div>

              <div class="flex items-center gap-2">
                <button class="chip cursor-pointer" @click="changeQty(item, item.cantidad - 1)">-</button>
                <span class="min-w-8 text-center text-sm font-semibold">{{ item.cantidad }}</span>
                <button class="chip cursor-pointer" @click="changeQty(item, item.cantidad + 1)">+</button>
              </div>

              <div class="sm:col-span-2 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2" v-if="sizeOptions(item).length">
                  <label class="text-xs font-semibold uppercase text-[color:var(--muted)]">Variante</label>
                  <select class="rounded-full border border-[color:var(--line)] bg-white px-3 py-1.5 text-sm" :value="item.talla || ''" @change="changeSize(item, $event.target.value)">
                    <option value="">Sin talla</option>
                    <option v-for="size in sizeOptions(item)" :key="size" :value="size">{{ size }}</option>
                  </select>
                </div>

                <div class="flex items-center gap-4">
                  <p class="text-sm font-bold">{{ money(item.subtotal) }}</p>
                  <button class="text-sm font-semibold text-[color:var(--accent)]" @click="removeItem(item.id)">Eliminar</button>
                </div>
              </div>
            </div>
          </article>
        </div>

        <aside class="rounded-2xl border border-black/5 bg-[color:var(--surface)] p-5">
          <h2 class="font-display text-xl font-bold">Resumen</h2>
          <div class="mt-5 space-y-3 border-y border-[color:var(--line)] py-4 text-sm">
            <div class="flex items-center justify-between">
              <span>Productos</span>
              <span>{{ count }} items</span>
            </div>
            <div class="flex items-center justify-between font-bold">
              <span>Total</span>
              <span>{{ money(total) }}</span>
            </div>
          </div>

          <button class="btn-main mt-5 w-full px-5 py-3 text-sm font-bold uppercase" @click="showCheckout = true">
            Finalizar compra
          </button>
          <button class="mt-3 w-full rounded-full border border-[color:var(--line)] px-5 py-2.5 text-sm font-semibold" @click="clearCart">
            Vaciar carrito
          </button>
        </aside>
      </div>
    </section>

    <div v-if="showCheckout" class="fixed inset-0 z-50 flex items-center justify-center bg-black/45 px-4">
      <div class="w-full max-w-xl rounded-3xl bg-white p-6">
        <h3 class="font-display text-2xl font-bold">Datos de envio</h3>
        <form class="mt-4 grid gap-3 sm:grid-cols-2" @submit.prevent="submitOrder">
          <input v-model="form.nombre" required placeholder="Nombre" class="rounded-xl border border-[color:var(--line)] px-3 py-2.5" />
          <input v-model="form.apellidos" placeholder="Apellidos" class="rounded-xl border border-[color:var(--line)] px-3 py-2.5" />
          <input v-model="form.email" required type="email" placeholder="Correo" class="rounded-xl border border-[color:var(--line)] px-3 py-2.5 sm:col-span-2" />
          <input v-model="form.telefono" placeholder="Telefono" class="rounded-xl border border-[color:var(--line)] px-3 py-2.5 sm:col-span-2" />
          <textarea v-model="form.direccion" required placeholder="Direccion de entrega" class="rounded-xl border border-[color:var(--line)] px-3 py-2.5 sm:col-span-2"></textarea>

          <div class="sm:col-span-2 mt-2 flex justify-end gap-2">
            <button type="button" class="btn-soft px-4 py-2 text-sm font-semibold" @click="showCheckout = false">Cancelar</button>
            <button type="submit" class="btn-main px-4 py-2 text-sm font-bold" :disabled="saving">
              {{ saving ? 'Procesando...' : 'Confirmar pedido' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';

const items = ref([]);
const total = ref(0);
const count = ref(0);
const loading = ref(true);
const showCheckout = ref(false);
const saving = ref(false);

const form = ref({
  nombre: '',
  apellidos: '',
  email: '',
  telefono: '',
  direccion: '',
});

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

const money = (value) =>
  new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(value || 0);

const productImage = (item) => {
  const value = item?.producto?.foto;
  if (!value) return '/images/sinfoto.jpg';
  if (String(value).startsWith('http')) return value;
  return `/storage/${value}`;
};

const sizeOptions = (item) => {
  const raw = item?.producto?.tallas || '';
  if (!raw) return [];
  return raw.split(',').map((s) => s.trim()).filter(Boolean);
};

const loadCart = async () => {
  loading.value = true;

  try {
    const res = await fetch('/api/carrito', { headers: { Accept: 'application/json' } });
    const payload = await res.json();
    const data = payload?.data || {};

    items.value = data.items || [];
    total.value = data.total || 0;
    count.value = data.cantidad_total || 0;
    window.dispatchEvent(new Event('cart-updated'));
  } finally {
    loading.value = false;
  }
};

const changeQty = async (item, qty) => {
  await fetch(`/api/carrito/actualizar/${item.id}`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      'X-CSRF-TOKEN': csrf(),
    },
    body: JSON.stringify({ cantidad: Math.max(0, qty) }),
  });

  await loadCart();
};

const changeSize = async (item, talla) => {
  await fetch(`/api/carrito/actualizar/${item.id}`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      'X-CSRF-TOKEN': csrf(),
    },
    body: JSON.stringify({ talla: talla || null }),
  });

  await loadCart();
};

const removeItem = async (itemId) => {
  await fetch(`/api/carrito/eliminar/${itemId}`, {
    method: 'DELETE',
    headers: {
      Accept: 'application/json',
      'X-CSRF-TOKEN': csrf(),
    },
  });

  await loadCart();
};

const clearCart = async () => {
  await fetch('/api/carrito/vaciar', {
    method: 'DELETE',
    headers: {
      Accept: 'application/json',
      'X-CSRF-TOKEN': csrf(),
    },
  });

  await loadCart();
};

const submitOrder = async () => {
  saving.value = true;

  try {
    const orderItems = items.value.map((item) => ({
      producto_id: item.producto.id,
      cantidad: item.cantidad,
      talla: item.talla || null,
    }));

    const res = await fetch('/api/pedidos', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrf(),
      },
      body: JSON.stringify({
        ...form.value,
        items: orderItems,
      }),
    });

    const payload = await res.json();

    if (!payload.success) {
      throw new Error(payload.message || 'No fue posible crear el pedido');
    }

    await clearCart();
    showCheckout.value = false;
    router.visit(`/pedido/gracias/${payload.data.id}`);
  } finally {
    saving.value = false;
  }
};

onMounted(loadCart);
</script>
