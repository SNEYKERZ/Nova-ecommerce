<template>
  <div ref="root" class="relative" @keydown.esc.stop="close(true)">
    <button
      ref="trigger"
      type="button"
      class="sf-toggle"
      :class="{ 'is-set': isSet && !open }"
      :aria-expanded="open ? 'true' : 'false'"
      :aria-controls="panelId"
      @click="open ? close() : (open = true)"
    >
      {{ label }}<span v-if="value" class="font-normal opacity-80">: {{ value }}</span>
      <svg class="h-3.5 w-3.5 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
    </button>
    <Transition name="pop">
      <div v-if="open" :id="panelId" class="sf-pop" :class="align === 'right' ? 'right-0' : 'left-0'" role="group" :aria-label="label">
        <slot :close="close" />
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { nextTick, onBeforeUnmount, ref, watch } from 'vue';

defineProps({
  label: { type: String, required: true },
  value: { type: String, default: '' },
  isSet: { type: Boolean, default: false },
  align: { type: String, default: 'left' },
});

const panelId = `rail-menu-${Math.random().toString(36).slice(2, 9)}`;
const open = ref(false);
const root = ref(null);
const trigger = ref(null);

const close = (refocus = false) => {
  open.value = false;
  if (refocus) trigger.value?.focus();
};

const onPointer = (event) => {
  if (root.value && !root.value.contains(event.target)) close();
};

watch(open, async (value) => {
  if (value) {
    document.addEventListener('pointerdown', onPointer);
    await nextTick();
    root.value?.querySelector('.sf-pop button, .sf-pop input, .sf-pop select')?.focus();
  } else {
    document.removeEventListener('pointerdown', onPointer);
  }
});

onBeforeUnmount(() => document.removeEventListener('pointerdown', onPointer));
</script>

<style scoped>
.pop-enter-active, .pop-leave-active { transition: opacity 160ms ease, transform 220ms cubic-bezier(0.16, 1, 0.3, 1); }
.pop-enter-from, .pop-leave-to { opacity: 0; transform: translateY(-6px); }
</style>
