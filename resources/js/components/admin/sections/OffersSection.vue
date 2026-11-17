<template>
  <div>
    <PageHeader title="Ofertas" description="Descuentos automáticos por producto o por categoría, con fecha de inicio y fin.">
      <button type="button" class="a-btn a-btn-primary" @click="openCreate"><AdminIcon name="plus" size="sm" />Crear oferta</button>
    </PageHeader>

    <div class="a-card overflow-hidden">
      <div class="flex flex-col gap-3 border-b border-[var(--a-border)] p-3 sm:flex-row sm:items-center">
        <div class="a-tabs" role="tablist" aria-label="Filtrar ofertas">
          <button v-for="tab in tabs" :key="tab.value" type="button" role="tab" class="a-tab" :aria-selected="filter === tab.value ? 'true' : 'false'" @click="filter = tab.value">{{ tab.label }}</button>
        </div>
        <SearchField :model-value="localSearch" class="sm:ml-auto sm:max-w-xs" placeholder="Buscar oferta, producto o categoría" label="Buscar ofertas" @update:model-value="onSearchInput" />
      </div>

      <div v-if="filtered.length" class="overflow-x-auto">
        <table class="a-table">
          <thead>
            <tr>
              <th scope="col">Oferta</th>
              <th scope="col">Aplica a</th>
              <th scope="col">Descuento</th>
              <th scope="col">Vigencia</th>
              <th scope="col">Estado</th>
              <th scope="col"><span class="visually-hidden">Acciones</span></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in filtered" :key="item.id" class="is-clickable" @click="openEdit(item)">
              <td><button type="button" class="text-left font-semibold hover:underline" @click.stop="openEdit(item)">{{ item.titulo }}</button></td>
              <td class="text-[var(--a-ink-2)]">{{ targetLabel(item) }}</td>
              <td class="whitespace-nowrap font-medium tabular">{{ discountLabel(item) }}</td>
              <td class="whitespace-nowrap text-xs text-[var(--a-ink-2)]">{{ formatDate(item.fecha_inicio) }} – {{ formatDate(item.fecha_fin) }}</td>
              <td><span class="a-badge" :class="`tone-${status(item).tone}`">{{ status(item).label }}</span></td>
              <td @click.stop>
                <button type="button" class="a-btn a-btn-plain-critical a-btn-sm a-btn-icon" :aria-label="`Eliminar ${item.titulo}`" @click="remove(item)"><AdminIcon name="trash" size="sm" /></button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <EmptyState v-else icon="percent" :title="list.length ? 'Ninguna oferta con este filtro' : 'Sin ofertas todavía'" description="Crea una oferta para mostrar precios rebajados en el catálogo.">
        <button v-if="!list.length" type="button" class="a-btn a-btn-primary" @click="openCreate">Crear oferta</button>
      </EmptyState>

      <AdminPagination :meta="meta" :only="['ofertas']" section="ofertas" />
    </div>

    <Drawer :open="drawerOpen" :title="form.id ? 'Editar oferta' : 'Nueva oferta'" :busy="saving" @close="close">
      <form id="offer-form" class="space-y-4" novalidate @submit.prevent="save">
        <section class="a-card a-card-pad space-y-4">
          <FormField v-slot="f" label="Título" required :error="errors.titulo" help="Uso interno; no se muestra al cliente.">
            <input :id="f.id" v-model.trim="form.titulo" class="a-input" maxlength="255" placeholder="Ej. Black Friday gorras" :aria-invalid="f.invalid" :aria-describedby="f.describedby" />
          </FormField>
          <FormField v-slot="f" label="Descripción" optional>
            <input :id="f.id" v-model.trim="form.descripcion" class="a-input" />
          </FormField>
        </section>

        <section class="a-card a-card-pad space-y-3">
          <fieldset>
            <legend class="a-label">Aplica a</legend>
            <div class="mt-1 flex gap-4">
              <label class="a-check"><input v-model="form.target" type="radio" value="producto" />Un producto</label>
              <label class="a-check"><input v-model="form.target" type="radio" value="categoria" />Una categoría</label>
            </div>
          </fieldset>
          <FormField v-if="form.target === 'producto'" v-slot="f" label="Producto" required :error="errors.producto_id">
            <select :id="f.id" v-model="form.producto_id" class="a-input" :aria-invalid="f.invalid" :aria-describedby="f.describedby">
              <option :value="null">Selecciona un producto</option>
              <option v-for="p in productChoices" :key="p.id" :value="p.id">{{ p.nombre || p.referencia }} ({{ p.referencia }})</option>
            </select>
          </FormField>
          <FormField v-else v-slot="f" label="Categoría" required :error="errors.categoria_id">
            <select :id="f.id" v-model="form.categoria_id" class="a-input" :aria-invalid="f.invalid" :aria-describedby="f.describedby">
              <option :value="null">Selecciona una categoría</option>
              <option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.categoria }}</option>
            </select>
          </FormField>
        </section>

        <section class="a-card a-card-pad space-y-3">
          <fieldset>
            <legend class="a-label">Tipo de descuento</legend>
            <div class="mt-1 flex flex-wrap gap-4">
              <label class="a-check"><input v-model="form.tipo" type="radio" value="porcentaje" />Porcentaje</label>
              <label class="a-check"><input v-model="form.tipo" type="radio" value="fijo" />Valor fijo</label>
              <label class="a-check"><input v-model="form.tipo" type="radio" value="precio" />Precio especial</label>
            </div>
          </fieldset>
          <FormField v-slot="f" :label="valueLabel" required :error="errors.valor" :help="valueHelp">
            <input :id="f.id" v-model="form.valor" type="number" inputmode="decimal" min="0" :max="form.tipo === 'porcentaje' ? 100 : undefined" :step="form.tipo === 'porcentaje' ? 1 : 100" class="a-input max-w-[12rem]" :aria-invalid="f.invalid" :aria-describedby="f.describedby" />
          </FormField>
        </section>

        <section class="a-card a-card-pad space-y-3">
          <div class="grid gap-3 sm:grid-cols-2">
            <FormField v-slot="f" label="Empieza" required :error="errors.fecha_inicio">
              <input :id="f.id" v-model="form.fecha_inicio" type="datetime-local" class="a-input" :aria-invalid="f.invalid" :aria-describedby="f.describedby" />
            </FormField>
            <FormField v-slot="f" label="Termina" required :error="errors.fecha_fin">
              <input :id="f.id" v-model="form.fecha_fin" type="datetime-local" class="a-input" :aria-invalid="f.invalid" :aria-describedby="f.describedby" />
            </FormField>
          </div>
          <label class="a-check"><input v-model="form.esta_activa" type="checkbox" />Oferta activa</label>
        </section>
      </form>
      <template #footer>
        <button v-if="form.id" type="button" class="a-btn a-btn-plain-critical mr-auto" :disabled="saving" @click="remove(form)">Eliminar</button>
        <button type="button" class="a-btn a-btn-secondary" :disabled="saving" @click="close">Cancelar</button>
        <button type="submit" form="offer-form" class="a-btn a-btn-primary" :disabled="saving">
          <span v-if="saving" class="spinner" aria-hidden="true"></span>
          {{ saving ? 'Guardando…' : form.id ? 'Guardar cambios' : 'Crear oferta' }}
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
import Drawer from '../../ui/Drawer.vue';
import { useConfirm } from '../../../composables/useConfirm';
import { useToast } from '../../../composables/useToast';
import { api } from '../../../utils/http';
import { debounce, formatDate, money, toDatetimeLocal } from '../../../utils/format';

