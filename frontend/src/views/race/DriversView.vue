<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <h2 class="text-xl font-bold text-white">Pilotos</h2>
      <input
        v-model="busqueda"
        type="text"
        class="input w-64"
        placeholder="Buscar piloto..."
      />
    </div>

    <div v-if="f1Store.cargando" class="text-zinc-400 text-center py-10">Cargando pilotos...</div>

    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
      <div
        v-for="piloto in pilotosFiltrados"
        :key="piloto.id"
        class="card hover:border-zinc-600 transition-colors"
      >
        <div
          class="h-1 rounded-full mb-4 -mt-1"
          :style="{ backgroundColor: piloto.escuderia?.color || '#666' }"
        ></div>

        <div class="flex items-start justify-between gap-2">
          <div>
            <div class="flex items-center gap-2 mb-0.5">
              <p class="text-zinc-400 text-xs font-medium uppercase tracking-wider">
                {{ piloto.code || '---' }}
              </p>
            </div>
            <h3 class="font-semibold text-white leading-tight">
              {{ piloto.first_name }} <span class="font-black">{{ piloto.last_name }}</span>
            </h3>
            <p class="text-zinc-500 text-sm mt-0.5">{{ piloto.escuderia?.name || 'Sin escudería' }}</p>
          </div>
          <div class="text-right flex-shrink-0">
            <p class="text-red-400 font-bold text-lg">{{ formatearPrecio(piloto.price) }}</p>
            <p class="text-zinc-500 text-xs">precio fantasy</p>
          </div>
        </div>

        <div class="mt-3 flex items-center gap-2">
          <span v-if="piloto.number" class="text-xs bg-zinc-800 text-zinc-400 px-2 py-0.5 rounded font-mono">
            #{{ piloto.number }}
          </span>
          <span class="text-xs text-zinc-500">{{ piloto.nationality }}</span>
        </div>
      </div>
    </div>

    <p v-if="!f1Store.cargando && pilotosFiltrados.length === 0" class="text-zinc-500 text-center py-8">
      No se encontraron pilotos
    </p>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useF1Store } from '@/stores/f1'

const f1Store  = useF1Store()
const busqueda = ref('')

const pilotosFiltrados = computed(() => {
  const q = busqueda.value.toLowerCase()
  return f1Store.pilotos.filter(p =>
    !q ||
    p.first_name.toLowerCase().includes(q) ||
    p.last_name.toLowerCase().includes(q) ||
    p.code?.toLowerCase().includes(q) ||
    p.escuderia?.name?.toLowerCase().includes(q)
  )
})

function formatearPrecio(precio) {
  return (precio / 1_000_000).toFixed(1) + 'M €'
}

onMounted(() => f1Store.fetchPilotos())
</script>
