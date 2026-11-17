<template>
  <Transition name="fade">
    <div v-if="state.open" class="fixed inset-0 z-[95] flex items-end justify-center bg-black/50 p-4 sm:items-center" @click.self="close(false)">
      <div
        ref="panel"
        class="w-full max-w-md rounded-2xl bg-white p-5 shadow-2xl sm:p-6"
        role="alertdialog"
        aria-modal="true"
        :aria-labelledby="titleId"
        :aria-describedby="state.message ? descId : undefined"
        @keydown.esc="close(false)"
      >
        <h2 :id="titleId" class="text-base font-bold text-[#1a1a1a]">{{ state.title }}</h2>
        <p v-if="state.message" :id="descId" class="mt-2 text-sm leading-relaxed text-[#4a4a4a]">{{ state.message }}</p>
        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
          <button type="button" class="a-btn a-btn-secondary" @click="close(false)">{{ state.cancelLabel }}</button>
          <button
            ref="confirmBtn"
            type="button"
            class="a-btn"
            :class="state.tone === 'critical' ? 'a-btn-critical' : 'a-btn-primary'"
            @click="close(true)"
          >{{ state.confirmLabel }}</button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { nextTick, ref, watch } from 'vue';
import { useConfirm } from '../../composables/useConfirm';

const { state, close } = useConfirm();
const confirmBtn = ref(null);
const titleId = 'confirm-title';
const descId = 'confirm-desc';
let lastFocus = null;

watch(() => state.open, async (open) => {
  if (open) {
    lastFocus = document.activeElement;
    await nextTick();
    confirmBtn.value?.focus();
  } else if (lastFocus?.focus) {
    lastFocus.focus();
  }
});
</script>
