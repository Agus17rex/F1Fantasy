<template>
  <div class="min-h-screen bg-zinc-950 flex items-center justify-center p-4">
    <div class="text-center">
      <p class="text-8xl font-black text-red-500 mb-2">404</p>
      <h1 class="text-2xl font-bold text-white mb-2">Página no encontrada</h1>
      <p class="text-zinc-400 mb-6">La página que buscas no existe o ha sido movida.</p>
      <RouterLink to="/" class="btn-primary">Volver al inicio</RouterLink>
    </div>
  </div>
</template>

<script setup>
import { RouterLink } from 'vue-router'
</script>
