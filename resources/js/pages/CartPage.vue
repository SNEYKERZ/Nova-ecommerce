<template>
  <AppLayout>
    <Head title="Carrito" />

    <div class="mx-auto max-w-[110rem] px-4 py-10 sm:px-6 sm:py-14 lg:px-10">
      <div class="mb-10 flex flex-wrap items-end justify-between gap-4">
        <h1 class="sf-display sf-masthead">Carrito</h1>
        <div class="flex flex-col items-start gap-3 pb-2 sm:items-end">
          <ol class="flex flex-wrap items-center gap-x-5 gap-y-1 text-sm" aria-label="Pasos del pedido">
            <li v-for="(stepLabel, index) in steps" :key="stepLabel" class="relative inline-flex items-center gap-2" :class="index === currentStep ? 'font-semibold text-[var(--sf-ink)]' : 'text-[var(--sf-mute)]'" :aria-current="index === currentStep ? 'step' : undefined">
              <span class="sf-num">{{ index + 1 }}</span>{{ stepLabel }}
              <PencilMark shape="underline" :active="index === currentStep" />
            </li>
          </ol>
          <Link v-if="items.length" href="/" class="sf-link text-sm">Seguir comprando</Link>
        </div>
      </div>

      <!-- Cargando -->
      <div v-if="loading" class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]" aria-busy="true" aria-label="Cargando carrito">
        <div class="space-y-3">
          <div v-for="n in 3" :key="n" class="flex gap-4 border-b border-[var(--sf-rule)] p-4">
            <div class="skeleton h-28 w-20 shrink-0"></div>
            <div class="flex-1 space-y-2"><div class="skeleton h-4 w-2/3"></div><div class="skeleton h-3 w-1/3"></div><div class="skeleton mt-6 h-9 w-32"></div></div>
          </div>
        </div>
        <div class="skeleton h-72 !rounded-[var(--r-lg)]"></div>
      </div>

      <!-- Error de carga -->
      <div v-else-if="loadError" class="card px-6 py-14 text-center">
        <p class="sf-display text-4xl">No pudimos cargar tu carrito</p>
        <p class="mt-2 text-sm text-[var(--muted)]">{{ loadError }}</p>
        <button type="button" class="btn-main mt-6" @click="loadCart()">Reintentar</button>
      </div>

      <!-- Vacío -->
      <div v-else-if="!items.length" class="card px-6 py-16 text-center">
                <p class="sf-display text-5xl">Tu carrito está vacío</p>
        <p class="mx-auto mt-3 max-w-sm text-base text-[var(--muted)]">Explora {{ labels.catalogo === 'Menú' ? 'el menú' : 'el catálogo' }} y agrega lo que quieras pedir.</p>
        <Link href="/" class="sf-btn mt-7">Ir {{ labels.catalogo === 'Menú' ? 'al menú' : labels.catalogo === 'Repuestos' ? 'a repuestos' : 'al catálogo' }}</Link>
      </div>

      <div v-else class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <!-- Ítems -->
        <section aria-labelledby="items-title">
          <h2 id="items-title" class="visually-hidden">Productos en el carrito</h2>
          <ul class="border-t border-[var(--sf-rule)]">
            <li
              v-for="item in items"
              :key="item.id"
              class="flex gap-3 border-b border-[var(--sf-rule)] py-4 sm:gap-5 sm:py-5"
              :class="item._syncing ? 'opacity-60' : ''"
              :aria-busy="item._syncing ? 'true' : 'false'"
            >
              <Link :href="`/productos/${item.producto.id}`" class="shrink-0 overflow-hidden rounded-[var(--r)] bg-[var(--sand)]" tabindex="-1" aria-hidden="true">
                <img :src="productImage(item)" alt="" class="aspect-[3/4] w-20 object-cover sm:w-24" width="96" height="128" loading="lazy" />
              </Link>
              <div class="flex min-w-0 flex-1 flex-col">
                <div class="flex items-start justify-between gap-3">
                  <div class="min-w-0">
                    <Link :href="`/productos/${item.producto.id}`" class="line-clamp-2 text-base font-semibold hover:underline underline-offset-4">{{ itemName(item) }}</Link>
                    <p class="mt-0.5 text-xs text-[var(--muted)]"><template v-if="item.producto.categoria">{{ item.producto.categoria }} · </template>Ref. {{ item.producto.referencia }}</p>
                    <p class="mt-1 text-xs tabular-nums">
                      <span class="font-semibold" :class="hasDiscount(item) ? 'text-[var(--accent)]' : ''">{{ money(unitPrice(item)) }} c/u</span>
                      <span v-if="hasDiscount(item)" class="ml-1 text-[var(--muted)] line-through"><span class="visually-hidden">Antes </span>{{ money(item.producto.precio_original) }}</span>
                    </p>
                  </div>
                  <p class="shrink-0 text-sm font-bold tabular-nums sm:text-base">{{ money(lineTotal(item)) }}</p>
                </div>

                <div class="mt-auto flex flex-wrap items-center gap-x-4 gap-y-2 pt-3">
                  <div v-if="sizeOptions(item).length" class="flex items-center gap-2">
                    <label :for="`talla-${item.id}`" class="text-xs font-semibold text-[var(--ink-2)]">{{ labels.variante }}</label>
                    <select :id="`talla-${item.id}`" class="!w-auto !min-h-10 !py-1.5 !pl-3 text-sm" :value="item.talla || ''" :disabled="item._syncing" @change="changeSize(item, $event.target.value)">
                      <option value="" disabled>Elegir</option>
                      <option v-for="s in sizeOptions(item)" :key="s" :value="s">{{ s }}</option>
                    </select>
                  </div>
                  <div class="qty" role="group" :aria-label="`Cantidad de ${itemName(item)}`">
                    <button type="button" aria-label="Quitar uno" :disabled="item.cantidad <= 1 || item._syncing" @click="changeQty(item, item.cantidad - 1)">−</button>
                    <output aria-live="polite">{{ item.cantidad }}</output>
                    <button type="button" aria-label="Agregar uno" :disabled="item._syncing" @click="changeQty(item, item.cantidad + 1)">+</button>
                  </div>
                  <button type="button" class="ml-auto inline-flex min-h-10 items-center gap-1.5 rounded px-1 text-xs font-semibold text-[var(--muted)] hover:text-[var(--accent)]" :aria-label="`Eliminar ${itemName(item)}`" @click="removeItem(item)">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M10 11v6M14 11v6M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2l1-12M9 7V4h6v3"/></svg>
                    Eliminar
                  </button>
                </div>
                <p v-if="sizeOptions(item).length && !item.talla" class="field-error">{{ labels.elegirVariante }} antes de finalizar.</p>
              </div>
            </li>
          </ul>
          <div class="mt-3 flex justify-end">
            <button type="button" class="btn-ghost text-xs text-[var(--muted)]" @click="clearCart">Vaciar carrito</button>
          </div>
        </section>

        <!-- Resumen -->
        <aside class="lg:sticky lg:top-24" aria-labelledby="summary-title">
          <div class="border border-[var(--sf-ink)] p-5 sm:p-6">
            <h2 id="summary-title" class="sf-display text-3xl">Resumen</h2>

            <!-- Cupón -->
            <div class="mt-4">
              <p v-if="couponNotice" class="mb-3 rounded-[var(--r)] bg-[var(--warning-soft)] px-3 py-2 text-xs font-semibold text-[var(--warning)]" role="status">{{ couponNotice }}</p>
              <form v-if="!appliedCupon" novalidate @submit.prevent="applyCupon">
                <label for="cupon" class="field-label">Cupón de descuento</label>
                <div class="flex gap-2">
                  <input id="cupon" v-model="cuponCode" type="text" autocomplete="off" autocapitalize="characters" placeholder="Ej. BIENVENIDO10" class="uppercase" :aria-invalid="couponError ? 'true' : 'false'" aria-describedby="cupon-error" @input="couponError = ''" />
                  <button type="submit" class="btn-soft shrink-0" :disabled="couponLoading || !cuponCode.trim()">
                    <span v-if="couponLoading" class="spinner" aria-hidden="true"></span>
                    Aplicar
                  </button>
                </div>
                <p v-if="couponError" id="cupon-error" class="field-error" role="alert">{{ couponError }}</p>
              </form>
              <div v-else class="flex items-center justify-between rounded-[var(--r)] bg-[var(--success-soft)] px-3 py-2.5 text-sm">
                <span class="font-semibold text-[var(--success)]">Cupón {{ appliedCupon.codigo }} aplicado</span>
                <button type="button" class="btn-ghost !py-1 text-xs" @click="removeCupon">Quitar</button>
              </div>
            </div>

            <dl class="mt-5 space-y-2.5 border-t border-[var(--border-light)] pt-4 text-sm tabular-nums">
              <div class="flex justify-between">
                <dt class="text-[var(--ink-2)]">Subtotal ({{ count }} {{ count === 1 ? 'artículo' : 'artículos' }})</dt>
                <dd class="font-semibold">{{ money(subtotal) }}</dd>
              </div>
              <div v-if="descuentoCupon > 0" class="flex justify-between text-[var(--success)]">
                <dt>Descuento {{ appliedCupon?.codigo }}</dt>
                <dd class="font-semibold">−{{ money(descuentoCupon) }}</dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-[var(--ink-2)]">Envío</dt>
                <dd class="text-[var(--ink-2)]">A coordinar</dd>
              </div>
              <div class="flex items-baseline justify-between border-t border-[var(--border-light)] pt-3">
                <dt class="text-base font-bold">Total</dt>
                <dd class="text-3xl font-extrabold">{{ money(finalTotal) }}</dd>
              </div>
            </dl>

            <p v-if="missingSizes" class="mt-4 bg-[var(--sf-pencil-wash)] px-3 py-2 text-xs font-semibold">Hay productos sin {{ labels.variante.toLowerCase() }}. Elígela para continuar.</p>

            <button type="button" class="sf-btn mt-5 w-full !min-h-[3.25rem]" :disabled="missingSizes" @click="openCheckout">Finalizar pedido</button>
            <button v-if="whatsappNumber" type="button" class="sf-btn sf-btn-whatsapp mt-2 w-full !min-h-[3.25rem]" :disabled="whatsappSaving || missingSizes" @click="pedirWhatsapp">
              <span v-if="whatsappSaving" class="spinner" aria-hidden="true"></span>
              <svg v-else class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18a8 8 0 0 1-4.1-1.1l-.3-.2-3 .8.8-2.9-.2-.3A8 8 0 1 1 12 20Zm4.4-6c-.2-.1-1.4-.7-1.6-.8-.2-.1-.4-.1-.5.1l-.7.9c-.1.2-.3.2-.5.1a6.6 6.6 0 0 1-3.3-2.9c-.2-.4.2-.4.7-1.3.1-.2 0-.3 0-.4l-.7-1.7c-.2-.5-.4-.4-.5-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2c0 1.3.9 2.5 1.1 2.7.1.2 1.8 2.8 4.4 3.9 1.6.7 2.3.8 3.1.6.5-.1 1.4-.6 1.6-1.1.2-.6.2-1 .1-1.1l-.4-.2Z"/></svg>
              {{ whatsappSaving ? 'Creando pedido…' : 'Pedir por WhatsApp' }}
            </button>
            <p class="mt-3 text-center text-xs text-[var(--muted)]">Pagas al confirmar el pedido con la tienda.</p>
          </div>
        </aside>
      </div>
    </div>

    <!-- Checkout -->
    <Teleport to="#contenido" defer>
      <Transition name="fade">
        <div v-if="showCheckout" class="overlay" aria-hidden="true" @click="closeCheckout"></div>
      </Transition>
      <Transition name="sheet">
        <div v-if="showCheckout" class="fixed inset-x-0 bottom-0 z-[61] flex max-h-[94vh] justify-center sm:inset-0 sm:items-center sm:p-4" @click.self="closeCheckout">
          <div
            ref="checkoutPanel"
            class="w-full max-w-lg overflow-y-auto bg-[var(--sf-paper)] p-5 pb-[max(1.25rem,env(safe-area-inset-bottom))] text-[var(--sf-ink)] shadow-2xl sm:p-8"
            role="dialog"
            aria-modal="true"
            aria-labelledby="checkout-title"
            @keydown.esc="closeCheckout"
          >
            <div class="flex items-start justify-between gap-3">
              <div>
                <p class="sf-num text-sm text-[var(--sf-mute)]">Paso 2 de 3</p>
                <h2 id="checkout-title" class="sf-display mt-1 text-4xl">Datos de entrega</h2>
                <p class="mt-1 text-sm text-[var(--muted)]">Total a pagar: <strong class="text-[var(--ink)] tabular-nums">{{ money(finalTotal) }}</strong></p>
              </div>
              <button type="button" class="icon-btn -m-2" aria-label="Cerrar" :disabled="saving" @click="closeCheckout">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.3 5.3a1 1 0 0 0-1.4 1.4L8.6 10l-3.7 3.3a1 1 0 1 0 1.4 1.4L10 11.4l3.3 3.3a1 1 0 0 0 1.4-1.4L11.4 10l3.3-3.3a1 1 0 0 0-1.4-1.4L10 8.6 6.3 5.3Z"/></svg>
              </button>
            </div>

            <p v-if="formError" class="mt-4 rounded-[var(--r)] bg-[var(--accent-soft)] px-3 py-2.5 text-sm font-semibold text-[var(--accent)]" role="alert">{{ formError }}</p>

            <form class="mt-5 grid gap-4" novalidate @submit.prevent="submitOrder">
              <div class="grid gap-4 sm:grid-cols-2">
                <div>
                  <label for="co-nombre" class="field-label">Nombre *</label>
                  <input id="co-nombre" v-model.trim="form.nombre" type="text" autocomplete="given-name" required :aria-invalid="!!errors.nombre" aria-describedby="err-nombre" @blur="validateField('nombre')" />
                  <p v-if="errors.nombre" id="err-nombre" class="field-error">{{ errors.nombre }}</p>
                </div>
                <div>
                  <label for="co-apellidos" class="field-label">Apellidos</label>
                  <input id="co-apellidos" v-model.trim="form.apellidos" type="text" autocomplete="family-name" :aria-invalid="!!errors.apellidos" />
                  <p v-if="errors.apellidos" class="field-error">{{ errors.apellidos }}</p>
                </div>
              </div>
              <div>
                <label for="co-email" class="field-label">Correo electrónico *</label>
                <input id="co-email" v-model.trim="form.email" type="email" inputmode="email" autocomplete="email" required :aria-invalid="!!errors.email" aria-describedby="err-email" @blur="validateField('email')" />
                <p v-if="errors.email" id="err-email" class="field-error">{{ errors.email }}</p>
              </div>
              <div>
                <label for="co-telefono" class="field-label">Celular</label>
                <input id="co-telefono" v-model.trim="form.telefono" type="tel" inputmode="tel" autocomplete="tel" placeholder="300 123 4567" :aria-invalid="!!errors.telefono" aria-describedby="hint-tel err-telefono" @blur="validateField('telefono')" />
                <p v-if="errors.telefono" id="err-telefono" class="field-error">{{ errors.telefono }}</p>
                <p v-else id="hint-tel" class="field-hint">Te escribimos para coordinar el envío.</p>
              </div>
              <div>
                <label for="co-direccion" class="field-label">Dirección de entrega *</label>
                <textarea id="co-direccion" v-model.trim="form.direccion" rows="2" autocomplete="street-address" required placeholder="Calle, número, barrio y ciudad" :aria-invalid="!!errors.direccion" aria-describedby="err-direccion" @blur="validateField('direccion')"></textarea>
                <p v-if="errors.direccion" id="err-direccion" class="field-error">{{ errors.direccion }}</p>
              </div>
              <div class="mt-1 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" class="sf-btn-line" :disabled="saving" @click="closeCheckout">Volver al carrito</button>
                <button type="submit" class="sf-btn !min-h-12 sm:min-w-52" :disabled="saving">
                  <span v-if="saving" class="spinner" aria-hidden="true"></span>
                  {{ saving ? 'Enviando pedido…' : 'Confirmar pedido' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </Transition>
    </Teleport>
  </AppLayout>
</template>

<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, onUnmounted, reactive, ref, watch } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';
import PencilMark from '../components/store/PencilMark.vue';
import { useRubro } from '../composables/useRubro';
import { useConfirm } from '../composables/useConfirm';
import { useToast } from '../composables/useToast';
import { api } from '../utils/http';
import { mediaUrl, money } from '../utils/format';

const page = usePage();
const rubro = useRubro();
const labels = computed(() => rubro.preset.value);
const steps = ['Carrito', 'Datos de entrega', 'Confirmación'];
const toast = useToast();
const { confirm } = useConfirm();

const items = ref([]);
const loading = ref(true);
const loadError = ref('');
const showCheckout = ref(false);
const checkoutPanel = ref(null);
const saving = ref(false);
const whatsappSaving = ref(false);

const form = reactive({ nombre: '', apellidos: '', email: '', telefono: '', direccion: '' });
const errors = reactive({});
const formError = ref('');
const currentStep = computed(() => (showCheckout.value ? 1 : 0));

const cuponCode = ref('');
const appliedCupon = ref(null);
const descuentoCupon = ref(0);
const couponLoading = ref(false);
const couponError = ref('');
const couponNotice = ref('');

/** Precio unitario vigente calculado por el servidor (ya incluye ofertas) */
const unitPrice = (item) => Number(item.producto.precio) || (item._unit ?? 0);
const hasDiscount = (item) => item.producto.precio_original != null && Number(item.producto.precio_original) > unitPrice(item);
const lineTotal = (item) => unitPrice(item) * item.cantidad;
const itemName = (item) => item.producto.nombre || item.producto.referencia;

/* Totales: los del servidor (fuente de verdad); el cálculo local sólo cubre el instante entre cambios */
const serverTotals = ref(null);
const localSubtotal = computed(() => items.value.reduce((sum, item) => sum + lineTotal(item), 0));
const subtotal = computed(() => serverTotals.value?.subtotal ?? localSubtotal.value);
const count = computed(() => items.value.reduce((sum, item) => sum + item.cantidad, 0));
const finalTotal = computed(() => serverTotals.value?.total ?? Math.max(0, subtotal.value - descuentoCupon.value));
const missingSizes = computed(() => items.value.some((item) => sizeOptions(item).length && !item.talla));
const whatsappNumber = computed(() => (page.props.app?.whatsapp || '').replace(/\D/g, '') || null);

const productImage = (item) => mediaUrl(item?.producto?.foto);
const sizeOptions = (item) => String(item?.producto?.tallas || '').split(',').map((s) => s.trim()).filter(Boolean);

const hydrate = (data) => {
  items.value = (data?.items || []).map((item) => ({
    ...item,
    _unit: item.subtotal && item.cantidad ? Number(item.subtotal) / item.cantidad : null,
    _syncing: false,
  }));
  const hadCupon = appliedCupon.value;
  appliedCupon.value = data?.cupon || null;
  descuentoCupon.value = Number(data?.descuento_cupon || 0);
  serverTotals.value = data && data.subtotal != null && data.total != null
    ? { subtotal: Number(data.subtotal), total: Number(data.total) }
    : null;
  if (hadCupon && !appliedCupon.value && items.value.length) {
    couponNotice.value = `El cupón ${hadCupon.codigo} ya no aplica a tu carrito y se quitó.`;
  }
};

const loadCart = async ({ silent = false } = {}) => {
  if (!silent) loading.value = true;
  loadError.value = '';
  try {
    const payload = await api('/api/carrito');
    hydrate(payload.data);
    window.dispatchEvent(new Event('cart-updated'));
  } catch (error) {
    if (!silent) loadError.value = error.message;
  } finally {
    loading.value = false;
  }
};

const applyCupon = async () => {
  const code = cuponCode.value.trim().toUpperCase();
  if (!code) return;
  couponLoading.value = true;
  couponError.value = '';
  try {
    const payload = await api('/api/carrito/aplicar-cupon', { method: 'POST', body: { codigo: code } });
    appliedCupon.value = payload.data.cupon;
    descuentoCupon.value = Number(payload.data.descuento_cupon || 0);
    couponNotice.value = '';
    cuponCode.value = '';
    loadCart({ silent: true });
    toast.success(`Cupón ${payload.data.cupon.codigo} aplicado: −${money(descuentoCupon.value)}`);
  } catch (error) {
    couponError.value = error.errors?.codigo || error.message || 'El cupón no es válido.';
  } finally {
    couponLoading.value = false;
  }
};

const removeCupon = async () => {
  try {
    await api('/api/carrito/quitar-cupon', { method: 'DELETE' });
    appliedCupon.value = null;
    descuentoCupon.value = 0;
    toast.success('Cupón quitado');
    loadCart({ silent: true });
  } catch (error) {
    toast.error(error.message);
  }
};

const changeQty = async (item, qty) => {
  qty = Math.min(999, Math.max(1, parseInt(qty, 10) || 1));
  if (qty === item.cantidad) return;
  const prev = item.cantidad;
  item.cantidad = qty;
  item._syncing = true;
  serverTotals.value = null;
  try {
    await api(`/api/carrito/actualizar/${item.id}`, { method: 'PUT', body: { cantidad: qty } });
    window.dispatchEvent(new Event('cart-updated'));
    await loadCart({ silent: true }); // totales y cupón recalculados en servidor
  } catch (error) {
    if (await handleStale(error)) return;
    item.cantidad = prev;
    toast.error(error.message || 'No se pudo actualizar la cantidad.');
  } finally {
    item._syncing = false;
  }
};

const changeSize = async (item, talla) => {
  const prev = item.talla;
  item.talla = talla || null;
  item._syncing = true;
  try {
    await api(`/api/carrito/actualizar/${item.id}`, { method: 'PUT', body: { talla: talla || null } });
  } catch (error) {
    if (await handleStale(error)) return;
    item.talla = prev;
    toast.error(error.message || 'No se pudo cambiar la talla.');
  } finally {
    item._syncing = false;
  }
};

const removeItem = async (item) => {
  const idx = items.value.findIndex((i) => i.id === item.id);
  if (idx === -1) return;
  const [removed] = items.value.splice(idx, 1);
  serverTotals.value = null;
  try {
    await api(`/api/carrito/eliminar/${item.id}`, { method: 'DELETE' });
    window.dispatchEvent(new Event('cart-updated'));
    toast.success(`${itemName(removed)} eliminado del carrito`);
    await loadCart({ silent: true });
  } catch (error) {
    if (await handleStale(error)) return;
    items.value.splice(idx, 0, removed);
    toast.error(error.message || 'No se pudo eliminar el producto.');
  }
};

/** 404 en actualizar/eliminar: el ítem ya no pertenece a este carrito → resincronizar */
const handleStale = async (error) => {
  if (error.status !== 404) return false;
  toast.info('Tu carrito cambió en otra pestaña. Lo actualizamos.');
  await loadCart({ silent: true });
  return true;
};

const emptyCartRequest = () => api('/api/carrito/vaciar', { method: 'DELETE' });

const clearCart = async () => {
  const ok = await confirm({
    title: '¿Vaciar el carrito?',
    message: `Se quitarán ${count.value} ${count.value === 1 ? 'artículo' : 'artículos'}.`,
    confirmLabel: 'Vaciar carrito',
  });
  if (!ok) return;
  const backup = { items: [...items.value], cupon: appliedCupon.value, descuento: descuentoCupon.value };
  items.value = [];
  appliedCupon.value = null;
  descuentoCupon.value = 0;
  serverTotals.value = null;
  try {
    await emptyCartRequest();
    window.dispatchEvent(new Event('cart-updated'));
  } catch (error) {
    items.value = backup.items;
    appliedCupon.value = backup.cupon;
    descuentoCupon.value = backup.descuento;
    toast.error(error.message || 'No se pudo vaciar el carrito.');
  }
};

/* ── Validación del checkout ── */
const rules = {
  nombre: (v) => (!v ? 'Escribe tu nombre.' : v.length > 255 ? 'El nombre es demasiado largo.' : ''),
  email: (v) => (!v ? 'Escribe tu correo.' : /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v) ? '' : 'Revisa el correo, parece incompleto.'),
  telefono: (v) => (!v ? '' : v.replace(/\D/g, '').length < 7 ? 'El celular debe tener al menos 7 dígitos.' : ''),
  direccion: (v) => (!v ? 'Escribe la dirección de entrega.' : v.length < 6 ? 'Agrega más detalles a la dirección.' : ''),
};
const validateField = (field) => {
  const message = rules[field]?.(form[field]) || '';
  if (message) errors[field] = message;
  else delete errors[field];
  return !message;
};
const validateAll = () => Object.keys(rules).map(validateField).every(Boolean);

