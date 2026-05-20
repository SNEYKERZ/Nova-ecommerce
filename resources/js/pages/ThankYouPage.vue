<template>
  <AppLayout>
    <Head title="Gracias" />
    <section class="mx-auto w-full max-w-4xl px-4 pt-12 text-center sm:px-6 sm:pt-16 lg:px-8">
      <div class="rounded-2xl border border-[color:var(--line)] bg-[color:var(--surface)] p-6 sm:rounded-3xl sm:p-10">
        <p class="chip mx-auto inline-flex bg-[color:var(--sand)]">Pedido confirmado</p>
        <h1 class="font-display mt-3 text-2xl font-bold sm:mt-4 sm:text-3xl md:text-4xl">Gracias por tu compra</h1>
        <p class="mt-2 text-sm text-[color:var(--muted)] sm:mt-3 sm:text-base">Tu numero de pedido es <strong>#{{ pedidoId }}</strong>.</p>
        <div class="mt-6 flex flex-col gap-3 sm:mt-8 sm:flex-row sm:justify-center">
          <Link href="/" class="btn-main px-5 py-2.5 text-xs font-bold sm:px-6 sm:py-3 sm:text-sm">Volver al catalogo</Link>
          <Link href="/carrito" class="btn-soft px-5 py-2.5 text-xs font-semibold sm:px-6 sm:py-3 sm:text-sm">Revisar carrito</Link>
        </div>
      </div>
    </section>
  </AppLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../layouts/AppLayout.vue';

defineProps({
  pedidoId: { type: [String, Number], required: true },
});
</script>
