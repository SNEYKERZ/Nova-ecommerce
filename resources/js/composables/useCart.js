import { reactive } from 'vue';
import { api } from '../utils/http';
import { useToast } from './useToast';

/**
 * Estado compartido del carrito para el header, las tarjetas y el aviso "agregado".
 * La fuente de verdad es /api/carrito; aquí sólo se guarda el contador y el último agregado.
 */
const state = reactive({
  count: 0,
  bump: 0,
  lastAdded: null, // { nombre, foto, talla, cantidad, precio }
  pending: {},     // productoId-talla → true mientras se agrega
});

let loaded = false;

export function useCart() {
  const toast = useToast();

  const sync = async () => {
    try {
      const payload = await api('/api/carrito');
      state.count = payload?.data?.cantidad_total ?? 0;
      loaded = true;
    } catch {
      /* el contador no debe romper la página */
    }
  };

  const ensureLoaded = () => {
    if (!loaded) sync();
  };

  /**
   * @param {{id:number,nombre?:string,sku?:string,foto?:string,precio?:number,precioOferta?:number}} product
   */
  const add = async (product, { talla = null, cantidad = 1 } = {}) => {
    const key = `${product.id}-${talla ?? ''}`;
    if (state.pending[key]) return false;
    state.pending[key] = true;
    try {
      await api('/api/carrito/agregar', {
        method: 'POST',
        body: { producto_id: product.id, cantidad, talla },
      });
      state.count += cantidad;
      state.bump += 1;
      state.lastAdded = {
        id: product.id,
        nombre: product.nombre || product.sku || product.referencia || 'Producto',
        foto: product.foto || null,
        talla,
        cantidad,
        precio: product.precioOferta ?? product.precio ?? null,
        at: Date.now(),
      };
      window.dispatchEvent(new Event('cart-updated'));
      return true;
    } catch (error) {
      toast.error(error.message || 'No fue posible agregar el producto.');
      return false;
    } finally {
      delete state.pending[key];
    }
  };

  const isPending = (productId, talla = null) => Boolean(state.pending[`${productId}-${talla ?? ''}`]);
  const dismissAdded = () => { state.lastAdded = null; };

  return { state, sync, ensureLoaded, add, isPending, dismissAdded };
}
