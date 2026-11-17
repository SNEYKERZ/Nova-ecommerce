<template>
  <svg :class="sizeClass" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
    <path :d="ICONS[name] || ''" />
  </svg>
</template>

<script setup>
import { computed } from 'vue';
import { ICONS } from './adminNav';

const props = defineProps({
  name: { type: String, required: true },
  size: { type: String, default: 'md' },
});
const sizeClass = computed(() => (props.size === 'sm' ? 'h-4 w-4 shrink-0' : 'h-5 w-5 shrink-0'));
</script>
