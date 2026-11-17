<template>
  <div class="flex flex-col items-center px-6 py-12 text-center">
    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-[var(--a-surface-2)] text-[var(--a-muted)]">
      <AdminIcon :name="icon" />
    </div>
    <p class="mt-4 text-sm font-semibold text-[var(--a-ink)]">{{ title }}</p>
    <p v-if="description" class="mt-1 max-w-sm text-sm text-[var(--a-muted)]">{{ description }}</p>
    <div v-if="$slots.default" class="mt-5"><slot /></div>
  </div>
</template>

<script setup>
import AdminIcon from './AdminIcon.vue';

defineProps({
  title: { type: String, required: true },
  description: { type: String, default: '' },
  icon: { type: String, default: 'box' },
});
</script>
