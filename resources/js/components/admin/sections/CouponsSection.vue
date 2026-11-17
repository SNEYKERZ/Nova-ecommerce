<template>
  <div>
    <PageHeader title="Cupones" description="Códigos que tus clientes escriben en el carrito para obtener un descuento.">
      <button type="button" class="a-btn a-btn-primary" @click="openCreate"><AdminIcon name="plus" size="sm" />Crear cupón</button>
    </PageHeader>

    <div class="a-card overflow-hidden">
      <div class="border-b border-[var(--a-border)] p-3">
        <SearchField v-model="query" class="sm:max-w-xs" placeholder="Buscar código" label="Buscar cupones" />
      </div>
      <div v-if="filtered.length" class="overflow-x-auto">
        <table class="a-table">
          <thead>
            <tr>
              <th scope="col">Código</th>
              <th scope="col">Descuento</th>
              <th scope="col" class="num">Compra mínima</th>
              <th scope="col">Usos</th>
              <th scope="col">Vence</th>
              <th scope="col">Estado</th>
              <th scope="col"><span class="visually-hidden">Acciones</span></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="cupon in filtered" :key="cupon.id" class="is-clickable" @click="openEdit(cupon)">
              <td>
                <button type="button" class="font-mono text-sm font-semibold tracking-wide hover:underline" @click.stop="openEdit(cupon)">{{ cupon.codigo }}</button>
              </td>
              <td class="font-medium tabular">{{ cupon.tipo === 'porcentaje' ? `${Number(cupon.valor_descuento)}%` : money(cupon.valor_descuento) }}</td>
              <td class="num">{{ cupon.monto_minimo ? money(cupon.monto_minimo) : '—' }}</td>
              <td class="tabular">{{ cupon.usos_actuales || 0 }}<span class="text-[var(--a-muted)]"> / {{ cupon.max_usos || '∞' }}</span></td>
              <td class="whitespace-nowrap text-[var(--a-ink-2)]">{{ cupon.fecha_expiracion ? formatDate(cupon.fecha_expiracion) : 'Sin vencimiento' }}</td>
              <td><span class="a-badge" :class="`tone-${status(cupon).tone}`">{{ status(cupon).label }}</span></td>
              <td @click.stop>
                <div class="flex justify-end gap-1">
                  <button type="button" class="a-btn a-btn-plain a-btn-sm" :aria-label="`Copiar código ${cupon.codigo}`" @click="copy(cupon.codigo)">Copiar</button>
                  <button type="button" class="a-btn a-btn-plain-critical a-btn-sm a-btn-icon" :aria-label="`Eliminar cupón ${cupon.codigo}`" @click="remove(cupon)"><AdminIcon name="trash" size="sm" /></button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <EmptyState v-else icon="ticket" :title="list.length ? 'Ningún cupón coincide' : 'Sin cupones todavía'" description="Ejemplo: BIENVENIDO10 con 10% de descuento para nuevos clientes.">
        <button v-if="!list.length" type="button" class="a-btn a-btn-primary" @click="openCreate">Crear cupón</button>
      </EmptyState>
      <AdminPagination :meta="meta" :only="['cupones']" section="cupones" />
    </div>

    <Drawer :open="drawerOpen" :title="form.id ? `Editar cupón ${form.codigo}` : 'Nuevo cupón'" :busy="saving" @close="close">
      <form id="coupon-form" class="a-card a-card-pad space-y-4" novalidate @submit.prevent="save">
        <FormField v-slot="f" label="Código" required :error="errors.codigo" help="Letras, números, guion o guion bajo. Se guarda en mayúsculas.">
          <input :id="f.id" :value="form.codigo" class="a-input font-mono uppercase tracking-wide" maxlength="50" autocomplete="off" placeholder="BIENVENIDO10" :aria-invalid="f.invalid" :aria-describedby="f.describedby" @input="onCodeInput" />
        </FormField>
        <fieldset>
          <legend class="a-label">Tipo de descuento</legend>
          <div class="mt-1 flex gap-4">
            <label class="a-check"><input v-model="form.tipo" type="radio" value="porcentaje" />Porcentaje</label>
            <label class="a-check"><input v-model="form.tipo" type="radio" value="fijo" />Valor fijo (COP)</label>
          </div>
        </fieldset>
        <div class="grid gap-4 sm:grid-cols-2">
          <FormField v-slot="f" :label="form.tipo === 'porcentaje' ? 'Descuento (%)' : 'Descuento (COP)'" required :error="errors.valor_descuento">
            <input :id="f.id" v-model="form.valor_descuento" type="number" min="0.01" :max="form.tipo === 'porcentaje' ? 100 : undefined" :step="form.tipo === 'porcentaje' ? 1 : 100" class="a-input" :aria-invalid="f.invalid" :aria-describedby="f.describedby" />
          </FormField>
          <FormField v-slot="f" label="Compra mínima (COP)" optional :error="errors.monto_minimo">
            <input :id="f.id" v-model="form.monto_minimo" type="number" min="0" step="1000" class="a-input" placeholder="Sin mínimo" :aria-invalid="f.invalid" :aria-describedby="f.describedby" />
          </FormField>
          <FormField v-slot="f" label="Usos máximos" optional :error="errors.max_usos">
            <input :id="f.id" v-model="form.max_usos" type="number" min="1" step="1" class="a-input" placeholder="Ilimitados" :aria-invalid="f.invalid" :aria-describedby="f.describedby" />
          </FormField>
          <FormField v-slot="f" label="Vence el" optional :error="errors.fecha_expiracion">
            <input :id="f.id" v-model="form.fecha_expiracion" type="date" class="a-input" :aria-invalid="f.invalid" :aria-describedby="f.describedby" />
          </FormField>
        </div>
        <label class="a-check"><input v-model="form.esta_activo" type="checkbox" />Cupón activo</label>
      </form>
      <template #footer>
        <button v-if="form.id" type="button" class="a-btn a-btn-plain-critical mr-auto" :disabled="saving" @click="remove(form)">Eliminar</button>
        <button type="button" class="a-btn a-btn-secondary" :disabled="saving" @click="close">Cancelar</button>
        <button type="submit" form="coupon-form" class="a-btn a-btn-primary" :disabled="saving">
          <span v-if="saving" class="spinner" aria-hidden="true"></span>
          {{ saving ? 'Guardando…' : form.id ? 'Guardar cambios' : 'Crear cupón' }}
        </button>
      </template>
    </Drawer>
  </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
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
import { formatDate, money, toDateInput } from '../../../utils/format';

