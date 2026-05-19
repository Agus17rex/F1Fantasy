<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4" @click.self="$emit('cerrar')">
      <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="$emit('cerrar')" />

      <div class="relative z-10 bg-zinc-900 border border-zinc-800 rounded-2xl w-full max-w-lg max-h-[88vh] overflow-y-auto shadow-2xl">

        <!-- Header -->
        <div class="flex items-center gap-4 p-5 border-b border-zinc-800">
          <LogoEscuderia :escuderia="escuderia" size="xl" />
          <div class="flex-1 min-w-0">
            <h2 class="text-xl font-black text-white">{{ escuderia.nombre }}</h2>
            <p class="text-zinc-400 text-sm">{{ escuderia.nacionalidad }}</p>
          </div>
          <button @click="$emit('cerrar')" class="text-zinc-500 hover:text-white transition-colors flex-shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Info -->
        <div class="grid grid-cols-2 gap-3 p-5 border-b border-zinc-800">
          <div class="bg-zinc-800 rounded-lg p-3 text-center">
            <p class="text-zinc-500 text-xs mb-1">Precio</p>
            <p class="text-red-400 font-bold">{{ formatearPrecio(escuderia.precio) }}</p>
          </div>
          <div class="bg-zinc-800 rounded-lg p-3 text-center">
            <p class="text-zinc-500 text-xs mb-1">Total temporada</p>
            <p class="text-green-400 font-bold">{{ totalTemporada }} pts</p>
          </div>
        </div>

        <!-- Selector de carrera -->
        <div class="p-5 space-y-4">
          <select v-model="carreraSeleccionada" @change="cargarResultados"
            class="w-full bg-zinc-800 text-white border border-zinc-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-red-500">
            <option value="">— Selecciona una carrera —</option>
            <option v-for="c in carrerasPuntuadas" :key="c.id" :value="c.id">
              R{{ c.ronda }} · {{ c.nombre }}
            </option>
          </select>

          <div v-if="cargando" class="text-zinc-400 text-center py-6 text-sm">Cargando...</div>

          <div v-else-if="carreraSeleccionada && pilotosCarrera.length === 0" class="text-zinc-500 text-center py-6 text-sm">
            Sin datos para esta carrera.
          </div>

          <div v-else-if="pilotosCarrera.length" class="space-y-3">
            <h3 class="text-xs text-zinc-500 uppercase tracking-wider font-medium">Pilotos en carrera</h3>

            <div v-for="r in pilotosCarrera" :key="r.piloto?.id"
              class="bg-zinc-800 rounded-lg p-3 space-y-2">
              <p class="text-white font-semibold">{{ r.piloto?.nombre }} {{ r.piloto?.apellido }}</p>
              <div class="grid grid-cols-3 gap-2 text-center text-xs">
                <div>
                  <p class="text-zinc-500">Carrera</p>
                  <p class="text-zinc-300 font-medium">{{ r.posicion_final ? 'P' + r.posicion_final : '—' }}</p>
                </div>
                <div>
                  <p class="text-zinc-500">Qualy</p>
                  <p class="text-zinc-300 font-medium">{{ r.posicion_clasificacion ? 'P' + r.posicion_clasificacion : '—' }}</p>
                </div>
                <div>
                  <p class="text-zinc-500">Pts carrera</p>
                  <p class="font-bold" :class="r.puntos_carrera >= 0 ? 'text-green-400' : 'text-red-400'">{{ r.puntos_carrera }}</p>
                </div>
              </div>
            </div>

            <!-- Puntos escudería -->
            <div class="bg-zinc-800 rounded-lg p-4 space-y-2">
              <div class="flex justify-between text-sm">
                <span class="text-zinc-400">Suma pts carrera</span>
                <span class="text-zinc-300">{{ sumaCarrera }}</span>
              </div>
              <div class="flex justify-between items-center pt-2 border-t border-zinc-700">
                <span class="text-white font-semibold">Pts escudería (÷2)</span>
                <span class="text-green-400 font-black text-xl">{{ ptsEscuderia }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { f1Service } from '@/services/f1Service'
import { useF1Store } from '@/stores/f1'
import LogoEscuderia from '@/components/media/LogoEscuderia.vue'

const props = defineProps({ escuderia: { type: Object, required: true } })
defineEmits(['cerrar'])

const f1Store             = useF1Store()
const carreraSeleccionada = ref('')
const resultados          = ref([])
const totalTemporada      = ref(0)
const cargando            = ref(false)

const carrerasPuntuadas = computed(() =>
  f1Store.carreras.filter(c => c.estado === 'scored').sort((a, b) => b.ronda - a.ronda)
)

const pilotosCarrera = computed(() =>
  resultados.value.filter(r => r.escuderia?.id === props.escuderia.id)
)

const sumaCarrera  = computed(() => pilotosCarrera.value.reduce((s, r) => s + (r.puntos_carrera || 0), 0))
const ptsEscuderia = computed(() => Math.round(sumaCarrera.value / 2))

async function cargarResultados() {
  resultados.value = []
  if (!carreraSeleccionada.value) return
  cargando.value = true
  try {
    const { data } = await f1Service.getCarrera(carreraSeleccionada.value)
    resultados.value = data.resultados || []
  } finally {
    cargando.value = false
  }
}

async function cargarTotal() {
  try {
    const { data } = await f1Service.getFantasyRanking()
    const e = data.escuderias?.find(e => e.id === props.escuderia.id)
    totalTemporada.value = e?.total_fantasy_pts ?? 0
  } catch {}
}

watch(() => props.escuderia, () => {
  carreraSeleccionada.value = ''
  resultados.value = []
  cargarTotal()
  f1Store.fetchCarreras()
}, { immediate: true })

function formatearPrecio(p) { return p ? (p / 1_000_000).toFixed(1) + 'M €' : '—' }
</script>
