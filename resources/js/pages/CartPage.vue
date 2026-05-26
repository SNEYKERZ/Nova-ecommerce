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
      <div v-else class="grid gap-4 sm:gap-6 lg:grid-cols-3 items-start">

        <!-- Items list -->
        <div class="lg:col-span-2" style="border:1.5px solid var(--border);border-radius:var(--r);overflow:hidden">
          <article
            v-for="item in items"
            :key="item.id"
            class="grid gap-3 p-3 sm:gap-4 sm:p-4 border-b"
            style="grid-template-columns:60px 1fr;border-color:var(--border-light);background:var(--white);transition:opacity 0.18s"
            :style="item._syncing ? 'opacity:0.55' : ''"
          >
            <!-- Image -->
            <div style="border-radius:var(--r);overflow:hidden;background:#f0ede8;aspect-ratio:3/4;width:60px;flex-shrink:0" class="sm:w-[72px]">
              <img :src="productImage(item)" :alt="item.producto.referencia" style="width:100%;height:100%;object-fit:cover;display:block" />
            </div>

            <!-- Info -->
            <div class="flex flex-col gap-1.5 sm:gap-2 min-w-0">
              <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                  <h3 class="font-display text-xs font-black uppercase tracking-wide truncate sm:text-sm" style="color:var(--ink)">
                    {{ item.producto.nombre || item.producto.referencia }}
                  </h3>
                  <p class="text-[10px] mt-0.5 sm:text-xs" style="color:var(--muted)">{{ item.producto.categoria }}</p>
                  <p class="text-[10px] sm:text-xs" style="color:var(--muted)">Ref: {{ item.producto.referencia }}</p>
                </div>
                <button @click="removeItem(item.id)" class="shrink-0 text-[10px] font-semibold sm:text-xs" style="color:var(--accent);background:none;border:none;cursor:pointer;padding:0;text-decoration:underline;text-underline-offset:3px">
                  Eliminar
                </button>
              </div>

              <div class="flex flex-wrap items-center justify-between gap-2 mt-auto">
                <!-- Size selector -->
                <div v-if="sizeOptions(item).length" class="flex items-center gap-1.5">
                  <label class="label-xs">Talla</label>
                  <select style="width:auto;padding:0.25rem 2rem 0.25rem 0.5rem;font-size:0.75rem" :value="item.talla || ''" @change="changeSize(item, $event.target.value)">
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

              <!-- Cupón de descuento -->
              <div class="pt-3 mt-3" style="border-top:1px dashed var(--border-light)">
                <template v-if="!appliedCupon">
                  <div class="flex gap-2">
                    <input v-model="cuponCode" type="text" placeholder="¿Tienes un cupón?"
                      class="flex-1 text-xs" style="padding:0.4rem 0.6rem;border:1.5px solid var(--border);border-radius:var(--r);background:var(--white);outline:none"
                      @keyup.enter="applyCupon" />
                    <button @click="applyCupon" :disabled="couponLoading"
                      class="text-xs font-semibold px-3 py-1.5 cursor-pointer"
                      style="border:1.5px solid var(--border);border-radius:var(--r);background:var(--surface-alt);color:var(--ink)">
                      {{ couponLoading ? '...' : 'Aplicar' }}
                    </button>
                  </div>
                  <p v-if="couponError" class="mt-1 text-xs" style="color:#dc2626">{{ couponError }}</p>
                </template>
                <template v-else>
                  <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                      <span class="text-xs font-semibold" style="color:var(--ink)">Cupón: {{ appliedCupon.codigo }}</span>
                      <span class="text-xs font-semibold" style="color:#059669">−{{ money(descuentoCupon) }}</span>
                    </div>
                    <button @click="removeCupon" class="text-xs underline cursor-pointer" style="color:var(--accent);background:none;border:none;padding:0">
                      Quitar
                    </button>
                  </div>
                </template>
              </div>
            </div>

            <div class="space-y-1 mb-4 pb-4" style="border-bottom:1.5px solid var(--border-light)">
              <div v-if="descuentoCupon > 0" class="flex justify-between text-xs">
                <span style="color:#059669">Descuento cupón {{ appliedCupon?.codigo }}</span>
                <span style="color:#059669">−{{ money(descuentoCupon) }}</span>
              </div>
            </div>

            <div class="flex justify-between items-center mb-5">
              <span class="font-display text-sm font-black uppercase tracking-wide" style="color:var(--ink)">Total</span>
              <span class="font-display text-xl font-black" style="color:var(--ink)">{{ money(finalTotal) }}</span>
            </div>

            <button class="btn-main w-full py-3" @click="showCheckout = true">
              Finalizar pedido
            </button>

            <button
              v-if="whatsappLink && !whatsappSaving"
              @click="pedirWhatsapp"
              class="btn-soft mt-2 w-full py-3 inline-flex items-center justify-center gap-2"
            >
              <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
              {{ whatsappSaving ? 'Creando pedido...' : 'Pedir por WhatsApp' }}
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
        <div v-if="showCheckout" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center px-0 sm:px-4" style="pointer-events:none">
          <div class="card w-full max-w-lg p-5 sm:p-6 rounded-b-none sm:rounded-b-2xl" style="pointer-events:auto;max-height:90vh;overflow-y:auto">
            <div class="flex items-center justify-between mb-4 sm:mb-5">
              <h3 class="font-display text-base font-black uppercase tracking-[0.08em] sm:text-lg">Datos de entrega</h3>
              <button @click="showCheckout = false" class="btn-ghost text-xs">✕ Cerrar</button>
            </div>
            <form class="grid gap-3" @submit.prevent="submitOrder">
              <div class="grid grid-cols-2 gap-3">
                <input v-model="form.nombre" required placeholder="Nombre *" class="text-sm" />
                <input v-model="form.apellidos" placeholder="Apellidos" class="text-sm" />
              </div>
              <input v-model="form.email" required type="email" placeholder="Correo electrónico *" class="text-sm" />
              <input v-model="form.telefono" placeholder="Teléfono" class="text-sm" />
              <textarea v-model="form.direccion" required placeholder="Dirección de entrega *" rows="2" class="text-sm"></textarea>
              <div class="flex justify-end gap-2 mt-1">
                <button type="button" class="btn-soft px-3 py-2 text-xs sm:px-4" @click="showCheckout = false">Cancelar</button>
                <button type="submit" class="btn-main px-4 py-2 text-xs sm:px-5 sm:py-2.5" :disabled="saving">
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
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';

