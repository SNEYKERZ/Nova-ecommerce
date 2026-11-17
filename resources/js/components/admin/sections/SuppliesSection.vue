<template>
  <div>
    <PageHeader title="Insumos" description="Materia prima para la fabricación: costo por unidad y alertas de stock mínimo.">
      <button type="button" class="a-btn a-btn-primary" @click="openCreate"><AdminIcon name="plus" size="sm" />Agregar insumo</button>
    </PageHeader>

    <div v-if="lowStock.length" class="mb-4 flex items-start gap-3 rounded-xl bg-[var(--tone-warning-bg)] px-4 py-3 text-sm text-[var(--tone-warning)]" role="status">
      <AdminIcon name="alert" />
      <p><strong>{{ lowStock.length }} {{ lowStock.length === 1 ? 'insumo está' : 'insumos están' }} en o bajo el stock mínimo:</strong> {{ lowStock.map((i) => i.nombre).join(', ') }}.</p>
    </div>

    <div class="a-card overflow-hidden">
      <div class="flex flex-col gap-3 border-b border-[var(--a-border)] p-3 sm:flex-row sm:items-center">
        <label class="a-check"><input v-model="onlyLow" type="checkbox" />Sólo stock bajo</label>
        <SearchField :model-value="localSearch" class="sm:ml-auto sm:max-w-xs" placeholder="Buscar nombre, SKU o proveedor" label="Buscar insumos" @update:model-value="onSearchInput" />
      </div>
      <div v-if="filtered.length" class="overflow-x-auto">
        <table class="a-table">
          <thead>
            <tr>
              <th scope="col">Insumo</th>
              <th scope="col">Compra</th>
              <th scope="col" class="num">Costo unitario</th>
              <th scope="col">Stock</th>
              <th scope="col">Proveedor</th>
              <th scope="col">Estado</th>
              <th scope="col"><span class="visually-hidden">Acciones</span></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in filtered" :key="item.id" class="is-clickable" @click="openEdit(item)">
              <td>
                <button type="button" class="text-left font-semibold hover:underline" @click.stop="openEdit(item)">{{ item.nombre }}</button>
                <p class="text-xs text-[var(--a-muted)]">SKU {{ item.sku }}</p>
              </td>
              <td class="text-xs text-[var(--a-ink-2)]">
                {{ item.tipo_registro === 'PAQUETE' ? `Paquete de ${item.unidades_por_paquete || '?'}` : `${item.cantidad_compra || '?'} unidades` }}
                <span class="block tabular">{{ money(item.costo_total_compra) }}</span>
              </td>
              <td class="num">{{ money(item.costo_unitario) }}</td>
              <td>
                <span class="tabular" :class="isLow(item) ? 'font-semibold text-[var(--a-critical)]' : ''">{{ item.stock_actual }} {{ item.unidad }}</span>
                <span class="block text-xs text-[var(--a-muted)]">mín. {{ item.stock_minimo }}</span>
              </td>
              <td class="text-[var(--a-ink-2)]">{{ item.proveedor || '—' }}</td>
              <td><span class="a-badge" :class="item.activo ? 'tone-success' : ''">{{ item.activo ? 'Activo' : 'Inactivo' }}</span></td>
              <td @click.stop>
                <button type="button" class="a-btn a-btn-plain-critical a-btn-sm a-btn-icon" :aria-label="`Eliminar ${item.nombre}`" @click="remove(item)"><AdminIcon name="trash" size="sm" /></button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <EmptyState v-else icon="box" :title="list.length ? 'Ningún insumo coincide' : 'Sin insumos registrados'" description="Registra hilos, telas, viseras o etiquetas para controlar costos y existencias.">
        <button v-if="!list.length" type="button" class="a-btn a-btn-primary" @click="openCreate">Agregar insumo</button>
      </EmptyState>
      <AdminPagination :meta="meta" :only="['insumos']" section="insumos" />
    </div>

    <Drawer :open="drawerOpen" :title="form.id ? 'Editar insumo' : 'Nuevo insumo'" :busy="saving" @close="close">
      <form id="supply-form" class="space-y-4" novalidate @submit.prevent="save">
        <section class="a-card a-card-pad grid gap-4 sm:grid-cols-2">
          <FormField v-slot="f" class="sm:col-span-2" label="Nombre" required :error="errors.nombre">
            <input :id="f.id" v-model.trim="form.nombre" class="a-input" maxlength="255" placeholder="Ej. Hilo de algodón negro" :aria-invalid="f.invalid" :aria-describedby="f.describedby" />
          </FormField>
          <FormField v-slot="f" label="SKU" required :error="errors.sku">
            <input :id="f.id" v-model.trim="form.sku" class="a-input" maxlength="80" placeholder="HIL-001" :aria-invalid="f.invalid" :aria-describedby="f.describedby" />
          </FormField>
          <FormField v-slot="f" label="Unidad de medida" required :error="errors.unidad">
            <input :id="f.id" v-model.trim="form.unidad" class="a-input" maxlength="50" list="unidades" placeholder="unidad, metro, kilo…" :aria-invalid="f.invalid" :aria-describedby="f.describedby" />
            <datalist id="unidades"><option value="unidad" /><option value="metro" /><option value="kilo" /><option value="rollo" /><option value="cono" /></datalist>
          </FormField>
          <FormField v-slot="f" class="sm:col-span-2" label="Proveedor" optional>
            <input :id="f.id" v-model.trim="form.proveedor" class="a-input" maxlength="255" />
          </FormField>
        </section>

        <section class="a-card a-card-pad space-y-4">
          <fieldset>
            <legend class="a-label">¿Cómo lo compras?</legend>
            <div class="mt-1 flex flex-wrap gap-4">
              <label class="a-check"><input v-model="form.tipo_registro" type="radio" value="UNIDAD" />Por unidades</label>
              <label class="a-check"><input v-model="form.tipo_registro" type="radio" value="PAQUETE" />Por paquete</label>
            </div>
          </fieldset>
          <div class="grid gap-4 sm:grid-cols-2">
            <FormField v-if="form.tipo_registro === 'PAQUETE'" v-slot="f" label="Unidades por paquete" required :error="errors.unidades_por_paquete">
              <input :id="f.id" v-model="form.unidades_por_paquete" type="number" min="1" step="1" class="a-input" :aria-invalid="f.invalid" :aria-describedby="f.describedby" />
            </FormField>
            <FormField v-else v-slot="f" label="Cantidad comprada" required :error="errors.cantidad_compra">
              <input :id="f.id" v-model="form.cantidad_compra" type="number" min="1" step="1" class="a-input" :aria-invalid="f.invalid" :aria-describedby="f.describedby" />
            </FormField>
            <FormField v-slot="f" label="Costo total de la compra (COP)" required :error="errors.costo_total_compra">
              <input :id="f.id" v-model="form.costo_total_compra" type="number" min="0" step="100" class="a-input" :aria-invalid="f.invalid" :aria-describedby="f.describedby" />
            </FormField>
          </div>
          <div class="rounded-lg bg-[var(--a-surface-2)] px-3 py-2.5 text-sm">
            <span class="text-[var(--a-ink-2)]">Costo por unidad:</span>
            <strong class="ml-1 tabular">{{ unitCost > 0 ? money(unitCost) : '—' }}</strong>
          </div>
        </section>

        <section class="a-card a-card-pad grid gap-4 sm:grid-cols-2">
          <FormField v-slot="f" label="Stock actual" required :error="errors.stock_actual">
            <input :id="f.id" v-model="form.stock_actual" type="number" min="0" step="1" class="a-input" :aria-invalid="f.invalid" :aria-describedby="f.describedby" />
          </FormField>
          <FormField v-slot="f" label="Stock mínimo" required :error="errors.stock_minimo" help="Te avisamos cuando el stock llegue a este número.">
            <input :id="f.id" v-model="form.stock_minimo" type="number" min="0" step="1" class="a-input" :aria-invalid="f.invalid" :aria-describedby="f.describedby" />
          </FormField>
          <label class="a-check sm:col-span-2"><input v-model="form.activo" type="checkbox" />Insumo activo</label>
        </section>
      </form>
      <template #footer>
        <button v-if="form.id" type="button" class="a-btn a-btn-plain-critical mr-auto" :disabled="saving" @click="remove(form)">Eliminar</button>
        <button type="button" class="a-btn a-btn-secondary" :disabled="saving" @click="close">Cancelar</button>
        <button type="submit" form="supply-form" class="a-btn a-btn-primary" :disabled="saving">
          <span v-if="saving" class="spinner" aria-hidden="true"></span>
          {{ saving ? 'Guardando…' : form.id ? 'Guardar cambios' : 'Crear insumo' }}
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
import { debounce, money } from '../../../utils/format';

