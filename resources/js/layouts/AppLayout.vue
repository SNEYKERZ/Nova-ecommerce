<template>
  <div class="sf flex min-h-screen flex-col" :data-rubro="rubro.key.value" :style="storeStyles">
    <Head v-if="rubroFont">
      <link rel="stylesheet" :href="rubroFont" />
    </Head>
    <a href="#contenido" class="skip-link">Saltar al contenido</a>

    <!-- Banner de suplantación -->
    <div v-if="isImpersonating" class="flex flex-wrap items-center justify-center gap-3 bg-amber-300 px-4 py-2 text-center text-xs font-bold text-amber-950">
      <span>Estás operando como {{ page.props.auth?.user?.name }}</span>
      <button type="button" class="rounded bg-amber-950/15 px-3 py-1.5 font-semibold hover:bg-amber-950/25" @click="leaveImpersonation">Volver a mi cuenta</button>
    </div>

    <!-- Encabezado -->
    <header class="sticky top-0 z-50 border-b" :style="navbarStyles">
      <div class="mx-auto grid h-16 max-w-[110rem] grid-cols-[1fr_auto_1fr] items-center gap-4 px-4 sm:px-6 lg:px-10">
        <div class="flex items-center">
          <button
            type="button"
            class="sf-menu-btn md:hidden"
            :aria-expanded="mobileOpen ? 'true' : 'false'"
            aria-controls="menu-movil"
            @click="mobileOpen = !mobileOpen"
          >
            <span class="sf-menu-icon" :class="{ 'is-open': mobileOpen }" aria-hidden="true"><i></i><i></i></span>
            <span class="text-sm font-semibold">{{ mobileOpen ? 'Cerrar' : 'Menú' }}</span>
          </button>
          <nav class="hidden items-center gap-7 md:flex" aria-label="Principal">
            <Link
              v-for="link in navLinks"
              :key="link.href"
              :href="link.href"
              class="sf-nav-link"
              :aria-current="isCurrent(link.href) ? 'page' : undefined"
            >
              {{ link.label }}
              <PencilMark shape="underline" :active="isCurrent(link.href)" />
            </Link>
          </nav>
        </div>

        <Link href="/" class="flex min-w-0 items-center justify-center gap-2.5" :aria-label="`${appName}, ir al inicio`">
          <img v-if="appLogo" :src="appLogo" alt="" class="h-8 w-8 shrink-0 object-cover" width="32" height="32" />
          <span class="sf-display truncate text-[1.65rem] leading-none sm:text-[1.9rem]">{{ appName }}</span>
        </Link>

        <div class="flex items-center justify-end gap-1 sm:gap-3">
          <Link v-if="isAdmin" :href="adminUrl" class="hidden text-sm font-semibold underline-offset-4 hover:underline lg:inline">
            {{ isSuperAdmin ? 'Super admin' : 'Panel' }}
          </Link>
          <Link href="/carrito" class="sf-cart-link" :aria-label="cartLabel">
            <span class="hidden text-sm font-semibold sm:inline">Carrito</span>
            <svg class="h-5 w-5 sm:hidden" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14l-1.2 11.2a1 1 0 0 1-1 .8H7.2a1 1 0 0 1-1-.8L5 8Z"/><path stroke-linecap="round" d="M9 8V6a3 3 0 0 1 6 0v2"/></svg>
            <span :key="cart.state.bump" class="sf-cart-count sf-num" :class="{ 'cart-bump': cart.state.bump, 'is-empty': !cart.state.count }" aria-hidden="true">
              {{ cart.state.count > 99 ? '99+' : cart.state.count }}
            </span>
          </Link>
        </div>
      </div>

      <Transition name="menu">
        <nav v-if="mobileOpen" id="menu-movil" class="sf-mobile-menu md:hidden" :style="navbarStyles" aria-label="Menú móvil">
          <ul>
            <li v-for="link in mobileLinks" :key="link.href">
              <Link :href="link.href" class="sf-display flex min-h-16 items-center justify-between text-5xl" :aria-current="isCurrent(link.href) ? 'page' : undefined" @click="mobileOpen = false">
                <span class="relative">
                  {{ link.label }}
                  <PencilMark shape="underline" :active="isCurrent(link.href)" />
                </span>
                <span v-if="link.href === '/carrito' && cart.state.count" class="sf-num text-base font-semibold opacity-70">{{ cart.state.count }}</span>
              </Link>
            </li>
          </ul>
          <button v-if="isAdmin" type="button" class="mt-6 text-sm font-semibold underline underline-offset-4" @click="logout">Cerrar sesión</button>
        </nav>
      </Transition>
    </header>

    <main id="contenido" class="flex-1" tabindex="-1">
      <slot />
    </main>

    <!-- Pie -->
    <footer class="mt-24" :style="footerStyles">
      <div class="mx-auto max-w-[110rem] px-4 pb-8 pt-12 sm:px-6 lg:px-10">
        <div class="grid gap-10 border-b pb-12 md:grid-cols-12" :style="ruleStyle">
          <div class="md:col-span-5">
            <p class="max-w-md text-base leading-relaxed" style="opacity:.85">{{ footerAbout }}</p>
            <a v-if="whatsappHref" :href="whatsappHref" target="_blank" rel="noopener" class="mt-6 inline-flex min-h-11 items-center gap-2.5 border px-4 text-sm font-semibold transition hover:opacity-80" :style="ruleStyle">
              <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path :d="icons.whatsapp" /></svg>
              Escríbenos por WhatsApp
            </a>
          </div>

          <nav class="md:col-span-3 md:col-start-7" aria-label="Tienda">
            <p class="mb-3 text-sm font-semibold" style="opacity:.6">Tienda</p>
            <ul>
              <li v-for="link in footerLinks" :key="link.href"><Link :href="link.href" class="inline-flex min-h-10 items-center text-base underline-offset-4 hover:underline">{{ link.label }}</Link></li>
              <li v-if="empresasUrl"><a :href="empresasUrl" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-10 items-center text-base underline-offset-4 hover:underline">Empresas</a></li>
            </ul>
          </nav>

          <div class="md:col-span-3">
            <p class="mb-3 text-sm font-semibold" style="opacity:.6">Contacto</p>
            <ul class="space-y-1 text-base">
              <li v-if="appWhatsapp"><a :href="whatsappHref" target="_blank" rel="noopener" class="sf-num inline-flex min-h-10 items-center underline-offset-4 hover:underline">{{ appWhatsapp }}</a></li>
              <li v-for="social in socialLinks" :key="social.label">
                <a :href="social.href" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-10 items-center gap-2.5 underline-offset-4 hover:underline">
                  <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path :d="social.icon" /></svg>
                  {{ social.label }}
                </a>
              </li>
              <li v-if="!appWhatsapp && !socialLinks.length" style="opacity:.75">Pronto publicaremos nuestros canales.</li>
            </ul>
          </div>
        </div>

        <p class="sf-display sf-footer-name select-none py-8" aria-hidden="true">{{ appName }}</p>

        <div class="flex flex-col gap-3 text-sm sm:flex-row sm:items-center sm:justify-between" style="opacity:.75">
          <p>© {{ year }} {{ appName }}</p>
          <div class="flex items-center gap-5">
            <Link v-if="!isAdmin" href="/admin/login" class="underline-offset-4 hover:underline">Acceso tienda</Link>
            <span>Hecho con Vendex</span>
          </div>
        </div>
      </div>
    </footer>

    <CartAddedNotice />
    <ToastHost />
    <ConfirmDialog />
  </div>
