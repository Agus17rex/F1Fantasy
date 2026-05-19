<template>
  <div class="max-w-5xl mx-auto space-y-8">

    <!-- ── Hero ── -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-red-600/20 via-zinc-900 to-zinc-900 border border-red-500/20 px-6 py-10 text-center">
      <!-- decoración de fondo -->
      <div class="absolute -top-12 -right-12 w-48 h-48 bg-red-600/10 rounded-full blur-3xl pointer-events-none" />
      <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-red-600/10 rounded-full blur-2xl pointer-events-none" />

      <p class="text-red-400 text-sm font-semibold uppercase tracking-widest mb-3">Sistema de puntuación</p>
      <h1 class="text-3xl sm:text-4xl font-extrabold text-white mb-3">¿Cómo se puntúa?</h1>
      <p class="text-zinc-400 text-sm sm:text-base max-w-xl mx-auto leading-relaxed">
        Cada piloto, escudería y coche de tu equipo acumula puntos en cada Gran Premio.
        Los puntos se calculan automáticamente tras cada carrera.
      </p>

      <!-- 3 fichas de resumen -->
      <div class="grid grid-cols-3 gap-3 sm:gap-5 mt-8 max-w-lg mx-auto">
        <div class="bg-zinc-800/70 rounded-xl p-3 sm:p-4 text-center border border-zinc-700/50">
          <p class="text-2xl sm:text-3xl mb-1">🏁</p>
          <p class="text-white font-bold text-xs sm:text-sm">Piloto</p>
          <p class="text-zinc-500 text-xs mt-0.5 hidden sm:block">Carrera + qualy</p>
        </div>
        <div class="bg-zinc-800/70 rounded-xl p-3 sm:p-4 text-center border border-zinc-700/50">
          <p class="text-2xl sm:text-3xl mb-1">🏆</p>
          <p class="text-white font-bold text-xs sm:text-sm">Escudería</p>
          <p class="text-zinc-500 text-xs mt-0.5 hidden sm:block">Pts carrera ÷ 2</p>
        </div>
        <div class="bg-zinc-800/70 rounded-xl p-3 sm:p-4 text-center border border-zinc-700/50">
          <p class="text-2xl sm:text-3xl mb-1">🏎️</p>
          <p class="text-white font-bold text-xs sm:text-sm">Coche</p>
          <p class="text-zinc-500 text-xs mt-0.5 hidden sm:block">Pts qualy ÷ 2</p>
        </div>
      </div>
    </div>

    <!-- ── Cargando ── -->
    <div v-if="cargando" class="flex items-center justify-center py-20">
      <div class="flex flex-col items-center gap-3">
        <div class="w-8 h-8 border-2 border-red-500 border-t-transparent rounded-full animate-spin" />
        <p class="text-zinc-500 text-sm">Cargando reglas...</p>
      </div>
    </div>

    <template v-else>

      <!-- ── Grid de tablas ── -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

        <!-- Posición en carrera -->
        <div class="card space-y-4">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-green-500/15 flex items-center justify-center text-lg flex-shrink-0">🏁</div>
            <div>
              <h3 class="font-bold text-white text-sm">Posición en carrera</h3>
              <p class="text-zinc-500 text-xs">Puntos por posición final en la carrera</p>
            </div>
          </div>
          <div class="divide-y divide-zinc-800/60">
            <div v-for="r in grupos.carrera" :key="r.evento"
              class="flex items-center justify-between py-2.5">
              <span class="text-zinc-300 text-sm">{{ r.descripcion }}</span>
              <span class="font-bold text-sm tabular-nums min-w-[2.5rem] text-right rounded-lg px-2 py-0.5"
                :class="r.puntos >= 0
                  ? 'text-green-400 bg-green-500/10'
                  : 'text-red-400 bg-red-500/10'">
                {{ r.puntos > 0 ? '+' : '' }}{{ r.puntos }}
              </span>
            </div>
          </div>
        </div>

        <!-- Clasificación (qualy) -->
        <div class="card space-y-4">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-blue-500/15 flex items-center justify-center text-lg flex-shrink-0">⏱️</div>
            <div>
              <h3 class="font-bold text-white text-sm">Clasificación (Qualy)</h3>
              <p class="text-zinc-500 text-xs">Puntos qualy — también los usa el coche</p>
            </div>
          </div>
          <div class="divide-y divide-zinc-800/60">
            <div v-for="r in grupos.qualy" :key="r.evento"
              class="flex items-center justify-between py-2.5">
              <span class="text-zinc-300 text-sm">{{ r.descripcion }}</span>
              <span class="font-bold text-sm tabular-nums min-w-[2.5rem] text-right rounded-lg px-2 py-0.5 text-blue-400 bg-blue-500/10">
                +{{ r.puntos }}
              </span>
            </div>
          </div>
        </div>

        <!-- Bonificaciones -->
        <div class="card space-y-4">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-amber-500/15 flex items-center justify-center text-lg flex-shrink-0">⚡</div>
            <div>
              <h3 class="font-bold text-white text-sm">Bonificaciones</h3>
              <p class="text-zinc-500 text-xs">Puntos extra por rendimiento especial</p>
            </div>
          </div>
          <div class="divide-y divide-zinc-800/60">
            <div v-for="r in grupos.bonus" :key="r.evento"
              class="flex items-center justify-between py-2.5">
              <span class="text-zinc-300 text-sm">{{ r.descripcion }}</span>
              <span class="font-bold text-sm tabular-nums min-w-[2.5rem] text-right rounded-lg px-2 py-0.5 text-amber-400 bg-amber-500/10">
                +{{ r.puntos }}
              </span>
            </div>
          </div>
        </div>

        <!-- Penalizaciones -->
        <div class="card space-y-4">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-red-500/15 flex items-center justify-center text-lg flex-shrink-0">🚫</div>
            <div>
              <h3 class="font-bold text-white text-sm">Penalizaciones</h3>
              <p class="text-zinc-500 text-xs">Puntos negativos por incidentes o sanciones</p>
            </div>
          </div>
          <div class="divide-y divide-zinc-800/60">
            <div v-for="r in grupos.penalizacion" :key="r.evento"
              class="flex items-center justify-between py-2.5">
              <span class="text-zinc-300 text-sm">{{ r.descripcion }}</span>
              <span class="font-bold text-sm tabular-nums min-w-[2.5rem] text-right rounded-lg px-2 py-0.5 text-red-400 bg-red-500/10">
                {{ r.puntos }}
              </span>
            </div>
          </div>
        </div>

      </div>

      <!-- ── Fila inferior: coche + escudería ── -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

        <!-- Cómo puntúa el coche -->
        <div class="rounded-2xl border border-blue-500/20 bg-blue-500/5 p-5 space-y-3">
          <p class="text-blue-400 font-bold text-sm flex items-center gap-2">
            <span class="text-base">🏎️</span> Puntuación del coche
          </p>
          <p class="text-zinc-400 text-sm leading-relaxed">
            El coche suma los <strong class="text-white">puntos de qualy</strong> de sus dos pilotos reales y divide el total entre 2 (redondeado).
          </p>
          <div class="flex flex-wrap gap-2">
            <span v-for="(pts, pos) in qualyBadges" :key="pos"
              class="bg-blue-500/10 border border-blue-500/25 text-blue-300 text-xs px-2.5 py-1 rounded-full font-semibold">
              {{ pos }} → +{{ pts }}
            </span>
          </div>
        </div>

        <!-- Cómo puntúa la escudería -->
        <div class="rounded-2xl border border-green-500/20 bg-green-500/5 p-5 space-y-3">
          <p class="text-green-400 font-bold text-sm flex items-center gap-2">
            <span class="text-base">🏆</span> Puntuación de la escudería
          </p>
          <p class="text-zinc-400 text-sm leading-relaxed">
            La escudería suma los <strong class="text-white">puntos de carrera</strong> de sus dos pilotos reales (posición final, DNF, etc.) y divide entre 2 (redondeado).
          </p>
          <p class="text-zinc-500 text-xs">
            Ejemplo: P3 (15 pts) + P7 (6 pts) = 21 ÷ 2 = <span class="text-green-400 font-bold">11 pts</span>
          </p>
        </div>

      </div>

    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { f1Service } from '@/services/f1Service'

const reglas   = ref([])
const cargando = ref(false)

const qualyBadges = {
  'Pole (P1)': 10, 'P2': 9, 'P3': 8, 'P4': 7, 'P5': 6
}

const grupos = computed(() => {
  const carrera      = []
  const qualy        = []
  const bonus        = []
  const penalizacion = []

  for (const r of reglas.value) {
    const ev = r.evento
    if (ev.startsWith('FINISH_') || ev === 'DNF' || ev === 'DNS' || ev === 'DISQUALIFIED') {
      carrera.push(r)
    } else if (ev.startsWith('QUALI_')) {
      qualy.push(r)
    } else if (r.puntos < 0) {
      penalizacion.push(r)
    } else {
      bonus.push(r)
    }
  }

  carrera.sort((a, b) => b.puntos - a.puntos)
  qualy.sort((a, b) => b.puntos - a.puntos)
  bonus.sort((a, b) => b.puntos - a.puntos)
  penalizacion.sort((a, b) => a.puntos - b.puntos)

  return { carrera, qualy, bonus, penalizacion }
})

onMounted(async () => {
  cargando.value = true
  try {
    const { data } = await f1Service.getReglas()
    reglas.value = data
  } finally {
    cargando.value = false
  }
})
</script>
