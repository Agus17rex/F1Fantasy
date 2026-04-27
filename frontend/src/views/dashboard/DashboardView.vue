<template>
  <div class="space-y-6">
    <!-- Bienvenida -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-white">Hola, {{ authStore.user?.nombre }} 👋</h2>
        <p class="text-zinc-400 text-sm mt-1">Temporada {{ temporadaActual }} · F1 Fantasy League</p>
      </div>
    </div>

    <!-- Próxima carrera -->
    <div v-if="f1Store.proximaCarrera" class="card border-l-4 border-l-red-500 flex items-center gap-6">
      <div class="flex-1">
        <p class="text-xs text-zinc-500 uppercase tracking-wider font-medium mb-1">Próxima carrera</p>
        <h3 class="text-xl font-bold text-white">{{ f1Store.proximaCarrera.nombre }}</h3>
        <p class="text-zinc-400 text-sm mt-0.5">
          {{ f1Store.proximaCarrera.circuito?.nombre }} · {{ formatearFecha(f1Store.proximaCarrera.fecha) }}
        </p>
      </div>
      <div class="text-right">
        <p class="text-3xl font-black text-white">{{ diasHastaCarrera }}</p>
        <p class="text-zinc-400 text-xs mt-0.5">días restantes</p>
      </div>
    </div>

    <!-- Stats rápidas -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="card text-center">
        <p class="text-3xl font-black text-white">{{ f1Store.carreras.length }}</p>
        <p class="text-zinc-400 text-sm mt-1">Carreras en temporada</p>
      </div>
      <div class="card text-center">
        <p class="text-3xl font-black text-white">{{ f1Store.pilotos.length }}</p>
        <p class="text-zinc-400 text-sm mt-1">Pilotos activos</p>
      </div>
      <div class="card text-center">
        <p class="text-3xl font-black text-red-500">{{ misLigas.length }}</p>
        <p class="text-zinc-400 text-sm mt-1">Mis ligas</p>
      </div>
      <div class="card text-center">
        <p class="text-3xl font-black text-white">{{ authStore.user?.total_points || 0 }}</p>
        <p class="text-zinc-400 text-sm mt-1">Puntos totales</p>
      </div>
    </div>

    <!-- Acciones rápidas -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <RouterLink to="/ligas" class="card hover:border-red-500/50 transition-colors cursor-pointer group">
        <div class="text-2xl mb-2">🏆</div>
        <h3 class="font-semibold text-white group-hover:text-red-400 transition-colors">Mis Ligas</h3>
        <p class="text-zinc-400 text-sm mt-1">Gestiona tus equipos y ligas</p>
      </RouterLink>

      <RouterLink to="/carreras" class="card hover:border-red-500/50 transition-colors cursor-pointer group">
        <div class="text-2xl mb-2">🏁</div>
        <h3 class="font-semibold text-white group-hover:text-red-400 transition-colors">Calendario</h3>
        <p class="text-zinc-400 text-sm mt-1">Ver todas las carreras de la temporada</p>
      </RouterLink>

      <RouterLink to="/pilotos" class="card hover:border-red-500/50 transition-colors cursor-pointer group">
        <div class="text-2xl mb-2">🏎️</div>
        <h3 class="font-semibold text-white group-hover:text-red-400 transition-colors">Pilotos</h3>
        <p class="text-zinc-400 text-sm mt-1">Consulta precios y estadísticas</p>
      </RouterLink>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useF1Store } from '@/stores/f1'
import { ligaService } from '@/services/leagueService'

const authStore      = useAuthStore()
const f1Store        = useF1Store()
const misLigas       = ref([])
const temporadaActual = new Date().getFullYear()

const diasHastaCarrera = computed(() => {
  if (!f1Store.proximaCarrera) return 0
  const diff = new Date(f1Store.proximaCarrera.fecha) - new Date()
  return Math.max(0, Math.ceil(diff / (1000 * 60 * 60 * 24)))
})

function formatearFecha(fecha) {
  return new Date(fecha).toLocaleDateString('es-ES', {
    day: 'numeric', month: 'long', year: 'numeric',
  })
}

onMounted(async () => {
  await Promise.all([
    f1Store.fetchCarreras(),
    f1Store.fetchPilotos(),
    f1Store.fetchProximaCarrera(),
  ])
  try {
    const { data } = await ligaService.getLigas()
    misLigas.value = data
  } catch {}
})
</script>
