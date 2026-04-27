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
          class="h-1 rounded-full mb-3 -mt-1"
          :style="{ backgroundColor: piloto.escuderia?.color || '#666' }"
        ></div>

        <div class="flex items-start gap-3">
          <AvatarPiloto :piloto="piloto" size="xl" />

          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-0.5">
              <p class="text-zinc-400 text-xs font-medium uppercase tracking-wider">
                {{ piloto.codigo || '---' }}
              </p>
              <span v-if="piloto.numero"
                class="text-xs bg-zinc-800 text-zinc-400 px-1.5 py-0.5 rounded font-mono">
                #{{ piloto.numero }}
              </span>
            </div>
            <h3 class="font-semibold text-white leading-tight">
              {{ piloto.nombre }} <span class="font-black">{{ piloto.apellido }}</span>
            </h3>
            <div class="flex items-center gap-1.5 mt-1.5">
              <LogoEscuderia :escuderia="piloto.escuderia" size="xs" />
              <p class="text-zinc-500 text-xs truncate">{{ piloto.escuderia?.nombre || 'Sin escudería' }}</p>
            </div>
          </div>
        </div>

        <div class="mt-3 pt-3 border-t border-zinc-800 flex items-center justify-between">
          <span class="text-xs text-zinc-500">{{ piloto.nacionalidad }}</span>
          <p class="text-red-400 font-bold text-sm">{{ formatearPrecio(piloto.precio) }}</p>
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
import AvatarPiloto   from '@/components/media/AvatarPiloto.vue'
import LogoEscuderia  from '@/components/media/LogoEscuderia.vue'

const f1Store  = useF1Store()
const busqueda = ref('')

const pilotosFiltrados = computed(() => {
  const q = busqueda.value.toLowerCase()
  return f1Store.pilotos.filter(p =>
    !q ||
    p.nombre?.toLowerCase().includes(q) ||
    p.apellido?.toLowerCase().includes(q) ||
    p.codigo?.toLowerCase().includes(q) ||
    p.escuderia?.nombre?.toLowerCase().includes(q)
  )
})

function formatearPrecio(precio) {
  return (precio / 1_000_000).toFixed(1) + 'M €'
}

onMounted(() => f1Store.fetchPilotos())
</script>
