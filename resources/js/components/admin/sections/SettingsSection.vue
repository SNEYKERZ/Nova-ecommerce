<template>
  <div>
    <PageHeader title="Ajustes de la tienda" description="Nombre, logo y colores que ven tus clientes." />

    <form class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]" novalidate @submit.prevent="save">
      <div class="space-y-5">
        <section class="a-card a-card-pad space-y-4" aria-labelledby="st-brand">
          <h2 id="st-brand" class="a-subtitle">Marca</h2>
          <FormField v-slot="f" label="Nombre de la tienda" required :error="errors.store_name">
            <input :id="f.id" v-model.trim="form.store_name" class="a-input" maxlength="255" :aria-invalid="f.invalid" :aria-describedby="f.describedby" />
          </FormField>
          <div>
            <p class="a-label">Logo</p>
            <div class="flex items-center gap-4">
              <img v-if="logoPreview" :src="logoPreview" alt="Logo actual" class="h-16 w-16 rounded-xl border border-[var(--a-border)] object-cover" />
              <span v-else class="flex h-16 w-16 items-center justify-center rounded-xl border border-dashed border-[var(--a-border-strong)] text-[var(--a-muted)]"><AdminIcon name="image" /></span>
              <div>
                <label class="a-btn a-btn-secondary cursor-pointer">
                  <AdminIcon name="upload" size="sm" />{{ logoPreview ? 'Cambiar logo' : 'Subir logo' }}
                  <input type="file" class="visually-hidden" accept="image/jpeg,image/png,image/webp" @change="onLogo" />
                </label>
                <p class="a-help">Cuadrado, JPG, PNG o WebP, máx. 10 MB.</p>
              </div>
            </div>
            <p v-if="errors.logo" class="a-error">{{ errors.logo }}</p>
          </div>
        </section>

        <section class="a-card a-card-pad space-y-4" aria-labelledby="st-colors">
          <div>
            <h2 id="st-colors" class="a-subtitle">Colores</h2>
            <p class="mt-0.5 text-xs text-[var(--a-muted)]">El color del texto se ajusta solo para que siempre se lea bien.</p>
          </div>
          <div v-for="color in colorFields" :key="color.key">
            <label :for="`c-${color.key}`" class="a-label">{{ color.label }}</label>
            <div class="flex items-center gap-2">
              <input :id="`c-${color.key}-picker`" v-model="form[color.key]" type="color" class="a-color" :aria-label="`${color.label}: selector`" />
              <input :id="`c-${color.key}`" v-model.trim="form[color.key]" class="a-input max-w-[9rem] font-mono uppercase" maxlength="7" :aria-invalid="errors[color.key] ? 'true' : 'false'" @blur="normalize(color.key)" />
              <span class="a-badge no-dot" :class="contrastTone(form[color.key])">{{ contrastLabel(form[color.key]) }}</span>
            </div>
            <p v-if="errors[color.key]" class="a-error">{{ errors[color.key] }}</p>
          </div>
          <div class="flex flex-wrap gap-2">
            <span class="text-xs font-medium text-[var(--a-ink-2)]">Combinaciones:</span>
            <button v-for="preset in presets" :key="preset.name" type="button" class="a-btn a-btn-secondary a-btn-sm" @click="applyPreset(preset)">
              <span class="flex overflow-hidden rounded-sm border border-black/10" aria-hidden="true">
                <span class="h-3 w-3" :style="{ background: preset.navbar_color }"></span>
                <span class="h-3 w-3" :style="{ background: preset.bg_color }"></span>
                <span class="h-3 w-3" :style="{ background: preset.footer_color }"></span>
              </span>
              {{ preset.name }}
            </button>
          </div>
        </section>
      </div>

      <!-- Vista previa -->
      <section class="lg:sticky lg:top-20 lg:self-start" aria-labelledby="st-preview">
        <h2 id="st-preview" class="a-subtitle mb-2">Vista previa</h2>
        <div class="overflow-hidden rounded-xl border border-[var(--a-border)] shadow-[var(--a-shadow)]" :style="{ background: validOr(form.bg_color, '#ffffff') }" aria-hidden="true">
          <div class="flex items-center justify-between px-4 py-3" :style="{ background: validOr(form.navbar_color, '#ffffff'), color: textOn(form.navbar_color) }">
            <div class="flex items-center gap-2">
              <img v-if="logoPreview" :src="logoPreview" alt="" class="h-6 w-6 rounded object-cover" />
              <span class="text-xs font-bold uppercase tracking-[0.12em]">{{ form.store_name || 'Tu tienda' }}</span>
            </div>
            <span class="hidden gap-4 text-[10px] font-bold uppercase tracking-widest sm:flex"><span>Catálogo</span><span>Galerías</span><span>Nosotros</span></span>
          </div>
          <div class="grid grid-cols-3 gap-2 p-4">
            <div v-for="n in 3" :key="n">
              <div class="aspect-[3/4] rounded-md bg-black/10"></div>
              <div class="mt-1.5 h-2 w-3/4 rounded" :style="{ background: textOn(form.bg_color), opacity: 0.7 }"></div>
              <div class="mt-1 h-2 w-1/3 rounded" :style="{ background: textOn(form.bg_color), opacity: 0.4 }"></div>
            </div>
          </div>
          <div class="px-4 py-4 text-[11px]" :style="{ background: validOr(form.footer_color, '#e8e6e3'), color: textOn(form.footer_color) }">
            <p class="font-bold uppercase tracking-[0.1em]">{{ form.store_name || 'Tu tienda' }}</p>
            <p class="mt-1 opacity-80">© {{ year }} · Todos los derechos reservados</p>
          </div>
        </div>
        <p class="mt-2 text-xs text-[var(--a-muted)]">Los colores de texto del menú y el pie también se pueden fijar desde la super administración.</p>

        <div class="mt-4 flex justify-end gap-2">
          <button type="button" class="a-btn a-btn-secondary" :disabled="saving || !dirty" @click="reset">Descartar</button>
          <button type="submit" class="a-btn a-btn-primary" :disabled="saving || !dirty">
            <span v-if="saving" class="spinner" aria-hidden="true"></span>
            {{ saving ? 'Guardando…' : 'Guardar ajustes' }}
          </button>
        </div>
      </section>
    </form>
  </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import AdminIcon from '../AdminIcon.vue';
