<template>
  <div>
    <PageHeader title="Pedidos" description="Confirma, actualiza el estado y corrige los pedidos de tu tienda." />

    <div class="a-card overflow-hidden">
      <div class="flex flex-col gap-3 border-b border-[var(--a-border)] p-3 sm:flex-row sm:items-center">
        <div class="a-tabs" role="tablist" aria-label="Filtrar por estado">
          <button v-for="tab in tabs" :key="tab.value" type="button" role="tab" class="a-tab" :aria-selected="statusFilter === tab.value ? 'true' : 'false'" @click="statusFilter = tab.value">
            {{ tab.label }}<span v-if="tab.count" class="ml-1 text-[var(--a-muted)] tabular">{{ tab.count }}</span>
          </button>
        </div>
        <SearchField v-model="query" class="sm:ml-auto sm:max-w-xs" placeholder="Buscar cliente o #pedido" label="Buscar pedidos" />
      </div>

      <div v-if="filtered.length" class="overflow-x-auto">
        <table class="a-table">
          <thead>
            <tr>
              <th scope="col">Pedido</th>
              <th scope="col">Fecha</th>
              <th scope="col">Cliente</th>
              <th scope="col">Canal</th>
              <th scope="col">Estado</th>
              <th scope="col" class="num">Artículos</th>
              <th scope="col" class="num">Total</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="pedido in filtered" :key="pedido.id" class="is-clickable" @click="openOrder(pedido)">
              <td>
                <button type="button" class="font-semibold underline-offset-2 hover:underline tabular" :aria-label="`Abrir pedido ${pedido.id}`" @click.stop="openOrder(pedido)">#{{ pedido.id }}</button>
              </td>
              <td class="whitespace-nowrap text-[var(--a-ink-2)]">{{ pedido.created_at }}</td>
              <td>
                <p class="font-medium">{{ customerName(pedido) }}</p>
                <p v-if="pedido.cliente?.email" class="text-xs text-[var(--a-muted)]">{{ pedido.cliente.email }}</p>
              </td>
              <td><span class="a-badge no-dot" :class="pedido.origen === 'whatsapp' ? 'tone-success' : ''">{{ pedido.origen === 'whatsapp' ? 'WhatsApp' : 'Web' }}</span></td>
              <td @click.stop>
                <label :for="`estado-${pedido.id}`" class="visually-hidden">Estado del pedido {{ pedido.id }}</label>
                <div class="flex items-center gap-2">
                  <StatusBadge :estado="pedido.estado" class="hidden xl:inline-flex" />
                  <select :id="`estado-${pedido.id}`" class="a-input !w-auto !min-h-8 !py-1 text-xs" :value="pedido.estado" :disabled="updating[pedido.id]" @change="changeStatus(pedido, $event.target.value, $event)">
                    <option v-for="state in ORDER_STATES" :key="state.value" :value="state.value">{{ state.label }}</option>
                  </select>
                  <span v-if="updating[pedido.id]" class="spinner text-[var(--a-muted)]" aria-label="Actualizando"></span>
                </div>
              </td>
              <td class="num">{{ pedido.items_count }}</td>
              <td class="num font-semibold">{{ money(pedido.total) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <EmptyState
        v-else
        icon="orders"
        :title="list.length ? 'No hay pedidos con este filtro' : 'Aún no hay pedidos'"
        :description="list.length ? 'Prueba con otro estado o borra la búsqueda.' : 'Los pedidos de la web y de WhatsApp aparecerán aquí.'"
      >
        <button v-if="list.length" type="button" class="a-btn a-btn-secondary" @click="statusFilter = 'ALL'; query = ''">Ver todos</button>
      </EmptyState>

      <AdminPagination :meta="meta" :only="['pedidos']" section="pedidos" />
    </div>

    <!-- Detalle / edición del pedido -->
    <Drawer :open="!!form" :title="form ? `Pedido #${form.id}` : ''" :subtitle="form ? `${form.created_at} · ${form.origen === 'whatsapp' ? 'WhatsApp' : 'Web'}` : ''" size="lg" :busy="saving" @close="closeOrder">
      <template v-if="form">
        <div class="a-card a-card-pad flex flex-wrap items-center gap-3">
          <StatusBadge :estado="form.estado" />
          <label for="drawer-estado" class="a-label !mb-0">Cambiar estado</label>
          <select id="drawer-estado" class="a-input !w-auto" :value="form.estado" :disabled="updating[form.id]" @change="changeStatus(form, $event.target.value, $event)">
            <option v-for="state in ORDER_STATES" :key="state.value" :value="state.value">{{ state.label }}</option>
          </select>
          <p class="w-full text-xs text-[var(--a-muted)]">Al confirmar se descuenta el stock de las tallas del pedido.</p>
        </div>

        <section class="a-card a-card-pad mt-4" aria-labelledby="ord-cliente">
          <h3 id="ord-cliente" class="a-subtitle">Cliente y entrega</h3>
          <div class="mt-3 grid gap-3 sm:grid-cols-2">
            <FormField v-slot="f" label="Nombre" :error="errors.nombre">
              <input :id="f.id" v-model.trim="form.nombre" class="a-input" :aria-invalid="f.invalid" :aria-describedby="f.describedby" />
            </FormField>
            <FormField v-slot="f" label="Teléfono" :error="errors.telefono">
              <input :id="f.id" v-model.trim="form.telefono" type="tel" class="a-input" :aria-invalid="f.invalid" :aria-describedby="f.describedby" />
            </FormField>
            <div class="text-sm sm:col-span-2">
              <p class="a-label">Correo</p>
              <p class="text-[var(--a-ink-2)]">{{ form.email || '—' }}<span v-if="form.apellidos"> · Apellidos: {{ form.apellidos }}</span></p>
            </div>
            <FormField v-slot="f" class="sm:col-span-2" label="Dirección de entrega" :error="errors.direccion">
              <textarea :id="f.id" v-model.trim="form.direccion" rows="2" class="a-input" :aria-invalid="f.invalid" :aria-describedby="f.describedby"></textarea>
            </FormField>
            <div class="flex flex-wrap gap-2 sm:col-span-2">
              <a v-if="whatsappLink" :href="whatsappLink" target="_blank" rel="noopener" class="a-btn a-btn-secondary a-btn-sm">Escribir por WhatsApp</a>
              <a v-if="form.email" :href="`mailto:${form.email}`" class="a-btn a-btn-secondary a-btn-sm">Enviar correo</a>
            </div>
          </div>
        </section>

        <section class="a-card mt-4 overflow-hidden" aria-labelledby="ord-items">
          <h3 id="ord-items" class="a-subtitle px-4 pt-4">Artículos</h3>
          <div class="mt-3 overflow-x-auto">
            <table class="a-table">
              <thead>
                <tr>
                  <th scope="col">Producto</th>
                  <th scope="col">Talla</th>
                  <th scope="col" class="num">Precio</th>
                  <th scope="col">Cant.</th>
                  <th scope="col" class="num">Subtotal</th>
                  <th scope="col"><span class="visually-hidden">Acciones</span></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, index) in form.items" :key="item._key">
                  <td class="font-medium">{{ item.producto_referencia }}<span v-if="!item.id" class="a-badge tone-info no-dot ml-2">Nuevo</span></td>
                  <td>
                    <label :for="`it-talla-${item._key}`" class="visually-hidden">Talla</label>
                    <input :id="`it-talla-${item._key}`" v-model.trim="item.talla" class="a-input !w-20 !min-h-8 !py-1" maxlength="40" />
                  </td>
                  <td class="num">{{ money(item.precio_unitario) }}</td>
                  <td>
                    <label :for="`it-cant-${item._key}`" class="visually-hidden">Cantidad</label>
                    <input :id="`it-cant-${item._key}`" v-model.number="item.cantidad" type="number" min="1" max="999" class="a-input !w-20 !min-h-8 !py-1" :aria-invalid="!(item.cantidad >= 1) ? 'true' : 'false'" />
                  </td>
                  <td class="num font-semibold">{{ money(item.precio_unitario * (item.cantidad || 0)) }}</td>
                  <td>
                    <button type="button" class="a-btn a-btn-plain-critical a-btn-sm a-btn-icon" :aria-label="`Quitar ${item.producto_referencia}`" @click="removeItem(index)"><AdminIcon name="trash" size="sm" /></button>
                  </td>
                </tr>
                <tr v-if="!form.items.length">
                  <td colspan="6" class="text-center text-sm text-[var(--a-muted)]">El pedido no tiene artículos. Agrega al menos uno.</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="flex flex-wrap items-end gap-2 border-t border-[var(--a-border)] p-4">
            <FormField v-slot="f" label="Agregar producto" class="min-w-48 flex-1">
              <select :id="f.id" v-model="newItem.producto_id" class="a-input">
                <option :value="null">Selecciona un producto</option>
                <option v-for="p in productOptions" :key="p.id" :value="p.id">{{ p.nombre || p.referencia }} — {{ money(p.precio) }}</option>
              </select>
            </FormField>
            <FormField v-slot="f" label="Talla" optional>
              <input :id="f.id" v-model.trim="newItem.talla" class="a-input !w-24" maxlength="40" />
            </FormField>
            <FormField v-slot="f" label="Cant.">
              <input :id="f.id" v-model.number="newItem.cantidad" type="number" min="1" max="999" class="a-input !w-20" />
            </FormField>
            <button type="button" class="a-btn a-btn-secondary" :disabled="!newItem.producto_id" @click="addItem"><AdminIcon name="plus" size="sm" />Agregar</button>
          </div>
        </section>

        <section class="a-card a-card-pad mt-4" aria-labelledby="ord-total">
          <h3 id="ord-total" class="a-subtitle">Totales</h3>
          <dl class="mt-3 space-y-1.5 text-sm tabular">
            <div class="flex justify-between"><dt class="text-[var(--a-ink-2)]">Subtotal</dt><dd>{{ money(itemsSubtotal) }}</dd></div>
            <div v-if="form.descuento_cupon > 0" class="flex justify-between text-[var(--tone-success)]"><dt>Descuento cupón</dt><dd>−{{ money(form.descuento_cupon) }}</dd></div>
            <div class="flex justify-between border-t border-[var(--a-border)] pt-2 text-base font-bold"><dt>Total</dt><dd>{{ money(manualTotal ? form.total : computedTotal) }}</dd></div>
          </dl>
          <label class="a-check mt-4">
            <input v-model="manualTotal" type="checkbox" />
            Ajustar el total a mano (por ejemplo, para sumar el envío)
          </label>
          <FormField v-if="manualTotal" v-slot="f" class="mt-3 max-w-xs" label="Total final (COP)" :error="errors.total">
            <input :id="f.id" v-model.number="form.total" type="number" min="0" step="100" class="a-input" :aria-invalid="f.invalid" :aria-describedby="f.describedby" />
          </FormField>
        </section>
      </template>

      <template #footer>
        <button type="button" class="a-btn a-btn-secondary" :disabled="saving" @click="closeOrder">Cancelar</button>
        <button type="button" class="a-btn a-btn-primary" :disabled="saving || !dirty" @click="saveOrder">
          <span v-if="saving" class="spinner" aria-hidden="true"></span>
          {{ saving ? 'Guardando…' : 'Guardar cambios' }}
        </button>
      </template>
    </Drawer>
  </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import AdminIcon from '../AdminIcon.vue';
import AdminPagination from '../AdminPagination.vue';
import EmptyState from '../EmptyState.vue';
import FormField from '../FormField.vue';
import PageHeader from '../PageHeader.vue';
import SearchField from '../SearchField.vue';
import StatusBadge from '../StatusBadge.vue';
import Drawer from '../../ui/Drawer.vue';
import { ORDER_STATES, orderState } from '../adminNav';
import { useConfirm } from '../../../composables/useConfirm';
import { useToast } from '../../../composables/useToast';
import { api } from '../../../utils/http';
import { money } from '../../../utils/format';

const props = defineProps({
  pedidos: { type: [Object, Array], default: () => ({ data: [] }) },
  productOptions: { type: Array, default: () => [] },
});

const toast = useToast();
const { confirm } = useConfirm();

const list = computed(() => (Array.isArray(props.pedidos) ? props.pedidos : props.pedidos?.data || []));
const meta = computed(() => (Array.isArray(props.pedidos) ? null : props.pedidos));

const statusFilter = ref('ALL');
const query = ref('');
const tabs = computed(() => [
  { value: 'ALL', label: 'Todos', count: 0 },
  ...ORDER_STATES.map((s) => ({ value: s.value, label: s.label, count: list.value.filter((p) => p.estado === s.value).length })),
]);

const customerName = (p) => [p.cliente?.nombre, p.cliente?.apellidos].filter(Boolean).join(' ') || (p.origen === 'whatsapp' ? 'Cliente de WhatsApp' : 'Sin nombre');

const filtered = computed(() => {
  const q = query.value.trim().toLowerCase().replace(/^#/, '');
  return list.value.filter((p) => {
    if (statusFilter.value !== 'ALL' && p.estado !== statusFilter.value) return false;
    if (!q) return true;
    return String(p.id) === q
      || customerName(p).toLowerCase().includes(q)
      || (p.cliente?.email || '').toLowerCase().includes(q)
      || (p.cliente?.telefono || '').includes(q);
  });
});

const reload = () => new Promise((resolve) => router.reload({ only: ['pedidos', 'stats', 'productos'], onFinish: resolve }));

/* ── Cambio de estado ── */
const updating = reactive({});
const changeStatus = async (pedido, estado, event) => {
  const previous = pedido.estado;
  if (estado === previous) return;
  const label = orderState(estado).label.toLowerCase();
  if (['CONFIRMADO', 'CANCELADO'].includes(estado)) {
    const ok = await confirm({
      title: estado === 'CONFIRMADO' ? `¿Confirmar el pedido #${pedido.id}?` : `¿Cancelar el pedido #${pedido.id}?`,
      message: estado === 'CONFIRMADO'
        ? 'Se descontará del inventario el stock de cada talla del pedido.'
        : 'El cliente dejará de ver este pedido como activo.',
      confirmLabel: estado === 'CONFIRMADO' ? 'Confirmar pedido' : 'Cancelar pedido',
      cancelLabel: 'Volver',
      tone: estado === 'CANCELADO' ? 'critical' : 'primary',
    });
    if (!ok) {
      if (event?.target) event.target.value = previous;
      return;
    }
  }
  updating[pedido.id] = true;
  try {
    await api(`/admin/pedidos/${pedido.id}/estado`, { method: 'PUT', body: { estado } });
    pedido.estado = estado;
    if (form.value?.id === pedido.id) form.value.estado = estado;
    toast.success(`Pedido #${pedido.id} marcado como ${label}`);
    reload();
  } catch (error) {
    if (event?.target) event.target.value = previous;
    toast.error(error.message);
  } finally {
    updating[pedido.id] = false;
  }
};

/* ── Edición ── */
const form = ref(null);
const original = ref(null);
const errors = reactive({});
const saving = ref(false);
const manualTotal = ref(false);
const newItem = reactive({ producto_id: null, talla: '', cantidad: 1 });
let keySeq = 0;

const snapshot = (value) => JSON.stringify({
  nombre: value.nombre, telefono: value.telefono, direccion: value.direccion,
  items: value.items.map((i) => [i.id, i.producto_id, i.cantidad, i.talla || '']),
});

const openOrder = (pedido) => {
  Object.keys(errors).forEach((k) => delete errors[k]);
  manualTotal.value = false;
  form.value = {
    id: pedido.id,
    estado: pedido.estado,
    origen: pedido.origen,
    created_at: pedido.created_at,
    nombre: pedido.cliente?.nombre || '',
    apellidos: pedido.cliente?.apellidos || '',
    email: pedido.cliente?.email || '',
    telefono: pedido.cliente?.telefono || '',
    direccion: pedido.direccion || '',
    total: Number(pedido.total) || 0,
    descuento_cupon: Number(pedido.descuento_cupon) || 0,
    items: (pedido.items || []).map((item) => ({ ...item, talla: item.talla || '', _key: ++keySeq })),
  };
  original.value = { ...form.value, items: form.value.items.map((i) => ({ ...i })) };
  Object.assign(newItem, { producto_id: null, talla: '', cantidad: 1 });
};

const closeOrder = async () => {
  if (dirty.value) {
    const ok = await confirm({ title: '¿Descartar cambios?', message: 'Los cambios del pedido no se han guardado.', confirmLabel: 'Descartar', cancelLabel: 'Seguir editando' });
    if (!ok) return;
  }
  form.value = null;
};

const dirty = computed(() => {
  if (!form.value || !original.value) return false;
  return manualTotal.value || snapshot(form.value) !== snapshot(original.value);
});

const itemsSubtotal = computed(() => (form.value?.items || []).reduce((sum, i) => sum + Number(i.precio_unitario) * (Number(i.cantidad) || 0), 0));
const computedTotal = computed(() => Math.max(0, itemsSubtotal.value - (form.value?.descuento_cupon || 0)));
watch(manualTotal, (on) => { if (on && form.value) form.value.total = computedTotal.value; });

const whatsappLink = computed(() => {
  const digits = (form.value?.telefono || '').replace(/\D/g, '');
  if (digits.length < 7) return null;
  const number = digits.length === 10 ? `57${digits}` : digits; // celular colombiano sin indicativo
  return `https://wa.me/${number}?text=${encodeURIComponent(`Hola ${form.value.nombre || ''}, te escribimos por tu pedido #${form.value.id}.`)}`;
});

const removeItem = (index) => { form.value.items.splice(index, 1); };

const addItem = () => {
  const product = props.productOptions.find((p) => p.id === newItem.producto_id);
  if (!product) return;
  const cantidad = Math.max(1, Math.min(999, Number(newItem.cantidad) || 1));
  form.value.items.push({
    id: null,
    producto_id: product.id,
    producto_referencia: product.referencia,
    precio_unitario: Number(product.precio) || 0,
    cantidad,
    talla: newItem.talla || '',
    _key: ++keySeq,
  });
  Object.assign(newItem, { producto_id: null, talla: '', cantidad: 1 });
};

const validate = () => {
  Object.keys(errors).forEach((k) => delete errors[k]);
  if (form.value.telefono && form.value.telefono.replace(/\D/g, '').length < 7) errors.telefono = 'Revisa el teléfono (mínimo 7 dígitos).';
  if (manualTotal.value && !(Number(form.value.total) >= 0)) errors.total = 'Escribe un total válido.';
  if (form.value.items.some((i) => !(Number(i.cantidad) >= 1 && Number(i.cantidad) <= 999))) {
    toast.error('Cada artículo debe tener una cantidad entre 1 y 999.');
    return false;
  }
  if (!form.value.items.length) {
    toast.error('El pedido debe tener al menos un artículo.');
    return false;
  }
  return !Object.keys(errors).length;
};

/**
 * Guarda en orden: datos del cliente → artículos eliminados → editados → nuevos → total manual.
 * El backend recalcula el total tras cada cambio de artículos.
 */
const saveOrder = async () => {
  if (!validate()) return;
  saving.value = true;
  const id = form.value.id;
  const before = original.value;
  try {
    const contactChanged = ['nombre', 'telefono', 'direccion'].some((k) => form.value[k] !== before[k]);
    if (contactChanged) {
      await api(`/admin/pedidos/${id}`, { method: 'PUT', body: { nombre: form.value.nombre, telefono: form.value.telefono, direccion: form.value.direccion } });
    }
    const currentIds = new Set(form.value.items.filter((i) => i.id).map((i) => i.id));
    for (const item of before.items.filter((i) => i.id && !currentIds.has(i.id))) {
      await api(`/admin/pedidos/${id}/items/${item.id}`, { method: 'DELETE' });
    }
    for (const item of form.value.items.filter((i) => i.id)) {
      const prev = before.items.find((i) => i.id === item.id);
      if (prev && (Number(prev.cantidad) !== Number(item.cantidad) || (prev.talla || '') !== (item.talla || ''))) {
        await api(`/admin/pedidos/${id}/items/${item.id}`, { method: 'PUT', body: { cantidad: Number(item.cantidad), talla: item.talla || null } });
      }
    }
    for (const item of form.value.items.filter((i) => !i.id)) {
      await api(`/admin/pedidos/${id}/items`, { method: 'POST', body: { producto_id: item.producto_id, cantidad: Number(item.cantidad), talla: item.talla || null } });
    }
    if (manualTotal.value) {
      await api(`/admin/pedidos/${id}`, { method: 'PUT', body: { total: Number(form.value.total) } });
    }
    toast.success(`Pedido #${id} actualizado`);
    form.value = null;
    await reload();
  } catch (error) {
    Object.entries(error.errors || {}).forEach(([key, message]) => { errors[key] = message; });
    toast.error(error.message || 'No se pudo guardar el pedido.');
    await reload(); // refleja lo que sí alcanzó a guardarse
    const fresh = list.value.find((p) => p.id === id);
    if (fresh) openOrder(fresh);
  } finally {
    saving.value = false;
  }
};
</script>
