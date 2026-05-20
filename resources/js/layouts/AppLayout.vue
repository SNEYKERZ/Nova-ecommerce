<template>
  <div class="min-h-screen flex flex-col" :style="storeStyles">

    <!-- ── Impersonation banner ──────────────────────── -->
    <div v-if="isImpersonating" class="flex items-center justify-center gap-3 bg-amber-400 px-4 py-2 text-center text-xs font-bold text-amber-900">
      <span>⚡ Estás operando como <span class="underline">{{ page.props.auth?.user?.name }}</span></span>
      <button class="rounded bg-amber-900/20 px-3 py-1 font-semibold hover:bg-amber-900/30 cursor-pointer" @click="leaveImpersonation">
        Volver a mi cuenta
      </button>
    </div>

    <!-- ── Announcement bar ────────────────────────────── -->
    <div v-if="announcement" class="text-center py-2 text-white text-xs font-semibold tracking-widest uppercase" style="background:var(--ink)">
      {{ announcement }}
    </div>

    <!-- ── Header ─────────────────────────────────────── -->
      <header class="sticky top-0 z-40 border-b" :style="navbarStyles">
      <div class="mx-auto flex items-center justify-between px-4 sm:px-6 lg:px-8 max-w-screen-2xl" style="height:56px">

        <!-- Logo -->
        <Link href="/" class="flex items-center gap-2.5 shrink-0">
          <img v-if="appLogo" :src="appLogo" alt="Logo" class="h-7 w-7 object-cover" style="border-radius:var(--r)" />
          <span class="font-display text-sm font-black uppercase tracking-[0.12em]" style="color:var(--ink)">{{ appName }}</span>
        </Link>

        <!-- Desktop nav (centrado) -->
        <nav class="hidden md:flex items-center gap-8 absolute left-1/2 -translate-x-1/2">
          <Link href="/" class="nav-link">Catálogo</Link>
          <Link href="/conocenos" class="nav-link">Nosotros</Link>
        </nav>

        <!-- Right icons -->
        <div class="flex items-center gap-3">
          <!-- Admin pill -->
          <Link v-if="isAdmin" :href="adminUrl" class="hidden sm:inline-flex btn-soft px-3 py-1.5 text-xs">
            {{ isSuperAdmin ? 'Super Admin' : 'Admin' }}
          </Link>
          <Link v-else href="/admin/login" class="hidden sm:inline-flex btn-ghost text-xs normal-case no-underline">
            Admin
          </Link>

          <!-- Cart -->
          <Link href="/carrito" class="relative flex items-center gap-1.5 py-1.5 px-2.5 group" style="border-radius:var(--r);transition:background 0.15s" @mouseenter="hoverCart=true" @mouseleave="hoverCart=false">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M16 10a4 4 0 0 1-8 0"/></svg>
            <span v-if="cartCount > 0" class="absolute -top-1 -right-1 h-4.5 w-4.5 flex items-center justify-center text-white text-[10px] font-black" style="background:var(--ink);border-radius:50%;min-width:1.1rem;height:1.1rem;padding:0 2px">
              {{ cartCount > 9 ? '9+' : cartCount }}
            </span>
          </Link>

          <!-- Logout -->
          <button v-if="isAdmin" @click="logout" class="hidden sm:flex btn-ghost text-xs">
            Salir
          </button>

          <!-- Mobile menu button -->
          <button class="md:hidden flex items-center justify-center w-9 h-9 cursor-pointer" style="border-radius:var(--r)" @click="mobileOpen=!mobileOpen">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path v-if="!mobileOpen" stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
              <path v-else stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
      </div>

      <!-- Mobile nav -->
      <div v-if="mobileOpen" class="md:hidden border-t px-4 py-4 space-y-3" :style="{ borderColor: 'var(--border)', background: navbarBg }">
        <Link href="/" class="block nav-link text-sm py-1" @click="mobileOpen=false">Catálogo</Link>
        <Link href="/conocenos" class="block nav-link text-sm py-1" @click="mobileOpen=false">Nosotros</Link>
        <Link href="/carrito" class="block nav-link text-sm py-1" @click="mobileOpen=false">Carrito ({{ cartCount }})</Link>
        <Link v-if="isAdmin" href="/admin" class="block nav-link text-sm py-1" @click="mobileOpen=false">Panel Admin</Link>
        <button v-if="isAdmin" @click="logout" class="block nav-link text-sm py-1 text-left w-full cursor-pointer">Cerrar sesión</button>
      </div>
    </header>

    <!-- ── Main ────────────────────────────────────────── -->
    <main class="flex-1">
      <slot />
    </main>

    <!-- ── Footer ─────────────────────────────────────── -->
      <footer class="border-t mt-20" :style="footerStyles">
      <div class="mx-auto max-w-screen-2xl px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid gap-10 md:grid-cols-3 lg:grid-cols-4">

          <!-- Brand -->
          <div class="lg:col-span-2">
            <p class="font-display text-lg font-black uppercase tracking-[0.1em]">{{ appName }}</p>
            <p class="mt-2 text-sm" style="color:rgba(255,255,255,0.55)">Moda y estilo para quienes marcan tendencia.</p>
            <a v-if="appWhatsapp" :href="`https://wa.me/${appWhatsapp.replace(/\D/g,'')}`" target="_blank" rel="noopener"
               class="mt-4 inline-flex items-center gap-2 text-xs font-semibold transition-opacity hover:opacity-70"
               style="color:rgba(255,255,255,0.75)">
              <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
              WhatsApp
            </a>
          </div>

          <!-- Links -->
          <div>
            <p class="text-xs font-bold uppercase tracking-widest mb-4" style="color:rgba(255,255,255,0.4)">Tienda</p>
            <div class="space-y-2.5">
              <Link href="/" class="block text-sm transition-opacity hover:opacity-70" style="color:rgba(255,255,255,0.75)">Catálogo</Link>
              <Link href="/conocenos" class="block text-sm transition-opacity hover:opacity-70" style="color:rgba(255,255,255,0.75)">Nosotros</Link>
              <Link href="/carrito" class="block text-sm transition-opacity hover:opacity-70" style="color:rgba(255,255,255,0.75)">Carrito</Link>
            </div>
          </div>

          <!-- Contact -->
          <div v-if="appWhatsapp">
            <p class="text-xs font-bold uppercase tracking-widest mb-4" style="color:rgba(255,255,255,0.4)">Contacto</p>
            <p class="text-sm" style="color:rgba(255,255,255,0.75)">{{ appWhatsapp }}</p>
          </div>

        </div>

        <div class="mt-10 pt-6 flex flex-col sm:flex-row items-center justify-between gap-3 border-t" style="border-color:rgba(255,255,255,0.1)">
          <p class="text-xs" style="color:rgba(255,255,255,0.35)">© {{ year }} {{ appName }}. Todos los derechos reservados.</p>
          <p class="text-xs" style="color:rgba(255,255,255,0.2)">Powered by Vendex</p>
        </div>
      </div>
    </footer>

  </div>
