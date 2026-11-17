<template>
  <div>
    <label v-if="label" :for="id" class="a-label">{{ label }}<span v-if="required" class="text-[var(--a-critical)]" aria-hidden="true"> *</span><span v-if="optional" class="a-opt"> (opcional)</span></label>
    <slot :id="id" :invalid="error ? 'true' : 'false'" :describedby="`${id}-msg`" />
    <p v-if="error" :id="`${id}-msg`" class="a-error">{{ error }}</p>
    <p v-else-if="help" :id="`${id}-msg`" class="a-help">{{ help }}</p>
  </div>
</template>

<script setup>
defineProps({
  label: { type: String, default: '' },
  error: { type: String, default: '' },
  help: { type: String, default: '' },
  optional: { type: Boolean, default: false },
  required: { type: Boolean, default: false },
  id: { type: String, default: () => `f-${Math.random().toString(36).slice(2, 8)}` },
});
</script>
