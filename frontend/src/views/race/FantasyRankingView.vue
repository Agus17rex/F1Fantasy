<template>
  <div class="space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h2 class="text-xl font-bold text-white">Ranking Fantasy</h2>
        <p class="text-zinc-500 text-sm mt-0.5">Puntos acumulados en la temporada</p>
      </div>
      <input v-model="busqueda" type="text" class="input w-56" placeholder="Buscar..." />
    </div>

    <div v-if="cargando" class="text-zinc-400 text-center py-16">Cargando ranking...</div>

    <div v-else-if="sinDatos" class="card text-center py-14 space-y-3">
      <p class="text-4xl">🏁</p>
      <p class="text-zinc-400 font-medium">Todavía no hay carreras puntuadas</p>
      <p class="text-zinc-600 text-sm">Los puntos aparecerán aquí después del primer gran premio</p>
    </div>

    <template v-else>
      <!-- ── Tabs ── -->
      <div class="flex gap-1 bg-zinc-900 rounded-xl p-1">
        <button v-for="tab in TABS" :key="tab.id"
          @click="tabActiva = tab.id"
          :class="tabActiva === tab.id ? 'bg-zinc-700 text-white shadow' : 'text-zinc-400 hover:text-white'"
          class="flex-1 py-2 text-sm font-medium rounded-lg transition-colors">
          {{ tab.icono }} {{ tab.label }}
        </button>
      </div>

      <!-- ── Pilotos ── -->
      <div v-if="tabActiva === 'pilotos'" class="card divide-y divide-zinc-800/60">
        <div v-for="(piloto, idx) in pilotosFiltrados" :key="piloto.id"
          class="flex items-center gap-4 py-3 first:pt-0 last:pb-0">
          <!-- Posición -->
          <div class="w-8 text-center flex-shrink-0">
            <span v-if="idx === 0" class="text-xl">🥇</span>
            <span v-else-if="idx === 1" class="text-xl">🥈</span>
            <span v-else-if="idx === 2" class="text-xl">🥉</span>
            <span v-else class="text-zinc-600 text-sm font-mono font-bold">{{ idx + 1 }}</span>
          </div>
          <!-- Barra de color + nombre -->
          <div class="w-1 h-9 rounded-full flex-shrink-0"
            :style="{ backgroundColor: piloto.escuderia?.color || '#555' }"></div>
          <div class="flex-1 min-w-0">
            <p class="text-white text-sm font-semibold">
              {{ piloto.first_name }} <span class="font-black">{{ piloto.last_name }}</span>
            </p>
            <p class="text-zinc-500 text-xs">{{ piloto.escuderia?.name || '—' }}</p>
          </div>
          <!-- Precio -->
          <p class="text-zinc-500 text-xs flex-shrink-0 hidden sm:block">{{ M(piloto.price) }}</p>
          <!-- Puntos + barra -->
          <div class="flex items-center gap-3 flex-shrink-0">
            <div class="w-24 hidden md:block">
              <div class="h-1.5 bg-zinc-800 rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all"
                  :style="{
                    width: maxPtsPilotos > 0 ? (piloto.total_fantasy_pts / maxPtsPilotos * 100) + '%' : '0%',
                    backgroundColor: piloto.escuderia?.color || '#e53e3e'
                  }"></div>
              </div>
            </div>
            <p class="font-black text-lg text-white tabular-nums w-14 text-right">
              {{ piloto.total_fantasy_pts }}
            </p>
            <p class="text-zinc-600 text-xs w-6">pts</p>
          </div>
        </div>
        <p v-if="pilotosFiltrados.length === 0" class="text-zinc-600 text-center py-6">Sin resultados</p>
      </div>

      <!-- ── Escuderías ── -->
      <div v-if="tabActiva === 'escuderias'" class="card divide-y divide-zinc-800/60">
        <div v-for="(esc, idx) in escuderiasFiltradas" :key="esc.id"
          class="flex items-center gap-4 py-3 first:pt-0 last:pb-0">
          <div class="w-8 text-center flex-shrink-0">
            <span v-if="idx === 0" class="text-xl">🥇</span>
            <span v-else-if="idx === 1" class="text-xl">🥈</span>
            <span v-else-if="idx === 2" class="text-xl">🥉</span>
            <span v-else class="text-zinc-600 text-sm font-mono font-bold">{{ idx + 1 }}</span>
          </div>
          <div class="w-4 h-4 rounded-full flex-shrink-0"
            :style="{ backgroundColor: esc.color || '#555' }"></div>
          <div class="flex-1 min-w-0">
            <p class="text-white text-sm font-bold">{{ esc.name }}</p>
            <p class="text-zinc-500 text-xs">{{ esc.nationality }}</p>
          </div>
          <p class="text-zinc-500 text-xs flex-shrink-0 hidden sm:block">{{ M(esc.price) }}</p>
          <div class="flex items-center gap-3 flex-shrink-0">
            <div class="w-24 hidden md:block">
              <div class="h-1.5 bg-zinc-800 rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all"
                  :style="{
                    width: maxPtsEscuderias > 0 ? (esc.total_fantasy_pts / maxPtsEscuderias * 100) + '%' : '0%',
                    backgroundColor: esc.color || '#e53e3e'
                  }"></div>
              </div>
            </div>
            <p class="font-black text-lg text-white tabular-nums w-14 text-right">
              {{ esc.total_fantasy_pts }}
            </p>
            <p class="text-zinc-600 text-xs w-6">pts</p>
          </div>
        </div>
        <p v-if="escuderiasFiltradas.length === 0" class="text-zinc-600 text-center py-6">Sin resultados</p>
      </div>

      <!-- ── Directores ── -->
      <div v-if="tabActiva === 'directores'" class="card divide-y divide-zinc-800/60">
        <div v-for="(dir, idx) in directoresFiltrados" :key="dir.id"
          class="flex items-center gap-4 py-3 first:pt-0 last:pb-0">
          <div class="w-8 text-center flex-shrink-0">
            <span v-if="idx === 0" class="text-xl">🥇</span>
            <span v-else-if="idx === 1" class="text-xl">🥈</span>
            <span v-else-if="idx === 2" class="text-xl">🥉</span>
            <span v-else class="text-zinc-600 text-sm font-mono font-bold">{{ idx + 1 }}</span>
          </div>
          <div class="w-8 h-8 rounded-full bg-zinc-800 flex items-center justify-center flex-shrink-0 text-sm"
            :style="dir.escuderia ? { borderLeft: '3px solid ' + dir.escuderia.color } : {}">
            🎩
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-white text-sm font-semibold">{{ dir.name }}</p>
            <p class="text-zinc-500 text-xs">{{ dir.escuderia?.name || '—' }}</p>
          </div>
          <p class="text-zinc-500 text-xs flex-shrink-0 hidden sm:block">{{ M(dir.price) }}</p>
          <div class="flex items-center gap-3 flex-shrink-0">
            <div class="w-24 hidden md:block">
              <div class="h-1.5 bg-zinc-800 rounded-full overflow-hidden">
                <div class="h-full bg-red-600 rounded-full transition-all"
                  :style="{ width: maxPtsDirectores > 0 ? (dir.total_fantasy_pts / maxPtsDirectores * 100) + '%' : '0%' }">
                </div>
              </div>
            </div>
            <p class="font-black text-lg text-white tabular-nums w-14 text-right">
              {{ dir.total_fantasy_pts }}
            </p>
            <p class="text-zinc-600 text-xs w-6">pts</p>
          </div>
        </div>
        <p v-if="directoresFiltrados.length === 0" class="text-zinc-600 text-center py-6">Sin resultados</p>
      </div>
    </template>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { f1Service } from '@/services/f1Service'

