<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="$emit('cerrar')">
      <!-- Fondo -->
      <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="$emit('cerrar')" />

      <!-- Panel -->
      <div class="relative z-10 bg-zinc-900 border border-zinc-800 rounded-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto shadow-2xl">

        <!-- Header -->
        <div class="flex items-center gap-4 p-5 border-b border-zinc-800">
          <AvatarPiloto :piloto="piloto" size="xl" />
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
              <span class="text-zinc-500 text-xs font-mono uppercase">{{ piloto.codigo }}</span>
              <span v-if="piloto.numero" class="text-xs bg-zinc-800 text-zinc-400 px-1.5 py-0.5 rounded font-mono">#{{ piloto.numero }}</span>
            </div>
            <h2 class="text-xl font-black text-white">{{ piloto.nombre }} {{ piloto.apellido }}</h2>
            <div class="flex items-center gap-2 mt-1">
              <LogoEscuderia :escuderia="piloto.escuderia" size="xs" />
              <span class="text-zinc-400 text-sm">{{ piloto.escuderia?.nombre }}</span>
            </div>
          </div>
          <button @click="$emit('cerrar')" class="text-zinc-500 hover:text-white transition-colors flex-shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Info básica -->
        <div class="grid grid-cols-2 gap-3 p-5 border-b border-zinc-800">
          <div class="bg-zinc-800 rounded-lg p-3 text-center">
            <p class="text-zinc-500 text-xs mb-1">Nacionalidad</p>
            <p class="text-white font-medium text-sm">{{ piloto.nacionalidad || '—' }}</p>
          </div>
          <div class="bg-zinc-800 rounded-lg p-3 text-center">
            <p class="text-zinc-500 text-xs mb-1">Precio</p>
            <p class="text-red-400 font-bold">{{ formatearPrecio(piloto.precio) }}</p>
          </div>
        </div>

        <!-- Selector de carrera -->
        <div class="p-5 space-y-4">
          <select v-model="carreraSeleccionada" @change="cargarResultado"
            class="w-full bg-zinc-800 text-white border border-zinc-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-red-500">
            <option value="">— Selecciona una carrera —</option>
            <option v-for="c in carrerasPuntuadas" :key="c.id" :value="c.id">
              R{{ c.ronda }} · {{ c.nombre }}
            </option>
          </select>

          <!-- Cargando -->
          <div v-if="cargando" class="text-zinc-400 text-center py-6 text-sm">Cargando...</div>

          <!-- Sin datos -->
          <div v-else-if="carreraSeleccionada && !resultado" class="text-zinc-500 text-center py-6 text-sm">
            Este piloto no tiene resultado en esta carrera.
          </div>

          <!-- Desglose de puntuación -->
          <div v-else-if="resultado" class="space-y-3">
            <h3 class="text-xs text-zinc-500 uppercase tracking-wider font-medium">Resultado</h3>

            <!-- Posiciones -->
            <div class="grid grid-cols-2 gap-3">
              <div class="bg-zinc-800 rounded-lg p-3 text-center">
                <p class="text-zinc-500 text-xs mb-1">Posición carrera</p>
                <p class="font-black text-2xl" :class="clasePodio(resultado.posicion_final)">
                  {{ resultado.posicion_final ? 'P' + resultado.posicion_final : '—' }}
                </p>
              </div>
              <div class="bg-zinc-800 rounded-lg p-3 text-center">
                <p class="text-zinc-500 text-xs mb-1">Posición qualy</p>
                <p class="font-black text-2xl text-blue-400">
                  {{ resultado.posicion_clasificacion ? 'P' + resultado.posicion_clasificacion : '—' }}
                </p>
              </div>
            </div>

            <!-- Estado + bonus -->
            <div class="flex items-center gap-2 flex-wrap">
              <span class="text-xs px-2 py-1 rounded-full"
                :class="['Finalizó','Doblado'].includes(resultado.estado)
                  ? 'bg-green-500/20 text-green-400'
                  : 'bg-red-500/20 text-red-400'">
                {{ resultado.estado }}
              </span>
              <span v-if="resultado.vuelta_rapida" class="text-xs px-2 py-1 rounded-full bg-purple-500/20 text-purple-400">
                ⚡ Vuelta rápida
              </span>
              <span v-if="resultado.piloto_del_dia" class="text-xs px-2 py-1 rounded-full bg-amber-500/20 text-amber-400">
                ★ Piloto del día
              </span>
            </div>

            <!-- Puntos -->
            <h3 class="text-xs text-zinc-500 uppercase tracking-wider font-medium pt-1">Puntuación fantasy</h3>
            <div class="space-y-2">
              <div class="flex items-center justify-between py-2 border-b border-zinc-800">
                <span class="text-zinc-400 text-sm">🏁 Pts de carrera</span>
                <span class="font-bold" :class="resultado.puntos_carrera >= 0 ? 'text-green-400' : 'text-red-400'">
                  {{ resultado.puntos_carrera >= 0 ? '+' : '' }}{{ resultado.puntos_carrera }}
                </span>
              </div>
              <div class="flex items-center justify-between py-2 border-b border-zinc-800">
                <span class="text-zinc-400 text-sm">⚡ Pts de velocidad (qualy + VR + PD)</span>
                <span class="text-blue-400 font-bold">+{{ resultado.puntos_velocidad }}</span>
              </div>
              <div class="flex items-center justify-between py-2 bg-zinc-800 rounded-lg px-3">
                <span class="text-white font-semibold">Total fantasy</span>
                <span class="text-white font-black text-xl">{{ resultado.puntos_fantasy }}</span>
              </div>
            </div>

            <!-- Total temporada -->
            <div v-if="totalTemporada !== null" class="bg-red-500/10 border border-red-500/20 rounded-lg p-3 flex items-center justify-between">
              <span class="text-zinc-400 text-sm">Total temporada</span>
              <span class="text-red-400 font-black text-lg">{{ totalTemporada }} pts</span>
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
import AvatarPiloto  from '@/components/media/AvatarPiloto.vue'
import LogoEscuderia from '@/components/media/LogoEscuderia.vue'

