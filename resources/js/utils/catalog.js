export const sortOptions = [
  { value: 'recentes', label: 'Más recientes' },
  { value: 'precio_menor', label: 'Precio: menor a mayor' },
  { value: 'precio_mayor', label: 'Precio: mayor a menor' },
  { value: 'alfabetico_asc', label: 'Nombre: A–Z' },
  { value: 'alfabetico_desc', label: 'Nombre: Z–A' },
];

/** Precio efectivo (con oferta si existe) */
export const effectivePrice = (product) => Number(product.tieneOferta ? product.precioOferta : product.precio) || 0;