</template>

<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import CartAddedNotice from '../components/store/CartAddedNotice.vue';
import ToastHost from '../components/ui/ToastHost.vue';
import ConfirmDialog from '../components/ui/ConfirmDialog.vue';
import { useCart } from '../composables/useCart';
import PencilMark from '../components/store/PencilMark.vue';
import { useRubro } from '../composables/useRubro';
import { bestInk, getContrastRatio, isValidHexColor, pickPencil } from '../utils/colorUtils';

const page = usePage();
const cart = useCart();
const year = new Date().getFullYear();
const mobileOpen = ref(false);
const rubro = useRubro();

/* Fuente de display propia de algunos rubros (las demás vienen en app.css) */
const RUBRO_FONTS = {
  restaurante: 'https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600..800&display=swap',
  autopartes: 'https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;800&display=swap',
};
const rubroFont = computed(() => RUBRO_FONTS[rubro.key.value] || null);

const icons = {
  facebook: 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z',
  instagram: 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z',
  tiktok: 'M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z',
  whatsapp: 'M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z',
};

const navLinks = computed(() => [
  { href: '/', label: rubro.preset.value.catalogo },
  { href: '/galerias', label: rubro.preset.value.galeria },
  { href: '/conocenos', label: 'Nosotros' },
]);
const footerLinks = computed(() => [...navLinks.value, { href: '/carrito', label: 'Carrito' }]);