const props = defineProps({
  cupones: { type: [Object, Array], default: () => ({ data: [] }) },
});

const toast = useToast();
const { confirm } = useConfirm();
const list = computed(() => (Array.isArray(props.cupones) ? props.cupones : props.cupones?.data || []));
const meta = computed(() => (Array.isArray(props.cupones) ? null : props.cupones));
const query = ref('');
const filtered = computed(() => {
  const q = query.value.trim().toUpperCase();
  return q ? list.value.filter((c) => (c.codigo || '').includes(q)) : list.value;
});

const status = (c) => {
  if (!c.esta_activo) return { label: 'Inactivo', tone: 'neutral' };
  if (c.fecha_expiracion && new Date(c.fecha_expiracion).getTime() < Date.now()) return { label: 'Vencido', tone: 'warning' };
  if (c.max_usos && Number(c.usos_actuales) >= Number(c.max_usos)) return { label: 'Agotado', tone: 'warning' };
  return { label: 'Activo', tone: 'success' };
};

const copy = async (code) => {
  try {
    await navigator.clipboard.writeText(code);
    toast.success(`Código ${code} copiado`);
  } catch {
    toast.error('No se pudo copiar el código.');
  }
};

const emptyForm = () => ({ id: null, codigo: '', tipo: 'porcentaje', valor_descuento: '', monto_minimo: '', max_usos: '', fecha_expiracion: '', esta_activo: true });
const drawerOpen = ref(false);
const form = ref(emptyForm());
const errors = reactive({});
const saving = ref(false);
let initial = '';