const focusFirstError = async () => {
  await nextTick();
  checkoutPanel.value?.querySelector('[aria-invalid="true"]')?.focus();
};

const openCheckout = async () => {
  formError.value = '';
  showCheckout.value = true;
  await nextTick();
  checkoutPanel.value?.querySelector('input')?.focus();
};
const closeCheckout = () => { if (!saving.value) showCheckout.value = false; };

/** URL de la página de gracias; respeta un token/URL firmada si el backend la envía */
const thankYouUrl = (data, extra = {}) => {
  if (data?.gracias_url) return data.gracias_url;
  const params = new URLSearchParams();
  if (data?.token) params.set('token', data.token);
  Object.entries(extra).forEach(([key, value]) => params.set(key, value));
  const query = params.toString();
  return `/pedido/gracias/${data.id}${query ? `?${query}` : ''}`;
};

const submitOrder = async () => {
  formError.value = '';
  if (!validateAll()) {
    focusFirstError();
    return;
  }
  saving.value = true;
  try {
    const payload = await api('/api/pedidos', {
      method: 'POST',
      body: {
        ...form,
        items: items.value.map((i) => ({ producto_id: i.producto.id, cantidad: i.cantidad, talla: i.talla || null })),
      },
    });
    try { await emptyCartRequest(); } catch { /* el pedido ya existe; el carrito se limpia en la próxima visita */ }
    window.dispatchEvent(new Event('cart-updated'));
    showCheckout.value = false;
    router.visit(thankYouUrl(payload.data));
  } catch (error) {
    if (error.status === 422 && Object.keys(error.errors).length) {
      Object.entries(error.errors).forEach(([key, message]) => {
        if (key in form) errors[key] = message;
      });
      const itemError = Object.keys(error.errors).find((k) => k.startsWith('items'));
      formError.value = itemError ? 'Algún producto ya no está disponible. Revisa tu carrito.' : 'Revisa los campos marcados.';
      focusFirstError();
    } else {
      formError.value = error.message || 'No pudimos enviar el pedido. Inténtalo de nuevo.';
      if (error.status === 422) loadCart({ silent: true }); // stock o disponibilidad cambiaron
    }
  } finally {
    saving.value = false;
  }
};