</template>

<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const page       = usePage();
const cartCount  = ref(0);
const year       = new Date().getFullYear();
const mobileOpen = ref(false);
const hoverCart  = ref(false);

const isAdmin          = computed(() => ['admin', 'super_admin'].includes(page.props.auth?.user?.role || ''));
const isSuperAdmin     = computed(() => page.props.auth?.user?.role === 'super_admin');
const isImpersonating  = computed(() => page.props.auth?.user?.impersonating === true);
const adminUrl         = computed(() => isSuperAdmin.value ? '/super-admin' : '/admin');
const appName          = computed(() => page.props.app?.name || 'Vendex');
const appLogo          = computed(() => page.props.app?.logo || null);
const appWhatsapp      = computed(() => page.props.app?.whatsapp || null);
const announcement     = computed(() => null); // Set to a string to show the bar

const storeBg    = computed(() => page.props.app?.bg_color ?? '#ffffff');
const navbarBg   = computed(() => page.props.app?.navbar_color ?? '#ffffff');
const footerBg   = computed(() => page.props.app?.footer_color ?? '#111111');

const storeStyles = computed(() => ({
  background: storeBg.value,
  '--store-bg': storeBg.value,
}));

const navbarStyles = computed(() => ({
  background: navbarBg.value,
  borderColor: 'var(--border)',
}));

const footerStyles = computed(() => ({
  background: footerBg.value,
  color: 'var(--white)',
  borderColor: 'transparent',
}));

const syncCart = async () => {
  try {
    const res     = await fetch('/api/carrito', { headers: { Accept: 'application/json' } });
    const payload = await res.json();
    cartCount.value = payload?.data?.cantidad_total ?? 0;
  } catch {
    cartCount.value = 0;
  }
};

const onCartUpdated = () => syncCart();

onMounted(async () => {
  syncCart();
  window.addEventListener('cart-updated', onCartUpdated);

  // Auth consistency check — si shared props se desincronizan,
  // forzamos un re-check silencioso
  if (page.props.auth?.user && !page.props.auth.user.role) {
    try {
      const res = await fetch('/api/user/me', {
        headers: { Accept: 'application/json' },
      });
      if (res.ok) {
        const payload = await res.json();
        if (payload.role) {
          page.props.auth.user.role = payload.role;
        }
      }
    } catch {
      // silencio — no romper la UI por esto
    }
  }
});

onUnmounted(() => {
  window.removeEventListener('cart-updated', onCartUpdated);
});

const logout = () => router.post('/admin/logout');

const leaveImpersonation = () => {
  router.post('/admin/leave-impersonation');
};
</script>
