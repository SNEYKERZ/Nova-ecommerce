<template>
  <span class="a-badge" :class="`tone-${state.tone}`">{{ state.label }}</span>
</template>

<script setup>
import { computed } from 'vue';
import { orderState } from './adminNav';

const props = defineProps({ estado: { type: String, default: '' } });
const state = computed(() => orderState(props.estado));
</script>
