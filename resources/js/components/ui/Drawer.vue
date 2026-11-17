<template>
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="open" class="fixed inset-0 z-[70] bg-black/40" aria-hidden="true" @click="requestClose"></div>
    </Transition>
    <Transition name="drawer">
      <section
        v-if="open"
        ref="panel"
        class="fixed inset-y-0 right-0 z-[71] flex w-full flex-col bg-[var(--a-bg)] shadow-2xl"
        :class="widthClass"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="titleId"
        @keydown.esc.stop="requestClose"
      >
        <header class="flex items-center justify-between gap-3 border-b border-[var(--a-border)] bg-white px-4 py-3 sm:px-5">
          <div class="min-w-0">
            <h2 :id="titleId" class="truncate text-base font-bold text-[var(--a-ink)]">{{ title }}</h2>
            <p v-if="subtitle" class="truncate text-xs text-[var(--a-muted)]">{{ subtitle }}</p>
          </div>
          <button type="button" class="a-btn a-btn-plain a-btn-icon" aria-label="Cerrar panel" @click="requestClose">
            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.3 5.3a1 1 0 0 0-1.4 1.4L8.6 10l-3.7 3.3a1 1 0 1 0 1.4 1.4L10 11.4l3.3 3.3a1 1 0 0 0 1.4-1.4L11.4 10l3.3-3.3a1 1 0 0 0-1.4-1.4L10 8.6 6.3 5.3Z"/></svg>
          </button>
        </header>
        <div class="flex-1 overflow-y-auto px-4 py-4 sm:px-5">
          <slot />
        </div>
        <footer v-if="$slots.footer" class="flex flex-wrap items-center justify-end gap-2 border-t border-[var(--a-border)] bg-white px-4 py-3 sm:px-5">
          <slot name="footer" />
        </footer>
      </section>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
  size: { type: String, default: 'md' }, // md | lg
  busy: { type: Boolean, default: false },
});
const emit = defineEmits(['close']);

const panel = ref(null);
const titleId = `drawer-${Math.random().toString(36).slice(2, 8)}`;
const widthClass = computed(() => (props.size === 'lg' ? 'sm:max-w-3xl' : 'sm:max-w-xl'));
let lastFocus = null;

const requestClose = () => {
  if (!props.busy) emit('close');
};

watch(() => props.open, async (open) => {
  document.documentElement.style.overflow = open ? 'hidden' : '';
  if (open) {
    lastFocus = document.activeElement;
    await nextTick();
    const target = panel.value?.querySelector('[autofocus], input:not([type=hidden]):not([disabled]), select, textarea');
    (target || panel.value)?.focus?.();
  } else if (lastFocus?.focus) {
    lastFocus.focus();
  }
}, { immediate: true });

onBeforeUnmount(() => { document.documentElement.style.overflow = ''; });
</script>
