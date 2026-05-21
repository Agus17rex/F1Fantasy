<template>
  <div class="space-y-6">
    <h2 class="text-xl font-bold text-white">Puntuaciones</h2>

    <!-- Selector de carrera -->
    <div class="flex flex-wrap gap-2">
      <button
        v-for="c in opcionesCarrera" :key="c.id"
        @click="seleccionar(c.id)"
        :class="carreraSeleccionada === c.id
          ? 'bg-red-600 text-white'
          : 'bg-zinc-800 text-zinc-400 hover:text-white hover:bg-zinc-700'"
        class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors">
        {{ c.label }}
      </button>
    </div>

    <div v-if="cargando" class="text-zinc-400 text-center py-16">Cargando puntuaciones...</div>

    <template v-else-if="datos">

      <!-- Pestañas -->
      <div class="flex flex-wrap gap-2">
        <button v-for="tab in tabs" :key="tab.id"
          @click="tabActiva = tab.id"
          :class="tabActiva === tab.id
            ? 'bg-red-600 text-white'
            : 'bg-zinc-800 text-zinc-400 hover:text-white hover:bg-zinc-700'"
          class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">
          {{ tab.label }}
        </button>
      </div>

      <!-- ── Qualy Pilotos ── -->
      <div v-if="tabActiva === 'qualy'">
        <div class="overflow-x-auto rounded-xl border border-zinc-800">
          <table class="w-full text-sm">
            <thead class="bg-zinc-900 text-zinc-400 text-xs uppercase tracking-wider">
              <tr>
                <th class="px-4 py-3 text-center w-10">#</th>
                <th class="px-4 py-3 text-left">Piloto</th>
                <th class="px-4 py-3 text-center">Pos. Qualy</th>
                <th class="px-4 py-3 text-center">Pts Qualy</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800">
              <tr v-for="(r, idx) in resultadosOrdenadosPorQualy" :key="r.id"
                class="hover:bg-zinc-800/40"
                :style="{ borderLeft: '3px solid ' + (r.escuderia.color || '#3f3f46') }">
                <td class="px-4 py-3 text-center text-zinc-500 text-xs font-mono">{{ idx + 1 }}</td>
                <td class="px-4 py-3">
                  <div class="flex items-center gap-3">
                    <AvatarPiloto :piloto="r.piloto" size="md" />
                    <div>
                      <p class="text-white font-semibold leading-tight">{{ r.piloto.nombre }} {{ r.piloto.apellido }}</p>
                      <div class="flex items-center gap-1 mt-0.5">
                        <LogoEscuderia :escuderia="r.escuderia" size="xs" />
                        <span class="text-zinc-500 text-xs">{{ r.escuderia.nombre }}</span>
                      </div>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-3 text-center">
                  <span class="text-zinc-300 font-mono text-base font-bold">
                    {{ r.posicion_clasificacion ? 'P' + r.posicion_clasificacion : '—' }}
                  </span>
                </td>
                <td class="px-4 py-3 text-center">
                  <span class="font-black text-lg" :style="{ color: r.escuderia.color || '#60a5fa' }">
                    {{ r.puntos_qualy }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ── Carrera Pilotos ── -->
      <div v-if="tabActiva === 'carrera'">
        <div class="overflow-x-auto rounded-xl border border-zinc-800">
          <table class="w-full text-sm">
            <thead class="bg-zinc-900 text-zinc-400 text-xs uppercase tracking-wider">
              <tr>
                <th class="px-4 py-3 text-left">Piloto</th>
                <th v-if="!esTotalTemporada" class="px-4 py-3 text-center">Salida</th>
                <th v-if="!esTotalTemporada" class="px-4 py-3 text-center">Llegada</th>
                <th v-if="!esTotalTemporada" class="px-4 py-3 text-center">Estado</th>
                <th v-if="!esTotalTemporada" class="px-4 py-3 text-center">VR</th>
                <th class="px-4 py-3 text-center">Pts Carrera</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800">
              <tr v-for="r in resultadosOrdenadosPorCarrera" :key="r.id"
                class="hover:bg-zinc-800/40"
                :style="{ borderLeft: '3px solid ' + (r.escuderia.color || '#3f3f46') }">
                <td class="px-4 py-3">
                  <div class="flex items-center gap-3">
                    <AvatarPiloto :piloto="r.piloto" size="md" />
                    <div>
                      <p class="text-white font-semibold leading-tight">{{ r.piloto.nombre }} {{ r.piloto.apellido }}</p>
                      <div class="flex items-center gap-1 mt-0.5">
                        <LogoEscuderia :escuderia="r.escuderia" size="xs" />
                        <span class="text-zinc-500 text-xs">{{ r.escuderia.nombre }}</span>
                      </div>
                    </div>
                  </div>
                </td>
                <td v-if="!esTotalTemporada" class="px-4 py-3 text-center text-zinc-400 font-mono">{{ r.posicion_salida ?? '—' }}</td>
                <td v-if="!esTotalTemporada" class="px-4 py-3 text-center text-zinc-300 font-mono font-bold">{{ r.posicion_final ?? '—' }}</td>
                <td v-if="!esTotalTemporada" class="px-4 py-3 text-center">
                  <span class="text-xs px-2 py-0.5 rounded-full"
                    :class="{
                      'bg-green-500/15 text-green-400': r.estado === 'Finalizó',
                      'bg-zinc-700/50 text-zinc-400':  r.estado === 'Doblado',
                      'bg-red-500/15 text-red-400':    r.estado && r.estado !== 'Finalizó' && r.estado !== 'Doblado'
                    }">
                    {{ r.estado || '—' }}
                  </span>
                </td>
                <td v-if="!esTotalTemporada" class="px-4 py-3 text-center">
                  <span v-if="r.vuelta_rapida" class="text-purple-400 text-base">⚡</span>
                  <span v-else class="text-zinc-700">—</span>
                </td>
                <td class="px-4 py-3 text-center">
                  <span class="font-black text-lg"
                    :class="r.puntos_carrera >= 0 ? 'text-green-400' : 'text-red-400'">
                    {{ r.puntos_carrera }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ── Total Pilotos ── -->
      <div v-if="tabActiva === 'total'">
        <div class="overflow-x-auto rounded-xl border border-zinc-800">
          <table class="w-full text-sm">
            <thead class="bg-zinc-900 text-zinc-400 text-xs uppercase tracking-wider">
              <tr>
                <th class="px-4 py-3 text-center w-10">#</th>
                <th class="px-4 py-3 text-left">Piloto</th>
                <th class="px-4 py-3 text-center">Pts Carrera</th>
                <th class="px-4 py-3 text-center">Pts Qualy</th>
                <th class="px-4 py-3 text-center">Bonus</th>
                <th class="px-4 py-3 text-center">Total</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800">
              <tr v-for="(r, idx) in resultadosOrdenadosPorTotal" :key="r.id"
                class="hover:bg-zinc-800/40"
                :style="{ borderLeft: '3px solid ' + (r.escuderia.color || '#3f3f46') }">
                <td class="px-4 py-3 text-center text-zinc-500 text-xs font-mono">{{ idx + 1 }}</td>
                <td class="px-4 py-3">
                  <div class="flex items-center gap-3">
                    <AvatarPiloto :piloto="r.piloto" size="md" />
                    <div>
                      <p class="text-white font-semibold leading-tight">{{ r.piloto.nombre }} {{ r.piloto.apellido }}</p>
                      <div class="flex items-center gap-1 mt-0.5">
                        <LogoEscuderia :escuderia="r.escuderia" size="xs" />
                        <span class="text-zinc-500 text-xs">{{ r.escuderia.nombre }}</span>
                      </div>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-3 text-center">
                  <span :class="r.puntos_carrera >= 0 ? 'text-green-400' : 'text-red-400'" class="font-bold">
                    {{ r.puntos_carrera }}
                  </span>
                </td>
                <td class="px-4 py-3 text-center text-blue-400 font-bold">{{ r.puntos_qualy }}</td>
                <td class="px-4 py-3 text-center">
                  <span v-if="bonusPiloto(r) > 0" class="text-amber-400 font-bold">+{{ bonusPiloto(r) }}</span>
                  <span v-else class="text-zinc-700">—</span>
                </td>
                <td class="px-4 py-3 text-center">
                  <span class="font-black text-xl" :style="{ color: r.escuderia.color || '#fff' }">
                    {{ r.puntos_fantasy }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <p class="text-zinc-600 text-xs mt-2 px-1">Bonus = superar al compañero en carrera y/o qualy</p>
      </div>

      <!-- ── Escuderías ── -->
      <div v-if="tabActiva === 'escuderias'">
        <div class="overflow-x-auto rounded-xl border border-zinc-800">
          <table class="w-full text-sm">
            <thead class="bg-zinc-900 text-zinc-400 text-xs uppercase tracking-wider">
              <tr>
                <th class="px-4 py-3 text-left">Escudería</th>
                <th class="px-4 py-3 text-center">Suma pts carrera</th>
                <th class="px-4 py-3 text-center">Pts escudería (÷2)</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800">
              <tr v-for="e in escuderiasOrdenadas" :key="e.escuderia.nombre"
                class="hover:bg-zinc-800/40"
                :style="{ borderLeft: '3px solid ' + (e.escuderia.color || '#3f3f46') }">
                <td class="px-4 py-3">
                  <div class="flex items-center gap-3">
                    <LogoEscuderia :escuderia="e.escuderia" size="md" />
                    <span class="text-white font-semibold">{{ e.escuderia.nombre }}</span>
                  </div>
                </td>
                <td class="px-4 py-3 text-center text-zinc-400 font-mono">{{ e.suma_carrera }}</td>
                <td class="px-4 py-3 text-center">
                  <span class="font-black text-xl" :style="{ color: e.escuderia.color || '#4ade80' }">
                    {{ e.pts_escuderia }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ── Coches ── -->
      <div v-if="tabActiva === 'coches'">
        <div class="overflow-x-auto rounded-xl border border-zinc-800">
          <table class="w-full text-sm">
            <thead class="bg-zinc-900 text-zinc-400 text-xs uppercase tracking-wider">
              <tr>
                <th class="px-4 py-3 text-left">Coche</th>
                <th class="px-4 py-3 text-center">Suma pts qualy</th>
                <th class="px-4 py-3 text-center">Pts coche (÷2)</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800">
              <tr v-for="e in cochesOrdenados" :key="e.escuderia.nombre"
                class="hover:bg-zinc-800/40"
                :style="{ borderLeft: '3px solid ' + (e.escuderia.color || '#3f3f46') }">
                <td class="px-4 py-3">
                  <div class="flex items-center gap-3">
                    <ImagenCoche v-if="e.coche" :coche="e.coche" size="lg" />
                    <LogoEscuderia v-else :escuderia="e.escuderia" size="md" />
                    <div>
                      <p class="text-white font-semibold">{{ e.coche?.nombre ?? e.escuderia.nombre }}</p>
                      <div class="flex items-center gap-1 mt-0.5">
                        <LogoEscuderia :escuderia="e.escuderia" size="xs" />
                        <span class="text-zinc-500 text-xs">{{ e.escuderia.nombre }}</span>
                      </div>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-3 text-center text-zinc-400 font-mono">{{ e.suma_qualy }}</td>
                <td class="px-4 py-3 text-center">
                  <span class="font-black text-xl" :style="{ color: e.escuderia.color || '#60a5fa' }">
                    {{ e.pts_coche }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </template>

    <div v-else-if="!cargando && carreraSeleccionada !== null" class="card text-zinc-400 text-center py-8">
      Aún no hay carreras puntuadas.
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { f1Service } from '@/services/f1Service'
import AvatarPiloto  from '@/components/media/AvatarPiloto.vue'
import LogoEscuderia from '@/components/media/LogoEscuderia.vue'
import ImagenCoche   from '@/components/media/ImagenCoche.vue'

const carreras            = ref([])
const carreraSeleccionada = ref(0)   // 0 = total temporada (R0)
const datos               = ref(null)
const cargando            = ref(false)
const tabActiva           = ref('total')

const tabs = [
  { id: 'qualy',      label: '🏎️ Qualy pilotos' },
  { id: 'carrera',    label: '🏁 Carrera pilotos' },
  { id: 'total',      label: '🧑‍🦺 Total pilotos' },
  { id: 'escuderias', label: '🏆 Escuderías' },
  { id: 'coches',     label: '🔧 Coches' },
]

const esTotalTemporada = computed(() => carreraSeleccionada.value === 0)

// R0 siempre primero, luego las carreras puntuadas
const opcionesCarrera = computed(() => [
  { id: 0, label: 'R0 · Total' },
  ...carreras.value
    .filter(c => c.estado === 'scored')
    .sort((a, b) => a.ronda - b.ronda)
    .map(c => ({ id: c.id, label: `R${c.ronda} · ${c.nombre.replace('Grand Prix', 'GP')}` })),
])

const bonusPiloto = (r) => r.puntos_fantasy - r.puntos_carrera - r.puntos_qualy

const resultadosOrdenadosPorQualy = computed(() =>
  [...(datos.value?.resultados ?? [])].sort((a, b) =>
    (a.posicion_clasificacion ?? 99) - (b.posicion_clasificacion ?? 99)
  )
)
const resultadosOrdenadosPorCarrera = computed(() =>
  [...(datos.value?.resultados ?? [])].sort((a, b) =>
    esTotalTemporada.value
      ? b.puntos_carrera - a.puntos_carrera
      : (a.posicion_final ?? 99) - (b.posicion_final ?? 99)
  )
)
const resultadosOrdenadosPorTotal = computed(() =>
  [...(datos.value?.resultados ?? [])].sort((a, b) => b.puntos_fantasy - a.puntos_fantasy)
)
const escuderiasOrdenadas = computed(() =>
  [...(datos.value?.por_escuderia ?? [])].sort((a, b) => b.pts_escuderia - a.pts_escuderia)
)
const cochesOrdenados = computed(() =>
  [...(datos.value?.por_escuderia ?? [])].sort((a, b) => b.pts_coche - a.pts_coche)
)

async function seleccionar(id) {
  carreraSeleccionada.value = id
  await cargarDatos()
}

async function cargarDatos() {
  cargando.value = true
  datos.value    = null
  try {
    const { data } = carreraSeleccionada.value === 0
      ? await f1Service.getPuntuacionTemporada()
      : await f1Service.getPuntuacionCarrera(carreraSeleccionada.value)
    datos.value = data
  } catch {
    datos.value = null
  } finally {
    cargando.value = false
  }
}

onMounted(async () => {
  const { data } = await f1Service.getCarreras()
  carreras.value = data
  // Cargar R0 por defecto
  await cargarDatos()
})
</script>
