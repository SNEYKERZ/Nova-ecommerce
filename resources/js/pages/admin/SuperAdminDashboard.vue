<template>
  <AdminLayout :nav="superNav" :active="section" variant="super" @navigate="setSection">
    <Head :title="currentLabel" />

    <!-- Resumen -->
    <template v-if="section === 'resumen'">
      <PageHeader title="Resumen de la plataforma" description="Tiendas, usuarios y ventas de todas las tiendas Vendex." />
      <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div v-for="kpi in kpis" :key="kpi.label" class="a-card a-card-pad">
          <p class="text-xs font-medium text-[var(--a-muted)]">{{ kpi.label }}</p>
          <p class="mt-1 text-2xl font-bold tabular">{{ kpi.value }}</p>
          <p v-if="kpi.hint" class="mt-1 text-xs text-[var(--a-muted)]">{{ kpi.hint }}</p>
        </div>
      </div>
      <div class="mt-5 grid gap-5 lg:grid-cols-2">
        <section class="a-card overflow-hidden" aria-labelledby="sa-stores">
          <div class="flex items-center justify-between px-4 py-3">
            <h2 id="sa-stores" class="a-subtitle">Tiendas</h2>
            <button type="button" class="a-btn a-btn-plain a-btn-sm" @click="setSection('tiendas')">Gestionar</button>
          </div>
          <ul class="divide-y divide-[var(--a-border)] border-t border-[var(--a-border)]">
            <li v-for="store in stores.slice(0, 6)" :key="store.id" class="flex items-center gap-3 px-4 py-2.5 text-sm">
              <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-xs font-bold" :style="{ background: store.navbar_color || '#ebebeb', color: textOn(store.navbar_color) }">{{ (store.nombre || '?').slice(0, 1) }}</span>
              <div class="min-w-0 flex-1">
                <p class="truncate font-medium">{{ store.nombre }}</p>
                <p class="text-xs text-[var(--a-muted)]">{{ store.stats?.productos ?? 0 }} productos · {{ store.stats?.pedidos ?? 0 }} pedidos</p>
              </div>
              <span class="a-badge" :class="store.activo ? 'tone-success' : ''">{{ store.activo ? 'Activa' : 'Inactiva' }}</span>
            </li>
          </ul>
        </section>
        <section class="a-card overflow-hidden" aria-labelledby="sa-orders">
          <div class="flex items-center justify-between px-4 py-3">
            <h2 id="sa-orders" class="a-subtitle">Últimos pedidos</h2>
            <button type="button" class="a-btn a-btn-plain a-btn-sm" @click="setSection('pedidos')">Ver todos</button>
          </div>
          <ul v-if="recentOrders.length" class="divide-y divide-[var(--a-border)] border-t border-[var(--a-border)]">
            <li v-for="order in recentOrders.slice(0, 6)" :key="order.id" class="flex items-center gap-3 px-4 py-2.5 text-sm">
              <span class="w-12 font-semibold tabular">#{{ order.id }}</span>
              <div class="min-w-0 flex-1">
                <p class="truncate">{{ order.cliente }}</p>
                <p class="text-xs text-[var(--a-muted)]">{{ order.store_nombre || '—' }}</p>
              </div>
              <StatusBadge :estado="order.estado" />
              <span class="w-24 text-right font-semibold tabular">{{ money(order.total) }}</span>
            </li>
          </ul>
          <EmptyState v-else icon="orders" title="Sin pedidos todavía" />
        </section>
      </div>
    </template>

    <!-- Tiendas -->
    <template v-else-if="section === 'tiendas'">
      <PageHeader title="Tiendas" description="Cada tienda tiene su catálogo, pedidos y colores.">
        <button type="button" class="a-btn a-btn-primary" @click="openStore()"><AdminIcon name="plus" size="sm" />Crear tienda</button>
      </PageHeader>
      <div class="a-card overflow-hidden">
        <div class="border-b border-[var(--a-border)] p-3">
          <SearchField v-model="storeQuery" class="sm:max-w-xs" placeholder="Buscar por nombre, slug o dominio" label="Buscar tiendas" />
        </div>
        <div v-if="filteredStores.length" class="overflow-x-auto">
          <table class="a-table">
            <thead>
              <tr>
                <th scope="col">Tienda</th>
                <th scope="col">Dirección</th>
                <th scope="col">Colores</th>
                <th scope="col" class="num">Productos</th>
                <th scope="col" class="num">Pedidos</th>
                <th scope="col">Estado</th>
                <th scope="col"><span class="visually-hidden">Acciones</span></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="store in filteredStores" :key="store.id" class="is-clickable" @click="openStore(store)">
                <td>
                  <button type="button" class="text-left font-semibold hover:underline" @click.stop="openStore(store)">{{ store.nombre }}</button>
                  <p class="text-xs text-[var(--a-muted)]">{{ store.email || 'Sin correo' }}</p>
                </td>
                <td class="text-xs">
                  <code class="rounded bg-[var(--a-surface-2)] px-1.5 py-0.5">{{ store.slug }}</code>
                  <p v-if="store.dominio" class="mt-1 text-[var(--a-ink-2)]">{{ store.dominio }}</p>
                </td>
                <td>
                  <div class="flex items-center gap-1" :title="`Menú ${store.navbar_color} · Fondo ${store.bg_color} · Pie ${store.footer_color}`">
                    <span v-for="key in ['navbar_color', 'bg_color', 'footer_color']" :key="key" class="h-5 w-5 rounded border border-black/10" :style="{ background: store[key] }"></span>
                  </div>
                </td>
                <td class="num">{{ store.stats?.productos ?? 0 }}</td>
                <td class="num">{{ store.stats?.pedidos ?? 0 }}</td>
                <td><span class="a-badge" :class="store.activo ? 'tone-success' : ''">{{ store.activo ? 'Activa' : 'Inactiva' }}</span></td>
                <td @click.stop>
                  <button type="button" class="a-btn a-btn-plain-critical a-btn-sm a-btn-icon" :aria-label="`Eliminar tienda ${store.nombre}`" @click="deleteStore(store)"><AdminIcon name="trash" size="sm" /></button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <EmptyState v-else icon="store" :title="stores.length ? 'Ninguna tienda coincide' : 'Sin tiendas'" />
      </div>
    </template>

    <!-- Usuarios -->
    <template v-else-if="section === 'usuarios'">
      <PageHeader title="Usuarios" description="Administradores de cada tienda y super administradores.">
        <button type="button" class="a-btn a-btn-primary" @click="openUser()"><AdminIcon name="plus" size="sm" />Crear usuario</button>
      </PageHeader>
      <div class="a-card overflow-hidden">
        <div class="flex flex-col gap-3 border-b border-[var(--a-border)] p-3 sm:flex-row sm:items-center">
          <div class="a-tabs" role="tablist" aria-label="Filtrar por rol">
            <button v-for="tab in roleTabs" :key="tab.value" type="button" role="tab" class="a-tab" :aria-selected="roleFilter === tab.value ? 'true' : 'false'" @click="roleFilter = tab.value">{{ tab.label }}</button>
          </div>
          <SearchField v-model="userQuery" class="sm:ml-auto sm:max-w-xs" placeholder="Buscar nombre o correo" label="Buscar usuarios" />
        </div>
        <div v-if="filteredUsers.length" class="overflow-x-auto">
          <table class="a-table">
            <thead>
              <tr>
                <th scope="col">Usuario</th>
                <th scope="col">Rol</th>
                <th scope="col">Tienda</th>
                <th scope="col"><span class="visually-hidden">Acciones</span></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in filteredUsers" :key="user.id" class="is-clickable" @click="openUser(user)">
                <td>
                  <button type="button" class="text-left font-semibold hover:underline" @click.stop="openUser(user)">{{ user.name }}</button>
                  <p class="text-xs text-[var(--a-muted)]">{{ user.email }}</p>
                </td>
                <td><span class="a-badge" :class="roleTone(user.role)">{{ roleLabel(user.role) }}</span></td>
                <td class="text-[var(--a-ink-2)]">{{ user.store_nombre || '—' }}</td>
                <td @click.stop>
                  <div class="flex justify-end gap-1">
                    <button v-if="user.role === 'admin' && user.id !== me?.id" type="button" class="a-btn a-btn-secondary a-btn-sm" @click="impersonate(user)">Entrar como</button>
                    <button v-if="user.id !== me?.id" type="button" class="a-btn a-btn-plain-critical a-btn-sm a-btn-icon" :aria-label="`Eliminar usuario ${user.name}`" @click="deleteUser(user)"><AdminIcon name="trash" size="sm" /></button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <EmptyState v-else icon="users" title="Ningún usuario coincide" />
      </div>
    </template>

    <!-- Pedidos recientes -->
    <template v-else-if="section === 'pedidos'">
      <PageHeader title="Pedidos recientes" description="Los últimos pedidos de todas las tiendas." />
      <div class="a-card overflow-hidden">
        <div v-if="recentOrders.length" class="overflow-x-auto">
          <table class="a-table">
            <thead>
              <tr>
                <th scope="col">Pedido</th>
                <th scope="col">Tienda</th>
                <th scope="col">Cliente</th>
                <th scope="col">Fecha</th>
                <th scope="col">Estado</th>
                <th scope="col" class="num">Total</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="order in recentOrders" :key="order.id">
                <td class="font-semibold tabular">#{{ order.id }}</td>
                <td>{{ order.store_nombre || '—' }}</td>
                <td>{{ order.cliente }}</td>
                <td class="whitespace-nowrap text-[var(--a-ink-2)]">{{ formatDate(order.created_at, true) }}</td>
                <td><StatusBadge :estado="order.estado" /></td>
                <td class="num font-semibold">{{ money(order.total) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <EmptyState v-else icon="orders" title="Sin pedidos todavía" />
      </div>
    </template>

    <!-- Formulario tienda -->
    <Drawer :open="storeDrawer" :title="storeForm.id ? `Editar ${storeForm.nombre || 'tienda'}` : 'Nueva tienda'" size="lg" :busy="saving" @close="storeDrawer = false">
      <form id="store-form" class="space-y-4" novalidate @submit.prevent="saveStore">
        <section class="a-card a-card-pad grid gap-4 sm:grid-cols-2">
          <FormField v-slot="f" label="Nombre" required :error="storeErrors.nombre">
            <input :id="f.id" v-model.trim="storeForm.nombre" class="a-input" maxlength="255" :aria-invalid="f.invalid" :aria-describedby="f.describedby" @input="autoSlug" />
          </FormField>
          <FormField v-slot="f" label="Slug (subdominio)" required :error="storeErrors.slug" help="Minúsculas, números y guiones. Ej. mitienda">
            <input :id="f.id" v-model.trim="storeForm.slug" class="a-input font-mono" maxlength="255" :aria-invalid="f.invalid" :aria-describedby="f.describedby" @input="slugTouched = true" />
          </FormField>
          <FormField v-slot="f" label="Dominio propio" optional :error="storeErrors.dominio" help="Ej. mitienda.com (sin https://)">
            <input :id="f.id" v-model.trim="storeForm.dominio" class="a-input" maxlength="255" :aria-invalid="f.invalid" :aria-describedby="f.describedby" />
          </FormField>
          <FormField v-slot="f" label="Correo de contacto" optional :error="storeErrors.email">
            <input :id="f.id" v-model.trim="storeForm.email" type="email" class="a-input" :aria-invalid="f.invalid" :aria-describedby="f.describedby" />
          </FormField>
          <FormField v-slot="f" label="WhatsApp / teléfono" optional :error="storeErrors.telefono" help="Con indicativo, ej. 573001234567. Se usa para pedidos por WhatsApp.">
            <input :id="f.id" v-model.trim="storeForm.telefono" type="tel" class="a-input" maxlength="50" :aria-invalid="f.invalid" :aria-describedby="f.describedby" />
          </FormField>
          <FormField v-slot="f" label="Tipo de tienda" :error="storeErrors.rubro" help="Ajusta tipografía, densidad y textos del storefront.">
            <select :id="f.id" v-model="storeForm.rubro" class="a-input" :aria-invalid="f.invalid" :aria-describedby="f.describedby">
              <option v-for="r in rubros" :key="r.value" :value="r.value">{{ r.label }}</option>
            </select>
          </FormField>
          <label v-if="storeForm.id" class="a-check self-end pb-2"><input v-model="storeForm.activo" type="checkbox" />Tienda activa</label>
          <FormField v-if="!storeForm.id || descripcionKnown" v-slot="f" class="sm:col-span-2" label="Descripción (página Nosotros)" optional>
            <textarea :id="f.id" v-model.trim="storeForm.descripcion" rows="3" class="a-input"></textarea>
          </FormField>
        </section>
        <section class="a-card a-card-pad" aria-labelledby="sf-colors">
          <h3 id="sf-colors" class="a-subtitle">Colores</h3>
          <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div v-for="field in colorFields" :key="field.key">
              <label :for="`sc-${field.key}`" class="a-label">{{ field.label }}</label>
              <div class="flex items-center gap-2">
                <input v-model="storeForm[field.key]" type="color" class="a-color" :aria-label="`${field.label}: selector`" />
                <input :id="`sc-${field.key}`" v-model.trim="storeForm[field.key]" class="a-input max-w-[9rem] font-mono" maxlength="7" :aria-invalid="storeErrors[field.key] ? 'true' : 'false'" />
              </div>
              <p v-if="storeErrors[field.key]" class="a-error">{{ storeErrors[field.key] }}</p>
            </div>
          </div>
          <div class="mt-4 overflow-hidden rounded-lg border border-[var(--a-border)]" aria-hidden="true">
            <div class="px-3 py-2 text-xs font-bold uppercase tracking-widest" :style="{ background: storeForm.navbar_color, color: readable(storeForm.navbar_color, storeForm.navbar_text_color) }">{{ storeForm.nombre || 'Tienda' }} · Catálogo</div>
            <div class="h-10" :style="{ background: storeForm.bg_color }"></div>
            <div class="px-3 py-2 text-xs" :style="{ background: storeForm.footer_color, color: readable(storeForm.footer_color, storeForm.footer_text_color) }">© {{ storeForm.nombre || 'Tienda' }}</div>
          </div>
          <p v-if="lowContrast" class="a-help !text-[var(--tone-attention)]">Algún texto tiene poco contraste: en la tienda se reemplaza automáticamente por blanco o negro.</p>
        </section>
      </form>
      <template #footer>
        <button type="button" class="a-btn a-btn-secondary" :disabled="saving" @click="storeDrawer = false">Cancelar</button>
        <button type="submit" form="store-form" class="a-btn a-btn-primary" :disabled="saving">
          <span v-if="saving" class="spinner" aria-hidden="true"></span>
          {{ storeForm.id ? 'Guardar cambios' : 'Crear tienda' }}
        </button>
      </template>
    </Drawer>

    <!-- Formulario usuario -->
    <Drawer :open="userDrawer" :title="userForm.id ? `Editar ${userForm.name || 'usuario'}` : 'Nuevo usuario'" :busy="saving" @close="userDrawer = false">
      <form id="user-form" class="a-card a-card-pad space-y-4" novalidate @submit.prevent="saveUser">
        <FormField v-slot="f" label="Nombre" required :error="userErrors.name">
          <input :id="f.id" v-model.trim="userForm.name" class="a-input" maxlength="255" autocomplete="off" :aria-invalid="f.invalid" :aria-describedby="f.describedby" />
        </FormField>
        <FormField v-slot="f" label="Correo" required :error="userErrors.email">
          <input :id="f.id" v-model.trim="userForm.email" type="email" class="a-input" autocomplete="off" :aria-invalid="f.invalid" :aria-describedby="f.describedby" />
        </FormField>
        <FormField v-slot="f" :label="userForm.id ? 'Nueva contraseña' : 'Contraseña'" :required="!userForm.id" :optional="!!userForm.id" :error="userErrors.password" :help="userForm.id ? 'Déjala vacía para no cambiarla. Mínimo 8 caracteres.' : 'Mínimo 8 caracteres.'">
          <input :id="f.id" v-model="userForm.password" type="password" class="a-input" autocomplete="new-password" :aria-invalid="f.invalid" :aria-describedby="f.describedby" />
        </FormField>
        <FormField v-slot="f" label="Rol" required :error="userErrors.role">
          <select :id="f.id" v-model="userForm.role" class="a-input" :disabled="userForm.id === me?.id">
            <option value="admin">Administrador de tienda</option>
            <option value="super_admin">Super administrador</option>
          </select>
        </FormField>
        <FormField v-if="userForm.role === 'admin'" v-slot="f" label="Tienda" required :error="userErrors.store_id">
          <select :id="f.id" v-model="userForm.store_id" class="a-input" :aria-invalid="f.invalid" :aria-describedby="f.describedby">
            <option :value="null">Selecciona una tienda</option>
            <option v-for="store in stores" :key="store.id" :value="store.id">{{ store.nombre }}</option>
          </select>
        </FormField>
      </form>
      <template #footer>
        <button type="button" class="a-btn a-btn-secondary" :disabled="saving" @click="userDrawer = false">Cancelar</button>
        <button type="submit" form="user-form" class="a-btn a-btn-primary" :disabled="saving">
          <span v-if="saving" class="spinner" aria-hidden="true"></span>
          {{ userForm.id ? 'Guardar cambios' : 'Crear usuario' }}
        </button>
      </template>
    </Drawer>
  </AdminLayout>
</template>

<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, reactive, ref } from 'vue';
import AdminLayout from '../../layouts/AdminLayout.vue';
import AdminIcon from '../../components/admin/AdminIcon.vue';
import EmptyState from '../../components/admin/EmptyState.vue';
import FormField from '../../components/admin/FormField.vue';
import PageHeader from '../../components/admin/PageHeader.vue';
import SearchField from '../../components/admin/SearchField.vue';
import StatusBadge from '../../components/admin/StatusBadge.vue';
import Drawer from '../../components/ui/Drawer.vue';
import { superNav } from '../../components/admin/adminNav';
import { useConfirm } from '../../composables/useConfirm';
import { useToast } from '../../composables/useToast';
import { api } from '../../utils/http';
import { formatDate, money } from '../../utils/format';
import { getContrastRatio, getTextColorHex, isValidHexColor } from '../../utils/colorUtils';

const props = defineProps({
  stats: { type: Object, required: true },
  stores: { type: Array, default: () => [] },
  users: { type: Array, default: () => [] },
  recentOrders: { type: Array, default: () => [] },
});

const page = usePage();
const toast = useToast();
const { confirm } = useConfirm();
const me = computed(() => page.props.auth?.user || null);

/* Secciones */
const SECTIONS = superNav.map((s) => s.key);
const section = ref('resumen');
const currentLabel = computed(() => superNav.find((s) => s.key === section.value)?.label || 'Super admin');
const setSection = (key) => {
  if (!SECTIONS.includes(key)) return;
  section.value = key;
  const url = new URL(window.location.href);
  url.searchParams.set('seccion', key);
  window.history.replaceState(window.history.state, '', url.pathname + url.search);
  window.scrollTo({ top: 0 });
};
onMounted(() => {
  const value = new URLSearchParams(window.location.search).get('seccion');
  if (SECTIONS.includes(value)) section.value = value;
});

const kpis = computed(() => [
  { label: 'Tiendas activas', value: `${props.stats.tiendas_activas ?? 0} / ${props.stats.total_tiendas ?? 0}` },
  { label: 'Usuarios', value: props.stats.total_usuarios ?? 0, hint: `${props.stats.admins_tienda ?? 0} admins · ${props.stats.super_admins ?? 0} super admins` },
  { label: 'Pedidos', value: props.stats.total_pedidos ?? 0, hint: `${props.stats.total_productos ?? 0} productos publicados` },
  { label: 'Ventas registradas', value: money(props.stats.total_ingresos ?? 0), hint: 'Suma de todos los pedidos' },
]);

const textOn = (bg) => getTextColorHex(isValidHexColor(bg || '') ? bg : '#ebebeb');
const readable = (bg, text) => {
  if (!isValidHexColor(bg || '')) return '#111111';
  return isValidHexColor(text || '') && getContrastRatio(text, bg) >= 3 ? text : getTextColorHex(bg);
};
const reload = () => router.reload({ only: ['stats', 'stores', 'users', 'recentOrders'] });

/* ── Tiendas ── */
const storeQuery = ref('');
const filteredStores = computed(() => {
  const q = storeQuery.value.trim().toLowerCase();
  return q ? props.stores.filter((s) => [s.nombre, s.slug, s.dominio, s.email].some((v) => (v || '').toLowerCase().includes(q))) : props.stores;
});

const colorFields = [
  { key: 'navbar_color', label: 'Menú superior' },
  { key: 'navbar_text_color', label: 'Texto del menú' },
  { key: 'bg_color', label: 'Fondo' },
  { key: 'footer_color', label: 'Pie de página' },
  { key: 'footer_text_color', label: 'Texto del pie' },
];
const storeDrawer = ref(false);
/* El listado de tiendas no trae `descripcion`; sólo se edita si el backend la envía */
const descripcionKnown = ref(false);
const storeErrors = reactive({});
const slugTouched = ref(false);
const longHex = (v, fallback) => {
  if (!isValidHexColor(v || '')) return fallback;
  return v.length === 4 ? `#${v[1]}${v[1]}${v[2]}${v[2]}${v[3]}${v[3]}` : v;
};
const rubros = [
  { value: 'general', label: 'General' },
  { value: 'moda', label: 'Moda y accesorios' },
  { value: 'restaurante', label: 'Restaurante y comida' },
  { value: 'autopartes', label: 'Autopartes y motos' },
];
const emptyStore = () => ({
  id: null, nombre: '', slug: '', dominio: '', email: '', telefono: '', descripcion: '', rubro: 'general', activo: true,
  bg_color: '#ffffff', navbar_color: '#ffffff', footer_color: '#e8e6e3', navbar_text_color: '#111111', footer_text_color: '#111111',
});
const storeForm = reactive(emptyStore());
const lowContrast = computed(() => [['navbar_color', 'navbar_text_color'], ['footer_color', 'footer_text_color']]
  .some(([bg, fg]) => isValidHexColor(storeForm[bg] || '') && isValidHexColor(storeForm[fg] || '') && getContrastRatio(storeForm[fg], storeForm[bg]) < 3));

const openStore = (store = null) => {
  Object.keys(storeErrors).forEach((k) => delete storeErrors[k]);
  Object.assign(storeForm, emptyStore());
  slugTouched.value = Boolean(store);
  descripcionKnown.value = Boolean(store && Object.prototype.hasOwnProperty.call(store, 'descripcion'));
  if (store) {
    Object.assign(storeForm, {
      id: store.id,
      nombre: store.nombre || '',
      slug: store.slug || '',
      dominio: store.dominio || '',
      email: store.email || '',
      telefono: store.telefono || '',
      descripcion: store.descripcion || '',
      rubro: store.rubro || 'general',
      activo: Boolean(store.activo),
      bg_color: longHex(store.bg_color, '#ffffff'),
      navbar_color: longHex(store.navbar_color, '#ffffff'),
      footer_color: longHex(store.footer_color, '#e8e6e3'),
      navbar_text_color: longHex(store.navbar_text_color, '#111111'),
      footer_text_color: longHex(store.footer_text_color, '#111111'),
    });
  }
  storeDrawer.value = true;
};

const slugify = (value) => value.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
const autoSlug = () => { if (!slugTouched.value && !storeForm.id) storeForm.slug = slugify(storeForm.nombre); };

const saving = ref(false);
const saveStore = async () => {
  Object.keys(storeErrors).forEach((k) => delete storeErrors[k]);
  if (!storeForm.nombre) storeErrors.nombre = 'Escribe el nombre.';
  if (!storeForm.slug) storeErrors.slug = 'Escribe el slug.';
  else if (!/^[a-z0-9-]+$/.test(storeForm.slug)) storeErrors.slug = 'Sólo minúsculas, números y guiones.';
  if (storeForm.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(storeForm.email)) storeErrors.email = 'Correo no válido.';
  colorFields.forEach(({ key }) => { if (!isValidHexColor(storeForm[key] || '')) storeErrors[key] = 'Color hexadecimal no válido.'; });
  if (Object.keys(storeErrors).length) return;

  const { id, ...body } = storeForm;
  Object.keys(body).forEach((k) => { if (body[k] === '') body[k] = null; });
  if (id && !descripcionKnown.value) delete body.descripcion;
  saving.value = true;
  try {
    const payload = await api(id ? `/super-admin/stores/${id}` : '/super-admin/stores', { method: id ? 'PUT' : 'POST', body: { ...body, activo: storeForm.activo } });
    toast.success(payload.message || 'Tienda guardada');
    storeDrawer.value = false;
    reload();
  } catch (error) {
    Object.assign(storeErrors, error.errors || {});
    toast.error(error.message);
  } finally {
    saving.value = false;
  }
};

const deleteStore = async (store) => {
  const ok = await confirm({
    title: `¿Eliminar la tienda “${store.nombre}”?`,
    message: `Se perderán sus ${store.stats?.productos ?? 0} productos, ${store.stats?.pedidos ?? 0} pedidos y su configuración. No se puede deshacer.`,
    confirmLabel: 'Eliminar tienda',
  });
  if (!ok) return;
  try {
    const payload = await api(`/super-admin/stores/${store.id}`, { method: 'DELETE' });
    toast.success(payload.message || 'Tienda eliminada');
    reload();
  } catch (error) {
    toast.error(error.message);
  }
};

/* ── Usuarios ── */
const userQuery = ref('');
const roleFilter = ref('ALL');
const roleTabs = [
  { value: 'ALL', label: 'Todos' },
  { value: 'admin', label: 'Admins de tienda' },
  { value: 'super_admin', label: 'Super admins' },
  { value: 'cliente', label: 'Clientes' },
];
const roleLabel = (role) => ({ super_admin: 'Super admin', admin: 'Admin de tienda', cliente: 'Cliente' }[role] || role);
const roleTone = (role) => ({ super_admin: 'tone-magic', admin: 'tone-info' }[role] || '');
const filteredUsers = computed(() => {
  const q = userQuery.value.trim().toLowerCase();
  return props.users.filter((u) => (roleFilter.value === 'ALL' || u.role === roleFilter.value)
    && (!q || (u.name || '').toLowerCase().includes(q) || (u.email || '').toLowerCase().includes(q)));
});

const userDrawer = ref(false);
const userErrors = reactive({});
const userForm = reactive({ id: null, name: '', email: '', password: '', role: 'admin', store_id: null });

const openUser = (user = null) => {
  Object.keys(userErrors).forEach((k) => delete userErrors[k]);
  Object.assign(userForm, user
    ? { id: user.id, name: user.name, email: user.email, password: '', role: user.role === 'cliente' ? 'admin' : user.role, store_id: user.store_id ?? null }
    : { id: null, name: '', email: '', password: '', role: 'admin', store_id: null });
  userDrawer.value = true;
};

const saveUser = async () => {
  Object.keys(userErrors).forEach((k) => delete userErrors[k]);
  if (!userForm.name) userErrors.name = 'Escribe el nombre.';
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(userForm.email)) userErrors.email = 'Escribe un correo válido.';
  if (!userForm.id && userForm.password.length < 8) userErrors.password = 'Mínimo 8 caracteres.';
  if (userForm.id && userForm.password && userForm.password.length < 8) userErrors.password = 'Mínimo 8 caracteres.';
  if (userForm.role === 'admin' && !userForm.store_id) userErrors.store_id = 'Elige la tienda que administrará.';
  if (Object.keys(userErrors).length) return;

  const body = { name: userForm.name, email: userForm.email, role: userForm.role, store_id: userForm.role === 'admin' ? userForm.store_id : null };
  saving.value = true;
  try {
    if (userForm.id) {
      await api(`/super-admin/users/${userForm.id}`, { method: 'PUT', body });
      if (userForm.password) await api(`/super-admin/users/${userForm.id}/password`, { method: 'PUT', body: { password: userForm.password } });
      toast.success('Usuario actualizado');
    } else {
      const payload = await api('/super-admin/users', { method: 'POST', body: { ...body, password: userForm.password } });
      toast.success(payload.message || 'Usuario creado');
    }
    userDrawer.value = false;
    reload();
  } catch (error) {
    Object.assign(userErrors, error.errors || {});
    toast.error(error.message);
  } finally {
    saving.value = false;
  }
};

const deleteUser = async (user) => {
  const ok = await confirm({ title: `¿Eliminar a ${user.name}?`, message: `${user.email} perderá el acceso al panel.`, confirmLabel: 'Eliminar usuario' });
  if (!ok) return;
  try {
    const payload = await api(`/super-admin/users/${user.id}`, { method: 'DELETE' });
    toast.success(payload.message || 'Usuario eliminado');
    reload();
  } catch (error) {
    toast.error(error.message);
  }
};

const impersonate = async (user) => {
  const ok = await confirm({
    title: `¿Entrar como ${user.name}?`,
    message: 'Verás el panel de su tienda. Usa “Volver a super admin” en la barra superior para regresar.',
    confirmLabel: 'Entrar',
    tone: 'primary',
  });
  if (!ok) return;
  router.post(`/super-admin/users/${user.id}/impersonate`, {}, {
    onError: () => toast.error('No se pudo entrar como este usuario.'),
  });
};
</script>
