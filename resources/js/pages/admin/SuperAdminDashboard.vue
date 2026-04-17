<template>
  <AppLayout>
    <Head title="Super Admin - Vendex" />
    <section class="mx-auto w-full max-w-[96rem] px-4 py-8 sm:px-6 lg:px-10">
      <!-- Header -->
      <div class="mb-6 rounded-2xl border border-indigo-200 bg-indigo-50 p-6 shadow-sm">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-semibold tracking-tight text-indigo-900">Panel Super Administrador</h1>
            <p class="mt-2 text-sm text-indigo-700">Gestiona todas las tiendas, usuarios y configuración global de Vendex.</p>
          </div>
          <div class="text-right">
            <span class="inline-flex items rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-800">
              {{ stats.total_tiendas }} Tiendas
            </span>
          </div>
        </div>
        
        <!-- Stats Grid -->
        <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
          <div class="rounded-xl border border-indigo-200 bg-white p-4">
            <p class="text-xs text-indigo-500">Total Tiendas</p>
            <p class="text-2xl font-semibold text-indigo-900">{{ stats.total_tiendas }}</p>
          </div>
          <div class="rounded-xl border border-indigo-200 bg-white p-4">
            <p class="text-xs text-indigo-500">Tiendas Activas</p>
            <p class="text-2xl font-semibold text-indigo-900">{{ stats.tiendas_activas }}</p>
          </div>
          <div class="rounded-xl border border-indigo-200 bg-white p-4">
            <p class="text-xs text-indigo-500">Super Admins</p>
            <p class="text-2xl font-semibold text-indigo-900">{{ stats.super_admins }}</p>
          </div>
          <div class="rounded-xl border border-indigo-200 bg-white p-4">
            <p class="text-xs text-indigo-500">Admins Tienda</p>
            <p class="text-2xl font-semibold text-indigo-900">{{ stats.admins_tienda }}</p>
          </div>
          <div class="rounded-xl border border-indigo-200 bg-white p-4">
            <p class="text-xs text-indigo-500">Total Usuarios</p>
            <p class="text-2xl font-semibold text-indigo-900">{{ stats.total_usuarios }}</p>
          </div>
        </div>
      </div>

      <!-- Tabs -->
      <div class="mb-5 flex flex-wrap gap-2">
        <button v-for="tab in tabs" :key="tab.key" class="rounded-full border px-4 py-2 text-sm font-semibold" :class="activeTab === tab.key ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-slate-300 bg-white text-slate-700'" @click="activeTab = tab.key">
          {{ tab.label }}
        </button>
      </div>

      <!-- Stores Tab -->
      <section v-if="activeTab === 'tiendas'" class="space-y-4">
        <!-- Crear Tienda -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <h2 class="text-xl font-semibold text-slate-900">Crear Nueva Tienda</h2>
          <div class="mt-4 grid gap-3 md:grid-cols-2 lg:grid-cols-4">
            <input v-model="storeForm.nombre" placeholder="Nombre de la tienda" class="rounded-xl border border-slate-300 px-3 py-2.5" />
            <input v-model="storeForm.slug" placeholder="Slug (ej: mitienda)" class="rounded-xl border border-slate-300 px-3 py-2.5" />
            <input v-model="storeForm.dominio" placeholder="Dominio personalizado (opcional)" class="rounded-xl border border-slate-300 px-3 py-2.5" />
            <input v-model="storeForm.email" placeholder="Email de contacto" class="rounded-xl border border-slate-300 px-3 py-2.5" />
          </div>
          <div class="mt-4 flex gap-2">
            <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white" @click="createStore">Crear Tienda</button>
            <button class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700" @click="resetStoreForm">Limpiar</button>
          </div>
        </div>

        <!-- Lista de Tiendas -->
        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
          <table class="min-w-full text-sm">
            <thead class="bg-indigo-50 text-left text-indigo-900">
              <tr>
                <th class="px-3 py-3">Nombre</th>
                <th class="px-3 py-3">Slug</th>
                <th class="px-3 py-3">Dominio</th>
                <th class="px-3 py-3">Email</th>
                <th class="px-3 py-3">Estado</th>
                <th class="px-3 py-3">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="store in stores" :key="store.id" class="border-t border-slate-200 hover:bg-slate-50">
                <td class="px-3 py-3 font-medium text-slate-900">{{ store.nombre }}</td>
                <td class="px-3 py-3">{{ store.slug }}</td>
                <td class="px-3 py-3">{{ store.dominio || '-' }}</td>
                <td class="px-3 py-3">{{ store.email || '-' }}</td>
                <td class="px-3 py-3">
                  <span class="inline-flex items rounded-full px-2 py-1 text-xs font-semibold" :class="store.activo ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'">
                    {{ store.activo ? 'Activa' : 'Inactiva' }}
                  </span>
                </td>
                <td class="px-3 py-3">
                  <div class="flex gap-2">
                    <button class="rounded-md border border-indigo-300 px-2 py-1 text-indigo-600" @click="editStore(store)">Editar</button>
                    <button class="rounded-md border border-red-300 px-2 py-1 text-red-600" @click="deleteStore(store.id)">Eliminar</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- Users Tab -->
      <section v-if="activeTab === 'usuarios'" class="space-y-4">
        <!-- Crear Usuario -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <h2 class="text-xl font-semibold text-slate-900">Crear Nuevo Usuario</h2>
          <div class="mt-4 grid gap-3 md:grid-cols-2 lg:grid-cols-4">
            <input v-model="userForm.name" placeholder="Nombre" class="rounded-xl border border-slate-300 px-3 py-2.5" />
            <input v-model="userForm.email" type="email" placeholder="Email" class="rounded-xl border border-slate-300 px-3 py-2.5" />
            <input v-model="userForm.password" type="password" placeholder="Contraseña" class="rounded-xl border border-slate-300 px-3 py-2.5" />
            <select v-model="userForm.role" class="rounded-xl border border-slate-300 px-3 py-2.5">
              <option value="super_admin">Super Admin</option>
              <option value="admin">Admin de Tienda</option>
            </select>
          </div>
          <div class="mt-3" v-if="userForm.role === 'admin'">
            <select v-model="userForm.store_id" class="rounded-xl border border-slate-300 px-3 py-2.5">
              <option :value="null">Seleccionar tienda</option>
              <option v-for="store in stores" :key="store.id" :value="store.id">{{ store.nombre }}</option>
            </select>
          </div>
          <div class="mt-4 flex gap-2">
            <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white" @click="createUser">Crear Usuario</button>
            <button class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700" @click="resetUserForm">Limpiar</button>
          </div>
        </div>

        <!-- Lista de Usuarios -->
        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
          <table class="min-w-full text-sm">
            <thead class="bg-indigo-50 text-left text-indigo-900">
              <tr>
                <th class="px-3 py-3">Nombre</th>
                <th class="px-3 py-3">Email</th>
                <th class="px-3 py-3">Rol</th>
                <th class="px-3 py-3">Tienda</th>
                <th class="px-3 py-3">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in users" :key="user.id" class="border-t border-slate-200 hover:bg-slate-50">
                <td class="px-3 py-3 font-medium text-slate-900">{{ user.name }}</td>
                <td class="px-3 py-3">{{ user.email }}</td>
                <td class="px-3 py-3">
                  <span class="inline-flex items rounded-full px-2 py-1 text-xs font-semibold" :class="user.role === 'super_admin' ? 'bg-purple-100 text-purple-800' : 'bg-amber-100 text-amber-800'">
                    {{ user.role === 'super_admin' ? 'Super Admin' : 'Admin Tienda' }}
                  </span>
                </td>
                <td class="px-3 py-3">{{ user.store_nombre || '-' }}</td>
                <td class="px-3 py-3">
                  <div class="flex gap-2">
                    <button class="rounded-md border border-indigo-300 px-2 py-1 text-indigo-600" @click="editUser(user)">Editar</button>
                    <button class="rounded-md border border-red-300 px-2 py-1 text-red-600" @click="deleteUser(user.id)">Eliminar</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- Config Tab -->
      <section v-if="activeTab === 'config'" class="space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <h2 class="text-xl font-semibold text-slate-900">Configuración Global de Vendex</h2>
          <p class="mt-1 text-sm text-slate-600">Configuración que aplica a todas las tiendas.</p>
          
          <div class="mt-4 grid gap-4 lg:grid-cols-2">
            <div class="space-y-3">
              <div>
                <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Dominio base</label>
                <input v-model="configForm.domain" class="w-full rounded-xl border border-slate-300 px-3 py-2.5" placeholder="vendex.app" />
              </div>
              <div>
                <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Email de soporte</label>
                <input v-model="configForm.support_email" class="w-full rounded-xl border border-slate-300 px-3 py-2.5" placeholder="soporte@vendex.com" />
              </div>
              <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white" @click="saveConfig">Guardar Configuración</button>
            </div>
            <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4">
              <h3 class="font-semibold text-indigo-900">Información del Sistema</h3>
              <div class="mt-3 space-y-2 text-sm text-indigo-700">
                <p><strong>Versión:</strong> Vendex 1.0.0</p>
                <p><strong>Framework:</strong> Laravel 12</p>
                <p><strong>PHP:</strong> 8.3+</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Feedback -->
      <p v-if="feedback" class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-800">{{ feedback }}</p>
    </section>
  </AppLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '../../layouts/AppLayout.vue';

