<template>
  <div class="flex min-h-screen items-center justify-center px-4">
    <Head title="Login Admin" />
    <div class="w-full max-w-md rounded-3xl border border-[color:var(--line)] bg-[color:var(--surface)] p-8">
      <p class="chip inline-flex bg-[color:var(--sand)]">Panel administrativo</p>
      <h1 class="font-display mt-4 text-3xl font-bold">Acceso Admin</h1>
      <p class="mt-2 text-sm text-[color:var(--muted)]">Inicia sesion para gestionar fabrica, productos y promociones.</p>

      <form class="mt-6 space-y-3" @submit.prevent="submit">
        <div>
          <label class="mb-1 block text-xs font-semibold uppercase text-[color:var(--muted)]">Email</label>
          <input v-model="form.email" type="email" required class="w-full rounded-xl border border-[color:var(--line)] bg-white px-3 py-2.5" />
          <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
        </div>

        <div>
          <label class="mb-1 block text-xs font-semibold uppercase text-[color:var(--muted)]">Password</label>
          <input v-model="form.password" type="password" required class="w-full rounded-xl border border-[color:var(--line)] bg-white px-3 py-2.5" />
          <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
        </div>

        <button type="submit" class="btn-main w-full px-5 py-3 text-sm font-bold" :disabled="form.processing">
          {{ form.processing ? 'Ingresando...' : 'Ingresar al panel' }}
        </button>
      </form>

      <Link href="/" class="mt-4 inline-block text-sm font-semibold text-[color:var(--muted)]">Volver a la tienda</Link>
    </div>
  </div>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
  email: '',
  password: '',
});

const submit = () => {
  form.post('/admin/login');
};
</script>