/* ── Pedido por WhatsApp ── */
const buildWhatsappLink = () => {
  if (!whatsappNumber.value || !items.value.length) return null;
  const lines = items.value.map((item, idx) => {
    const size = item.talla ? ` (Talla ${item.talla})` : '';
    return `${idx + 1}. ${itemName(item)}${size} x ${item.cantidad} = ${money(lineTotal(item))}`;
  }).join('\n');
  let msg = `¡Hola! Quiero hacer este pedido:\n\n${lines}\n\nSubtotal: ${money(subtotal.value)}\n`;
  if (descuentoCupon.value > 0) msg += `Descuento cupón (${appliedCupon.value?.codigo}): -${money(descuentoCupon.value)}\n`;
  msg += `Total: ${money(finalTotal.value)}`;
  return { msg, url: (id) => `https://wa.me/${whatsappNumber.value}?text=${encodeURIComponent(`${msg}\n\nPedido #${id}`)}` };
};

const pedirWhatsapp = async () => {
  const link = buildWhatsappLink(); // se arma antes de vaciar el carrito
  if (!link) return;
  // La pestaña se abre de forma síncrona para que el navegador no la bloquee
  const tab = window.open('', '_blank');
  whatsappSaving.value = true;
  try {
    const payload = await api('/api/carrito/pedir-whatsapp', { method: 'POST' });
    const url = link.url(payload.data.id);
    items.value = [];
    window.dispatchEvent(new Event('cart-updated'));
    if (tab && !tab.closed) {
      tab.location.href = url;
      router.visit(thankYouUrl(payload.data, { canal: 'whatsapp' }));
    } else {
      window.location.href = url; // ventanas emergentes bloqueadas: abrir WhatsApp aquí mismo
    }
  } catch (error) {
    tab?.close();
    toast.error(error.message || 'No pudimos crear el pedido.');
    if (error.status === 422) loadCart({ silent: true });
  } finally {
    whatsappSaving.value = false;
  }
};

watch(showCheckout, (open) => { document.documentElement.style.overflow = open ? 'hidden' : ''; });
onMounted(() => loadCart());
onUnmounted(() => { document.documentElement.style.overflow = ''; });
</script>
