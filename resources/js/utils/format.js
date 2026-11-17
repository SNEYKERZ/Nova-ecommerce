const copFormatter = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 });

/** Formato de precio COP sin decimales: $ 59.900 */
export const money = (value) => copFormatter.format(Number(value) || 0);

/** Fecha corta en español de Colombia: 12 nov 2026 */
export const formatDate = (value, withTime = false) => {
  if (!value) return '—';
  const date = value instanceof Date ? value : new Date(value);
  if (Number.isNaN(date.getTime())) return String(value);
  return date.toLocaleDateString('es-CO', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    ...(withTime ? { hour: '2-digit', minute: '2-digit' } : {}),
  });
};

/** Date → valor para <input type="datetime-local"> en hora local */
export const toDatetimeLocal = (value) => {
  if (!value) return '';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '';
  return new Date(date.getTime() - date.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
};

export const toDateInput = (value) => (value ? toDatetimeLocal(value).slice(0, 10) : '');

/** URL de imagen guardada en storage o absoluta */
export const mediaUrl = (path, fallback = '/images/sinfoto.jpg') => {
  if (!path) return fallback;
  const value = String(path);
  if (value.startsWith('http') || value.startsWith('/')) return value;
  return `/storage/${value}`;
};

export const debounce = (fn, wait = 300) => {
  let timer = null;
  return (...args) => {
    clearTimeout(timer);
    timer = setTimeout(() => fn(...args), wait);
  };
};
