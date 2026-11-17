<template>
  <div class="flex min-h-screen items-center justify-center bg-[var(--a-bg)] px-4 py-10">
    <Head title="Iniciar sesión" />
    <div class="w-full max-w-sm">
      <div class="mb-6 text-center">
        <img v-if="appLogo" :src="appLogo" alt="" class="mx-auto mb-3 h-12 w-12 rounded-xl object-cover" width="48" height="48" />
        <p class="text-sm font-semibold text-[var(--a-muted)]">{{ appName }}</p>
        <h1 class="mt-1 text-2xl font-bold text-[var(--a-ink)]">Inicia sesión</h1>
        <p class="mt-1 text-sm text-[var(--a-ink-2)]">Gestiona pedidos, productos y la apariencia de tu tienda.</p>
      </div>

      <form class="a-card a-card-pad space-y-4" novalidate @submit.prevent="submit">
        <p v-if="generalError" class="rounded-lg bg-[var(--tone-critical-bg)] px-3 py-2 text-sm font-medium text-[var(--tone-critical)]" role="alert">{{ generalError }}</p>
        <div>
          <label for="login-email" class="a-label">Correo electrónico</label>
          <input id="login-email" v-model.trim="form.email" type="email" class="a-input" autocomplete="username" inputmode="email" required autofocus :aria-invalid="form.errors.email ? 'true' : 'false'" aria-describedby="login-email-error" />
          <p v-if="form.errors.email" id="login-email-error" class="a-error">{{ form.errors.email }}</p>
        </div>
        <div>
          <label for="login-password" class="a-label">Contraseña</label>
          <div class="relative">
            <input id="login-password" v-model="form.password" :type="showPassword ? 'text' : 'password'" class="a-input pr-20" autocomplete="current-password" required :aria-invalid="form.errors.password ? 'true' : 'false'" aria-describedby="login-password-error" />
            <button type="button" class="absolute inset-y-0 right-1 my-auto h-8 rounded-md px-2 text-xs font-semibold text-[var(--a-ink-2)] hover:bg-[var(--a-hover)]" :aria-pressed="showPassword ? 'true' : 'false'" @click="showPassword = !showPassword">
              {{ showPassword ? 'Ocultar' : 'Mostrar' }}
            </button>
          </div>
          <p v-if="form.errors.password" id="login-password-error" class="a-error">{{ form.errors.password }}</p>
        </div>
        <button type="submit" class="a-btn a-btn-primary w-full !min-h-10" :disabled="form.processing">
          <span v-if="form.processing" class="spinner" aria-hidden="true"></span>
          {{ form.processing ? 'Ingresando…' : 'Ingresar' }}
        </button>
      </form>

      <p class="mt-5 text-center text-sm">
        <Link href="/" class="font-medium text-[var(--a-ink-2)] underline underline-offset-4 hover:text-[var(--a-ink)]">Volver a la tienda</Link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const appName = computed(() => page.props.app?.name || 'Vendex');
const appLogo = computed(() => page.props.app?.logo || null);
const showPassword = ref(false);
const generalError = ref('');

const form = useForm({ email: '', password: '' });

const submit = () => {
  generalError.value = '';
  form.clearErrors();
  if (!form.email) form.setError('email', 'Escribe tu correo.');
  if (!form.password) form.setError('password', 'Escribe tu contraseña.');
  if (form.hasErrors) return;
  form.post('/admin/login', {
    onError: (errors) => {
      if (!Object.keys(errors).length) generalError.value = 'No pudimos iniciar sesión. Inténtalo de nuevo.';
    },
    onFinish: () => form.reset('password'),
  });
};
</script>
