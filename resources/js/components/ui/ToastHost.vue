<template>
  <div class="pointer-events-none fixed inset-x-0 bottom-0 z-[90] flex flex-col items-center gap-2 p-4 sm:items-end sm:p-6" aria-live="polite" aria-atomic="false">
    <TransitionGroup name="toast">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl px-4 py-3 text-sm shadow-lg"
        :class="toast.type === 'error' ? 'bg-[#8e0b21] text-white' : 'bg-[#1a1a1a] text-white'"
        :role="toast.type === 'error' ? 'alert' : 'status'"
      >
        <svg v-if="toast.type === 'error'" class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM9 6a1 1 0 1 1 2 0v4a1 1 0 1 1-2 0V6Zm1 9a1.25 1.25 0 1 0 0-2.5A1.25 1.25 0 0 0 10 15Z" clip-rule="evenodd"/></svg>
        <svg v-else class="mt-0.5 h-4 w-4 shrink-0 text-[#7ee2a8]" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4L8 12.6l7.3-7.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
        <p class="flex-1 font-medium leading-snug">{{ toast.message }}</p>
        <a v-if="toast.action" :href="toast.action.href" class="shrink-0 font-semibold underline underline-offset-2">{{ toast.action.label }}</a>
        <button type="button" class="-m-1 shrink-0 rounded p-1 opacity-70 hover:opacity-100" aria-label="Cerrar aviso" @click="dismiss(toast.id)">
          <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.3 5.3a1 1 0 0 0-1.4 1.4L8.6 10l-3.7 3.3a1 1 0 1 0 1.4 1.4L10 11.4l3.3 3.3a1 1 0 0 0 1.4-1.4L11.4 10l3.3-3.3a1 1 0 0 0-1.4-1.4L10 8.6 6.3 5.3Z"/></svg>
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup>
import { useToast } from '../../composables/useToast';

const { toasts, dismiss } = useToast();
</script>
