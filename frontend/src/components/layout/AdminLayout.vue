<template>
  <div class="flex h-screen overflow-hidden bg-zinc-950">

    <!-- Overlay móvil -->
    <div
      v-if="sidebarAbierto"
      class="fixed inset-0 bg-black/60 z-30 lg:hidden"
      @click="sidebarAbierto = false"
    />

    <!-- Sidebar Admin -->
    <aside
      :class="[
        'fixed lg:relative inset-y-0 left-0 z-40 w-60 flex-shrink-0 bg-zinc-900 border-r border-zinc-800 flex flex-col transition-transform duration-300',
        sidebarAbierto ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
      ]"
    >
      <div class="h-16 flex items-center px-5 border-b border-zinc-800 gap-2">
        <span class="text-red-500 font-bold text-lg tracking-wider">F1</span>
        <span class="text-white font-bold text-lg">ADMIN</span>
        <span class="ml-auto text-xs bg-red-600/20 text-red-400 px-2 py-0.5 rounded font-medium">PANEL</span>
        <!-- Cerrar en móvil -->
        <button class="ml-2 lg:hidden text-zinc-400 hover:text-white" @click="sidebarAbierto = false">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        <RouterLink to="/admin" custom v-slot="{ navigate }">
          <button @click="navegar(navigate)" :class="route.name === 'AdminPanel' ? 'nav-link-active' : 'nav-link'" class="w-full">
            📊 Dashboard
          </button>
        </RouterLink>
        <RouterLink to="/admin/carreras" custom v-slot="{ isActive, navigate }">
          <button @click="navegar(navigate)" :class="isActive ? 'nav-link-active' : 'nav-link'" class="w-full">
            🏁 Gestión Carreras
          </button>
        </RouterLink>
        <RouterLink to="/admin/pilotos" custom v-slot="{ isActive, navigate }">
          <button @click="navegar(navigate)" :class="isActive ? 'nav-link-active' : 'nav-link'" class="w-full">
            🏎️ Precios Pilotos
          </button>
        </RouterLink>
        <RouterLink to="/admin/puntuacion" custom v-slot="{ isActive, navigate }">
          <button @click="navegar(navigate)" :class="isActive ? 'nav-link-active' : 'nav-link'" class="w-full">
            ⚙️ Reglas Puntuación
          </button>
        </RouterLink>
        <RouterLink to="/admin/resultados" custom v-slot="{ isActive, navigate }">
          <button @click="navegar(navigate)" :class="isActive ? 'nav-link-active' : 'nav-link'" class="w-full">
            📋 Ver Puntuaciones
          </button>
        </RouterLink>

        <div class="pt-4">
          <RouterLink to="/" class="nav-link w-full flex" @click="sidebarAbierto = false">
            ← Volver a la app
          </RouterLink>
        </div>
      </nav>
    </aside>

    <!-- Main -->
    <main class="flex-1 overflow-y-auto min-w-0">
      <header class="h-16 bg-zinc-900 border-b border-zinc-800 flex items-center px-4 sticky top-0 z-20">
        <!-- Hamburguesa móvil -->
        <button class="lg:hidden mr-3 text-zinc-400 hover:text-white" @click="sidebarAbierto = true">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
        </button>
        <h1 class="text-base lg:text-lg font-semibold text-white truncate">Panel de Administración</h1>
        <span class="ml-auto text-sm text-zinc-400 hidden sm:block flex-shrink-0">{{ authStore.user?.nombre }}</span>
      </header>
      <div class="p-4 lg:p-6 animate-slide-up">
        <RouterView />
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { RouterLink, RouterView, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const authStore      = useAuthStore()
const route          = useRoute()
const sidebarAbierto = ref(false)

function navegar(fn) {
  fn()
  sidebarAbierto.value = false
}
</script>
