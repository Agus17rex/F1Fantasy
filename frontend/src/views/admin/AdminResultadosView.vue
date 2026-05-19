<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <h2 class="text-xl font-bold text-white">Puntuaciones por Carrera</h2>
      <button @click="recalcularTodo" :disabled="recalculando"
        class="flex items-center gap-2 px-4 py-2 rounded-lg bg-amber-600 hover:bg-amber-500 disabled:opacity-50 text-white text-sm font-medium transition-colors">
        <svg v-if="recalculando" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
        </svg>
        {{ recalculando ? 'Recalculando...' : 'Recalcular todo' }}
      </button>
    </div>

    <p class="text-zinc-500 text-xs -mt-2">
      "Recalcular todo" aplica todas las reglas desde cero (incluye adelantamientos, superar compañero y penalizaciones manuales).
    </p>

    <!-- Selector de carrera -->
    <div class="card flex flex-col sm:flex-row sm:items-center gap-3">
      <label class="text-zinc-400 text-sm whitespace-nowrap">Seleccionar carrera:</label>
      <select v-model="carreraSeleccionada" @change="cargarPuntuacion"
        class="flex-1 bg-zinc-800 text-white border border-zinc-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-red-500">
        <option value="">— Elige una carrera —</option>
        <option v-for="c in carreras" :key="c.id" :value="c.id">
          R{{ c.ronda }} · {{ c.nombre }} ({{ formatearFecha(c.fecha) }})
          {{ c.estado === 'scored' ? '✓' : '' }}
        </option>
      </select>
    </div>

    <div v-if="cargando" class="text-zinc-400 text-center py-10">Cargando puntuaciones...</div>

    <template v-else-if="datos">

      <!-- Resumen por escudería / coche -->
      <div>
        <h3 class="text-base font-semibold text-white mb-3">🏆 Escuderías y 🏎️ Coches</h3>
        <div class="overflow-x-auto rounded-xl border border-zinc-800">
          <table class="w-full text-sm">
            <thead class="bg-zinc-900 text-zinc-400 text-xs uppercase tracking-wider">
              <tr>
                <th class="px-4 py-3 text-left">Escudería</th>
                <th class="px-4 py-3 text-center">Suma pts carrera</th>
                <th class="px-4 py-3 text-center">Suma pts qualy</th>
                <th class="px-4 py-3 text-center">Pts escudería (÷2)</th>
                <th class="px-4 py-3 text-center">Pts coche (÷2)</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800">
              <tr v-for="e in datos.por_escuderia" :key="e.escuderia" class="hover:bg-zinc-800/40">
                <td class="px-4 py-3 text-white font-medium">{{ e.escuderia }}</td>
                <td class="px-4 py-3 text-center text-zinc-300">{{ e.suma_carrera }}</td>
                <td class="px-4 py-3 text-center text-zinc-300">{{ e.suma_qualy }}</td>
                <td class="px-4 py-3 text-center">
                  <span class="font-bold text-green-400">{{ e.pts_escuderia }}</span>
                </td>
                <td class="px-4 py-3 text-center">
                  <span class="font-bold text-blue-400">{{ e.pts_coche }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Resultados por piloto -->
      <div>
        <h3 class="text-base font-semibold text-white mb-3">🧑‍🦺 Pilotos</h3>
        <div class="overflow-x-auto rounded-xl border border-zinc-800">
          <table class="w-full text-sm">
            <thead class="bg-zinc-900 text-zinc-400 text-xs uppercase tracking-wider">
              <tr>
                <th class="px-4 py-3 text-left">Piloto</th>
                <th class="px-4 py-3 text-left">Escudería</th>
                <th class="px-4 py-3 text-center">Salida</th>
                <th class="px-4 py-3 text-center">Llegada</th>
                <th class="px-4 py-3 text-center">Qualy</th>
                <th class="px-4 py-3 text-center">Estado</th>
                <th class="px-4 py-3 text-center">VR</th>
                <th class="px-4 py-3 text-center">PD</th>
                <th class="px-4 py-3 text-center">Pts carrera</th>
                <th class="px-4 py-3 text-center">Pts vel.</th>
                <th class="px-4 py-3 text-center">Total</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800">
              <tr v-for="r in datos.resultados" :key="r.id" class="hover:bg-zinc-800/40">
                <td class="px-4 py-3 text-white font-medium whitespace-nowrap">{{ r.piloto }}</td>
                <td class="px-4 py-3 text-zinc-400 whitespace-nowrap">{{ r.escuderia }}</td>
                <td class="px-4 py-3 text-center text-zinc-300">{{ r.posicion_salida ?? '—' }}</td>
                <td class="px-4 py-3 text-center text-zinc-300">{{ r.posicion_final ?? '—' }}</td>
                <td class="px-4 py-3 text-center text-zinc-300">{{ r.posicion_clasificacion ?? '—' }}</td>
                <td class="px-4 py-3 text-center">
                  <span class="text-xs"
                    :class="{
                      'text-green-400': r.estado === 'Finalizó',
                      'text-zinc-400':  r.estado === 'Doblado',
                      'text-red-400':   r.estado && r.estado !== 'Finalizó' && r.estado !== 'Doblado'
                    }">
                    {{ r.estado || '—' }}
                  </span>
                </td>
                <td class="px-4 py-3 text-center">
                  <span v-if="r.vuelta_rapida" class="text-purple-400">⚡</span>
                  <span v-else class="text-zinc-700">—</span>
                </td>
                <td class="px-4 py-3 text-center">
                  <span v-if="r.piloto_del_dia" class="text-amber-400">★</span>
                  <span v-else class="text-zinc-700">—</span>
                </td>
                <td class="px-4 py-3 text-center">
                  <span :class="r.puntos_carrera >= 0 ? 'text-green-400' : 'text-red-400'" class="font-medium">
                    {{ r.puntos_carrera }}
                  </span>
                </td>
                <td class="px-4 py-3 text-center">
                  <span class="text-blue-400 font-medium">{{ r.puntos_velocidad }}</span>
                </td>
                <td class="px-4 py-3 text-center">
                  <span class="text-white font-bold">{{ r.puntos_fantasy }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <p class="text-zinc-600 text-xs mt-2 px-1">VR = Vuelta rápida · PD = Piloto del día</p>
      </div>

    </template>

    <div v-else-if="carreraSeleccionada" class="card text-zinc-400 text-center py-8">
      Esta carrera aún no tiene puntuaciones calculadas.
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { adminService } from '@/services/adminService'

const carreras            = ref([])
const carreraSeleccionada = ref('')
const datos               = ref(null)
const cargando            = ref(false)
const recalculando        = ref(false)

async function cargarCarreras() {
  const { data } = await adminService.getCarreras()
  carreras.value = data
}

async function cargarPuntuacion() {
  if (!carreraSeleccionada.value) { datos.value = null; return }
  cargando.value = true
  datos.value    = null
  try {
    const { data } = await adminService.getPuntuacionCarrera(carreraSeleccionada.value)
    datos.value = data
  } catch {
    datos.value = null
  } finally {
    cargando.value = false
  }
}

async function recalcularTodo() {
  if (!confirm('¿Recalcular todos los puntos desde cero? Esto puede tardar unos segundos.')) return
  recalculando.value = true
  try {
    await adminService.recalcularTodo()
    alert('✓ Recálculo completado correctamente')
    if (carreraSeleccionada.value) await cargarPuntuacion()
  } catch (e) {
    alert('Error al recalcular: ' + (e.response?.data?.message ?? e.message))
  } finally {
    recalculando.value = false
  }
}

function formatearFecha(d) {
  return new Date(d).toLocaleDateString('es-ES', { day: 'numeric', month: 'short' })
}

onMounted(cargarCarreras)
</script>
