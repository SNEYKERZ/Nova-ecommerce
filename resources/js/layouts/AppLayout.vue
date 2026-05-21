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
          <Link href="/galerias" class="nav-link">Galerías</Link>
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
        <Link href="/galerias" class="block nav-link text-sm py-1" @click="mobileOpen=false">Galerías</Link>
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
            <p class="font-display text-lg font-black uppercase tracking-[0.1em]" style="color:#111111">{{ appName }}</p>
            <p class="mt-2 text-sm" style="color:#555555">Moda y estilo para quienes marcan tendencia.</p>

            <!-- Social links -->
            <div class="mt-4 flex items-center gap-3">
              <a v-if="socialFacebook" :href="socialFacebook" target="_blank" rel="noopener noreferrer"
                 class="inline-flex items-center gap-1.5 text-xs font-semibold transition-opacity hover:opacity-70"
                 style="color:#333333">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                Facebook
              </a>
              <a v-if="socialInstagram" :href="socialInstagram" target="_blank" rel="noopener noreferrer"
                 class="inline-flex items-center gap-1.5 text-xs font-semibold transition-opacity hover:opacity-70"
                 style="color:#333333">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                Instagram
              </a>
              <a v-if="socialTiktok" :href="socialTiktok" target="_blank" rel="noopener noreferrer"
                 class="inline-flex items-center gap-1.5 text-xs font-semibold transition-opacity hover:opacity-70"
                 style="color:#333333">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
                TikTok
              </a>
              <a v-if="appWhatsapp" :href="`https://wa.me/${appWhatsapp.replace(/\D/g,'')}`" target="_blank" rel="noopener"
                 class="inline-flex items-center gap-1.5 text-xs font-semibold transition-opacity hover:opacity-70"
                 style="color:#333333">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                WhatsApp
              </a>
            </div>
          </div>

          <!-- Links -->
          <div>
            <p class="text-xs font-bold uppercase tracking-widest mb-4" style="color:#666666">Tienda</p>
            <div class="space-y-2.5">
              <Link href="/" class="block text-sm transition-opacity hover:opacity-70" style="color:#333333">Catálogo</Link>
              <Link href="/galerias" class="block text-sm transition-opacity hover:opacity-70" style="color:#333333">Galerías</Link>
              <Link href="/conocenos" class="block text-sm transition-opacity hover:opacity-70" style="color:#333333">Nosotros</Link>
              <Link href="/carrito" class="block text-sm transition-opacity hover:opacity-70" style="color:#333333">Carrito</Link>
              <a v-if="empresasUrl" :href="empresasUrl" target="_blank" rel="noopener noreferrer"
                 class="block text-sm transition-opacity hover:opacity-70" style="color:#333333">Empresas</a>
            </div>
          </div>

          <!-- Contact -->
          <div>
            <p class="text-xs font-bold uppercase tracking-widest mb-4" style="color:#666666">Contacto</p>
            <p v-if="appWhatsapp" class="text-sm" style="color:#333333">{{ appWhatsapp }}</p>
            <p class="mt-2 text-xs" style="color:#888888">{{ appName }} — Tienda oficial</p>
          </div>

        </div>

        <div class="mt-10 pt-6 flex flex-col sm:flex-row items-center justify-between gap-3 border-t" style="border-color:#dddddd">
          <p class="text-xs" style="color:#777777">© {{ year }} {{ appName }}. Todos los derechos reservados.</p>
          <p class="text-xs" style="color:#999999">Powered by Vendex</p>
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
const socialFacebook   = computed(() => page.props.app?.social?.facebook || null);
const socialInstagram  = computed(() => page.props.app?.social?.instagram || null);
const socialTiktok     = computed(() => page.props.app?.social?.tiktok || null);
const empresasUrl      = computed(() => page.props.app?.empresas_url || null);

const storeBg    = computed(() => page.props.app?.bg_color ?? '#ffffff');
const navbarBg   = computed(() => page.props.app?.navbar_color ?? '#ffffff');
const footerBg   = computed(() => page.props.app?.footer_color ?? '#ffffff');

const storeStyles = computed(() => ({
  background: storeBg.value,
  '--store-bg': storeBg.value,
}));

const navbarTextColor = computed(() => page.props.app?.navbar_text_color ?? '#111111');
const footerTextColor = computed(() => page.props.app?.footer_text_color ?? '#111111');

const navbarStyles = computed(() => ({
  background: navbarBg.value,
  color: navbarTextColor.value,
  borderColor: 'var(--border)',
}));

const footerStyles = computed(() => ({
  background: footerBg.value,
  color: footerTextColor.value,
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
