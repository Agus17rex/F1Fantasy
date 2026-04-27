<template>
  <div class="space-y-4">
    <h2 class="text-xl font-bold text-white">Gestión de Carreras</h2>

    <div v-if="cargando" class="text-zinc-400 text-center py-10">Cargando carreras...</div>

    <div v-else class="space-y-3">
      <div v-for="carrera in carreras" :key="carrera.id" class="card">
        <div class="flex items-center gap-4">
          <!-- Info -->
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-0.5">
              <span class="text-zinc-500 text-sm font-mono">R{{ carrera.ronda }}</span>
              <h3 class="font-semibold text-white">{{ carrera.nombre }}</h3>
              <span :class="claseBadge(carrera.estado)" class="ml-1">{{ etiquetaEstado(carrera.estado) }}</span>
            </div>
            <p class="text-zinc-500 text-sm">
              {{ carrera.circuito?.nombre }} · {{ formatearFecha(carrera.fecha) }}
              <span v-if="carrera.resultados_count" class="ml-2 text-zinc-600">
                {{ carrera.resultados_count }} resultados
              </span>
            </p>
          </div>

          <!-- Acciones -->
          <div class="flex items-center gap-2 flex-shrink-0">
            <button
              @click="sincronizarResultados(carrera)"
              :disabled="accionCargando[carrera.id]"
              class="btn-secondary text-xs py-1.5 px-3"
            >
              {{ accionCargando[carrera.id] === 'sync' ? 'Sincronizando...' : '↓ Resultados' }}
            </button>

            <button
              v-if="carrera.estado !== 'scored'"
              @click="puntuarCarrera(carrera)"
              :disabled="accionCargando[carrera.id] || !carrera.resultados_count"
              class="btn-primary text-xs py-1.5 px-3"
              :title="!carrera.resultados_count ? 'Primero sincroniza los resultados' : ''"
            >
              {{ accionCargando[carrera.id] === 'puntuar' ? 'Calculando...' : '⚡ Puntuar' }}
            </button>

            <span v-else class="text-green-400 text-xs font-medium px-2">✓ Puntuada</span>
          </div>
        </div>

        <!-- Feedback -->
        <p
          v-if="feedback[carrera.id]"
          class="mt-2 text-sm"
          :class="feedback[carrera.id].tipo === 'exito' ? 'text-green-400' : 'text-red-400'"
        >
          {{ feedback[carrera.id].mensaje }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { adminService } from '@/services/adminService'

const carreras      = ref([])
const cargando      = ref(false)
const accionCargando = reactive({})
const feedback       = reactive({})

async function cargarCarreras() {
  cargando.value = true
  try {
    const { data } = await adminService.getCarreras()
    carreras.value = data
  } finally {
    cargando.value = false
  }
}

async function sincronizarResultados(carrera) {
  accionCargando[carrera.id] = 'sync'
  feedback[carrera.id]       = null
  try {
    const { data } = await adminService.sincronizarResultados(carrera.id)
    feedback[carrera.id] = { tipo: 'exito', mensaje: `✓ ${data.message}` }
    await cargarCarreras()
  } catch (e) {
    feedback[carrera.id] = { tipo: 'error', mensaje: e.response?.data?.message || 'Error al sincronizar' }
  } finally {
    delete accionCargando[carrera.id]
  }
}

async function puntuarCarrera(carrera) {
  accionCargando[carrera.id] = 'puntuar'
  feedback[carrera.id]       = null
  try {
    const { data } = await adminService.puntuarCarrera(carrera.id)
    feedback[carrera.id] = { tipo: 'exito', mensaje: `✓ ${data.message}` }
    await cargarCarreras()
  } catch (e) {
    feedback[carrera.id] = { tipo: 'error', mensaje: e.response?.data?.message || 'Error al puntuar' }
  } finally {
    delete accionCargando[carrera.id]
  }
}

function formatearFecha(d) {
  return new Date(d).toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' })
}

function claseBadge(estado) {
  return { upcoming: 'badge-upcoming', active: 'badge-active', scored: 'badge-scored' }[estado] || 'badge-scored'
}

function etiquetaEstado(estado) {
  return { upcoming: 'Próxima', active: 'En curso', scored: 'Puntuada', cancelled: 'Cancelada' }[estado] || estado
}

onMounted(cargarCarreras)
</script>
