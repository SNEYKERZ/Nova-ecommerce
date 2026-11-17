<template>
  <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
    <div class="min-w-0">
      <h1 class="a-title">{{ title }}</h1>
      <p v-if="description" class="mt-1 text-sm text-[var(--a-ink-2)]">{{ description }}</p>
    </div>
    <div v-if="$slots.default" class="flex flex-wrap items-center gap-2"><slot /></div>
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, required: true },
  description: { type: String, default: '' },
});
</script>
