<template>
  <div>
    <PageHeader :title="`Hola, ${firstName}`" description="Esto es lo que necesita tu atención hoy.">
      <a href="/" target="_blank" rel="noopener" class="a-btn a-btn-secondary">Ver tienda</a>
    </PageHeader>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <button v-for="kpi in kpis" :key="kpi.label" type="button" class="a-card a-card-pad text-left transition hover:shadow-[var(--a-shadow-pop)]" @click="$emit('navigate', kpi.section)">
        <p class="text-xs font-medium text-[var(--a-muted)]">{{ kpi.label }}</p>
        <p class="mt-1 text-2xl font-bold tabular">{{ kpi.value }}</p>
        <p v-if="kpi.hint" class="mt-1 text-xs" :class="kpi.alert ? 'font-semibold text-[var(--tone-attention)]' : 'text-[var(--a-muted)]'">{{ kpi.hint }}</p>
      </button>
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
      <section class="a-card overflow-hidden" aria-labelledby="ov-orders">
        <div class="flex items-center justify-between px-4 py-3">
          <h2 id="ov-orders" class="a-subtitle">Pedidos recientes</h2>
          <button type="button" class="a-btn a-btn-plain a-btn-sm" @click="$emit('navigate', 'pedidos')">Ver todos</button>
        </div>
        <ul v-if="recentOrders.length" class="divide-y divide-[var(--a-border)] border-t border-[var(--a-border)]">
          <li v-for="pedido in recentOrders" :key="pedido.id" class="flex items-center gap-3 px-4 py-3 text-sm">
            <span class="w-12 font-semibold tabular">#{{ pedido.id }}</span>
            <div class="min-w-0 flex-1">
              <p class="truncate font-medium">{{ customerName(pedido) }}</p>
              <p class="text-xs text-[var(--a-muted)]">{{ pedido.created_at }} · {{ pedido.origen === 'whatsapp' ? 'WhatsApp' : 'Web' }}</p>
            </div>
            <StatusBadge :estado="pedido.estado" />
            <span class="w-24 text-right font-semibold tabular">{{ money(pedido.total) }}</span>
          </li>
        </ul>
        <EmptyState v-else icon="orders" title="Aún no hay pedidos" description="Cuando un cliente compre por la web o por WhatsApp lo verás aquí." />
      </section>

      <div class="space-y-5">
        <section class="a-card a-card-pad" aria-labelledby="ov-stock">
          <h2 id="ov-stock" class="a-subtitle">Inventario por revisar</h2>
          <ul v-if="stockAlerts.length" class="mt-3 space-y-2 text-sm">
            <li v-for="alert in stockAlerts" :key="alert.key" class="flex items-center justify-between gap-3">
              <span class="min-w-0 truncate">{{ alert.label }}</span>
              <span class="a-badge" :class="alert.tone">{{ alert.value }}</span>
            </li>
          </ul>
          <p v-else class="mt-2 text-sm text-[var(--a-muted)]">Todo en orden en los registros visibles.</p>
        </section>

        <section class="a-card a-card-pad" aria-labelledby="ov-quick">
          <h2 id="ov-quick" class="a-subtitle">Accesos rápidos</h2>
          <div class="mt-3 grid grid-cols-2 gap-2">
            <button type="button" class="a-btn a-btn-secondary justify-start" @click="$emit('navigate', 'productos')"><AdminIcon name="plus" size="sm" />Producto</button>
            <button type="button" class="a-btn a-btn-secondary justify-start" @click="$emit('navigate', 'cupones')"><AdminIcon name="ticket" size="sm" />Cupón</button>
            <button type="button" class="a-btn a-btn-secondary justify-start" @click="$emit('navigate', 'contenido')"><AdminIcon name="image" size="sm" />Carrusel</button>
            <button type="button" class="a-btn a-btn-secondary justify-start" @click="$emit('navigate', 'ajustes')"><AdminIcon name="settings" size="sm" />Colores</button>
          </div>
        </section>
      </div>
    </div>
  </div>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminIcon from '../AdminIcon.vue';
import EmptyState from '../EmptyState.vue';
import PageHeader from '../PageHeader.vue';
import StatusBadge from '../StatusBadge.vue';
import { money } from '../../../utils/format';

const props = defineProps({
  stats: { type: Object, required: true },
  pedidos: { type: Array, default: () => [] },
  insumos: { type: Array, default: () => [] },
  productos: { type: Array, default: () => [] },
  settings: { type: Object, default: () => ({}) },
});
defineEmits(['navigate']);

const page = usePage();
const firstName = computed(() => (page.props.auth?.user?.name || '').split(' ')[0] || 'equipo');
const pending = computed(() => props.stats?.pedidosPendientes ?? props.pedidos.filter((p) => p.estado === 'PENDIENTE').length);
const recentOrders = computed(() => props.pedidos.slice(0, 6));
const customerName = (p) => [p.cliente?.nombre, p.cliente?.apellidos].filter(Boolean).join(' ') || (p.origen === 'whatsapp' ? 'Cliente de WhatsApp' : 'Sin nombre');

const kpis = computed(() => [
  { label: 'Pedidos pendientes', value: pending.value, hint: pending.value ? 'Confírmalos para descontar stock' : 'Nada pendiente', alert: pending.value > 0, section: 'pedidos' },
  { label: 'Productos', value: props.stats?.productos ?? 0, hint: 'En el catálogo', section: 'productos' },
  { label: 'Ofertas activas', value: props.stats?.ofertasActivas ?? 0, hint: `${props.stats?.ofertas ?? 0} en total`, section: 'ofertas' },
  { label: 'Insumos', value: props.stats?.insumos ?? 0, hint: 'Materia prima registrada', section: 'insumos' },
]);

const stockAlerts = computed(() => {
  const alerts = [];
  props.productos
    .filter((p) => p.estado === 'DISPONIBLE' && (p.stock_por_talla?.length ?? 0) > 0 && p.stock_total <= 0)
    .slice(0, 4)
    .forEach((p) => alerts.push({ key: `p${p.id}`, label: p.nombre || p.referencia, value: 'Sin stock', tone: 'tone-critical' }));
  props.insumos
    .filter((i) => Number(i.stock_actual) <= Number(i.stock_minimo))
    .slice(0, 4)
    .forEach((i) => alerts.push({ key: `i${i.id}`, label: `Insumo: ${i.nombre}`, value: `${i.stock_actual} ${i.unidad}`, tone: 'tone-warning' }));
  return alerts;
});
</script>
