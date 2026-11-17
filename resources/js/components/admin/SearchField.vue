<template>
  <div class="relative min-w-0 flex-1">
    <span class="pointer-events-none absolute inset-y-0 left-2.5 flex items-center text-[var(--a-muted)]"><AdminIcon name="search" size="sm" /></span>
    <label :for="id" class="visually-hidden">{{ label }}</label>
    <input :id="id" :value="modelValue" type="search" class="a-input !pl-8" :placeholder="placeholder" autocomplete="off" @input="$emit('update:modelValue', $event.target.value)" />
  </div>
</template>

<script setup>
import AdminIcon from './AdminIcon.vue';

defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: 'Buscar' },
  label: { type: String, default: 'Buscar' },
  id: { type: String, default: () => `search-${Math.random().toString(36).slice(2, 7)}` },
});
defineEmits(['update:modelValue']);
</script>
