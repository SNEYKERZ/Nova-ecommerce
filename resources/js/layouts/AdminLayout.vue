<template>
  <div class="min-h-screen bg-[var(--a-bg)] text-[var(--a-ink)]">
    <a href="#admin-main" class="admin-skip">Saltar al contenido</a>

    <!-- Barra superior -->
    <header class="sticky top-0 z-40 flex h-14 items-center gap-3 bg-[var(--a-topbar)] px-3 text-white sm:px-4">
      <button
        type="button"
        class="flex h-9 w-9 items-center justify-center rounded-lg hover:bg-white/10 lg:hidden"
        :aria-expanded="navOpen ? 'true' : 'false'"
        aria-controls="admin-nav"
        aria-label="Abrir navegación"
        @click="navOpen = true"
      >
        <AdminIcon name="menu" />
      </button>
      <div class="flex min-w-0 items-center gap-2.5">
        <img v-if="logo" :src="logo" alt="" class="h-7 w-7 rounded-md bg-white object-cover" width="28" height="28" />
        <span v-else class="flex h-7 w-7 items-center justify-center rounded-md bg-white text-xs font-black text-[#1a1a1a]">{{ initials }}</span>
        <div class="min-w-0 leading-tight">
          <p class="truncate text-sm font-semibold">{{ brand }}</p>
          <p class="hidden text-[11px] text-white/60 sm:block">{{ variant === 'super' ? 'Super administración' : 'Panel de la tienda' }}</p>
        </div>
      </div>
      <div class="ml-auto flex items-center gap-1 sm:gap-2">
        <a v-if="variant === 'store'" href="/" target="_blank" rel="noopener" class="hidden items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-white/85 hover:bg-white/10 hover:text-white sm:inline-flex">
          Ver tienda <AdminIcon name="external" size="sm" />
        </a>
        <span class="hidden max-w-[12rem] truncate text-xs text-white/70 md:inline" :title="user?.email">{{ user?.name }}</span>
        <button type="button" class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-white/85 hover:bg-white/10 hover:text-white" @click="logout">
          <AdminIcon name="logout" size="sm" /><span class="hidden sm:inline">Salir</span>
        </button>
      </div>
    </header>

    <div v-if="impersonating" class="flex flex-wrap items-center justify-center gap-3 bg-[var(--tone-warning-bg)] px-4 py-2 text-center text-xs font-semibold text-[var(--tone-warning)]">
      <span>Estás viendo el panel como {{ user?.name }} ({{ user?.email }}).</span>
      <button type="button" class="a-btn a-btn-secondary a-btn-sm" @click="leaveImpersonation">Volver a super admin</button>
    </div>

    <div class="mx-auto flex max-w-[110rem]">
      <!-- Navegación lateral (escritorio) -->
      <nav id="admin-nav-desktop" class="sticky top-14 hidden h-[calc(100vh-3.5rem)] w-60 shrink-0 overflow-y-auto bg-[var(--a-sidebar)] px-3 py-4 lg:block" aria-label="Panel">
        <AdminNavList :items="nav" :active="active" @navigate="go" />
      </nav>

      <!-- Navegación lateral (móvil) -->
      <Teleport to="body">
        <Transition name="fade">
          <div v-if="navOpen" class="fixed inset-0 z-[60] bg-black/40 lg:hidden" aria-hidden="true" @click="navOpen = false"></div>
        </Transition>
        <Transition name="drawer-left">
          <nav
            v-if="navOpen"
            id="admin-nav"
            class="fixed inset-y-0 left-0 z-[61] w-72 max-w-[85vw] overflow-y-auto bg-[var(--a-sidebar)] px-3 py-4 shadow-2xl lg:hidden"
            aria-label="Panel"
            @keydown.esc="navOpen = false"
          >
            <div class="mb-3 flex items-center justify-between px-2">
              <p class="text-sm font-bold">{{ brand }}</p>
              <button type="button" class="a-btn a-btn-plain a-btn-icon" aria-label="Cerrar navegación" @click="navOpen = false">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.3 5.3a1 1 0 0 0-1.4 1.4L8.6 10l-3.7 3.3a1 1 0 1 0 1.4 1.4L10 11.4l3.3 3.3a1 1 0 0 0 1.4-1.4L11.4 10l3.3-3.3a1 1 0 0 0-1.4-1.4L10 8.6 6.3 5.3Z"/></svg>
              </button>
            </div>
            <AdminNavList :items="nav" :active="active" @navigate="go" />
            <a v-if="variant === 'store'" href="/" target="_blank" rel="noopener" class="mt-4 flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-[var(--a-ink-2)] hover:bg-[var(--a-hover)]">
              <AdminIcon name="external" size="sm" /> Ver tienda
            </a>
          </nav>
        </Transition>
      </Teleport>

      <main id="admin-main" class="min-w-0 flex-1 px-4 py-5 sm:px-6 sm:py-6 lg:px-8" tabindex="-1">
        <div class="mx-auto max-w-6xl">
          <slot />
        </div>
      </main>
    </div>

    <ToastHost />
    <ConfirmDialog />
  </div>
