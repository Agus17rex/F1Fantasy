<template>
  <div class="flex h-screen overflow-hidden bg-zinc-950">
    <!-- Sidebar -->
    <aside class="w-64 flex-shrink-0 bg-zinc-900 border-r border-zinc-800 flex flex-col">
      <!-- Logo -->
      <div class="h-16 flex items-center px-6 border-b border-zinc-800">
        <span class="text-red-500 font-bold text-xl tracking-wider">F1</span>
        <span class="text-white font-bold text-xl ml-1">FANTASY</span>
      </div>

      <!-- Nav -->
      <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        <RouterLink to="/" custom v-slot="{ navigate }">
          <button @click="navigate" :class="route.name === 'Dashboard' ? 'nav-link-active' : 'nav-link'" class="w-full">
            <IconHome /> Dashboard
          </button>
        </RouterLink>

        <RouterLink to="/carreras"     custom v-slot="{ isActive, navigate }">
          <button @click="navigate" :class="isActive ? 'nav-link-active' : 'nav-link'" class="w-full">
            <IconFlag /> Carreras
          </button>
        </RouterLink>

        <RouterLink to="/pilotos"     custom v-slot="{ isActive, navigate }">
          <button @click="navigate" :class="isActive ? 'nav-link-active' : 'nav-link'" class="w-full">
            <IconHelmet /> Pilotos
          </button>
        </RouterLink>

        <RouterLink to="/escuderias"  custom v-slot="{ isActive, navigate }">
          <button @click="navigate" :class="isActive ? 'nav-link-active' : 'nav-link'" class="w-full">
            <IconCar /> Escuderías
          </button>
        </RouterLink>

        <RouterLink to="/coches" custom v-slot="{ isActive, navigate }">
          <button @click="navigate" :class="isActive ? 'nav-link-active' : 'nav-link'" class="w-full">
            <IconRace /> Coches
          </button>
        </RouterLink>

        <RouterLink to="/ranking-fantasy" custom v-slot="{ isActive, navigate }">
          <button @click="navigate" :class="isActive ? 'nav-link-active' : 'nav-link'" class="w-full">
            <IconChart /> Ranking Fantasy
          </button>
        </RouterLink>

        <RouterLink to="/ligas"       custom v-slot="{ isActive, navigate }">
          <button @click="navigate" :class="isActive ? 'nav-link-active' : 'nav-link'" class="w-full">
            <IconTrophy /> Mis Ligas
          </button>
        </RouterLink>

        <!-- Admin link -->
        <template v-if="authStore.isAdmin">
          <div class="pt-4 pb-1">
            <p class="text-xs text-zinc-500 px-3 uppercase tracking-wider font-medium">Admin</p>
          </div>
          <RouterLink to="/admin" custom v-slot="{ isActive, navigate }">
            <button @click="navigate" :class="isActive ? 'nav-link-active' : 'nav-link'" class="w-full">
              <IconShield /> Panel Admin
            </button>
          </RouterLink>
        </template>
      </nav>

      <!-- User -->
      <div class="border-t border-zinc-800 p-3 space-y-2">
        <!-- Avatar + nombre → perfil -->
        <RouterLink to="/perfil" class="flex items-center gap-3 p-2 rounded-lg hover:bg-zinc-800 transition-colors cursor-pointer group">
          <div class="w-8 h-8 rounded-full bg-red-600 flex items-center justify-center text-sm font-bold flex-shrink-0">
            {{ authStore.user?.nombre?.charAt(0).toUpperCase() }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-white truncate group-hover:text-red-400 transition-colors">
              {{ authStore.user?.nombre }}
            </p>
            <p class="text-xs text-zinc-500 truncate">{{ authStore.user?.email }}</p>
          </div>
          <svg class="w-3.5 h-3.5 text-zinc-600 group-hover:text-zinc-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
          </svg>
        </RouterLink>
        <!-- Botón cerrar sesión -->
        <button @click="handleLogout"
          class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-zinc-400 hover:text-red-400 hover:bg-red-500/10 transition-colors text-sm">
          <IconLogout />
          <span>Cerrar sesión</span>
        </button>
      </div>
    </aside>

    <!-- Main content -->
    <main class="flex-1 overflow-y-auto">
      <!-- Top bar -->
      <header class="h-16 bg-zinc-900 border-b border-zinc-800 flex items-center px-6 sticky top-0 z-10">
        <h1 class="text-lg font-semibold text-white">{{ pageTitle }}</h1>
        <div class="ml-auto flex items-center gap-3">
          <span v-if="nextRace" class="text-xs text-zinc-400">
            Próxima carrera: <span class="text-red-400 font-medium">{{ nextRace.name }}</span>
          </span>
        </div>
      </header>

      <div class="p-6 animate-slide-up">
        <RouterView />
      </div>
    </main>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useRoute, useRouter, RouterLink, RouterView } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useF1Store } from '@/stores/f1'

// Simple inline icons (SVG)
const IconHome    = { template: `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>` }
const IconFlag    = { template: `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/></svg>` }
const IconHelmet  = { template: `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>` }
const IconCar     = { template: `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>` }
const IconTrophy  = { template: `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>` }
const IconChart   = { template: `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>` }
const IconRace    = { template: `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>` }
const IconShield  = { template: `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>` }
const IconLogout  = { template: `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>` }

const authStore = useAuthStore()
const f1Store   = useF1Store()
const route     = useRoute()
const router    = useRouter()

const nextRace = computed(() => f1Store.proximaCarrera)

const pageTitles = {
  Dashboard:      'Dashboard',
  Carreras:       'Calendario de Carreras',
  DetalleCarrera: 'Detalle de Carrera',
  Pilotos:        'Pilotos',
  Escuderias:     'Escuderías',
  Coches:         'Coches',
  RankingFantasy: 'Ranking Fantasy',
  Ligas:          'Mis Ligas',
  DetalleLiga:    'Liga',
  Perfil:         'Mi Perfil',
}

const pageTitle = computed(() => pageTitles[route.name] || 'F1 Fantasy')

async function handleLogout() {
  await authStore.logout()
  router.push('/login')
}

onMounted(() => {
  f1Store.fetchProximaCarrera()
})
</script>
