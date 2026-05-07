<template>
  <div class="space-y-6">
    <h2 class="text-xl font-bold text-white">Puntuaciones por Carrera</h2>

    <!-- Selector de carrera -->
    <div class="card flex items-center gap-4">
      <label class="text-zinc-400 text-sm whitespace-nowrap">Seleccionar carrera:</label>
      <select v-model="carreraSeleccionada" @change="cargarPuntuacion" class="flex-1 bg-zinc-800 text-white border border-zinc-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-red-500">
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
                <th class="px-4 py-3 text-center">Suma puntos_carrera</th>
                <th class="px-4 py-3 text-center">Suma qualy</th>
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
                <th class="px-4 py-3 text-center">Pos. carrera</th>
                <th class="px-4 py-3 text-center">Pos. qualy</th>
                <th class="px-4 py-3 text-center">Estado</th>
                <th class="px-4 py-3 text-center">VR</th>
                <th class="px-4 py-3 text-center">PD</th>
                <th class="px-4 py-3 text-center">Pts carrera</th>
                <th class="px-4 py-3 text-center">Pts velocidad</th>
                <th class="px-4 py-3 text-center">Total</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800">
              <tr v-for="r in datos.resultados" :key="r.piloto" class="hover:bg-zinc-800/40">
                <td class="px-4 py-3 text-white font-medium">{{ r.piloto }}</td>
                <td class="px-4 py-3 text-zinc-400">{{ r.escuderia }}</td>
                <td class="px-4 py-3 text-center text-zinc-300">
                  {{ r.posicion_final ?? '—' }}
                </td>
                <td class="px-4 py-3 text-center text-zinc-300">
                  {{ r.posicion_clasificacion ?? '—' }}
                </td>
                <td class="px-4 py-3 text-center">
                  <span v-if="r.estado && r.estado !== 'Terminó'" class="text-red-400 text-xs">{{ r.estado }}</span>
                  <span v-else class="text-zinc-500 text-xs">OK</span>
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

const carreras           = ref([])
const carreraSeleccionada = ref('')
const datos              = ref(null)
const cargando           = ref(false)

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

function formatearFecha(d) {
  return new Date(d).toLocaleDateString('es-ES', { day: 'numeric', month: 'short' })
}

onMounted(cargarCarreras)
</script>
