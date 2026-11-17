<template>
  <AppLayout>
    <Head title="Pedido recibido" />
    <section class="mx-auto w-full max-w-[110rem] px-4 pt-12 sm:px-6 sm:pt-16 lg:px-10">
      <p class="sf-num text-sm text-[var(--sf-mute)]">Paso 3 de 3 · Confirmación</p>
      <h1 class="sf-display sf-masthead mt-3">Gracias</h1>

      <div class="mt-8 grid gap-10 border-t border-[var(--sf-rule)] pt-8 lg:grid-cols-12">
        <div class="lg:col-span-5">
          <p class="text-xl leading-relaxed text-[var(--sf-ink-2)]">Recibimos tu pedido. Guarda este número para cualquier consulta.</p>
          <p class="relative mt-6 inline-block">
            <span class="sf-display sf-num text-7xl sm:text-8xl">#{{ pedidoId }}</span>
            <PencilMark :active="drawn" />
          </p>
        </div>

        <div class="lg:col-span-6 lg:col-start-7">
          <h2 class="text-sm font-semibold text-[var(--sf-mute)]">Qué sigue</h2>
          <ol class="mt-3 border-t border-[var(--sf-rule)]">
            <li v-for="(step, index) in steps" :key="index" class="flex gap-5 border-b border-[var(--sf-rule)] py-4">
              <span class="sf-num w-6 shrink-0 text-base font-bold text-[var(--pencil)]">{{ index + 1 }}</span>
              <span class="text-base">{{ step }}</span>
            </li>
          </ol>
          <div class="mt-8 flex flex-col gap-3 sm:flex-row">
            <Link href="/" class="sf-btn">Seguir comprando</Link>
            <a v-if="whatsappHref" :href="whatsappHref" target="_blank" rel="noopener" class="sf-btn-line">Escribir a la tienda</a>
          </div>
        </div>
      </div>
    </section>
  </AppLayout>
</template>

<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';
import PencilMark from '../components/store/PencilMark.vue';

const props = defineProps({
  pedidoId: { type: [String, Number], required: true },
});

const page = usePage();
const drawn = ref(false);
onMounted(() => { setTimeout(() => { drawn.value = true; }, 350); });

const viaWhatsapp = computed(() => /[?&]canal=whatsapp/.test(page.url || ''));
const steps = computed(() => [
  viaWhatsapp.value
    ? 'Envía el mensaje que abrimos en WhatsApp para que la tienda reciba tu pedido.'
    : 'La tienda revisa tu pedido y confirma la disponibilidad.',
  'Te contactamos para acordar el pago y el envío.',
  'Recibes tu pedido en la dirección indicada.',
]);
const whatsappHref = computed(() => {
  const digits = (page.props.app?.whatsapp || '').replace(/\D/g, '');
  if (!digits) return null;
  return `https://wa.me/${digits}?text=${encodeURIComponent(`Hola, quiero consultar mi pedido #${props.pedidoId}.`)}`;
});
</script>