const props = defineProps({
  ofertas: { type: [Object, Array], default: () => ({ data: [] }) },
  categorias: { type: Array, default: () => [] },
  productOptions: { type: Array, default: () => [] },
  search: { type: String, default: '' },
});
const emit = defineEmits(['search']);

const toast = useToast();
const { confirm } = useConfirm();

const list = computed(() => (Array.isArray(props.ofertas) ? props.ofertas : props.ofertas?.data || []));
const meta = computed(() => (Array.isArray(props.ofertas) ? null : props.ofertas));

const status = (item) => {
  const now = Date.now();
  if (!item.esta_activa) return { label: 'Pausada', tone: 'neutral', key: 'off' };
  if (item.fecha_inicio && new Date(item.fecha_inicio).getTime() > now) return { label: 'Programada', tone: 'info', key: 'scheduled' };
  if (item.fecha_fin && new Date(item.fecha_fin).getTime() < now) return { label: 'Vencida', tone: 'warning', key: 'expired' };
  return { label: 'Activa', tone: 'success', key: 'active' };
};

const filter = ref('ALL');
const tabs = [
  { value: 'ALL', label: 'Todas' },
  { value: 'active', label: 'Activas' },
  { value: 'scheduled', label: 'Programadas' },
  { value: 'expired', label: 'Vencidas' },
  { value: 'off', label: 'Pausadas' },
];
const filtered = computed(() => list.value.filter((o) => filter.value === 'ALL' || status(o).key === filter.value));

