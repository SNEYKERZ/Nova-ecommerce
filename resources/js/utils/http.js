/**
 * Cliente HTTP mínimo para los endpoints JSON del proyecto (carrito, pedidos, panel).
 * - Envía CSRF y cabeceras JSON.
 * - Normaliza errores: 422 (validación por campo), 419 (sesión), 403, 404, 429, 5xx.
 * - Lanza ApiError con mensaje amigable en español y `errors` por campo.
 */
export class ApiError extends Error {
  constructor(message, { status = 0, errors = {}, payload = null } = {}) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.errors = errors;
    this.payload = payload;
  }
}

export const csrfToken = () =>
  document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

/** Cookie XSRF-TOKEN que Laravel renueva en cada respuesta (sigue válida tras login/logout en la SPA). */
const xsrfCookie = () => {
  const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
  return match ? decodeURIComponent(match[1]) : '';
};

const FRIENDLY = {
  401: 'Tu sesión terminó. Vuelve a iniciar sesión.',
  403: 'No tienes permiso para realizar esta acción.',
  404: 'No encontramos lo que buscabas. Recarga la página e inténtalo de nuevo.',
  413: 'El archivo es demasiado grande.',
  419: 'La sesión expiró. Recarga la página e inténtalo de nuevo.',
  429: 'Demasiados intentos seguidos. Espera un momento e inténtalo de nuevo.',
  500: 'Algo falló en el servidor. Inténtalo de nuevo en unos segundos.',
};

/** Aplana errores de Laravel { campo: [msg] } → { campo: msg } */
export const flattenErrors = (errors) => {
  const out = {};
  Object.entries(errors || {}).forEach(([key, value]) => {
    out[key] = Array.isArray(value) ? value[0] : String(value);
  });
  return out;
};

/**
 * @param {string} url
 * @param {{ method?: string, body?: object|FormData|null, spoof?: boolean }} options
 *   spoof: con FormData y método PUT/PATCH/DELETE envía POST + _method (Laravel no lee multipart en PUT)
 */
export async function api(url, { method = 'GET', body = null, spoof = true } = {}) {
  const headers = {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  };
  const xsrf = xsrfCookie();
  if (xsrf) headers['X-XSRF-TOKEN'] = xsrf;
  else headers['X-CSRF-TOKEN'] = csrfToken();
  let requestBody = null;
  let httpMethod = method.toUpperCase();

  if (body instanceof FormData) {
    if (spoof && httpMethod !== 'POST' && httpMethod !== 'GET') {
      body.append('_method', httpMethod);
      httpMethod = 'POST';
    }
    requestBody = body;
  } else if (body !== null && httpMethod !== 'GET') {
    headers['Content-Type'] = 'application/json';
    requestBody = JSON.stringify(body);
  }

  let res;
  try {
    res = await fetch(url, { method: httpMethod, headers, body: requestBody, credentials: 'same-origin' });
  } catch {
    throw new ApiError('Sin conexión. Revisa tu internet e inténtalo de nuevo.', { status: 0 });
  }

  let payload = null;
  const text = await res.text();
  if (text) {
    try {
      payload = JSON.parse(text);
    } catch {
      payload = null;
    }
  }

  if (!res.ok || (payload && payload.success === false)) {
    const errors = flattenErrors(payload?.errors);
    let message = payload?.message;
    if (res.status === 422 && Object.keys(errors).length) {
      message = Object.keys(errors).length === 1 ? Object.values(errors)[0] : 'Revisa los campos marcados.';
    }
    if (!message || res.status === 500 || res.status === 419 || res.status === 429 || /^(Server Error|Too Many Attempts\.?|CSRF token mismatch\.?|Unauthenticated\.?|This action is unauthorized\.?)$/i.test(message)) {
      message = FRIENDLY[res.status] || (res.status >= 500 ? FRIENDLY[500] : message) || 'No se pudo completar la acción.';
    }
    throw new ApiError(message, { status: res.status, errors, payload });
  }

  if (payload === null && text) {
    // Respuesta HTML inesperada (p. ej. redirección a login)
    throw new ApiError(FRIENDLY[419], { status: res.status });
  }

  return payload ?? {};
}