</template>

<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { computed, defineComponent, h, ref, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import AdminIcon from '../components/admin/AdminIcon.vue';
import ToastHost from '../components/ui/ToastHost.vue';
import ConfirmDialog from '../components/ui/ConfirmDialog.vue';
import { useToast } from '../composables/useToast';

const props = defineProps({
  nav: { type: Array, required: true },
  active: { type: String, default: '' },
  variant: { type: String, default: 'store' }, // store | super
  title: { type: String, default: '' },
});
const emit = defineEmits(['navigate']);

const page = usePage();
const toast = useToast();
const navOpen = ref(false);

const user = computed(() => page.props.auth?.user || null);
const impersonating = computed(() => user.value?.impersonating === true);
const brand = computed(() => (props.variant === 'super' ? 'Vendex' : page.props.app?.name || 'Mi tienda'));
const logo = computed(() => (props.variant === 'super' ? null : page.props.app?.logo || null));
const initials = computed(() => brand.value.split(/\s+/).map((w) => w[0]).join('').slice(0, 2).toUpperCase());

const go = (item) => {
  navOpen.value = false;
  if (item.href) router.visit(item.href);
  else emit('navigate', item.key);
};

const logout = () => router.post('/admin/logout');
const leaveImpersonation = () => router.post('/admin/leave-impersonation');

watch(navOpen, (open) => { document.documentElement.style.overflow = open ? 'hidden' : ''; });

// Mensajes flash del servidor → toasts
watch(() => page.props.flash, (flash) => {
  if (flash?.success) toast.success(flash.success);
  if (flash?.message) toast.info(flash.message);
  if (flash?.error) toast.error(flash.error);
}, { immediate: true, deep: true });

const AdminNavList = defineComponent({
  props: { items: Array, active: String },
  emits: ['navigate'],
  setup(listProps, { emit: emitNav }) {
    return () => h('ul', { class: 'space-y-0.5' }, listProps.items.map((item) => {
      const isActive = item.key === listProps.active;
      const className = [
        'flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm font-medium transition-colors',
        isActive ? 'bg-white text-[var(--a-ink)] shadow-[var(--a-shadow)]' : 'text-[var(--a-ink-2)] hover:bg-[var(--a-hover)] hover:text-[var(--a-ink)]',
      ].join(' ');
      const children = [
        h(AdminIcon, { name: item.icon, size: 'sm' }),
        h('span', { class: 'flex-1 truncate' }, item.label),
        item.badge ? h('span', { class: 'rounded-full bg-[var(--tone-attention-bg)] px-1.5 text-[11px] font-semibold text-[var(--tone-attention)] tabular' }, String(item.badge)) : null,
      ];
      const node = item.href
        ? h(Link, { href: item.href, class: className, 'aria-current': isActive ? 'page' : undefined }, () => children)
        : h('button', { type: 'button', class: className, 'aria-current': isActive ? 'page' : undefined, onClick: () => emitNav('navigate', item) }, children);
      return h('li', { key: item.key }, [node]);
    }));
  },
});
</script>

<style scoped>
.admin-skip {
  position: absolute;
  left: 0.75rem;
  top: -4rem;
  z-index: 100;
  background: #fff;
  color: #1a1a1a;
  padding: 0.5rem 0.9rem;
  border-radius: 8px;
  font-size: 0.8rem;
  font-weight: 700;
}
.admin-skip:focus { top: 0.6rem; }
main:focus { outline: none; }
.drawer-left-enter-active, .drawer-left-leave-active { transition: transform 0.25s var(--ease); }
.drawer-left-enter-from, .drawer-left-leave-to { transform: translateX(-100%); }
</style>
