<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <h2 class="text-xl font-bold text-white">Calendario {{ temporadaActual }}</h2>
      <span class="text-zinc-400 text-sm">{{ f1Store.carreras.length }} Grandes Premios</span>
    </div>

    <div v-if="f1Store.cargando" class="text-zinc-400 text-center py-10">Cargando calendario...</div>

    <div v-else class="space-y-2">
      <RouterLink
        v-for="carrera in f1Store.carreras"
        :key="carrera.id"
        :to="`/carreras/${carrera.id}`"
        class="card flex items-center gap-4 hover:border-zinc-600 transition-colors cursor-pointer"
      >
        <div class="w-10 h-10 rounded-full bg-zinc-800 flex items-center justify-center text-sm font-bold text-zinc-300 flex-shrink-0">
          {{ carrera.round }}
        </div>

        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2">
            <h3 class="font-semibold text-white truncate">{{ carrera.name }}</h3>
            <span :class="claseBadge(carrera.status)">{{ etiquetaEstado(carrera.status) }}</span>
          </div>
          <p class="text-zinc-400 text-sm truncate">{{ carrera.circuito?.name }} · {{ carrera.circuito?.country }}</p>
        </div>

        <div class="text-right flex-shrink-0">
          <p class="text-white font-medium text-sm">{{ formatearFecha(carrera.date) }}</p>
          <p v-if="carrera.time" class="text-zinc-500 text-xs">{{ carrera.time }}</p>
        </div>

        <svg class="w-4 h-4 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
      </RouterLink>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useF1Store } from '@/stores/f1'

const f1Store       = useF1Store()
const temporadaActual = new Date().getFullYear()

function formatearFecha(fecha) {
  return new Date(fecha).toLocaleDateString('es-ES', { day: 'numeric', month: 'short' })
}

function claseBadge(estado) {
  return { upcoming: 'badge-upcoming', active: 'badge-active', scored: 'badge-scored' }[estado] || 'badge-scored'
}

function etiquetaEstado(estado) {
  return { upcoming: 'Próxima', active: 'En curso', scored: 'Puntuada', cancelled: 'Cancelada' }[estado] || estado
}

onMounted(() => f1Store.fetchCarreras())
</script>
