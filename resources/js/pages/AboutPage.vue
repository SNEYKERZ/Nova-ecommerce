<template>
  <AppLayout>
    <Head :title="`Sobre ${storeName}`" />
    <section class="mx-auto w-full max-w-[110rem] px-4 pt-10 sm:px-6 sm:pt-14 lg:px-10">
      <h1 class="sf-display sf-masthead">{{ storeName }}</h1>

      <div class="mt-8 grid gap-10 border-t border-[var(--sf-rule)] pt-8 lg:grid-cols-12">
        <p class="whitespace-pre-line text-xl leading-relaxed text-[var(--sf-ink-2)] sm:text-2xl lg:col-span-7">{{ description }}</p>

        <div v-if="contactItems.length" class="lg:col-span-4 lg:col-start-9">
          <h2 class="text-sm font-semibold text-[var(--sf-mute)]">Contacto</h2>
          <ul class="mt-3 divide-y divide-[var(--sf-rule)] border-y border-[var(--sf-rule)]">
            <li v-for="item in contactItems" :key="item.label">
              <a :href="item.href" :target="item.external ? '_blank' : undefined" :rel="item.external ? 'noopener' : undefined" class="group flex min-h-14 items-center justify-between gap-4">
                <span class="text-sm text-[var(--sf-mute)]">{{ item.label }}</span>
                <span class="sf-num truncate text-base font-semibold underline-offset-4 group-hover:underline">{{ item.value }}</span>
              </a>
            </li>
          </ul>
        </div>
      </div>

      <section class="mt-20" aria-labelledby="how-title">
        <h2 id="how-title" class="sf-display text-5xl sm:text-6xl">Cómo pedir</h2>
        <ol class="mt-8 grid border-l border-t border-[var(--sf-rule)] md:grid-cols-3">
          <li v-for="(step, index) in steps" :key="step.title" class="border-b border-r border-[var(--sf-rule)] p-5 sm:p-6">
            <p class="sf-display text-6xl text-[var(--pencil)]">{{ index + 1 }}</p>
            <p class="mt-6 text-lg font-semibold">{{ step.title }}</p>
            <p class="mt-1 text-base text-[var(--sf-ink-2)]">{{ step.text }}</p>
          </li>
        </ol>
      </section>

      <div class="mt-14">
        <Link href="/" class="sf-btn">Ver {{ labels.catalogo.toLowerCase() }}</Link>
      </div>
    </section>
  </AppLayout>
</template>

<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';
import { useRubro } from '../composables/useRubro';

const props = defineProps({
  store: { type: Object, default: null },
});

const page = usePage();
const rubro = useRubro();
const labels = computed(() => rubro.preset.value);
const storeName = computed(() => props.store?.nombre || page.props.app?.name || 'Nuestra tienda');
const description = computed(() => props.store?.descripcion || `${storeName.value}. ${labels.value.about}`);

const steps = computed(() => [
  { title: 'Arma tu pedido', text: `Agrega al carrito lo que quieras y envíalo desde la web o por WhatsApp.` },
  { title: 'Confirmamos contigo', text: 'Te contactamos para acordar el medio de pago.' },
  { title: rubro.key.value === 'restaurante' ? 'Recibe tu pedido' : 'Recibe en casa', text: 'El costo del envío depende de tu ciudad o zona.' },
]);

const contactItems = computed(() => {
  const list = [];
  const phone = props.store?.telefono || page.props.app?.whatsapp;
  const digits = (phone || '').replace(/\D/g, '');
  if (digits) list.push({ label: 'WhatsApp', value: phone, href: `https://wa.me/${digits}`, external: true });
  if (props.store?.email) list.push({ label: 'Correo', value: props.store.email, href: `mailto:${props.store.email}` });
  return list;
});
</script>
