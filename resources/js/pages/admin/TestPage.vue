<template>
  <div style="display: flex; min-height: 100vh; align-items: center; justify-content: center; background: white;">
    <div style="text-align: center; padding: 32px;">
      <h1 style="font-size: 24px; font-weight: bold; color: #1e293b;">ADMIN TEST OK</h1>
      <p style="margin-top: 8px; color: #64748b;">
        Inertia v3 funciona correctamente.
        <br>Usuario: {{ user?.name ?? 'sin nombre' }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const user = page.props.auth?.user;
</script>