const role = computed(() => page.props.auth?.user?.role || '');
const isAdmin = computed(() => ['admin', 'super_admin'].includes(role.value));
const isSuperAdmin = computed(() => role.value === 'super_admin');
const isImpersonating = computed(() => page.props.auth?.user?.impersonating === true);
const adminUrl = computed(() => (isSuperAdmin.value ? '/super-admin' : '/admin'));
const mobileLinks = computed(() => [
  ...navLinks.value,
  { href: '/carrito', label: 'Carrito' },
  ...(isAdmin.value ? [{ href: adminUrl.value, label: 'Panel de administración' }] : []),
]);

const appName = computed(() => page.props.app?.name || 'Vendex');
const appLogo = computed(() => page.props.app?.logo || null);
const appWhatsapp = computed(() => page.props.app?.whatsapp || null);
const whatsappHref = computed(() => {
  const digits = (appWhatsapp.value || '').replace(/\D/g, '');
  return digits ? `https://wa.me/${digits}` : null;
});
const empresasUrl = computed(() => page.props.app?.empresas_url || null);
const footerAbout = computed(() => {
  const text = (page.props.app?.descripcion || '').trim();
  if (text) return text.length > 220 ? `${text.slice(0, 217).trimEnd()}…` : text;
  return rubro.preset.value.about;
});
const socialLinks = computed(() => {
  const social = page.props.app?.social || {};
  return [
    social.instagram && { label: 'Instagram', href: social.instagram, icon: icons.instagram },
    social.facebook && { label: 'Facebook', href: social.facebook, icon: icons.facebook },
    social.tiktok && { label: 'TikTok', href: social.tiktok, icon: icons.tiktok },
    whatsappHref.value && { label: 'WhatsApp', href: whatsappHref.value, icon: icons.whatsapp },
  ].filter(Boolean);
});

const cartLabel = computed(() => {
  const n = cart.state.count;
  return n ? `Carrito, ${n} ${n === 1 ? 'producto' : 'productos'}` : 'Carrito vacío';
});

/**
 * Colores configurables por tienda. Si el color de texto elegido no contrasta
 * lo suficiente con el fondo (< 4.5:1) se usa automáticamente la tinta legible.
 * El "lápiz" (acento) sale del primer color de la tienda con carácter.
 */
const safeColor = (value, fallback) => (value && isValidHexColor(value) ? value : fallback);
const readableText = (bg, text) => (getContrastRatio(text, bg) >= 4.5 ? text : bestInk(bg));

const storeBg = computed(() => safeColor(page.props.app?.bg_color, '#f4f3ef'));
const navbarBg = computed(() => safeColor(page.props.app?.navbar_color, storeBg.value));
const footerBg = computed(() => safeColor(page.props.app?.footer_color, '#141414'));
const navbarText = computed(() => readableText(navbarBg.value, safeColor(page.props.app?.navbar_text_color, '#141414')));
const footerText = computed(() => readableText(footerBg.value, safeColor(page.props.app?.footer_text_color, '#f4f3ef')));
const ink = computed(() => bestInk(storeBg.value));
const pencil = computed(() => pickPencil(storeBg.value, [page.props.app?.navbar_color, page.props.app?.footer_color, page.props.app?.navbar_text_color]));

const storeStyles = computed(() => ({
  '--store-bg': storeBg.value,
  '--sf-ink': ink.value,
  '--pencil': pencil.value,
  '--pencil-ink': bestInk(pencil.value),
  '--navbar-color': navbarBg.value,
  '--navbar-text-color': navbarText.value,
  '--footer-color': footerBg.value,
  '--footer-text-color': footerText.value,
}));
const navbarStyles = computed(() => ({
  background: navbarBg.value,
  color: navbarText.value,
  borderColor: `color-mix(in srgb, ${navbarText.value} 14%, ${navbarBg.value})`,
}));
const footerStyles = computed(() => ({ background: footerBg.value, color: footerText.value }));
const ruleStyle = computed(() => ({ borderColor: `color-mix(in srgb, ${footerText.value} 22%, ${footerBg.value})` }));

