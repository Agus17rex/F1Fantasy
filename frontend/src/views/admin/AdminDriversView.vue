<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-bold text-white">Precios del Mercado</h2>
        <p class="text-zinc-400 text-sm mt-0.5">Escala automática 10 M – 80 M según clasificación del campeonato</p>
      </div>
      <button @click="recalcular" class="btn-primary text-sm" :disabled="recalculando">
        {{ recalculando ? 'Actualizando...' : '⚡ Recalcular precios' }}
      </button>
    </div>

    <p v-if="mensaje" :class="esError ? 'text-red-400 bg-red-500/10 border-red-500/20' : 'text-green-400 bg-green-500/10 border-green-500/20'"
       class="text-sm border rounded-lg px-4 py-3">
      {{ esError ? '✗' : '✓' }} {{ mensaje }}
    </p>

    <!-- Pilotos -->
    <div class="card">
      <h3 class="font-semibold text-white mb-4">Pilotos</h3>
      <div v-if="cargando" class="text-zinc-400 text-sm py-4 text-center">Cargando...</div>
      <div v-else class="space-y-1.5">
        <div
          v-for="piloto in pilotos"
          :key="piloto.id"
          class="flex items-center gap-3 py-1.5 border-b border-zinc-800 last:border-0"
        >
          <div class="w-1 h-8 rounded-full flex-shrink-0" :style="{ backgroundColor: piloto.escuderia?.color || '#888' }"></div>
          <p class="flex-1 text-white text-sm">
            {{ piloto.nombre }} <strong>{{ piloto.apellido }}</strong>
            <span class="text-zinc-500 text-xs ml-2">{{ piloto.escuderia?.nombre }}</span>
          </p>
          <span class="text-red-400 font-mono text-sm font-semibold">
            {{ formatearPrecio(piloto.precio) }}
          </span>
        </div>
      </div>
    </div>

    <!-- Escuderías -->
    <div class="card">
      <h3 class="font-semibold text-white mb-4">Escuderías</h3>
      <div v-if="cargando" class="text-zinc-400 text-sm py-4 text-center">Cargando...</div>
      <div v-else class="space-y-1.5">
        <div
          v-for="escuderia in escuderias"
          :key="escuderia.id"
          class="flex items-center gap-3 py-1.5 border-b border-zinc-800 last:border-0"
        >
          <div class="w-3 h-3 rounded-full flex-shrink-0" :style="{ backgroundColor: escuderia.color || '#888' }"></div>
          <p class="flex-1 text-white text-sm font-medium">{{ escuderia.nombre }}</p>
          <span class="text-red-400 font-mono text-sm font-semibold">
            {{ formatearPrecio(escuderia.precio) }}
          </span>
        </div>
      </div>
    </div>

    <!-- Coches -->
    <div class="card">
      <h3 class="font-semibold text-white mb-4">Coches</h3>
      <div v-if="cargando" class="text-zinc-400 text-sm py-4 text-center">Cargando...</div>
      <div v-else class="space-y-1.5">
        <div
          v-for="coche in coches"
          :key="coche.id"
          class="flex items-center gap-3 py-1.5 border-b border-zinc-800 last:border-0"
        >
          <div class="w-3 h-8 rounded flex-shrink-0" :style="{ backgroundColor: coche.escuderia?.color || '#888' }"></div>
          <p class="flex-1 text-white text-sm font-medium">
            {{ coche.nombre }}
            <span class="text-zinc-500 text-xs ml-2 font-normal">{{ coche.escuderia?.nombre }}</span>
          </p>
          <span class="text-red-400 font-mono text-sm font-semibold">
            {{ formatearPrecio(coche.precio) }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useF1Store } from '@/stores/f1'
import { adminService } from '@/services/adminService'
import api from '@/services/api'

const f1Store      = useF1Store()
const pilotos      = ref([])
const escuderias   = ref([])
const coches       = ref([])
const cargando     = ref(false)
const recalculando = ref(false)
const mensaje      = ref('')
const esError      = ref(false)

function formatearPrecio(precio) {
  if (!precio) return '—'
  return (precio / 1_000_000).toFixed(1) + ' M €'
}

async function cargarDatos() {
  cargando.value = true
  try {
    await Promise.all([f1Store.fetchPilotos(), f1Store.fetchEscuderias()])
    pilotos.value    = [...f1Store.pilotos].sort((a, b) => b.precio - a.precio)
    escuderias.value = [...f1Store.escuderias].sort((a, b) => b.precio - a.precio)

    const { data } = await api.get('/f1/coches')
    coches.value = [...data].sort((a, b) => b.precio - a.precio)
  } finally {
    cargando.value = false
  }
}

async function recalcular() {
  recalculando.value = true
  mensaje.value      = ''
  esError.value      = false
  try {
    const { data } = await adminService.actualizarPrecios(new Date().getFullYear())
    mensaje.value = data.message || 'Precios recalculados correctamente'
    await cargarDatos()
  } catch (e) {
    esError.value = true
    mensaje.value = e.response?.data?.message || 'Error al recalcular los precios. Comprueba la conexión con la API de F1.'
  } finally {
    recalculando.value = false
  }
}

onMounted(cargarDatos)
</script>
