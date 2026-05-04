<template>
  <AppLayout>
    <Head title="Carrito" />

    <div class="mx-auto max-w-screen-2xl px-4 sm:px-6 lg:px-8 py-10">

      <div class="mb-8">
        <p class="label-xs">Checkout</p>
        <h1 class="font-display text-3xl font-black uppercase tracking-[0.08em] mt-1" style="color:var(--ink)">
          Mi carrito
        </h1>
      </div>

      <!-- Skeleton -->
      <div v-if="loading" class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-3">
          <div v-for="n in 3" :key="n" class="skeleton h-28 rounded-none"></div>
        </div>
        <div class="skeleton h-52 rounded-none"></div>
      </div>

      <!-- Empty -->
      <div v-else-if="!items.length" class="card py-20 text-center">
        <svg class="mx-auto h-10 w-10 mb-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="color:var(--muted)">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/>
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M16 10a4 4 0 0 1-8 0"/>
        </svg>
        <p class="font-display text-xl font-black uppercase tracking-wide mb-2">Tu carrito está vacío</p>
        <p class="text-sm mb-6" style="color:var(--muted)">Agrega productos desde el catálogo para continuar</p>
        <Link href="/" class="btn-main px-6 py-3">Ver catálogo</Link>
      </div>

      <!-- Items + Summary -->
      <div v-else class="grid gap-6 lg:grid-cols-3 items-start">

        <!-- Items list -->
        <div class="lg:col-span-2" style="border:1.5px solid var(--border);border-radius:var(--r);overflow:hidden">
          <article
            v-for="item in items"
            :key="item.id"
            class="grid gap-4 p-4 border-b"
            style="grid-template-columns:72px 1fr;border-color:var(--border-light);background:var(--white);transition:opacity 0.18s"
            :style="item._syncing ? 'opacity:0.55' : ''"
          >
            <!-- Image -->
            <div style="border-radius:var(--r);overflow:hidden;background:#f0ede8;aspect-ratio:3/4;width:72px;flex-shrink:0">
              <img :src="productImage(item)" :alt="item.producto.referencia" style="width:100%;height:100%;object-fit:cover;display:block" />
            </div>

            <!-- Info -->
            <div class="flex flex-col gap-2 min-w-0">
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <h3 class="font-display text-sm font-black uppercase tracking-wide truncate" style="color:var(--ink)">
                    {{ item.producto.nombre || item.producto.referencia }}
                  </h3>
                  <p class="text-xs mt-0.5" style="color:var(--muted)">{{ item.producto.categoria }}</p>
                  <p class="text-xs" style="color:var(--muted)">Ref: {{ item.producto.referencia }}</p>
                </div>
                <button @click="removeItem(item.id)" class="shrink-0 text-xs font-semibold" style="color:var(--accent);background:none;border:none;cursor:pointer;padding:0;text-decoration:underline;text-underline-offset:3px">
                  Eliminar
                </button>
              </div>

              <div class="flex flex-wrap items-center justify-between gap-3 mt-auto">
                <!-- Size selector -->
                <div v-if="sizeOptions(item).length" class="flex items-center gap-2">
                  <label class="label-xs">Talla</label>
                  <select style="width:auto;padding:0.3rem 2.2rem 0.3rem 0.65rem" :value="item.talla || ''" @change="changeSize(item, $event.target.value)">
                    <option value="">—</option>
                    <option v-for="s in sizeOptions(item)" :key="s" :value="s">{{ s }}</option>
                  </select>
                </div>
                <div v-else class="flex-1"></div>

                <!-- Qty stepper -->
                <div class="flex items-center gap-2">
                  <button class="qty-btn" @click="changeQty(item, item.cantidad - 1)">−</button>
                  <span class="qty-display">{{ item.cantidad }}</span>
                  <button class="qty-btn" @click="changeQty(item, item.cantidad + 1)">+</button>
                </div>

                <!-- Subtotal -->
                <p class="font-display text-sm font-black" style="color:var(--ink)">
                  {{ money(item.producto.precio * item.cantidad) }}
                </p>
              </div>
            </div>
          </article>

          <!-- Bottom row -->
          <div class="flex items-center justify-between px-4 py-3" style="background:var(--surface-alt)">
            <Link href="/" class="btn-ghost text-xs">← Seguir comprando</Link>
            <button @click="clearCart" class="btn-ghost text-xs" style="opacity:0.5">Vaciar carrito</button>
          </div>
        </div>

        <!-- Summary -->
        <aside>
          <div class="card p-5" style="position:sticky;top:76px">
            <h2 class="font-display text-sm font-black uppercase tracking-[0.12em] mb-4" style="color:var(--ink)">
              Resumen del pedido
            </h2>

            <div class="space-y-2.5 text-sm pb-4 mb-4" style="border-bottom:1.5px solid var(--border-light)">
              <div class="flex justify-between">
                <span style="color:var(--muted)">Artículos</span>
                <span class="font-semibold">{{ count }}</span>
              </div>
              <div class="flex justify-between">
                <span style="color:var(--muted)">Subtotal</span>
                <span class="font-semibold">{{ money(total) }}</span>
              </div>
              <div class="flex justify-between text-xs" style="color:var(--muted)">
                <span>Envío</span>
                <span>A coordinar</span>
              </div>
            </div>

            <div class="flex justify-between items-center mb-5">
              <span class="font-display text-sm font-black uppercase tracking-wide" style="color:var(--ink)">Total</span>
              <span class="font-display text-xl font-black" style="color:var(--ink)">{{ money(total) }}</span>
            </div>

            <button class="btn-main w-full py-3" @click="showCheckout = true">
              Finalizar pedido
            </button>
          </div>
        </aside>
      </div>
    </div>

    <!-- Toast -->
    <div v-if="toast" :class="['toast', toastError ? 'error' : '']">{{ toast }}</div>

    <!-- Checkout overlay -->
    <transition name="fade">
      <div v-if="showCheckout" class="overlay" @click.self="showCheckout = false"></div>
    </transition>

    <transition name="slide-up">
      <div v-if="showCheckout" class="fixed inset-0 z-50 flex items-center justify-center px-4" style="pointer-events:none">
        <div class="card w-full max-w-lg p-6" style="pointer-events:auto;max-height:90vh;overflow-y:auto">
          <div class="flex items-center justify-between mb-5">
            <h3 class="font-display text-lg font-black uppercase tracking-[0.08em]">Datos de entrega</h3>
            <button @click="showCheckout = false" class="btn-ghost text-xs">✕ Cerrar</button>
          </div>
          <form class="grid gap-3 sm:grid-cols-2" @submit.prevent="submitOrder">
            <input v-model="form.nombre" required placeholder="Nombre *" />
            <input v-model="form.apellidos" placeholder="Apellidos" />
            <input v-model="form.email" required type="email" placeholder="Correo electrónico *" class="sm:col-span-2" />
            <input v-model="form.telefono" placeholder="Teléfono" class="sm:col-span-2" />
            <textarea v-model="form.direccion" required placeholder="Dirección de entrega *" class="sm:col-span-2" rows="2"></textarea>
            <div class="sm:col-span-2 flex justify-end gap-2 mt-1">
              <button type="button" class="btn-soft px-4 py-2 text-xs" @click="showCheckout = false">Cancelar</button>
              <button type="submit" class="btn-main px-5 py-2.5 text-xs" :disabled="saving">
                {{ saving ? 'Procesando...' : 'Confirmar pedido' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </transition>
  </AppLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';

const items       = ref([]);
const loading     = ref(true);
const showCheckout = ref(false);
const saving      = ref(false);
const toast       = ref('');
const toastError  = ref(false);

const form = ref({ nombre: '', apellidos: '', email: '', telefono: '', direccion: '' });

const total = computed(() => items.value.reduce((s, i) => s + i.producto.precio * i.cantidad, 0));
const count = computed(() => items.value.reduce((s, i) => s + i.cantidad, 0));

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

const money = (v) =>
  new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(v || 0);

const productImage = (item) => {
  const v = item?.producto?.foto;
  if (!v) return '/images/sinfoto.jpg';
  return String(v).startsWith('http') ? v : `/storage/${v}`;
};

const sizeOptions = (item) => {
  const raw = item?.producto?.tallas || '';
  if (!raw) return [];
  return raw.split(',').map((s) => s.trim()).filter(Boolean);
};

const showToast = (msg, error = false) => {
  toast.value = msg;
  toastError.value = error;
  setTimeout(() => { toast.value = ''; toastError.value = false; }, 2400);
};

const loadCart = async () => {
  try {
    const res = await fetch('/api/carrito', { headers: { Accept: 'application/json' } });
    const payload = await res.json();
    items.value = payload?.data?.items || [];
    window.dispatchEvent(new Event('cart-updated'));
  } finally {
    loading.value = false;
  }
};

const changeQty = async (item, qty) => {
  if (qty <= 0) return removeItem(item.id);
  const prev = item.cantidad;
  item.cantidad = qty;
  item._syncing = true;
  try {
    const res = await fetch(`/api/carrito/actualizar/${item.id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({ cantidad: qty }),
    });
    const payload = await res.json();
    if (!payload.success) throw new Error(payload.message);
    window.dispatchEvent(new Event('cart-updated'));
  } catch {
    item.cantidad = prev;
    showToast('No se pudo actualizar la cantidad', true);
  } finally {
    item._syncing = false;
  }
};

const changeSize = async (item, talla) => {
  const prev = item.talla;
  item.talla = talla || null;
  item._syncing = true;
  try {
    const res = await fetch(`/api/carrito/actualizar/${item.id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({ talla: talla || null }),
    });
    const payload = await res.json();
    if (!payload.success) throw new Error();
  } catch {
    item.talla = prev;
    showToast('No se pudo actualizar la talla', true);
  } finally {
    item._syncing = false;
  }
};

const removeItem = async (itemId) => {
  const idx = items.value.findIndex((i) => i.id === itemId);
  if (idx === -1) return;
  const [removed] = items.value.splice(idx, 1);
  window.dispatchEvent(new Event('cart-updated'));
  try {
    const res = await fetch(`/api/carrito/eliminar/${itemId}`, {
      method: 'DELETE',
      headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
    });
    const payload = await res.json();
    if (!payload.success) throw new Error();
  } catch {
    items.value.splice(idx, 0, removed);
    window.dispatchEvent(new Event('cart-updated'));
    showToast('No se pudo eliminar el producto', true);
  }
};

const clearCart = async () => {
  const backup = [...items.value];
  items.value = [];
  window.dispatchEvent(new Event('cart-updated'));
  try {
    const res = await fetch('/api/carrito/vaciar', {
      method: 'DELETE',
      headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
    });
    const payload = await res.json();
    if (!payload.success) throw new Error();
  } catch {
    items.value = backup;
    window.dispatchEvent(new Event('cart-updated'));
    showToast('No se pudo vaciar el carrito', true);
  }
};

const submitOrder = async () => {
  saving.value = true;
  try {
    const res = await fetch('/api/pedidos', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({
        ...form.value,
        items: items.value.map((i) => ({ producto_id: i.producto.id, cantidad: i.cantidad, talla: i.talla || null })),
      }),
    });
    const payload = await res.json();
    if (!payload.success) throw new Error(payload.message || 'No fue posible crear el pedido');
    await clearCart();
    showCheckout.value = false;
    router.visit(`/pedido/gracias/${payload.data.id}`);
  } catch (err) {
    showToast(err.message || 'Error al procesar el pedido', true);
  } finally {
    saving.value = false;
  }
};

onMounted(loadCart);
</script>