const clearErrors = () => Object.keys(errors).forEach((k) => delete errors[k]);
const onCodeInput = (event) => {
  const clean = event.target.value.toUpperCase().replace(/\s+/g, '');
  form.value.codigo = clean;
  event.target.value = clean;
  delete errors.codigo;
};
const openCreate = () => { clearErrors(); form.value = emptyForm(); initial = JSON.stringify(form.value); drawerOpen.value = true; };
const openEdit = (c) => {
  clearErrors();
  form.value = {
    id: c.id,
    codigo: c.codigo,
    tipo: c.tipo,
    valor_descuento: Number(c.valor_descuento) || '',
    monto_minimo: c.monto_minimo ? Number(c.monto_minimo) : '',
    max_usos: c.max_usos || '',
    fecha_expiracion: toDateInput(c.fecha_expiracion),
    esta_activo: Boolean(c.esta_activo),
  };
  initial = JSON.stringify(form.value);
  drawerOpen.value = true;
};
const close = async () => {
  if (JSON.stringify(form.value) !== initial) {
    const ok = await confirm({ title: '¿Descartar cambios?', message: 'El cupón tiene cambios sin guardar.', confirmLabel: 'Descartar', cancelLabel: 'Seguir editando' });
    if (!ok) return;
  }
  drawerOpen.value = false;
};

const validate = () => {
  clearErrors();
  const f = form.value;
  if (!f.codigo) errors.codigo = 'Escribe el código.';
  else if (!/^[A-Z0-9_-]+$/.test(f.codigo)) errors.codigo = 'Usa sólo letras sin tilde, números, guion o guion bajo.';
  const value = Number(f.valor_descuento);
  if (f.valor_descuento === '' || !(value > 0)) errors.valor_descuento = 'Escribe un descuento mayor que 0.';
  else if (f.tipo === 'porcentaje' && value > 100) errors.valor_descuento = 'El porcentaje no puede superar 100.';
  if (f.monto_minimo !== '' && Number(f.monto_minimo) < 0) errors.monto_minimo = 'No puede ser negativo.';
  if (f.max_usos !== '' && !(Number.isInteger(Number(f.max_usos)) && Number(f.max_usos) >= 1)) errors.max_usos = 'Debe ser un número entero de 1 o más.';
  return !Object.keys(errors).length;
};

const save = async () => {
  if (!validate()) return;
  const f = form.value;
  const body = {
    codigo: f.codigo,
    tipo: f.tipo,
    valor_descuento: Number(f.valor_descuento),
    monto_minimo: f.monto_minimo === '' ? null : Number(f.monto_minimo),
    max_usos: f.max_usos === '' ? null : Number(f.max_usos),
    fecha_expiracion: f.fecha_expiracion || null,
    esta_activo: f.esta_activo,
  };
  saving.value = true;
  try {
    const payload = await api(f.id ? `/admin/cupones/${f.id}` : '/admin/cupones', { method: f.id ? 'PUT' : 'POST', body });
    toast.success(payload.message || 'Cupón guardado');
    drawerOpen.value = false;
    router.reload({ only: ['cupones'] });
  } catch (error) {
    Object.assign(errors, error.errors || {});
    if (!Object.keys(error.errors || {}).length && /código/i.test(error.message)) errors.codigo = error.message;
    toast.error(error.message);
  } finally {
    saving.value = false;
  }
};

const remove = async (cupon) => {
  const ok = await confirm({ title: `¿Eliminar el cupón ${cupon.codigo}?`, message: 'Los clientes ya no podrán usarlo.', confirmLabel: 'Eliminar cupón' });
  if (!ok) return;
  try {
    const payload = await api(`/admin/cupones/${cupon.id}`, { method: 'DELETE' });
    toast.success(payload.message || 'Cupón eliminado');
    drawerOpen.value = false;
    router.reload({ only: ['cupones'] });
  } catch (error) {
    toast.error(error.message);
  }
};
</script>
