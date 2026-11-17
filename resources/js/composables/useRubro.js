import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * Preset por tipo de negocio (columna `stores.rubro`).
 * Cambia vocabulario y algunas decisiones de la interfaz; la estética base
 * (tipografía, densidad, proporción de los cuadros) vive en app.css bajo
 * [data-rubro="…"].
 */
const PRESETS = {
  general: {
    catalogo: 'Catálogo',
    galeria: 'Galería',
    item: ['producto', 'productos'],
    variante: 'Opción',
    elegirVariante: 'Elige una opción',
    buscar: 'Buscar en la tienda',
    mostrarSku: false,
    mostrarDescripcion: false,
    nuevo: 'Nuevo',
    nuevaColeccion: 'Sólo novedades',
    about: 'Haz tu pedido en la web o por WhatsApp y coordinamos contigo el pago y el envío.',
  },
  moda: {
    catalogo: 'Catálogo',
    galeria: 'Lookbook',
    item: ['producto', 'productos'],
    variante: 'Talla',
    elegirVariante: 'Elige tu talla',
    buscar: 'Buscar prendas y accesorios',
    mostrarSku: false,
    mostrarDescripcion: false,
    nuevo: 'Nuevo',
    nuevaColeccion: 'Sólo nueva colección',
    about: 'Haz tu pedido en la web o por WhatsApp y coordinamos contigo el pago y el envío.',
  },
  restaurante: {
    catalogo: 'Menú',
    galeria: 'Galería',
    item: ['plato', 'platos'],
    variante: 'Tamaño',
    elegirVariante: 'Elige el tamaño',
    buscar: 'Buscar en el menú',
    mostrarSku: false,
    mostrarDescripcion: true,
    nuevo: 'Nuevo',
    nuevaColeccion: 'Sólo platos nuevos',
    about: 'Haz tu pedido en la web o por WhatsApp y coordinamos contigo el pago y la entrega.',
  },
  autopartes: {
    catalogo: 'Repuestos',
    galeria: 'Galería',
    item: ['repuesto', 'repuestos'],
    variante: 'Variante',
    elegirVariante: 'Elige la variante',
    buscar: 'Buscar por nombre o referencia',
    mostrarSku: true,
    mostrarDescripcion: false,
    nuevo: 'Nuevo',
    nuevaColeccion: 'Sólo recién llegados',
    about: 'Cotiza y pide en la web o por WhatsApp; confirmamos referencia, pago y envío contigo.',
  },
};

export function rubroPreset(rubro) {
  return PRESETS[rubro] || PRESETS.general;
}

export function useRubro() {
  const page = usePage();
  const key = computed(() => (PRESETS[page.props.app?.rubro] ? page.props.app.rubro : 'general'));
  const preset = computed(() => PRESETS[key.value]);
  const plural = (n) => `${n} ${n === 1 ? preset.value.item[0] : preset.value.item[1]}`;
  return { key, preset, plural };
}