const cargando   = ref(false)
const busqueda   = ref('')
const tabActiva  = ref('pilotos')

const pilotos    = ref([])
const escuderias = ref([])
const directores = ref([])

const TABS = [
  { id: 'pilotos',    label: 'Pilotos',    icono: '🏎' },
  { id: 'escuderias', label: 'Escuderías', icono: '🏁' },
  { id: 'directores', label: 'Directores', icono: '🎩' },
]

const sinDatos = computed(() =>
  !cargando.value &&
  pilotos.value.every(p => p.total_fantasy_pts === 0) &&
  escuderias.value.every(e => e.total_fantasy_pts === 0)
)

// Máximos para las barras de progreso
const maxPtsPilotos    = computed(() => Math.max(1, ...pilotos.value.map(p => p.total_fantasy_pts)))
const maxPtsEscuderias = computed(() => Math.max(1, ...escuderias.value.map(e => e.total_fantasy_pts)))
const maxPtsDirectores = computed(() => Math.max(1, ...directores.value.map(d => d.total_fantasy_pts)))

// Filtros con búsqueda
const pilotosFiltrados = computed(() => {
  const q = busqueda.value.toLowerCase()
  return pilotos.value.filter(p =>
    !q ||
    p.first_name?.toLowerCase().includes(q) ||
    p.last_name?.toLowerCase().includes(q) ||
    p.escuderia?.name?.toLowerCase().includes(q)
  )
})

const escuderiasFiltradas = computed(() => {
  const q = busqueda.value.toLowerCase()
  return escuderias.value.filter(e => !q || e.name?.toLowerCase().includes(q))
})

const directoresFiltrados = computed(() => {
  const q = busqueda.value.toLowerCase()
  return directores.value.filter(d =>
    !q ||
    d.name?.toLowerCase().includes(q) ||
    d.escuderia?.name?.toLowerCase().includes(q)
  )
})

function M(v) { return v != null ? (v / 1_000_000).toFixed(1) + 'M' : '—' }

onMounted(async () => {
  cargando.value = true
  try {
    const { data } = await f1Service.getFantasyRanking()
    pilotos.value    = data.pilotos
    escuderias.value = data.escuderias
    directores.value = data.directores
  } finally {
    cargando.value = false
  }
})
</script>