const props = defineProps({
  piloto: { type: Object, required: true },
})
defineEmits(['cerrar'])

const f1Store             = useF1Store()
const carreraSeleccionada = ref('')
const resultado           = ref(null)
const totalTemporada      = ref(null)
const cargando            = ref(false)

const carrerasPuntuadas = computed(() =>
  f1Store.carreras.filter(c => c.estado === 'scored').sort((a, b) => b.ronda - a.ronda)
)

async function cargarResultado() {
  resultado.value = null
  if (!carreraSeleccionada.value) return
  cargando.value = true
  try {
    const { data } = await f1Service.getCarrera(carreraSeleccionada.value)
    resultado.value = data.resultados?.find(r => r.piloto?.id === props.piloto.id) ?? null
  } finally {
    cargando.value = false
  }
}

// Cargar total temporada al abrir
async function cargarTotal() {
  try {
    const { data } = await f1Service.getFantasyRanking()
    const encontrado = data.pilotos?.find(p => p.id === props.piloto.id)
    totalTemporada.value = encontrado?.total_fantasy_pts ?? 0
  } catch {}
}

// Bloquear scroll del body al abrir
watch(() => props.piloto, () => {
  carreraSeleccionada.value = ''
  resultado.value = null
  cargarTotal()
  f1Store.fetchCarreras()
}, { immediate: true })

function clasePodio(pos) {
  if (pos === 1) return 'text-yellow-400'
  if (pos === 2) return 'text-zinc-300'
  if (pos === 3) return 'text-amber-600'
  return 'text-zinc-400'
}

function formatearPrecio(precio) {
  return precio ? (precio / 1_000_000).toFixed(1) + 'M €' : '—'
}
</script>
