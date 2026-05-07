<template>
  <div class="space-y-4">
    <h2 class="text-xl font-bold text-white">Escuderías</h2>

    <div v-if="f1Store.cargando" class="text-zinc-400 text-center py-10">Cargando escuderías...</div>

    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="escuderia in f1Store.escuderias"
        :key="escuderia.id"
        class="card hover:border-zinc-600 transition-colors cursor-pointer"
        @click="escuderiaSeleccionada = escuderia"
      >
        <div class="h-1.5 rounded-full mb-4 -mt-1" :style="{ backgroundColor: escuderia.color || '#E8002D' }"></div>

        <div class="flex items-start gap-3">
          <LogoEscuderia :escuderia="escuderia" size="xl" />
          <div class="flex-1 min-w-0">
            <h3 class="font-bold text-white text-lg leading-tight">{{ escuderia.nombre }}</h3>
            <p class="text-zinc-500 text-sm mt-0.5">{{ escuderia.nacionalidad }}</p>
            <p class="text-red-400 font-bold text-xl mt-2">{{ formatearPrecio(escuderia.precio) }}</p>
          </div>
        </div>

        <div class="mt-3 pt-3 border-t border-zinc-800">
          <p class="text-zinc-500 text-xs">{{ escuderia.pilotos_count || 2 }} pilotos · toca para ver puntuación</p>
        </div>
      </div>
    </div>

    <EscuderiaModal
      v-if="escuderiaSeleccionada"
      :escuderia="escuderiaSeleccionada"
      @cerrar="escuderiaSeleccionada = null"
    />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useF1Store } from '@/stores/f1'
import LogoEscuderia    from '@/components/media/LogoEscuderia.vue'
import EscuderiaModal   from '@/components/modals/EscuderiaModal.vue'

const f1Store              = useF1Store()
const escuderiaSeleccionada = ref(null)

function formatearPrecio(p) { return (p / 1_000_000).toFixed(1) + 'M €' }

onMounted(() => f1Store.fetchEscuderias())
</script>
