<template>
  <AppLayout>
    <Head title="Conocenos" />
    <section class="mx-auto w-full max-w-5xl px-4 pt-12 sm:px-6 lg:px-8">
      <div class="glass rounded-3xl p-8 sm:p-12">
        <p class="chip inline-flex bg-[color:var(--sand)]">Sobre la plataforma</p>
        <h1 class="font-display mt-4 text-4xl font-bold">Base e-commerce adaptable</h1>
        <p class="mt-4 max-w-3xl text-[color:var(--muted)]">
          Este proyecto esta pensado para que cambies marca, categorias y colecciones sin rehacer arquitectura.
          La UI es modular y la capa SPA elimina recargas completas para mejorar conversion y percepcion de velocidad.
        </p>

        <div class="mt-8 grid gap-4 sm:grid-cols-3">
          <article class="rounded-2xl border border-[color:var(--line)] bg-white p-4">
            <h3 class="font-display text-lg font-semibold">Frontend SPA</h3>
            <p class="mt-2 text-sm text-[color:var(--muted)]">Vue 3 + Inertia para transiciones fluidas.</p>
          </article>
          <article class="rounded-2xl border border-[color:var(--line)] bg-white p-4">
            <h3 class="font-display text-lg font-semibold">Backend Laravel</h3>
            <p class="mt-2 text-sm text-[color:var(--muted)]">API de productos, carrito, pedidos y contenido.</p>
          </article>
          <article class="rounded-2xl border border-[color:var(--line)] bg-white p-4">
            <h3 class="font-display text-lg font-semibold">Escalabilidad</h3>
            <p class="mt-2 text-sm text-[color:var(--muted)]">Lista para integrar pagos, auth y panel admin.</p>
          </article>
        </div>
      </div>
    </section>
  </AppLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '../layouts/AppLayout.vue';
</script>
