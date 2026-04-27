<template>
  <div class="space-y-6">
    <h2 class="text-xl font-bold text-white">Panel de Administración</h2>

    <!-- Estadísticas -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
      <div v-for="stat in stats" :key="stat.label" class="card text-center">
        <p class="text-3xl font-black" :class="stat.color || 'text-white'">{{ stat.value }}</p>
        <p class="text-zinc-400 text-xs mt-1">{{ stat.label }}</p>
      </div>
    </div>

    <!-- Botón sincronizar -->
    <div class="card flex items-center justify-between">
      <div>
        <h3 class="font-semibold text-white">Sincronizar datos F1</h3>
        <p class="text-zinc-400 text-sm mt-0.5">Actualiza pilotos, escuderías y calendario desde la API</p>
      </div>
      <button @click="handleSync" class="btn-primary text-sm" :disabled="sincronizando">
        {{ sincronizando ? 'Sincronizando...' : '🔄 Sincronizar' }}
      </button>
    </div>

    <!-- Mensaje éxito -->
    <p v-if="exito" class="text-green-400 text-sm bg-green-500/10 border border-green-500/20 rounded-lg px-4 py-3">
      ✓ {{ exito }}
    </p>
    <!-- Mensaje error -->
    <p v-if="errorMsg" class="text-red-400 text-sm bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-3">
      ✗ {{ errorMsg }}
    </p>

    <!-- Carreras recientes -->
    <div>
      <h3 class="text-lg font-semibold text-white mb-3">Últimas carreras</h3>
      <div class="space-y-2">
        <div v-for="carrera in carrerasRecientes" :key="carrera.id" class="card flex items-center gap-4">
          <div class="flex-1">
            <h4 class="font-medium text-white">{{ carrera.name }}</h4>
            <p class="text-zinc-500 text-sm">{{ carrera.circuito?.name }} · {{ formatearFecha(carrera.date) }}</p>
          </div>
          <span :class="claseBadge(carrera.status)">{{ etiquetaEstado(carrera.status) }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { adminService } from '@/services/adminService'

const stats            = ref([])
const carrerasRecientes = ref([])
const sincronizando    = ref(false)
const exito            = ref('')
const errorMsg         = ref('')

async function cargarPanel() {
  try {
    const { data } = await adminService.getPanelControl()
    stats.value = [
      { label: 'Usuarios',            value: data.estadisticas.total_usuarios,      color: 'text-white' },
      { label: 'Ligas',               value: data.estadisticas.total_ligas,         color: 'text-white' },
      { label: 'Equipos',             value: data.estadisticas.total_equipos,       color: 'text-white' },
      { label: 'Carreras puntuadas',  value: data.estadisticas.carreras_puntuadas,  color: 'text-green-400' },
      { label: 'Carreras pendientes', value: data.estadisticas.carreras_pendientes, color: 'text-amber-400' },
    ]
    carrerasRecientes.value = data.carreras_recientes
  } catch {}
}

async function handleSync() {
  sincronizando.value = true
  exito.value    = ''
  errorMsg.value = ''
  try {
    const { data } = await adminService.sincronizarDatosF1(new Date().getFullYear())
    exito.value = `Sincronizados: ${data.pilotos} pilotos, ${data.escuderias} escuderías, ${data.carreras} carreras`
    await cargarPanel()
  } catch {
    errorMsg.value = 'Error al sincronizar. Comprueba la conexión con la API.'
  } finally {
    sincronizando.value = false
  }
}

function formatearFecha(d) {
  return new Date(d).toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' })
}

function claseBadge(estado) {
  return { upcoming: 'badge-upcoming', active: 'badge-active', scored: 'badge-scored' }[estado] || 'badge-scored'
}

function etiquetaEstado(estado) {
  return { upcoming: 'Próxima', active: 'En curso', scored: 'Puntuada' }[estado] || estado
}

onMounted(cargarPanel)
</script>