const items       = ref([]);
const loading     = ref(true);
const showCheckout = ref(false);
const saving      = ref(false);
const whatsappSaving = ref(false);
const toast       = ref('');
const toastError  = ref(false);

const form = ref({ nombre: '', apellidos: '', email: '', telefono: '', direccion: '' });

// Cupón state
const cuponCode = ref('');
const appliedCupon = ref(null);
const descuentoCupon = ref(0);
const couponLoading = ref(false);
const couponError = ref('');

const total = computed(() => items.value.reduce((s, i) => s + i.producto.precio * i.cantidad, 0));
const count = computed(() => items.value.reduce((s, i) => s + i.cantidad, 0));

const finalTotal = computed(() => {
  let t = total.value;
  t -= descuentoCupon.value;
  return Math.max(0, t);
});

const page = usePage();
const whatsapp = computed(() => page.props.app?.whatsapp || null);

const whatsappLink = computed(() => {
  const phone = whatsapp.value?.replace(/\D/g, '');
  if (!phone || !items.value.length) return null;

  const subtotal = total.value;
  const cuponDesc = descuentoCupon.value;
  const final = finalTotal.value;

  const lines = items.value.map((item, idx) => {
    const name = item.producto.nombre || item.producto.referencia;
    const size = item.talla ? ` (Talla: ${item.talla})` : '';
    const lineTotal = item.producto.precio * item.cantidad;
    return `${idx + 1}. ${name}${size} x ${item.cantidad} = ${money(lineTotal)}`;
  }).join('\n');

  const separator = '\n\n─────────────────────\n';
  let msg = '¡Hola! Quiero hacer el siguiente pedido:\n\n';
  msg += lines;
  msg += separator;
  msg += `Subtotal: ${money(subtotal)}\n`;
  if (cuponDesc > 0) {
    msg += `Descuento cupón (${appliedCupon.value?.codigo}): -${money(cuponDesc)}\n`;
  }
  msg += `Total: ${money(final)}`;

  return `https://wa.me/${phone}?text=${encodeURIComponent(msg)}`;
});

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
    if (payload?.data?.cupon) {
      appliedCupon.value = payload.data.cupon;
      descuentoCupon.value = payload.data.descuento_cupon || 0;
    } else {
      appliedCupon.value = null;
      descuentoCupon.value = 0;
    }
    window.dispatchEvent(new Event('cart-updated'));
  } finally {
    loading.value = false;
  }
};

const applyCupon = async () => {
  const code = cuponCode.value?.trim();
  if (!code) return;
  couponLoading.value = true;
  couponError.value = '';
  try {
    const res = await fetch('/api/carrito/aplicar-cupon', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
      body: JSON.stringify({ codigo: code }),
    });
    const payload = await res.json();
    if (!payload.success) throw new Error(payload.message || 'Cupón inválido');
    appliedCupon.value = payload.data.cupon;
    descuentoCupon.value = payload.data.descuento_cupon;
    cuponCode.value = '';
    showToast(payload.message || 'Cupón aplicado');
  } catch (e) {
    couponError.value = e.message;
    setTimeout(() => { couponError.value = ''; }, 3000);
  } finally {
    couponLoading.value = false;
  }
};

const pedirWhatsapp = async () => {
  whatsappSaving.value = true;
  try {
    const res = await fetch('/api/carrito/pedir-whatsapp', {
      method: 'POST',
      headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
    });
    const payload = await res.json();
    if (!payload.success) throw new Error(payload.message || 'Error al crear pedido');

    // Vaciar frontend
    items.value = [];
    appliedCupon.value = null;
    descuentoCupon.value = 0;
    window.dispatchEvent(new Event('cart-updated'));

    // Abrir WhatsApp
    if (whatsappLink.value) {
      window.open(whatsappLink.value, '_blank');
    }
  } catch (e) {
    showToast(e.message, true);
  } finally {
    whatsappSaving.value = false;
  }
};

const removeCupon = async () => {
  try {
    const res = await fetch('/api/carrito/quitar-cupon', {
      method: 'DELETE',
      headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
    });
    const payload = await res.json();
    if (!payload.success) throw new Error(payload.message);
    appliedCupon.value = null;
    descuentoCupon.value = 0;
    showToast('Cupón quitado');
  } catch (e) {
    showToast(e.message, true);
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
  const backup = { items: [...items.value], cupon: appliedCupon.value, descuento: descuentoCupon.value };
  items.value = [];
  appliedCupon.value = null;
  descuentoCupon.value = 0;
  window.dispatchEvent(new Event('cart-updated'));
  try {
    const res = await fetch('/api/carrito/vaciar', {
      method: 'DELETE',
      headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
    });
    const payload = await res.json();
    if (!payload.success) throw new Error();
  } catch {
    items.value = backup.items;
    appliedCupon.value = backup.cupon;
    descuentoCupon.value = backup.descuento;
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
