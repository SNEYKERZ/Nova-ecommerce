<template>
  <nav v-if="meta && meta.last_page > 1" class="flex flex-wrap items-center justify-between gap-3 border-t border-[var(--a-border)] px-4 py-3" aria-label="Paginación">
    <p class="text-xs text-[var(--a-muted)]">{{ meta.from }}–{{ meta.to }} de {{ meta.total }}</p>
    <div class="flex items-center gap-1">
      <component
        :is="prevUrl ? Link : 'span'"
        :href="prevUrl || undefined"
        :only="prevUrl ? only : undefined"
        :preserve-scroll="prevUrl ? true : undefined"
        :preserve-state="prevUrl ? true : undefined"
        class="a-btn a-btn-secondary a-btn-sm"
        :class="!prevUrl && 'opacity-40'"
        :aria-disabled="!prevUrl ? 'true' : undefined"
      >‹ Anterior</component>
      <span class="px-2 text-xs font-semibold text-[var(--a-ink-2)] tabular">{{ meta.current_page }} / {{ meta.last_page }}</span>
      <component
        :is="nextUrl ? Link : 'span'"
        :href="nextUrl || undefined"
        :only="nextUrl ? only : undefined"
        :preserve-scroll="nextUrl ? true : undefined"
        :preserve-state="nextUrl ? true : undefined"
        class="a-btn a-btn-secondary a-btn-sm"
        :class="!nextUrl && 'opacity-40'"
        :aria-disabled="!nextUrl ? 'true' : undefined"
      >Siguiente ›</component>
    </div>
  </nav>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * Paginación de un paginador de Laravel. `only` limita la recarga a la prop de esa tabla
 * para no mover el resto de listados del panel (todos comparten el parámetro ?page).
 */
const props = defineProps({
  meta: { type: Object, default: null },
  only: { type: Array, default: () => [] },
  section: { type: String, default: '' },
});

const withSection = (url) => {
  if (!url) return null;
  const parsed = new URL(url, window.location.origin);
  if (props.section) parsed.searchParams.set('seccion', props.section);
  return parsed.pathname + parsed.search;
};
const prevUrl = computed(() => withSection(props.meta?.prev_page_url));
const nextUrl = computed(() => withSection(props.meta?.next_page_url));
</script>
