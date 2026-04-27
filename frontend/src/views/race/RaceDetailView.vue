<template>
  <div class="space-y-6">
    <div v-if="cargando" class="text-zinc-400 text-center py-10">Cargando carrera...</div>

    <template v-else-if="carrera">
      <!-- Header -->
      <div class="card border-l-4 border-l-red-500">
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="text-zinc-500 text-xs uppercase tracking-wider font-medium mb-1">
              Ronda {{ carrera.ronda }} · Temporada {{ carrera.temporada }}
            </p>
            <h2 class="text-2xl font-black text-white">{{ carrera.nombre }}</h2>
            <p class="text-zinc-400 mt-1">
              {{ carrera.circuito?.nombre }} · {{ carrera.circuito?.ubicacion }}, {{ carrera.circuito?.pais }}
            </p>
            <p class="text-zinc-500 text-sm mt-0.5">{{ formatearFecha(carrera.fecha) }}</p>
          </div>
          <span :class="claseBadge(carrera.estado)" class="flex-shrink-0">{{ etiquetaEstado(carrera.estado) }}</span>
        </div>
      </div>

      <!-- Resultados -->
      <div v-if="carrera.resultados?.length > 0" class="card">
        <h3 class="font-semibold text-white mb-4">Resultados de carrera</h3>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-zinc-500 text-xs uppercase tracking-wider border-b border-zinc-800">
                <th class="text-left pb-3 w-10">Pos</th>
                <th class="text-left pb-3">Piloto</th>
                <th class="text-left pb-3">Escudería</th>
                <th class="text-center pb-3">Salida</th>
                <th class="text-center pb-3">Estado</th>
                <th class="text-right pb-3">Pts F1</th>
                <th class="text-right pb-3 text-red-400">Pts Fantasy</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800">
              <tr v-for="resultado in resultadosOrdenados" :key="resultado.id" class="hover:bg-zinc-800/30 transition-colors">
                <td class="py-3 pr-3">
                  <span :class="clasePodio(resultado.posicion_final)" class="font-bold text-sm">
                    {{ resultado.posicion_final || '—' }}
                  </span>
                </td>
                <td class="py-3">
                  <div class="flex items-center gap-2">
                    <div class="w-1 h-5 rounded-full" :style="{ backgroundColor: resultado.escuderia?.color || '#888' }"></div>
                    <div>
                      <p class="text-white font-medium">{{ resultado.piloto?.nombre }} {{ resultado.piloto?.apellido }}</p>
                      <div class="flex items-center gap-1.5 mt-0.5">
                        <span v-if="resultado.vuelta_rapida" class="text-purple-400 text-xs">⚡ V.Rápida</span>
                        <span v-if="resultado.piloto_del_dia" class="text-yellow-400 text-xs">⭐ Piloto del día</span>
                      </div>
                    </div>
                  </div>
                </td>
                <td class="py-3 text-zinc-400">{{ resultado.escuderia?.nombre }}</td>
                <td class="py-3 text-center text-zinc-500">{{ resultado.posicion_salida || '—' }}</td>
                <td class="py-3 text-center">
                  <span :class="['Finalizó', 'Doblado'].includes(resultado.estado) ? 'text-green-400' : 'text-red-400'" class="text-xs">
                    {{ resultado.estado }}
                  </span>
                </td>
                <td class="py-3 text-right text-zinc-300 font-mono">{{ resultado.puntos_oficiales }}</td>
                <td class="py-3 text-right font-bold" :class="resultado.puntos_fantasy > 0 ? 'text-red-400' : 'text-zinc-600'">
                  {{ resultado.puntos_fantasy || '—' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-else class="card text-center py-8">
        <p class="text-zinc-500">Los resultados de esta carrera aún no están disponibles</p>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { f1Service } from '@/services/f1Service'

const route   = useRoute()
const carrera = ref(null)
const cargando = ref(false)

const resultadosOrdenados = computed(() =>
  [...(carrera.value?.resultados || [])].sort((a, b) => (a.posicion_final || 99) - (b.posicion_final || 99))
)

function formatearFecha(d) {
  return new Date(d).toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
}

function claseBadge(estado) {
  return { upcoming: 'badge-upcoming', active: 'badge-active', scored: 'badge-scored' }[estado] || 'badge-scored'
}

function etiquetaEstado(estado) {
  return { upcoming: 'Próxima', active: 'En curso', scored: 'Puntuada' }[estado] || estado
}

function clasePodio(pos) {
  if (pos === 1) return 'text-yellow-400'
  if (pos === 2) return 'text-zinc-300'
  if (pos === 3) return 'text-amber-600'
  return 'text-zinc-400'
}

onMounted(async () => {
  cargando.value = true
  try {
    const { data } = await f1Service.getCarrera(route.params.id)
    carrera.value = data
  } finally {
    cargando.value = false
  }
})
</script>
