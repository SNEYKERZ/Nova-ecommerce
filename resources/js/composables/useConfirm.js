import { reactive } from 'vue';

/** Diálogo de confirmación basado en promesas (reemplaza window.confirm). */
const state = reactive({
  open: false,
  title: '',
  message: '',
  confirmLabel: 'Confirmar',
  cancelLabel: 'Cancelar',
  tone: 'critical',
  resolve: null,
});

export function useConfirm() {
  const confirm = ({ title, message = '', confirmLabel = 'Confirmar', cancelLabel = 'Cancelar', tone = 'critical' }) =>
    new Promise((resolve) => {
      Object.assign(state, { open: true, title, message, confirmLabel, cancelLabel, tone, resolve });
    });

  const close = (result) => {
    state.resolve?.(result);
    Object.assign(state, { open: false, resolve: null });
  };

  return { state, confirm, close };
}