const localSearch = ref(props.search);
watch(() => props.search, (v) => { localSearch.value = v; });
const emitSearch = debounce((v) => emit('search', v), 350);
const onSearchInput = (v) => { localSearch.value = v; emitSearch(v.trim()); };

const targetLabel = (o) => {
  if (o.producto_id) return `Producto: ${o.producto?.referencia || `#${o.producto_id}`}`;
  if (o.categoria_id) return `Categoría: ${o.categoria?.categoria || `#${o.categoria_id}`}`;
  return '—';
};
const discountLabel = (o) => {
  if (Number(o.descuento_porcentaje) > 0) return `−${Number(o.descuento_porcentaje)}%`;
  if (Number(o.descuento_fijo) > 0) return `−${money(o.descuento_fijo)}`;
  if (Number(o.precio_oferta) > 0) return `Precio ${money(o.precio_oferta)}`;
  return '—';
};

/* Selector de productos: añade el producto de la oferta si no está en la página cargada */
const productChoices = computed(() => {
  const options = [...props.productOptions];
  if (form.value.producto_id && !options.some((p) => p.id === form.value.producto_id)) {
    const current = list.value.find((o) => o.producto_id === form.value.producto_id)?.producto;
    options.unshift({ id: form.value.producto_id, referencia: current?.referencia || `#${form.value.producto_id}` });
  }
  return options;
});

const emptyForm = () => {
  const now = new Date();
  return {
    id: null, titulo: '', descripcion: '', target: 'producto', producto_id: null, categoria_id: null,
    tipo: 'porcentaje', valor: '', fecha_inicio: toDatetimeLocal(now),
    fecha_fin: toDatetimeLocal(new Date(now.getTime() + 7 * 86400000)), esta_activa: true,
  };
};

const drawerOpen = ref(false);
const form = ref(emptyForm());
const errors = reactive({});
const saving = ref(false);
let initial = '';

const valueLabel = computed(() => ({ porcentaje: 'Porcentaje de descuento (%)', fijo: 'Descuento en pesos (COP)', precio: 'Precio final de oferta (COP)' }[form.value.tipo]));
const valueHelp = computed(() => ({ porcentaje: 'Entre 1 y 100.', fijo: 'Se resta del precio normal.', precio: 'Reemplaza el precio normal mientras dure la oferta.' }[form.value.tipo]));

