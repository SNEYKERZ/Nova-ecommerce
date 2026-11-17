/**
 * Utilidades para cálculo de colores y contraste
 * Nota: La mayoría del cálculo de contraste ocurre en servidor (StylingService)
 * Estas funciones son útiles para previsualizaciones en tiempo real
 */

/**
 * Valida que un color sea un hex válido
 */
export function isValidHexColor(color) {
  return /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(color);
}

/**
 * Normaliza un color hex a formato #RRGGBB
 */
export function normalizeHex(color) {
  if (!isValidHexColor(color)) {
    return '#ffffff';
  }

  if (color.length === 4) {
    return '#' + color[1] + color[1] + color[2] + color[2] + color[3] + color[3];
  }

  return color.substring(0, 7);
}

/**
 * Calcula la luminancia relativa de un color (WCAG 2.0)
 */
export function getLuminance(hexColor) {
  let hex = hexColor.replace('#', '');

  if (hex.length === 3) {
    hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
  }

  const r = parseInt(hex.substring(0, 2), 16) / 255;
  const g = parseInt(hex.substring(2, 4), 16) / 255;
  const b = parseInt(hex.substring(4, 6), 16) / 255;

  const rLinear = r <= 0.03928 ? r / 12.92 : Math.pow((r + 0.055) / 1.055, 2.4);
  const gLinear = g <= 0.03928 ? g / 12.92 : Math.pow((g + 0.055) / 1.055, 2.4);
  const bLinear = b <= 0.03928 ? b / 12.92 : Math.pow((b + 0.055) / 1.055, 2.4);

  return 0.2126 * rLinear + 0.7152 * gLinear + 0.0722 * bLinear;
}

/**
 * Determina si se debe usar texto claro o oscuro sobre un color
 */
export function getTextColor(backgroundColor, threshold = 0.5) {
  const normalized = normalizeHex(backgroundColor);

  if (!isValidHexColor(normalized)) {
    return 'dark';
  }

  const luminance = getLuminance(normalized);
  return luminance > threshold ? 'dark' : 'light';
}

/**
 * Retorna el color hex de texto recomendado
 */
export function getTextColorHex(backgroundColor) {
  return getTextColor(backgroundColor) === 'light' ? '#ffffff' : '#111111';
}

/**
 * Calcula el ratio de contraste WCAG entre dos colores
 */
export function getContrastRatio(color1, color2) {
  const lum1 = getLuminance(normalizeHex(color1));
  const lum2 = getLuminance(normalizeHex(color2));

  const lighter = Math.max(lum1, lum2);
  const darker = Math.min(lum1, lum2);

  return (lighter + 0.05) / (darker + 0.05);
}

/**
 * Verifica si el contraste cumple con WCAG AA (4.5:1 para texto normal)
 */
export function meetsWCAGAA(foreground, background) {
  return getContrastRatio(foreground, background) >= 4.5;
}

/**
 * Verifica si el contraste cumple con WCAG AAA (7:1 para texto normal)
 */
export function meetsWCAGAAA(foreground, background) {
  return getContrastRatio(foreground, background) >= 7;
}

export default {
  isValidHexColor,
  normalizeHex,
  getLuminance,
  getTextColor,
  getTextColorHex,
  getContrastRatio,
  meetsWCAGAA,
  meetsWCAGAAA,
};

/* ── Storefront: tinta y "lápiz" (color de acento) ─────────────────────── */

const hexToRgb = (hex) => {
  const h = normalizeHex(hex).slice(1);
  return [0, 2, 4].map((i) => parseInt(h.slice(i, i + 2), 16));
};
const rgbToHex = (rgb) => `#${rgb.map((v) => Math.round(Math.max(0, Math.min(255, v))).toString(16).padStart(2, '0')).join('')}`;
const mixHex = (a, b, t) => {
  const ca = hexToRgb(a);
  const cb = hexToRgb(b);
  return rgbToHex(ca.map((v, i) => v + (cb[i] - v) * t));
};

/** Saturación y luminosidad HSL (0–1) */
export function getSaturationLightness(hex) {
  const [r, g, b] = hexToRgb(hex).map((v) => v / 255);
  const max = Math.max(r, g, b);
  const min = Math.min(r, g, b);
  const l = (max + min) / 2;
  const s = max === min ? 0 : (max - min) / (1 - Math.abs(2 * l - 1));
  return { s, l };
}

/** Tinta legible sobre un fondo: la que más contraste da entre casi negro y blanco. */
export function bestInk(background, dark = '#141414', light = '#ffffff') {
  if (!isValidHexColor(background || '')) return dark;
  return getContrastRatio(dark, background) >= getContrastRatio(light, background) ? dark : light;
}

/**
 * Color de "lápiz" de la tienda: el primer color configurado con carácter
 * (saturado, ni casi blanco ni casi negro). Se ajusta hacia la tinta hasta
 * contrastar ≥ 4.5:1 con el fondo, porque también colorea precios y texto.
 */
export function pickPencil(background, candidates = [], fallback = '#c8361b') {
  const usable = candidates
    .filter((c) => isValidHexColor(c || ''))
    .map(normalizeHex)
    .find((c) => {
      const { s, l } = getSaturationLightness(c);
      return s >= 0.35 && l >= 0.18 && l <= 0.72;
    });
  let pencil = usable || fallback;
  const bg = isValidHexColor(background || '') ? normalizeHex(background) : '#ffffff';
  const target = bestInk(bg);
  for (let i = 0; i < 12 && getContrastRatio(pencil, bg) < 4.5; i++) pencil = mixHex(pencil, target, 0.12);
  return pencil;
}
