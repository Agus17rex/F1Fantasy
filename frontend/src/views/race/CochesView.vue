<template>
  <div class="space-y-4">
    <h2 class="text-xl font-bold text-white">Coches</h2>

    <div v-if="cargando" class="text-zinc-400 text-center py-10">Cargando coches...</div>

    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="coche in coches"
        :key="coche.id"
        class="card hover:border-zinc-600 transition-colors cursor-pointer"
        @click="cocheSeleccionado = coche"
      >
        <div class="h-1.5 rounded-full mb-4 -mt-1" :style="{ backgroundColor: coche.escuderia?.color || '#E8002D' }"></div>

        <!-- Imagen del coche -->
        <div class="flex items-center justify-center mb-3 h-28 bg-zinc-900 rounded-lg overflow-hidden">
          <img v-if="coche.foto" :src="coche.foto" :alt="coche.nombre"
            class="h-full w-full object-contain"
            @error="e => e.target.style.display = 'none'" />
          <span v-else class="text-4xl">🏎️</span>
        </div>

        <div class="flex items-start gap-3">
          <LogoEscuderia :escuderia="coche.escuderia" size="md" />
          <div class="flex-1 min-w-0">
            <h3 class="font-bold text-white leading-tight">{{ coche.nombre }}</h3>
            <p class="text-zinc-500 text-sm">{{ coche.escuderia?.nombre }}</p>
            <p class="text-red-400 font-bold text-lg mt-1">{{ formatearPrecio(coche.precio) }}</p>
          </div>
        </div>

        <div class="mt-3 pt-3 border-t border-zinc-800">
          <p class="text-zinc-500 text-xs">Puntúa por qualy · toca para ver puntuación</p>
        </div>
      </div>
    </div>

    <CocheModal
      v-if="cocheSeleccionado"
      :coche="cocheSeleccionado"
      @cerrar="cocheSeleccionado = null"
    />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useF1Store } from '@/stores/f1'
import { f1Service } from '@/services/f1Service'
import LogoEscuderia from '@/components/media/LogoEscuderia.vue'
import CocheModal    from '@/components/modals/CocheModal.vue'

const f1Store        = useF1Store()
const coches         = ref([])
const cargando       = ref(false)
const cocheSeleccionado = ref(null)

function formatearPrecio(p) { return (p / 1_000_000).toFixed(1) + 'M €' }

onMounted(async () => {
  f1Store.fetchCarreras()
  cargando.value = true
  try {
    const { data } = await f1Service.getCoches()
    coches.value = data
  } finally {
    cargando.value = false
  }
})
</script>
