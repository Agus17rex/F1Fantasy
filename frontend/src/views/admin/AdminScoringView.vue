<template>
  <div class="space-y-6">
    <h2 class="text-xl font-bold text-white">Reglas de Puntuación Fantasy</h2>

    <div class="card">
      <p class="text-zinc-400 text-sm mb-4">
        Estas reglas se aplican automáticamente al calcular los puntos de cada carrera.
        El capitán multiplica sus puntos por x2.
      </p>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Carrera -->
        <div>
          <h3 class="text-sm font-semibold text-zinc-300 uppercase tracking-wider mb-3">🏁 Posición en carrera</h3>
          <div class="space-y-1.5">
            <div v-for="rule in raceRules" :key="rule.event" class="flex items-center justify-between py-1.5 border-b border-zinc-800 last:border-0">
              <p class="text-zinc-300 text-sm">{{ rule.description }}</p>
              <span class="font-bold text-sm" :class="rule.points >= 0 ? 'text-green-400' : 'text-red-400'">
                {{ rule.points > 0 ? '+' : '' }}{{ rule.points }}
              </span>
            </div>
          </div>
        </div>

        <!-- Qualy -->
        <div>
          <h3 class="text-sm font-semibold text-zinc-300 uppercase tracking-wider mb-3">⏱️ Clasificación & Bonos</h3>
          <div class="space-y-1.5">
            <div v-for="rule in qualiAndBonusRules" :key="rule.event" class="flex items-center justify-between py-1.5 border-b border-zinc-800 last:border-0">
              <p class="text-zinc-300 text-sm">{{ rule.description }}</p>
              <span class="font-bold text-sm" :class="rule.points >= 0 ? 'text-green-400' : 'text-red-400'">
                {{ rule.points > 0 ? '+' : '' }}{{ rule.points }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Capitán info -->
      <div class="mt-6 bg-amber-500/10 border border-amber-500/20 rounded-lg p-4">
        <p class="text-amber-400 font-semibold text-sm">★ Capitán — multiplicador x2</p>
        <p class="text-zinc-400 text-xs mt-1">
          El piloto que marques como capitán obtiene el doble de puntos fantasy en cada carrera.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
const raceRules = [
  { event: 'FINISH_P1',  points: 25, description: '1ª posición en carrera' },
  { event: 'FINISH_P2',  points: 18, description: '2ª posición en carrera' },
  { event: 'FINISH_P3',  points: 15, description: '3ª posición en carrera' },
  { event: 'FINISH_P4',  points: 12, description: '4ª posición' },
  { event: 'FINISH_P5',  points: 10, description: '5ª posición' },
  { event: 'FINISH_P6',  points: 8,  description: '6ª-7ª posición' },
  { event: 'FINISH_P8',  points: 4,  description: '8ª-9ª posición' },
  { event: 'FINISH_P10', points: 1,  description: '10ª posición' },
  { event: 'DNF',        points: -15, description: 'No termina (DNF)' },
  { event: 'DNS',        points: -20, description: 'No sale (DNS)' },
  { event: 'DISQ',       points: -25, description: 'Descalificado' },
]

const qualiAndBonusRules = [
  { event: 'QUALI_P1',       points: 10, description: 'Pole position' },
  { event: 'QUALI_P2',       points: 9,  description: '2ª en qualy' },
  { event: 'QUALI_P3',       points: 8,  description: '3ª en qualy' },
  { event: 'QUALI_P4',       points: 7,  description: '4ª en qualy' },
  { event: 'QUALI_P5',       points: 6,  description: '5ª en qualy' },
  { event: 'FASTEST_LAP',    points: 5,  description: 'Vuelta rápida' },
  { event: 'DRIVER_OF_DAY',  points: 5,  description: 'Piloto del día' },
  { event: 'OVERTAKES_3',    points: 3,  description: 'Supera a 3+ coches' },
  { event: 'OVERTAKES_5',    points: 5,  description: 'Supera a 5+ coches' },
  { event: 'BEATS_TEAMMATE', points: 3,  description: 'Supera al compañero' },
  { event: 'PENALTY_GRID',   points: -5, description: 'Penalización de grid' },
  { event: 'PENALTY_TIME',   points: -5, description: 'Penalización de tiempo' },
]
</script>
