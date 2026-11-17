import { reactive } from 'vue';

/** Store global de toasts (tienda y panel). */
const state = reactive({ items: [] });
let seq = 0;

export function useToast() {
  const dismiss = (id) => {
    const index = state.items.findIndex((t) => t.id === id);
    if (index !== -1) state.items.splice(index, 1);
  };

  const push = (message, { type = 'success', duration = 3500, action = null } = {}) => {
    const id = ++seq;
    state.items.push({ id, message, type, action });
    if (state.items.length > 4) state.items.shift();
    if (duration) setTimeout(() => dismiss(id), duration);
    return id;
  };

  return {
    toasts: state.items,
    dismiss,
    success: (message, options = {}) => push(message, { ...options, type: 'success' }),
    error: (message, options = {}) => push(message, { duration: 6000, ...options, type: 'error' }),
    info: (message, options = {}) => push(message, { ...options, type: 'info' }),
  };
}
