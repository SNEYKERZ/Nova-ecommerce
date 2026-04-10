<template>
  <div class="min-h-screen">
    <header class="sticky top-0 z-40 border-b border-black/5 bg-[color:var(--surface)]/90 backdrop-blur">
      <div class="mx-auto flex w-full max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
        <Link href="/" class="flex items-center gap-2 font-display text-xl font-bold tracking-tight">
          <img v-if="appLogo" :src="appLogo" alt="Logo tienda" class="h-8 w-8 rounded-full object-cover" />
          <span>{{ appName }}</span>
        </Link>

        <nav class="hidden items-center gap-7 text-sm font-semibold md:flex">
          <Link href="/" class="text-[color:var(--ink)]/85 hover:text-[color:var(--ink)]">Inicio</Link>
          <Link href="/conocenos" class="text-[color:var(--ink)]/85 hover:text-[color:var(--ink)]">Marca</Link>
          <Link v-if="isAdmin" href="/admin" class="text-[color:var(--ink)]/85 hover:text-[color:var(--ink)]">Panel Admin</Link>
          <Link v-else href="/admin/login" class="text-[color:var(--ink)]/85 hover:text-[color:var(--ink)]">Login Admin</Link>
          <Link href="/carrito" class="relative inline-flex items-center gap-2 text-[color:var(--ink)]/85 hover:text-[color:var(--ink)]">
            Carrito
            <span class="rounded-full bg-[color:var(--ink)] px-2 py-0.5 text-xs text-white">{{ cartCount }}</span>
          </Link>
          <button
            v-if="isAdmin"
            type="button"
            class="rounded-full border border-[color:var(--line)] px-3 py-1 text-xs"
            @click="logout"
          >
            Salir
          </button>
        </nav>

        <Link href="/carrito" class="md:hidden rounded-full border border-[color:var(--line)] px-3 py-1.5 text-sm font-semibold">
          Cart {{ cartCount }}
        </Link>
      </div>
    </header>

    <main>
      <slot />
    </main>

    <footer class="mt-20 border-t border-black/5 bg-[color:var(--surface-dark)] text-white">
      <div class="mx-auto flex w-full max-w-7xl flex-col gap-5 px-4 py-10 sm:px-6 lg:px-8 md:flex-row md:items-center md:justify-between">
        <div>
          <p class="font-display text-base font-semibold tracking-wide">NOVA COMMERCE BASE</p>
          <p class="mt-1 text-sm text-white/70">Plantilla e-commerce SPA adaptable a cualquier nicho.</p>
        </div>
        <p class="text-xs text-white/60">{{ year }} © Todos los derechos reservados.</p>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

const cartCount = ref(0);
const year = new Date().getFullYear();
const page = usePage();
const isAdmin = computed(() => ['admin', 'super_admin'].includes(page.props.auth?.user?.role || ''));
const appName = computed(() => page.props.app?.name || 'Nova Commerce');
const appLogo = computed(() => page.props.app?.logo || null);

const syncCart = async () => {
  try {
    const res = await fetch('/api/carrito', { headers: { Accept: 'application/json' } });
    const payload = await res.json();
    cartCount.value = payload?.data?.cantidad_total ?? 0;
  } catch {
    cartCount.value = 0;
  }
};

onMounted(() => {
  syncCart();
  window.addEventListener('cart-updated', syncCart);
});

const logout = () => {
  router.post('/admin/logout');
};
</script>
