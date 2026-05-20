<template>
  <AppLayout>
    <Head title="Conocenos" />
    <section class="mx-auto w-full max-w-5xl px-4 pt-10 sm:px-6 sm:pt-12 lg:px-8">
      <div class="glass rounded-2xl p-6 sm:rounded-3xl sm:p-8 lg:p-12">
        <p class="chip inline-flex bg-[color:var(--sand)]">Sobre la plataforma</p>
        <h1 class="font-display mt-3 text-2xl font-bold sm:mt-4 sm:text-3xl md:text-4xl">{{ appName }}</h1>
        <p class="mt-3 max-w-3xl text-sm text-[color:var(--muted)] sm:mt-4 sm:text-base">
          Este proyecto esta pensado para que cambies marca, categorias y colecciones sin rehacer arquitectura.
          La UI es modular y la capa SPA elimina recargas completas para mejorar conversion y percepcion de velocidad.
        </p>

        <div class="mt-6 grid gap-3 sm:mt-8 sm:gap-4 sm:grid-cols-3">
          <article class="rounded-xl border border-[color:var(--line)] bg-white p-4 sm:rounded-2xl">
            <h3 class="font-display text-base font-semibold sm:text-lg">Frontend SPA</h3>
            <p class="mt-1 text-xs text-[color:var(--muted)] sm:mt-2 sm:text-sm">Vue 3 + Inertia para transiciones fluidas.</p>
          </article>
          <article class="rounded-xl border border-[color:var(--line)] bg-white p-4 sm:rounded-2xl">
            <h3 class="font-display text-base font-semibold sm:text-lg">Backend Laravel</h3>
            <p class="mt-1 text-xs text-[color:var(--muted)] sm:mt-2 sm:text-sm">API de productos, carrito, pedidos y contenido.</p>
          </article>
          <article class="rounded-xl border border-[color:var(--line)] bg-white p-4 sm:rounded-2xl">
            <h3 class="font-display text-base font-semibold sm:text-lg">Escalabilidad</h3>
            <p class="mt-1 text-xs text-[color:var(--muted)] sm:mt-2 sm:text-sm">Lista para integrar pagos, auth y panel admin.</p>
          </article>
        </div>
      </div>
    </section>
  </AppLayout>
</template>

<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';

const page = usePage();
const appName = computed(() => page.props.app?.name || import.meta.env.VITE_APP_NAME || 'Vendex');
</script>