const props = defineProps({
  insumos: { type: [Object, Array], default: () => ({ data: [] }) },
  search: { type: String, default: '' },
});
const emit = defineEmits(['search']);

const toast = useToast();
const { confirm } = useConfirm();
const list = computed(() => (Array.isArray(props.insumos) ? props.insumos : props.insumos?.data || []));
const meta = computed(() => (Array.isArray(props.insumos) ? null : props.insumos));
const isLow = (i) => Number(i.stock_actual) <= Number(i.stock_minimo);
const lowStock = computed(() => list.value.filter((i) => i.activo && isLow(i)));
const onlyLow = ref(false);
const filtered = computed(() => (onlyLow.value ? list.value.filter(isLow) : list.value));

const localSearch = ref(props.search);
watch(() => props.search, (v) => { localSearch.value = v; });
const emitSearch = debounce((v) => emit('search', v), 350);
const onSearchInput = (v) => { localSearch.value = v; emitSearch(v.trim()); };

const emptyForm = () => ({
  id: null, nombre: '', sku: '', unidad: 'unidad', tipo_registro: 'UNIDAD', unidades_por_paquete: '', cantidad_compra: '',
  costo_total_compra: '', stock_actual: 0, stock_minimo: 0, proveedor: '', activo: true,
});
const drawerOpen = ref(false);
const form = ref(emptyForm());
const errors = reactive({});
const saving = ref(false);
let initial = '';

