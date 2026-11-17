<template>
  <svg
    class="pencil-mark"
    :class="[`pencil-mark--${shape}`, { 'is-on': active }]"
    :viewBox="shape === 'tick' ? '0 0 40 32' : '0 0 120 48'"
    preserveAspectRatio="none"
    aria-hidden="true"
    focusable="false"
  >
    <path v-if="shape === 'ring'" pathLength="1" d="M14 31C6 23 15 9 47 6c31-3 63 2 66 15 3 14-29 22-60 22C25 43 6 37 7 26 8 17 22 11 36 10" />
    <path v-else-if="shape === 'underline'" pathLength="1" d="M3 40c26-5 61-7 114-3" />
    <path v-else pathLength="1" d="M5 17l9 10L36 4" />
  </svg>
</template>

<script setup>
/**
 * Marca de lápiz graso: se dibuja (stroke-dashoffset) cuando `active` pasa a true.
 * Va dentro de un contenedor con position: relative.
 */
defineProps({
  active: { type: Boolean, default: false },
  shape: { type: String, default: 'ring' }, // ring | underline | tick
});
</script>
