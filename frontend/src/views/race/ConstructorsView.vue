<template>
  <div class="space-y-4">
    <h2 class="text-xl font-bold text-white">Escuderías</h2>

    <div v-if="f1Store.cargando" class="text-zinc-400 text-center py-10">Cargando escuderías...</div>

    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="escuderia in f1Store.escuderias"
        :key="escuderia.id"
        class="card hover:border-zinc-600 transition-colors"
      >
        <div class="h-1.5 rounded-full mb-4 -mt-1" :style="{ backgroundColor: escuderia.color || '#E8002D' }"></div>

        <div class="flex items-start justify-between">
          <div>
            <h3 class="font-bold text-white text-lg">{{ escuderia.name }}</h3>
            <p class="text-zinc-500 text-sm mt-0.5">{{ escuderia.nationality }}</p>
          </div>
          <div class="text-right">
            <p class="text-red-400 font-bold text-xl">{{ formatearPrecio(escuderia.price) }}</p>
            <p class="text-zinc-500 text-xs">precio fantasy</p>
          </div>
        </div>

        <div class="mt-3 pt-3 border-t border-zinc-800">
          <p class="text-zinc-500 text-xs">{{ escuderia.pilotos_count || 2 }} pilotos</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useF1Store } from '@/stores/f1'

const f1Store = useF1Store()

function formatearPrecio(p) { return (p / 1_000_000).toFixed(1) + 'M €' }

onMounted(() => f1Store.fetchEscuderias())
</script>