const clearErrors = () => Object.keys(errors).forEach((k) => delete errors[k]);
const openCreate = () => { clearErrors(); form.value = emptyForm(); initial = JSON.stringify(form.value); drawerOpen.value = true; };
const openEdit = (o) => {
  clearErrors();
  const tipo = Number(o.descuento_porcentaje) > 0 ? 'porcentaje' : Number(o.descuento_fijo) > 0 ? 'fijo' : 'precio';
  form.value = {
    id: o.id,
    titulo: o.titulo || '',
    descripcion: o.descripcion || '',
    target: o.producto_id ? 'producto' : 'categoria',
    producto_id: o.producto_id || null,
    categoria_id: o.categoria_id || null,
    tipo,
    valor: Number({ porcentaje: o.descuento_porcentaje, fijo: o.descuento_fijo, precio: o.precio_oferta }[tipo]) || '',
    fecha_inicio: toDatetimeLocal(o.fecha_inicio),
    fecha_fin: toDatetimeLocal(o.fecha_fin),
    esta_activa: Boolean(o.esta_activa),
  };
  initial = JSON.stringify(form.value);
  drawerOpen.value = true;
};
const close = async () => {
  if (JSON.stringify(form.value) !== initial) {
    const ok = await confirm({ title: '¿Descartar cambios?', message: 'La oferta tiene cambios sin guardar.', confirmLabel: 'Descartar', cancelLabel: 'Seguir editando' });
    if (!ok) return;
  }
  drawerOpen.value = false;
};

const validate = () => {
  clearErrors();
  const f = form.value;
  if (!f.titulo) errors.titulo = 'Escribe un título.';
  if (f.target === 'producto' && !f.producto_id) errors.producto_id = 'Elige el producto.';
  if (f.target === 'categoria' && !f.categoria_id) errors.categoria_id = 'Elige la categoría.';
  const value = Number(f.valor);
  if (f.valor === '' || !(value > 0)) errors.valor = 'Escribe un valor mayor que 0.';
  else if (f.tipo === 'porcentaje' && value > 100) errors.valor = 'El porcentaje no puede superar 100.';
  if (!f.fecha_inicio) errors.fecha_inicio = 'Elige la fecha de inicio.';
  if (!f.fecha_fin) errors.fecha_fin = 'Elige la fecha de fin.';
  else if (f.fecha_inicio && new Date(f.fecha_fin) <= new Date(f.fecha_inicio)) errors.fecha_fin = 'Debe terminar después de empezar.';
  return !Object.keys(errors).length;
};

const save = async () => {
  if (!validate()) return;
  const f = form.value;
  const body = {
    titulo: f.titulo,
    descripcion: f.descripcion || null,
    producto_id: f.target === 'producto' ? f.producto_id : null,
    categoria_id: f.target === 'categoria' ? f.categoria_id : null,
    descuento_porcentaje: f.tipo === 'porcentaje' ? Number(f.valor) : null,
    descuento_fijo: f.tipo === 'fijo' ? Number(f.valor) : null,
    precio_oferta: f.tipo === 'precio' ? Number(f.valor) : null,
    fecha_inicio: f.fecha_inicio,
    fecha_fin: f.fecha_fin,
    esta_activa: f.esta_activa,
  };
  saving.value = true;
  try {
    const payload = await api(f.id ? `/admin/ofertas/${f.id}` : '/admin/ofertas', { method: f.id ? 'PUT' : 'POST', body });
    toast.success(payload.message || 'Oferta guardada');
    drawerOpen.value = false;
    router.reload({ only: ['ofertas', 'stats'] });
  } catch (error) {
    Object.entries(error.errors || {}).forEach(([key, message]) => {
      const field = ['descuento_porcentaje', 'descuento_fijo', 'precio_oferta'].includes(key) ? 'valor' : key;
      errors[field] = message;
    });
    toast.error(error.message);
  } finally {
    saving.value = false;
  }
};

const remove = async (item) => {
  const ok = await confirm({ title: `¿Eliminar la oferta “${item.titulo}”?`, message: 'Los productos volverán a su precio normal.', confirmLabel: 'Eliminar oferta' });
  if (!ok) return;
  try {
    const payload = await api(`/admin/ofertas/${item.id}`, { method: 'DELETE' });
    toast.success(payload.message || 'Oferta eliminada');
    drawerOpen.value = false;
    router.reload({ only: ['ofertas', 'stats'] });
  } catch (error) {
    toast.error(error.message);
  }
};
</script>