const props = defineProps({
  stats: { type: Object, required: true },
  stores: { type: Array, default: () => [] },
  users: { type: Array, default: () => [] },
});

const tabs = [
  { key: 'tiendas', label: 'Gestionar Tiendas' },
  { key: 'usuarios', label: 'Gestionar Usuarios' },
  { key: 'config', label: 'Configuración Global' },
];

const activeTab = ref('tiendas');
const feedback = ref('');

const storeForm = ref({ nombre: '', slug: '', dominio: '', email: '' });
const userForm = ref({ name: '', email: '', password: '', role: 'admin', store_id: null });
const configForm = ref({ domain: 'vendex.app', support_email: '' });

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

const notify = (message) => {
  feedback.value = message;
  setTimeout(() => { feedback.value = ''; }, 3000);
};

const requestJson = async (url, method, body) => {
  const res = await fetch(url, {
    method,
    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
    body: body ? JSON.stringify(body) : null,
  });
  const payload = await res.json();
  if (!res.ok || !payload.success) throw new Error(payload.message || 'Error');
  notify(payload.message);
  return payload;
};

// Store functions
const createStore = async () => {
  await requestJson('/super-admin/stores', 'POST', storeForm.value);
  resetStoreForm();
  window.location.reload();
};

const editStore = (store) => {
  storeForm.value = { ...store, activo: store.activo };
  activeTab.value = 'tiendas';
};

const deleteStore = async (id) => {
  if (!confirm('¿Eliminar esta tienda? Se eliminarán todos sus datos.')) return;
  await requestJson(`/super-admin/stores/${id}`, 'DELETE', {});
  window.location.reload();
};

const resetStoreForm = () => { storeForm.value = { nombre: '', slug: '', dominio: '', email: '' }; };

// User functions
const createUser = async () => {
  await requestJson('/super-admin/users', 'POST', userForm.value);
  resetUserForm();
  window.location.reload();
};

const editUser = (user) => {
  userForm.value = { 
    name: user.name, 
    email: user.email, 
    password: '', 
    role: user.role, 
    store_id: user.store_id 
  };
  activeTab.value = 'usuarios';
};

const deleteUser = async (id) => {
  if (!confirm('¿Eliminar este usuario?')) return;
  await requestJson(`/super-admin/users/${id}`, 'DELETE', {});
  window.location.reload();
};

const resetUserForm = () => { userForm.value = { name: '', email: '', password: '', role: 'admin', store_id: null }; };

// Config functions
const saveConfig = () => {
  notify('Configuración guardada (funcionalidad en desarrollo)');
};
</script>