const currentPath = computed(() => (page.url || '/').split('?')[0]);
const isCurrent = (href) => (href === '/' ? currentPath.value === '/' : currentPath.value.startsWith(href));

watch(currentPath, () => { mobileOpen.value = false; });
watch(mobileOpen, (open) => { document.documentElement.style.overflow = open ? 'hidden' : ''; });

const onCartUpdated = () => cart.sync();
onMounted(() => {
  cart.ensureLoaded();
  window.addEventListener('cart-updated', onCartUpdated);
});
onUnmounted(() => window.removeEventListener('cart-updated', onCartUpdated));

const logout = () => router.post('/admin/logout');
const leaveImpersonation = () => router.post('/admin/leave-impersonation');
</script>

<style scoped>
.skip-link {
  position: absolute;
  left: 0.75rem;
  top: -4rem;
  z-index: 100;
  background: var(--sf-ink);
  color: var(--sf-paper);
  padding: 0.6rem 1rem;
  font-size: 0.85rem;
  font-weight: 700;
}
.skip-link:focus { top: 0.75rem; }
main:focus { outline: none; }

.sf-nav-link {
  position: relative;
  display: inline-flex;
  align-items: center;
  min-height: 2.5rem;
  font-size: 0.92rem;
  font-weight: 600;
  color: inherit;
  opacity: 0.78;
  transition: opacity 160ms ease;
}
.sf-nav-link:hover, .sf-nav-link[aria-current="page"] { opacity: 1; }

.sf-menu-btn { display: inline-flex; align-items: center; gap: 0.6rem; min-height: 2.75rem; background: none; border: 0; color: inherit; }
@media (min-width: 768px) { .sf-menu-btn { display: none; } }
.sf-menu-icon { position: relative; display: inline-block; width: 1.3rem; height: 0.7rem; }
.sf-menu-icon i { position: absolute; left: 0; right: 0; height: 1.6px; background: currentColor; transition: transform 260ms cubic-bezier(0.16, 1, 0.3, 1), top 260ms cubic-bezier(0.16, 1, 0.3, 1); }
.sf-menu-icon i:first-child { top: 0; }
.sf-menu-icon i:last-child { top: calc(100% - 1.6px); }
.sf-menu-icon.is-open i:first-child { top: calc(50% - 0.8px); transform: rotate(45deg); }
.sf-menu-icon.is-open i:last-child { top: calc(50% - 0.8px); transform: rotate(-45deg); }

.sf-cart-link { display: inline-flex; align-items: center; gap: 0.5rem; min-height: 2.75rem; padding: 0 0.25rem; color: inherit; }
.sf-cart-count {
  display: inline-flex;
  min-width: 1.6rem;
  height: 1.6rem;
  align-items: center;
  justify-content: center;
  padding: 0 0.35rem;
  border-radius: 999px;
  background: var(--pencil);
  color: var(--pencil-ink);
  font-size: 0.75rem;
  font-weight: 800;
}
.sf-cart-count.is-empty { background: transparent; color: inherit; border: 1px solid currentColor; opacity: 0.6; }

.sf-mobile-menu {
  position: fixed;
  inset: 4rem 0 0 0;
  z-index: 49;
  overflow-y: auto;
  padding: 1.5rem 1rem calc(2rem + env(safe-area-inset-bottom));
  border-top: 1px solid color-mix(in srgb, currentColor 14%, transparent);
}
.sf-mobile-menu li + li { border-top: 1px solid color-mix(in srgb, currentColor 12%, transparent); }
.menu-enter-active, .menu-leave-active { transition: opacity 240ms ease, clip-path 420ms cubic-bezier(0.16, 1, 0.3, 1); }
.menu-enter-from, .menu-leave-to { opacity: 0; clip-path: inset(0 0 100% 0); }
.menu-enter-to, .menu-leave-from { clip-path: inset(0 0 0 0); }

.sf-footer-name {
  font-size: clamp(3.5rem, 15vw, 15rem);
  line-height: 0.82;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: clip;
}
</style>