import FormField from '../FormField.vue';
import PageHeader from '../PageHeader.vue';
import { useToast } from '../../../composables/useToast';
import { api } from '../../../utils/http';
import { getContrastRatio, getTextColorHex, isValidHexColor } from '../../../utils/colorUtils';

const props = defineProps({
  settings: { type: Object, default: () => ({}) },
});

const toast = useToast();
const year = new Date().getFullYear();
const colorFields = [
  { key: 'navbar_color', label: 'Menú superior' },
  { key: 'bg_color', label: 'Fondo de la página' },
  { key: 'footer_color', label: 'Pie de página' },
];
const presets = [
  { name: 'Clásico', navbar_color: '#ffffff', bg_color: '#ffffff', footer_color: '#e8e6e3' },
  { name: 'Arena', navbar_color: '#f5f4f2', bg_color: '#f5f4f2', footer_color: '#111111' },
  { name: 'Nocturno', navbar_color: '#111111', bg_color: '#ffffff', footer_color: '#111111' },
];

const toLongHex = (value) => {
  if (!isValidHexColor(value || '')) return value;
  return value.length === 4 ? `#${value[1]}${value[1]}${value[2]}${value[2]}${value[3]}${value[3]}`.toLowerCase() : value.toLowerCase();
};
const fromProps = () => ({
  store_name: props.settings?.store_name || '',
  bg_color: toLongHex(props.settings?.bg_color || '#ffffff'),
  navbar_color: toLongHex(props.settings?.navbar_color || '#ffffff'),
  footer_color: toLongHex(props.settings?.footer_color || '#e8e6e3'),
});

const form = reactive(fromProps());
const errors = reactive({});
const logoFile = ref(null);
const logoPreview = ref(props.settings?.logo_url || null);
const saving = ref(false);
let initial = JSON.stringify(form);

const dirty = computed(() => JSON.stringify(form) !== initial || Boolean(logoFile.value));

const reset = () => {
  Object.assign(form, fromProps());
  if (logoFile.value && logoPreview.value) URL.revokeObjectURL(logoPreview.value);
  logoFile.value = null;
  logoPreview.value = props.settings?.logo_url || null;
  Object.keys(errors).forEach((k) => delete errors[k]);
  initial = JSON.stringify(form);
};
watch(() => props.settings, reset, { deep: true });

const validOr = (value, fallback) => (isValidHexColor(value || '') ? value : fallback);
const textOn = (bg) => getTextColorHex(validOr(bg, '#ffffff'));
const contrastLabel = (bg) => (isValidHexColor(bg || '') ? (textOn(bg) === '#ffffff' ? 'Texto blanco' : 'Texto oscuro') : 'Color inválido');
const contrastTone = (bg) => {
  if (!isValidHexColor(bg || '')) return 'tone-critical';
  return getContrastRatio(textOn(bg), bg) >= 4.5 ? '' : 'tone-warning';
};
const normalize = (key) => {
  let value = (form[key] || '').trim();
  if (value && !value.startsWith('#')) value = `#${value}`;
  form[key] = toLongHex(value);
};
const applyPreset = (preset) => {
  form.navbar_color = preset.navbar_color;
  form.bg_color = preset.bg_color;
  form.footer_color = preset.footer_color;
};

const onLogo = (event) => {
  const file = event.target.files?.[0];
  event.target.value = '';
  delete errors.logo;
  if (!file) return;
  if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
    errors.logo = 'El logo debe ser JPG, PNG o WebP.';
    return;
  }
  if (file.size > 10 * 1024 * 1024) {
    errors.logo = 'El logo pesa más de 10 MB.';
    return;
  }
  if (logoFile.value && logoPreview.value) URL.revokeObjectURL(logoPreview.value);
  logoFile.value = file;
  logoPreview.value = URL.createObjectURL(file);
};

const save = async () => {
  Object.keys(errors).forEach((k) => delete errors[k]);
  colorFields.forEach(({ key }) => normalize(key));
  if (!form.store_name) errors.store_name = 'Escribe el nombre de la tienda.';
  colorFields.forEach(({ key }) => { if (!isValidHexColor(form[key] || '')) errors[key] = 'Usa un color hexadecimal, por ejemplo #111111.'; });
  if (Object.keys(errors).length) return;

  const data = new FormData();
  data.append('store_name', form.store_name);
  data.append('bg_color', form.bg_color);
  data.append('navbar_color', form.navbar_color);
  data.append('footer_color', form.footer_color);
  if (logoFile.value) data.append('logo', logoFile.value);

  saving.value = true;
  try {
    const payload = await api('/admin/settings', { method: 'POST', body: data });
    toast.success(payload.message || 'Ajustes guardados');
    logoFile.value = null;
    initial = JSON.stringify(form);
    // Recarga completa de props compartidas: el nombre/logo/colores también los usa la tienda
    router.reload();
  } catch (error) {
    Object.assign(errors, error.errors || {});
    toast.error(error.message);
  } finally {
    saving.value = false;
  }
};
</script>
