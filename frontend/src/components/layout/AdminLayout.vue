<template>
  <div class="flex h-screen overflow-hidden bg-zinc-950">
    <!-- Sidebar Admin -->
    <aside class="w-60 flex-shrink-0 bg-zinc-900 border-r border-zinc-800 flex flex-col">
      <div class="h-16 flex items-center px-5 border-b border-zinc-800 gap-2">
        <span class="text-red-500 font-bold text-lg tracking-wider">F1</span>
        <span class="text-white font-bold text-lg">ADMIN</span>
        <span class="ml-auto text-xs bg-red-600/20 text-red-400 px-2 py-0.5 rounded font-medium">PANEL</span>
      </div>

      <nav class="flex-1 px-3 py-4 space-y-1">
        <RouterLink to="/admin"         custom v-slot="{ isActive, navigate }">
          <button @click="navigate" :class="isActive ? 'nav-link-active' : 'nav-link'" class="w-full">
            📊 Dashboard
          </button>
        </RouterLink>
        <RouterLink to="/admin/carreras"   custom v-slot="{ isActive, navigate }">
          <button @click="navigate" :class="isActive ? 'nav-link-active' : 'nav-link'" class="w-full">
            🏁 Gestión Carreras
          </button>
        </RouterLink>
        <RouterLink to="/admin/pilotos"    custom v-slot="{ isActive, navigate }">
          <button @click="navigate" :class="isActive ? 'nav-link-active' : 'nav-link'" class="w-full">
            🏎️ Precios Pilotos
          </button>
        </RouterLink>
        <RouterLink to="/admin/puntuacion" custom v-slot="{ isActive, navigate }">
          <button @click="navigate" :class="isActive ? 'nav-link-active' : 'nav-link'" class="w-full">
            ⚙️ Reglas Puntuación
          </button>
        </RouterLink>

        <div class="pt-4">
          <RouterLink to="/" class="nav-link w-full flex">
            ← Volver a la app
          </RouterLink>
        </div>
      </nav>
    </aside>

    <!-- Main -->
    <main class="flex-1 overflow-y-auto">
      <header class="h-16 bg-zinc-900 border-b border-zinc-800 flex items-center px-6 sticky top-0 z-10">
        <h1 class="text-lg font-semibold text-white">Panel de Administración</h1>
        <span class="ml-4 text-sm text-zinc-400">{{ authStore.user?.name }}</span>
      </header>
      <div class="p-6 animate-slide-up">
        <RouterView />
      </div>
    </main>
  </div>
</template>

<script setup>
import { RouterLink, RouterView } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
</script>
