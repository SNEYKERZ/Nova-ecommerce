/** Navegación del panel de tienda. `href` = enlace a otra ruta; sin href = sección dentro de /admin */
export const storeNav = [
  { key: 'resumen', label: 'Resumen', icon: 'home' },
  { key: 'pedidos', label: 'Pedidos', icon: 'orders' },
  { key: 'productos', label: 'Productos', icon: 'tag' },
  { key: 'ofertas', label: 'Ofertas', icon: 'percent' },
  { key: 'cupones', label: 'Cupones', icon: 'ticket' },
  { key: 'insumos', label: 'Insumos', icon: 'box' },
  { key: 'contenido', label: 'Contenido del home', icon: 'image' },
  { key: 'galerias', label: 'Galerías', icon: 'gallery', href: '/admin/galleries' },
  { key: 'ajustes', label: 'Ajustes', icon: 'settings' },
];

export const superNav = [
  { key: 'resumen', label: 'Resumen', icon: 'home' },
  { key: 'tiendas', label: 'Tiendas', icon: 'store' },
  { key: 'usuarios', label: 'Usuarios', icon: 'users' },
  { key: 'pedidos', label: 'Pedidos recientes', icon: 'orders' },
];

export const ORDER_STATES = [
  { value: 'PENDIENTE', label: 'Pendiente', tone: 'attention' },
  { value: 'CONFIRMADO', label: 'Confirmado', tone: 'info' },
  { value: 'ENVIADO', label: 'Enviado', tone: 'magic' },
  { value: 'ENTREGADO', label: 'Entregado', tone: 'success' },
  { value: 'CANCELADO', label: 'Cancelado', tone: 'critical' },
];

export const orderState = (value) =>
  ORDER_STATES.find((s) => s.value === value) || { value, label: value || '—', tone: 'neutral' };

/** Regla de URL de destino aceptada por el backend (http(s), ruta relativa o #ancla) */
export const LINK_PATTERN = /^(https?:\/\/.+|\/[a-z0-9/_-]*|#[a-z0-9-]*)$/i;

export const ICONS = {
  home: 'M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-9.5Z',
  orders: 'M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4H6Zm-3 4h18M16 10a4 4 0 0 1-8 0',
  tag: 'M3 12V4a1 1 0 0 1 1-1h8l9 9-9 9-9-9Zm5-5h.01',
  percent: 'M19 5 5 19M7.5 9a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Zm9 9a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z',
  ticket: 'M3 8a2 2 0 0 1 0 4 2 2 0 0 1 0 4v2a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1v-2a2 2 0 0 1 0-4 2 2 0 0 1 0-4V6a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v2Zm11-3v14',
  box: 'm21 8-9-5-9 5 9 5 9-5Zm-18 0v8l9 5 9-5V8M12 13v8',
  image: 'M4 5h16a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Zm0 11 5-5 4 4 2-2 5 5M15 9h.01',
  gallery: 'M4 4h7v7H4V4Zm9 0h7v4h-7V4Zm0 6h7v10h-7V10Zm-9 3h7v7H4v-7Z',
  settings: 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm7.4-3a7.4 7.4 0 0 0-.1-1.2l2-1.6-2-3.4-2.4 1a7.3 7.3 0 0 0-2-1.2L14.5 3h-5l-.4 2.6a7.3 7.3 0 0 0-2 1.2l-2.4-1-2 3.4 2 1.6a7.4 7.4 0 0 0 0 2.4l-2 1.6 2 3.4 2.4-1a7.3 7.3 0 0 0 2 1.2l.4 2.6h5l.4-2.6a7.3 7.3 0 0 0 2-1.2l2.4 1 2-3.4-2-1.6c.1-.4.1-.8.1-1.2Z',
  store: 'M4 9h16l-1-5H5L4 9Zm0 0v11h16V9M4 9a2.7 2.7 0 0 0 5.3 0 2.7 2.7 0 0 0 5.4 0A2.7 2.7 0 0 0 20 9M10 20v-5h4v5',
  users: 'M16 19v-1a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v1m7-9a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm13 9v-1a4 4 0 0 0-3-3.9M16 2.1a4 4 0 0 1 0 7.8',
  external: 'M14 4h6v6m0-6L10 14M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5',
  search: 'm21 21-4.3-4.3M10.5 18a7.5 7.5 0 1 0 0-15 7.5 7.5 0 0 0 0 15Z',
  plus: 'M12 5v14M5 12h14',
  trash: 'M4 7h16M10 11v6M14 11v6M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2l1-12M9 7V4h6v3',
  edit: 'M4 20h4L19 9l-4-4L4 16v4Zm10-14 4 4',
  menu: 'M4 7h16M4 12h16M4 17h16',
  logout: 'M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M10 17l5-5-5-5M15 12H3',
  alert: 'M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z',
  upload: 'M12 16V4m0 0-4 4m4-4 4 4M4 16v3a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-3',
};