const unitCost = computed(() => {
  const total = Number(form.value.costo_total_compra) || 0;
  const qty = Number(form.value.tipo_registro === 'PAQUETE' ? form.value.unidades_por_paquete : form.value.cantidad_compra) || 0;
  return qty > 0 ? total / qty : 0;
});

const clearErrors = () => Object.keys(errors).forEach((k) => delete errors[k]);
const openCreate = () => { clearErrors(); form.value = emptyForm(); initial = JSON.stringify(form.value); drawerOpen.value = true; };
const openEdit = (item) => {
  clearErrors();
  form.value = {
    id: item.id,
    nombre: item.nombre || '',
    sku: item.sku || '',
    unidad: item.unidad || 'unidad',
    tipo_registro: item.tipo_registro || 'UNIDAD',
    unidades_por_paquete: item.unidades_por_paquete ?? '',
    cantidad_compra: item.cantidad_compra ?? '',
    costo_total_compra: Number(item.costo_total_compra) || '',
    stock_actual: item.stock_actual ?? 0,
    stock_minimo: item.stock_minimo ?? 0,
    proveedor: item.proveedor || '',
    activo: Boolean(item.activo),
  };
  initial = JSON.stringify(form.value);
  drawerOpen.value = true;
};
const close = async () => {
  if (JSON.stringify(form.value) !== initial) {
    const ok = await confirm({ title: '¿Descartar cambios?', message: 'El insumo tiene cambios sin guardar.', confirmLabel: 'Descartar', cancelLabel: 'Seguir editando' });
    if (!ok) return;
  }
  drawerOpen.value = false;
};

const isPositiveInt = (v) => Number.isInteger(Number(v)) && Number(v) >= 1;
const isNonNegInt = (v) => v !== '' && Number.isInteger(Number(v)) && Number(v) >= 0;

const validate = () => {
  clearErrors();
  const f = form.value;
  if (!f.nombre) errors.nombre = 'Escribe el nombre.';
  if (!f.sku) errors.sku = 'Escribe el SKU.';
  if (!f.unidad) errors.unidad = 'Indica la unidad.';
  if (f.tipo_registro === 'PAQUETE' && !isPositiveInt(f.unidades_por_paquete)) errors.unidades_por_paquete = 'Indica cuántas unidades trae el paquete.';
  if (f.tipo_registro === 'UNIDAD' && !isPositiveInt(f.cantidad_compra)) errors.cantidad_compra = 'Indica cuántas unidades compraste.';
  if (!(Number(f.costo_total_compra) > 0)) errors.costo_total_compra = 'Escribe un costo mayor que 0.';
  if (!isNonNegInt(f.stock_actual)) errors.stock_actual = 'Debe ser un número entero de 0 o más.';
  if (!isNonNegInt(f.stock_minimo)) errors.stock_minimo = 'Debe ser un número entero de 0 o más.';
  return !Object.keys(errors).length;
};

const save = async () => {
  if (!validate()) return;
  const f = form.value;
  const body = {
    nombre: f.nombre,
    sku: f.sku,
    unidad: f.unidad,
    tipo_registro: f.tipo_registro,
    unidades_por_paquete: f.tipo_registro === 'PAQUETE' ? Number(f.unidades_por_paquete) : null,
    cantidad_compra: f.tipo_registro === 'UNIDAD' ? Number(f.cantidad_compra) : null,
    costo_total_compra: Number(f.costo_total_compra),
    stock_actual: Number(f.stock_actual),
    stock_minimo: Number(f.stock_minimo),
    proveedor: f.proveedor || null,
    activo: f.activo,
  };
  saving.value = true;
  try {
    const payload = await api(f.id ? `/admin/insumos/${f.id}` : '/admin/insumos', { method: f.id ? 'PUT' : 'POST', body });
    toast.success(payload.message || 'Insumo guardado');
    drawerOpen.value = false;
    router.reload({ only: ['insumos', 'stats'] });
  } catch (error) {
    Object.assign(errors, error.errors || {});
    toast.error(error.message);
  } finally {
    saving.value = false;
  }
};

const remove = async (item) => {
  const ok = await confirm({ title: `¿Eliminar “${item.nombre}”?`, message: 'Esta acción no se puede deshacer.', confirmLabel: 'Eliminar insumo' });
  if (!ok) return;
  try {
    const payload = await api(`/admin/insumos/${item.id}`, { method: 'DELETE' });
    toast.success(payload.message || 'Insumo eliminado');
    drawerOpen.value = false;
    router.reload({ only: ['insumos', 'stats'] });
  } catch (error) {
    toast.error(error.message);
  }
};
</script>
