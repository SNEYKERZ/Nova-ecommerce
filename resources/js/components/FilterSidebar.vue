<template>
  <div class="space-y-3">
    <div>
      <label class="mb-1 block text-xs font-semibold tracking-wide text-[color:var(--muted)] uppercase">Categoria</label>
      <select :value="selectedCategory" class="w-full rounded-xl border border-[color:var(--line)] bg-white px-3 py-2 text-sm" @change="$emit('update:selectedCategory', $event.target.value)">
        <option value="ALL">Todas</option>
        <option v-for="cat in categorias" :key="cat.id" :value="cat.nombre || cat.categoria">{{ cat.nombre || cat.categoria }}</option>
      </select>
    </div>

    <div>
      <label class="mb-1 block text-xs font-semibold tracking-wide text-[color:var(--muted)] uppercase">Talla</label>
      <select :value="selectedSize" class="w-full rounded-xl border border-[color:var(--line)] bg-white px-3 py-2 text-sm" @change="$emit('update:selectedSize', $event.target.value)">
        <option value="ALL">Todas</option>
        <option v-for="size in availableSizes" :key="size" :value="size">{{ size }}</option>
      </select>
    </div>

    <div>
      <label class="mb-1 block text-xs font-semibold tracking-wide text-[color:var(--muted)] uppercase">Ordenar por</label>
      <select :value="sortBy" class="w-full rounded-xl border border-[color:var(--line)] bg-white px-3 py-2 text-sm" @change="$emit('update:sortBy', $event.target.value)">
        <option value="recentes">Recientes</option>
        <option value="precio_menor">Precio: menor a mayor</option>
        <option value="precio_mayor">Precio: mayor a menor</option>
        <option value="alfabetico_asc">Alfabetico A-Z</option>
        <option value="alfabetico_desc">Alfabetico Z-A</option>
      </select>
    </div>

    <div>
      <label class="mb-1 block text-xs font-semibold tracking-wide text-[color:var(--muted)] uppercase">Recientes</label>
      <select :value="selectedRecency" class="w-full rounded-xl border border-[color:var(--line)] bg-white px-3 py-2 text-sm" @change="$emit('update:selectedRecency', $event.target.value)">
        <option value="ALL">Todos</option>
        <option value="7">Ultimos 7 dias</option>
        <option value="30">Ultimos 30 dias</option>
        <option value="90">Ultimos 90 dias</option>
      </select>
    </div>

    <div>
      <label class="mb-1 block text-xs font-semibold tracking-wide text-[color:var(--muted)] uppercase">Precio minimo</label>
      <input :value="minPrice" type="number" min="0" class="w-full rounded-xl border border-[color:var(--line)] bg-white px-3 py-2 text-sm" placeholder="0" @input="$emit('update:minPrice', $event.target.value)" />
    </div>

    <div>
      <label class="mb-1 block text-xs font-semibold tracking-wide text-[color:var(--muted)] uppercase">Precio maximo</label>
      <input :value="maxPrice" type="number" min="0" class="w-full rounded-xl border border-[color:var(--line)] bg-white px-3 py-2 text-sm" placeholder="300000" @input="$emit('update:maxPrice', $event.target.value)" />
    </div>

    <label class="flex items-center gap-2 text-sm font-semibold">
      <input :checked="onlyNewCollection" type="checkbox" class="h-4 w-4 rounded border-[color:var(--line)]" @change="$emit('update:onlyNewCollection', $event.target.checked)" />
      Solo nueva coleccion
    </label>

    <button class="btn-soft w-full px-4 py-2 text-sm font-semibold" @click="$emit('reset')">Limpiar filtros</button>
  </div>
</template>

<script setup>
defineProps({
  categorias: { type: Array, default: () => [] },
  availableSizes: { type: Array, default: () => [] },
  selectedCategory: { type: String, default: 'ALL' },
  selectedSize: { type: String, default: 'ALL' },
  selectedRecency: { type: String, default: 'ALL' },
  onlyNewCollection: { type: Boolean, default: false },
  sortBy: { type: String, default: 'recentes' },
  minPrice: { type: [String, Number], default: '' },
  maxPrice: { type: [String, Number], default: '' },
});

defineEmits([
  'update:selectedCategory',
  'update:selectedSize',
  'update:selectedRecency',
  'update:onlyNewCollection',
  'update:sortBy',
  'update:minPrice',
  'update:maxPrice',
  'reset',
]);
</script>
