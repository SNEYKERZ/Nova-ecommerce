<template>
  <AppLayout>
    <Head title="Gracias" />
    <section class="mx-auto w-full max-w-4xl px-4 pt-16 text-center sm:px-6 lg:px-8">
      <div class="rounded-3xl border border-[color:var(--line)] bg-[color:var(--surface)] p-10">
        <p class="chip mx-auto inline-flex bg-[color:var(--sand)]">Pedido confirmado</p>
        <h1 class="font-display mt-4 text-4xl font-bold">Gracias por tu compra</h1>
        <p class="mt-3 text-[color:var(--muted)]">Tu numero de pedido es <strong>#{{ pedidoId }}</strong>.</p>
        <div class="mt-8 flex justify-center gap-3">
          <Link href="/" class="btn-main px-6 py-3 text-sm font-bold">Volver al catalogo</Link>
          <Link href="/carrito" class="btn-soft px-6 py-3 text-sm font-semibold">Revisar carrito</Link>
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
