<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="$emit('close')">
    <!-- Main Modal -->
    <div class="relative max-h-[90vh] max-w-4xl w-full overflow-y-auto rounded-lg bg-white shadow-xl">
      <!-- Close Button -->
      <button
        class="absolute right-4 top-4 z-10 rounded-full bg-white p-2 text-slate-500 hover:text-slate-700 transition-colors"
        @click="$emit('close')"
      >
        ✕
      </button>

      <!-- Content -->
      <div class="grid grid-cols-1 gap-8 p-6 md:grid-cols-2">
        <!-- Image - Large -->
        <div class="flex items-center justify-center bg-slate-100 rounded-lg overflow-hidden h-96 md:h-auto">
          <img
            :src="imagen.imagen_url"
            :alt="gallery.nombre"
            class="h-full w-full object-cover"
            loading="lazy"
            decoding="async"
          />
        </div>

        <!-- Products Section - Clean & Simple -->
        <div class="flex flex-col justify-center">
          <h3 class="mb-6 text-3xl font-bold text-slate-900">{{ gallery.nombre }}</h3>

          <!-- Products List -->
          <div v-if="imagen.productos?.length > 0" class="space-y-6">
            <div
              v-for="producto in imagen.productos"
              :key="producto.id"
              class="border-t border-slate-200 pt-6 first:border-t-0 first:pt-0 flex gap-4"
            >
              <!-- Producto Thumbnail -->
              <div class="flex-shrink-0 w-24 h-24 bg-slate-100 rounded-lg overflow-hidden">
                <img
                  v-if="producto.foto"
                  :src="producto.foto"
                  :alt="producto.nombre"
                  class="w-full h-full object-cover"
                  loading="lazy"
                  decoding="async"
                />
                <div v-else class="w-full h-full flex items-center justify-center text-slate-400 text-sm">
                  Sin foto
                </div>
              </div>

              <!-- Producto Info & Button -->
              <div class="flex-1 flex flex-col justify-between">
                <div>
                  <h4 class="text-lg font-semibold text-slate-900">{{ producto.nombre }}</h4>
                  <p v-if="producto.referencia" class="text-sm text-slate-500 mt-1">Ref: {{ producto.referencia }}</p>
                  <p class="text-2xl font-bold text-slate-900 mt-3">${{ formatPrice(producto.precio) }}</p>
                </div>

                <!-- Simple Add Button -->
                <button
                  class="w-full bg-slate-900 text-white py-3 rounded-lg font-semibold transition-all hover:bg-slate-800 active:scale-95 mt-4"
                  @click="initiateAddToCart(producto)"
                >
                  Añadir
                </button>
              </div>
            </div>
          </div>

          <!-- No Products -->
          <div v-else class="rounded-lg border border-slate-200 bg-slate-50 p-6 text-center text-slate-600">
            No hay productos asociados a esta imagen.
          </div>

          <!-- Feedback Message -->
          <div
            v-if="feedback.message"
            :class="{
              'mt-4 p-3 rounded-lg text-sm font-semibold': true,
              'border border-emerald-200 bg-emerald-50 text-emerald-800': feedback.type === 'success',
              'border border-red-200 bg-red-50 text-red-800': feedback.type === 'error',
              'border border-blue-200 bg-blue-50 text-blue-800': feedback.type === 'warning',
            }"
          >
            {{ feedback.message }}
          </div>
        </div>
      </div>
    </div>

    <!-- Size Selection Modal (Overlay) -->
    <div
      v-if="showSizeModal && currentProductForSize"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
      @click.self="closeSizeModal"
    >
      <div class="relative w-full max-w-sm rounded-lg bg-white shadow-2xl p-6 animate-scale-in">
        <!-- Close Button -->
        <button
          class="absolute right-3 top-3 text-slate-500 hover:text-slate-700 transition-colors"
          @click="closeSizeModal"
        >
          ✕
        </button>

        <!-- Modal Content -->
        <h3 class="text-xl font-bold text-slate-900 mb-6">Selecciona la talla</h3>

        <!-- Size Grid -->
        <div class="grid grid-cols-3 gap-3 mb-6">
          <button
            v-for="talla in currentProductForSize.tallas"
            :key="talla"
            class="py-3 px-2 border border-slate-300 rounded-lg font-semibold text-sm transition-all hover:border-slate-900"
            :class="{
              'bg-slate-900 text-white border-slate-900': selectedSizeForModal === talla,
              'bg-white text-slate-900': selectedSizeForModal !== talla,
            }"
            @click="selectedSizeForModal = talla"
          >
            {{ talla }}
          </button>
        </div>

        <!-- Error Message if no size selected -->
        <div
          v-if="sizeModalAttempted && !selectedSizeForModal"
          class="mb-4 p-3 rounded-lg border border-red-200 bg-red-50 text-sm font-semibold text-red-800"
        >
          ⚠️ Por favor selecciona una talla
        </div>

        <!-- Confirm Button -->
        <button
          class="w-full bg-slate-900 text-white py-3 rounded-lg font-semibold transition-all hover:bg-slate-800 active:scale-95"
          :class="{ 'opacity-50 cursor-not-allowed': !selectedSizeForModal }"
          :disabled="!selectedSizeForModal"
          @click="confirmAndAddToCart"
        >
          Confirmar y Agregar
        </button>
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

