<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="$emit('close')">
    <div class="relative max-h-[90vh] max-w-4xl w-full overflow-y-auto rounded-lg bg-white shadow-xl">
      <!-- Close Button -->
      <button
        class="absolute right-4 top-4 z-10 rounded-full bg-white p-2 text-slate-500 hover:text-slate-700"
        @click="$emit('close')"
      >
        ✕
      </button>

      <!-- Content -->
      <div class="grid grid-cols-1 gap-6 p-6 md:grid-cols-2">
        <!-- Image -->
        <div class="flex items-center justify-center bg-slate-100 rounded-lg overflow-hidden">
          <img :src="imagen.imagen_url" :alt="gallery.nombre" class="h-full w-full object-cover" loading="lazy" />
        </div>

        <!-- Products & Cart -->
        <div class="flex flex-col">
          <h3 class="mb-4 text-2xl font-bold text-slate-900">{{ gallery.nombre }}</h3>

          <!-- Products List -->
          <div v-if="imagen.productos?.length > 0" class="mb-6 flex-1 space-y-4">
            <div v-for="producto in imagen.productos" :key="producto.id" class="rounded-lg border border-slate-200 p-4">
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <h4 class="font-semibold text-slate-900">{{ producto.nombre }}</h4>
                  <p v-if="producto.referencia" class="text-xs text-slate-500">{{ producto.referencia }}</p>
                  <p class="mt-2 text-lg font-bold text-slate-900">${{ formatPrice(producto.precio) }}</p>
                </div>
              </div>

              <!-- Size Selector (if applicable) -->
              <div v-if="producto.tallas?.length > 0" class="mt-3 mb-3">
                <label class="mb-2 block text-xs font-semibold uppercase text-slate-500">Talla</label>
                <div class="flex flex-wrap gap-2">
                  <button
                    v-for="talla in producto.tallas"
                    :key="talla"
                    class="min-w-[2.8rem] h-[2.8rem] flex items-center justify-center border border-slate-300 rounded-lg text-xs font-bold uppercase cursor-pointer bg-white text-slate-900 transition-colors"
                    :class="{ 'bg-slate-900 text-white border-slate-900': selectedSize[producto.id] === talla }"
                    @click="selectedSize[producto.id] = talla"
                  >
                    {{ talla }}
                  </button>
                </div>
              </div>

              <!-- Color Selector (if applicable) -->
              <div v-if="producto.colores?.length > 0" class="mb-3">
                <label class="mb-2 block text-xs font-semibold uppercase text-slate-500">Color</label>
                <div class="flex flex-wrap gap-2">
                  <button
                    v-for="color in producto.colores"
                    :key="color"
                    class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-semibold transition-colors"
                    :class="selectedColor[producto.id] === color ? 'border-slate-900 bg-slate-900 text-white' : 'bg-white text-slate-700 hover:border-slate-900'"
                    @click="selectedColor[producto.id] = color"
                  >
                    {{ color }}
                  </button>
                </div>
              </div>

              <!-- Quantity & Add to Cart -->
              <div class="mt-4 flex items-center gap-2">
                <div class="flex items-center border border-slate-300 rounded-lg">
                  <button class="flex h-8 w-8 items-center justify-center border-r border-slate-300 bg-white cursor-pointer text-slate-900 font-semibold transition-colors hover:bg-slate-100" @click="decreaseQty(producto.id)">−</button>
                  <div class="w-8 text-center text-sm font-semibold text-slate-900">{{ quantities[producto.id] || 1 }}</div>
                  <button class="flex h-8 w-8 items-center justify-center border-l border-slate-300 bg-white cursor-pointer text-slate-900 font-semibold transition-colors hover:bg-slate-100" @click="increaseQty(producto.id)">+</button>
                </div>
                <button
                  class="btn-main flex-1"
                  @click="addToCart(producto)"
                >
                  Agregar al Carrito
                </button>
              </div>
            </div>
          </div>

          <!-- No Products -->
          <div v-else class="rounded-lg border border-slate-200 bg-slate-50 p-6 text-center text-slate-600">
            No hay productos asociados a esta imagen.
          </div>

          <!-- Feedback -->
          <p v-if="feedback" class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-800">
            {{ feedback }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
  gallery: { type: Object, required: true },
  imagen: { type: Object, required: true },
});

const emit = defineEmits(['close']);

const quantities = ref({});
const selectedSize = ref({});
const selectedColor = ref({});
const feedback = ref('');

const formatPrice = (price) => {
  return parseFloat(price).toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const increaseQty = (productoId) => {
  quantities.value[productoId] = (quantities.value[productoId] || 1) + 1;
};

const decreaseQty = (productoId) => {
  if ((quantities.value[productoId] || 1) > 1) {
    quantities.value[productoId]--;
  }
};

const addToCart = async (producto) => {
  try {
    const productId = producto.producto_id || producto.id;
    const qty = quantities.value[productId] || 1;
    const res = await fetch('/api/carrito/agregar', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({
        producto_id: productId,
        cantidad: qty,
        talla: selectedSize.value[productId] || null,
        color: selectedColor.value[productId] || null,
      }),
    });

    if (!res.ok) throw new Error('Error al agregar al carrito');

    quantities.value[productId] = 1;
    feedback.value = `${producto.nombre} agregado al carrito`;
    window.dispatchEvent(new Event('cart-updated'));
    setTimeout(() => { feedback.value = ''; }, 2000);
  } catch (e) {
    feedback.value = e.message;
  }
};
</script>
