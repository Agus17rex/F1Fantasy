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
          {{ carrera.ronda }}
        </div>

        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2">
            <h3 class="font-semibold text-white truncate">{{ carrera.nombre }}</h3>
            <span v-if="calcularBadge(carrera)" :class="claseBadge(carrera)">{{ etiquetaBadge(carrera) }}</span>
          </div>
          <p class="text-zinc-400 text-sm truncate">{{ carrera.circuito?.nombre }} · {{ carrera.circuito?.pais }}</p>
        </div>

        <div class="text-right flex-shrink-0">
          <p class="text-white font-medium text-sm">{{ formatearFecha(carrera.fecha) }}</p>
          <p v-if="carrera.hora" class="text-zinc-500 text-xs">{{ carrera.hora }}</p>
        </div>

        <svg class="w-4 h-4 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
      </RouterLink>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useF1Store } from '@/stores/f1'

const f1Store         = useF1Store()
const temporadaActual = new Date().getFullYear()

function formatearFecha(fecha) {
  return new Date(fecha).toLocaleDateString('es-ES', { day: 'numeric', month: 'short' })
}

// ID de la próxima carrera (solo una recibe el badge "Próxima")
const idProxima = computed(() => {
  const hoy = new Date()
  hoy.setHours(0, 0, 0, 0)
  const futura = f1Store.carreras
    .filter(c => c.estado !== 'scored' && new Date(c.fecha) >= hoy)
    .sort((a, b) => new Date(a.fecha) - new Date(b.fecha))
  // Excluir la que esté en semana de carrera (tiene su propio badge)
  const sinSemana = futura.filter(c => !esSemanaCarrera(c.fecha))
  return sinSemana[0]?.id ?? null
})

function lunesDeSemana(fecha) {
  const d = new Date(fecha)
  d.setHours(0, 0, 0, 0)
  const dia = d.getDay() // 0=dom
  d.setDate(d.getDate() - (dia === 0 ? 6 : dia - 1))
  return d
}

function esSemanaCarrera(fecha) {
  const hoy   = new Date(); hoy.setHours(0, 0, 0, 0)
  const lunes  = lunesDeSemana(fecha)
  const domingo = new Date(lunes); domingo.setDate(lunes.getDate() + 6)
  return hoy >= lunes && hoy <= domingo
}

function esEnCurso(fecha) {
  if (!esSemanaCarrera(fecha)) return false
  const diaSemana = new Date().getDay() // 0=dom, 5=vie, 6=sab
  return diaSemana === 5 || diaSemana === 6 || diaSemana === 0
}

function calcularBadge(carrera) {
  const hoy          = new Date(); hoy.setHours(0, 0, 0, 0)
  const fechaCarrera = new Date(carrera.fecha)

  if (esEnCurso(carrera.fecha))       return 'active'
  if (esSemanaCarrera(carrera.fecha)) return 'semana'
  if (fechaCarrera < hoy)             return 'pasada'   // pasada aunque esté puntuada
  if (carrera.id === idProxima.value) return 'upcoming'
  return null // sin badge para el resto de futuras
}

const CLASES = {
  upcoming: 'badge-upcoming',
  active:   'badge-active',
  scored:   'badge-scored',
  pasada:   'badge-pasada',
  semana:   'badge-semana',
}
const ETIQUETAS = {
  upcoming: 'Próxima',
  active:   'En curso',
  scored:   'Puntuada',
  pasada:   'Pasada',
  semana:   'Semana de carrera',
}

function claseBadge(carrera)    { return CLASES[calcularBadge(carrera)]    ?? '' }
function etiquetaBadge(carrera) { return ETIQUETAS[calcularBadge(carrera)] ?? '' }

onMounted(() => f1Store.fetchCarreras())
</script>