// Estados del modal de talla
const showSizeModal = ref(false);
const selectedSizeForModal = ref('');
const currentProductForSize = ref(null);
const sizeModalAttempted = ref(false);

// Estados de feedback
const feedback = ref({ message: '', type: '' });

/**
 * Formatea el precio con separador de miles
 */
const formatPrice = (price) => {
  return parseFloat(price).toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

/**
 * Inicia el flujo de agregar al carrito
 * Si el producto tiene tallas, muestra el modal de selección
 * Si no tiene tallas, agrega directo al carrito
 */
const initiateAddToCart = (producto) => {
  feedback.value = { message: '', type: '' };

  // Si no tiene tallas, agregar directo
  if (!producto.tallas || producto.tallas.length === 0) {
    addToCart(producto, null);
    return;
  }

  // Si tiene tallas, mostrar modal de selección
  currentProductForSize.value = producto;
  showSizeModal.value = true;
  selectedSizeForModal.value = '';
  sizeModalAttempted.value = false;
};

/**
 * Cierra el modal de selección de talla
 */
const closeSizeModal = () => {
  showSizeModal.value = false;
  selectedSizeForModal.value = '';
  currentProductForSize.value = null;
  sizeModalAttempted.value = false;
};

/**
 * Valida y confirma la selección de talla, luego agrega al carrito
 */
const confirmAndAddToCart = () => {
  // Validar que se seleccionó una talla
  if (!selectedSizeForModal.value) {
    sizeModalAttempted.value = true;
    return;
  }

  // Agregar al carrito con talla seleccionada
  addToCart(currentProductForSize.value, selectedSizeForModal.value);
  closeSizeModal();
};

/**
 * Agrega el producto al carrito en la API
 */
const addToCart = async (producto, talla) => {
  try {
    const productId = producto.producto_id || producto.id;
    if (!productId) {
      throw new Error('ID de producto inválido');
    }

    // Si requiere talla y no se proporcionó, validar
    if (producto.tallas && producto.tallas.length > 0 && !talla) {
      feedback.value = { message: '⚠️ Selecciona la talla', type: 'warning' };
      return;
    }

    // Realizar petición al carrito
    const res = await fetch('/api/carrito/agregar', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({
        producto_id: productId,
        cantidad: 1,
        talla: talla || null,
      }),
    });

    if (!res.ok) {
      const errData = await res.json().catch(() => ({}));
      throw new Error(errData.message || 'Error al agregar al carrito');
    }

    // Éxito
    feedback.value = {
      message: `✓ ${producto.nombre} agregado al carrito`,
      type: 'success',
    };
    window.dispatchEvent(new Event('cart-updated'));

    // Limpiar mensaje después de 3 segundos
    setTimeout(() => {
      feedback.value = { message: '', type: '' };
    }, 3000);
  } catch (e) {
    feedback.value = {
      message: `❌ ${e.message}`,
      type: 'error',
    };
    console.error('Add to cart error:', e);
  }
};
</script>

<style scoped>
@keyframes scale-in {
  from {
    opacity: 0;
    transform: scale(0.95);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.animate-scale-in {
  animation: scale-in 300ms cubic-bezier(0.4, 0, 0.2, 1);
}
</style>
