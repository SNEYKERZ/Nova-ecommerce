<template>
  <div class="space-y-8">
    <fieldset v-if="availableSizes.length">
      <legend class="mb-3 text-sm font-semibold text-[var(--sf-mute)]">{{ sizeLabel }}</legend>
      <div class="flex flex-wrap gap-2">
        <button type="button" class="sf-option" :aria-pressed="selectedSize === 'ALL'" @click="$emit('update:selectedSize', 'ALL')">Todas</button>
        <button
          v-for="size in availableSizes"
          :key="size"
          type="button"
          class="sf-option"
          :aria-pressed="selectedSize === size"
          @click="$emit('update:selectedSize', selectedSize === size ? 'ALL' : size)"
        >{{ size }}</button>
      </div>
    </fieldset>

    <fieldset class="sf-field">
      <legend class="mb-3 text-sm font-semibold text-[var(--sf-mute)]">Precio (COP)</legend>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label for="f-min" class="mb-1 block text-xs font-semibold text-[var(--sf-mute)]">Desde</label>
          <input id="f-min" :value="minPrice" type="number" inputmode="numeric" min="0" step="1000" placeholder="0" @input="$emit('update:minPrice', $event.target.value)" />
        </div>
        <div>
          <label for="f-max" class="mb-1 block text-xs font-semibold text-[var(--sf-mute)]">Hasta</label>
          <input id="f-max" :value="maxPrice" type="number" inputmode="numeric" min="0" step="1000" placeholder="Sin límite" @input="$emit('update:maxPrice', $event.target.value)" />
        </div>
      </div>
      <p v-if="priceError" class="mt-2 text-xs font-semibold text-[var(--pencil)]">{{ priceError }}</p>
    </fieldset>

    <fieldset>
      <legend class="mb-3 text-sm font-semibold text-[var(--sf-mute)]">Publicados</legend>
      <div class="flex flex-wrap gap-2">
        <button
          v-for="option in recencyOptions"
          :key="option.value"
          type="button"
          class="sf-option"
          :aria-pressed="selectedRecency === option.value"
          @click="$emit('update:selectedRecency', option.value)"
        >{{ option.label }}</button>
      </div>
    </fieldset>

    <button type="button" class="sf-option w-full !justify-start" :aria-pressed="onlyNewCollection" @click="$emit('update:onlyNewCollection', !onlyNewCollection)">
      {{ newLabel }}
    </button>

    <fieldset>
      <legend class="mb-3 text-sm font-semibold text-[var(--sf-mute)]">Ordenar por</legend>
      <div class="flex flex-col gap-2">
        <button
          v-for="option in sortOptions"
          :key="option.value"
          type="button"
          class="sf-option !justify-start"
          :aria-pressed="sortBy === option.value"
          @click="$emit('update:sortBy', option.value)"
        >{{ option.label }}</button>
      </div>
    </fieldset>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { sortOptions } from '../utils/catalog';

const props = defineProps({
  availableSizes: { type: Array, default: () => [] },
  selectedSize: { type: String, default: 'ALL' },
  selectedRecency: { type: String, default: 'ALL' },
  onlyNewCollection: { type: Boolean, default: false },
  sortBy: { type: String, default: 'recentes' },
  minPrice: { type: [String, Number], default: '' },
  maxPrice: { type: [String, Number], default: '' },
  sizeLabel: { type: String, default: 'Talla' },
  newLabel: { type: String, default: 'Sólo nueva colección' },
});

defineEmits([
  'update:selectedSize',
  'update:selectedRecency',
  'update:onlyNewCollection',
  'update:sortBy',
  'update:minPrice',
  'update:maxPrice',
]);

const recencyOptions = [
  { value: 'ALL', label: 'Cualquier fecha' },
  { value: '7', label: 'Últimos 7 días' },
  { value: '30', label: 'Últimos 30 días' },
  { value: '90', label: 'Últimos 90 días' },
];

const priceError = computed(() => {
  if (props.minPrice === '' || props.maxPrice === '') return '';
  return Number(props.minPrice) > Number(props.maxPrice) ? 'El precio “desde” es mayor que “hasta”.' : '';
});
</script>
