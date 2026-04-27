<template>
  <div class="space-y-6">
    <!-- Cabecera -->
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h2 class="text-xl font-bold text-white">{{ equipo?.name || 'Mi Equipo' }}</h2>
        <p class="text-zinc-400 text-sm mt-0.5">
          Presupuesto:
          <span class="text-white font-semibold">{{ formatearPrecio(presupuestoRestante) }}</span>
          <span class="text-zinc-600"> / {{ formatearPrecio(presupuestoInicial) }}</span>
        </p>
      </div>
      <span :class="equipoValido ? 'text-green-400' : 'text-amber-400'" class="text-sm font-medium">
        {{ equipoValido ? '✓ Equipo válido' : '⚠ Equipo incompleto' }}
      </span>
    </div>

    <!-- Barra de presupuesto -->
    <div class="w-full bg-zinc-800 rounded-full h-2">
      <div
        class="h-2 rounded-full transition-all duration-500"
        :class="porcentajePresupuesto > 90 ? 'bg-red-500' : 'bg-green-500'"
        :style="{ width: porcentajePresupuesto + '%' }"
      ></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

      <!-- ── COLUMNA IZQUIERDA: selección actual ──────────────────────────── -->
      <div class="space-y-3">
        <h3 class="font-semibold text-white">Mi selección</h3>

        <!-- Titulares -->
        <div class="card">
          <p class="text-zinc-400 text-xs font-semibold uppercase tracking-wider mb-2">
            🏁 Titulares
            <span class="text-zinc-600 font-normal normal-case">({{ titulares.length }}/2) · puntúan normal</span>
          </p>
          <div class="space-y-2">
            <div v-for="p in titulares" :key="p.id" class="flex items-center gap-2 p-2 rounded-lg bg-zinc-800/50">
              <div class="w-1 h-7 rounded-full flex-shrink-0" :style="{ backgroundColor: p.escuderia?.color || '#888' }"></div>
              <div class="flex-1 min-w-0">
                <p class="text-white text-sm font-medium truncate">{{ p.first_name }} {{ p.last_name }}</p>
                <p class="text-zinc-500 text-xs">{{ p.escuderia?.name }}</p>
              </div>
              <button @click="abrirCambioRol(p)" class="text-zinc-500 hover:text-blue-400 transition-colors text-xs px-1" title="Cambiar rol">⇄</button>
              <button @click="eliminarPiloto(p)" class="text-zinc-600 hover:text-red-400 transition-colors text-xs px-1">✕</button>
            </div>
            <p v-for="n in (2 - titulares.length)" :key="'slot-t-' + n" class="text-zinc-700 text-sm text-center py-1.5 border border-dashed border-zinc-800 rounded-lg">
              Slot vacío
            </p>
          </div>
        </div>

        <!-- Team Manager -->
        <div class="card">
          <p class="text-zinc-400 text-xs font-semibold uppercase tracking-wider mb-2">
            ⭐ Team Manager
            <span class="text-zinc-600 font-normal normal-case">({{ teamManagers.length }}/1) · puntúa x2</span>
          </p>
          <div class="space-y-2">
            <div v-for="p in teamManagers" :key="p.id" class="flex items-center gap-2 p-2 rounded-lg bg-amber-500/10">
              <div class="w-1 h-7 rounded-full flex-shrink-0" :style="{ backgroundColor: p.escuderia?.color || '#888' }"></div>
              <div class="flex-1 min-w-0">
                <p class="text-white text-sm font-medium truncate">{{ p.first_name }} {{ p.last_name }}</p>
                <p class="text-zinc-500 text-xs">{{ p.escuderia?.name }}</p>
              </div>
              <button @click="abrirCambioRol(p)" class="text-zinc-500 hover:text-blue-400 transition-colors text-xs px-1" title="Cambiar rol">⇄</button>
              <button @click="eliminarPiloto(p)" class="text-zinc-600 hover:text-red-400 transition-colors text-xs px-1">✕</button>
            </div>
            <p v-if="teamManagers.length === 0" class="text-zinc-700 text-sm text-center py-1.5 border border-dashed border-zinc-800 rounded-lg">
              Slot vacío
            </p>
          </div>
        </div>

        <!-- Banquillo -->
        <div class="card">
          <p class="text-zinc-400 text-xs font-semibold uppercase tracking-wider mb-2">
            🪑 Banquillo
            <span class="text-zinc-600 font-normal normal-case">({{ banquillo.length }}/1) · no puntúa</span>
          </p>
          <div class="space-y-2">
            <div v-for="p in banquillo" :key="p.id" class="flex items-center gap-2 p-2 rounded-lg bg-zinc-800/30 opacity-70">
              <div class="w-1 h-7 rounded-full flex-shrink-0" :style="{ backgroundColor: p.escuderia?.color || '#888' }"></div>
              <div class="flex-1 min-w-0">
                <p class="text-white text-sm font-medium truncate">{{ p.first_name }} {{ p.last_name }}</p>
                <p class="text-zinc-500 text-xs">{{ p.escuderia?.name }}</p>
              </div>
              <button @click="abrirCambioRol(p)" class="text-zinc-500 hover:text-blue-400 transition-colors text-xs px-1" title="Cambiar rol">⇄</button>
              <button @click="eliminarPiloto(p)" class="text-zinc-600 hover:text-red-400 transition-colors text-xs px-1">✕</button>
            </div>
            <p v-if="banquillo.length === 0" class="text-zinc-700 text-sm text-center py-1.5 border border-dashed border-zinc-800 rounded-lg">
              Slot vacío
            </p>
          </div>
        </div>

        <!-- Escudería -->
        <div class="card">
          <p class="text-zinc-400 text-xs font-semibold uppercase tracking-wider mb-2">
            🏎️ Escudería
            <span class="text-zinc-600 font-normal normal-case">({{ escuderiaSeleccionada ? 1 : 0 }}/1) · suma puntos de sus 2 pilotos</span>
          </p>
          <div v-if="escuderiaSeleccionada" class="flex items-center gap-3 p-2 rounded-lg bg-zinc-800/50">
            <div class="w-3 h-3 rounded-full flex-shrink-0"
              :style="{ backgroundColor: escuderiaSeleccionada.color || '#E8002D' }"></div>
            <div class="flex-1">
              <p class="text-white text-sm font-medium">{{ escuderiaSeleccionada.name }}</p>
              <p class="text-zinc-500 text-xs">{{ formatearPrecio(escuderiaSeleccionada.price) }}</p>
            </div>
          </div>
          <p v-else class="text-zinc-700 text-sm text-center py-1.5 border border-dashed border-zinc-800 rounded-lg">
            Slot vacío
          </p>
        </div>
      </div>

      <!-- ── COLUMNA DERECHA: panel de selección ─────────────────────────── -->
      <div class="space-y-3">

        <!-- Tabs -->
        <div class="flex gap-1 bg-zinc-900 rounded-lg p-1">
          <button
            @click="tabActiva = 'pilotos'"
            :class="tabActiva === 'pilotos' ? 'bg-zinc-700 text-white' : 'text-zinc-400 hover:text-white'"
            class="flex-1 text-sm py-1.5 rounded transition-colors font-medium"
          >Pilotos</button>
          <button
            @click="tabActiva = 'escuderias'"
            :class="tabActiva === 'escuderias' ? 'bg-zinc-700 text-white' : 'text-zinc-400 hover:text-white'"
            class="flex-1 text-sm py-1.5 rounded transition-colors font-medium"
          >Escuderías</button>
        </div>

        <!-- Selector de rol -->
        <div v-if="tabActiva === 'pilotos'" class="flex gap-1.5">
          <button
            v-for="r in ROLES"
            :key="r.value"
            @click="rolSeleccionado = r.value"
            :disabled="contarRol(r.value) >= r.max"
            :class="[
              rolSeleccionado === r.value && contarRol(r.value) < r.max
                ? 'bg-red-600 text-white border-red-500'
                : contarRol(r.value) >= r.max
                  ? 'bg-zinc-900 text-zinc-700 border-zinc-800 cursor-not-allowed'
                  : 'bg-zinc-900 text-zinc-400 border-zinc-700 hover:border-zinc-500'
            ]"
            class="flex-1 text-xs py-1.5 px-1 rounded-lg border transition-colors text-center"
          >
            <span class="block font-medium">{{ r.label }}</span>
            <span class="block opacity-60">{{ contarRol(r.value) }}/{{ r.max }}</span>
          </button>
        </div>

        <!-- Lista de pilotos -->
        <div v-if="tabActiva === 'pilotos'" class="space-y-1.5 max-h-[400px] overflow-y-auto pr-1">
          <div
            v-for="piloto in f1Store.pilotos"
            :key="piloto.id"
            @click="añadirPiloto(piloto)"
            class="flex items-center gap-3 p-2.5 rounded-lg bg-zinc-900 border border-transparent transition-colors"
            :class="[
              pilotoYaEnEquipo(piloto) || contarRol(rolSeleccionado) >= limiteRol(rolSeleccionado)
                ? 'opacity-40 cursor-not-allowed'
                : 'hover:bg-zinc-800 hover:border-zinc-700 cursor-pointer'
            ]"
          >
            <div class="w-1 h-7 rounded-full flex-shrink-0"
              :style="{ backgroundColor: piloto.escuderia?.color || '#888' }"></div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-1.5">
                <p class="text-white text-sm font-medium truncate">
                  {{ piloto.first_name }} <strong>{{ piloto.last_name }}</strong>
                </p>
                <span v-if="piloto.es_reserva" class="text-xs bg-zinc-800 text-zinc-500 px-1 py-0.5 rounded flex-shrink-0">Reserva</span>
              </div>
              <p class="text-zinc-500 text-xs">{{ piloto.escuderia?.name || 'Sin escudería' }}</p>
            </div>
            <p class="text-red-400 text-sm font-semibold flex-shrink-0">{{ formatearPrecio(piloto.price) }}</p>
          </div>
        </div>

        <!-- Lista de escuderías -->
        <div v-if="tabActiva === 'escuderias'" class="space-y-1.5 max-h-[400px] overflow-y-auto pr-1">
          <div
            v-for="escuderia in f1Store.escuderias"
            :key="escuderia.id"
            @click="establecerEscuderia(escuderia)"
            class="flex items-center gap-3 p-2.5 rounded-lg bg-zinc-900 hover:bg-zinc-800 cursor-pointer transition-colors border border-transparent hover:border-zinc-700"
            :class="{ 'border-red-500/40 bg-red-500/5': escuderiaSeleccionada?.id === escuderia.id }"
          >
            <div class="w-3 h-3 rounded-full flex-shrink-0" :style="{ backgroundColor: escuderia.color || '#888' }"></div>
            <div class="flex-1">
              <p class="text-white text-sm font-medium">{{ escuderia.name }}</p>
              <p class="text-zinc-500 text-xs">{{ escuderia.nationality }}</p>
            </div>
            <p class="text-red-400 text-sm font-semibold">{{ formatearPrecio(escuderia.price) }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal: cambiar rol -->
    <div v-if="modalRol" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4" @click.self="modalRol = null">
      <div class="card w-full max-w-sm">
        <h3 class="font-bold text-white mb-1">Cambiar rol</h3>
        <p class="text-zinc-400 text-sm mb-4">
          {{ modalRol.first_name }} {{ modalRol.last_name }}
          <span class="text-zinc-600">· actualmente: {{ etiquetaRol(modalRol.pivot?.role) }}</span>
        </p>
        <div class="space-y-2">
          <button
            v-for="r in ROLES.filter(r => r.value !== modalRol.pivot?.role)"
            :key="r.value"
            @click="contarRolSin(r.value, modalRol.id) < r.max && cambiarRol(modalRol, r.value)"
            class="w-full text-left px-4 py-3 rounded-lg border transition-colors"
            :class="contarRolSin(r.value, modalRol.id) >= r.max
              ? 'border-zinc-800 text-zinc-600 cursor-not-allowed'
              : 'border-zinc-700 text-white hover:border-red-500 hover:bg-red-500/5 cursor-pointer'"
          >
            <p class="font-medium">{{ r.label }}</p>
            <p class="text-xs text-zinc-500 mt-0.5">
              {{ r.descripcion }}
              {{ contarRolSin(r.value, modalRol.id) >= r.max ? '— completo' : '' }}
            </p>
          </button>
        </div>
        <button @click="modalRol = null" class="mt-4 w-full btn-secondary text-sm">Cancelar</button>
      </div>
    </div>

    <!-- Toast -->
    <div v-if="toast" class="fixed bottom-6 left-1/2 -translate-x-1/2 bg-zinc-800 text-white text-sm px-5 py-3 rounded-xl shadow-lg border border-zinc-700 z-50 whitespace-nowrap">
      {{ toast }}
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useF1Store } from '@/stores/f1'
import { equipoService } from '@/services/leagueService'

const route   = useRoute()
const f1Store = useF1Store()
const ligaId  = route.params.ligaId

const equipo              = ref(null)
const presupuestoInicial  = ref(0)
const tabActiva           = ref('pilotos')
const toast               = ref('')
const modalRol            = ref(null)
let   timerToast          = null

const rolSeleccionado = ref('titular')

// Definición de roles con sus límites
const ROLES = [
  { value: 'titular',      label: 'Titular',      max: 2, descripcion: 'Puntúa normal' },
  { value: 'team_manager', label: 'Team Manager',  max: 1, descripcion: 'Puntúa x2' },
  { value: 'banquillo',    label: 'Banquillo',     max: 1, descripcion: 'No puntúa' },
]

// ─── Datos derivados ──────────────────────────────────────────────────────────

const todosLosPilotos = computed(() => equipo.value?.pilotos || [])

const titulares    = computed(() => todosLosPilotos.value.filter(p => p.pivot?.role === 'titular'))
const teamManagers = computed(() => todosLosPilotos.value.filter(p => p.pivot?.role === 'team_manager'))
const banquillo    = computed(() => todosLosPilotos.value.filter(p => p.pivot?.role === 'banquillo'))

const escuderiaSeleccionada = computed(() => equipo.value?.escuderias?.[0] || null)
const presupuestoRestante   = computed(() => equipo.value?.presupuesto_restante ?? presupuestoInicial.value)

const porcentajePresupuesto = computed(() => {
  if (!presupuestoInicial.value) return 0
  const gastado = presupuestoInicial.value - presupuestoRestante.value
  return Math.min(100, (gastado / presupuestoInicial.value) * 100)
})

const equipoValido = computed(() =>
  titulares.value.length === 2 &&
  teamManagers.value.length === 1 &&
  banquillo.value.length === 1 &&
  escuderiaSeleccionada.value !== null
)

// ─── Helpers ──────────────────────────────────────────────────────────────────

function contarRol(rol)            { return todosLosPilotos.value.filter(p => p.pivot?.role === rol).length }
function contarRolSin(rol, excId)  { return todosLosPilotos.value.filter(p => p.pivot?.role === rol && p.id !== excId).length }
function limiteRol(rol)            { return ROLES.find(r => r.value === rol)?.max ?? 0 }
function pilotoYaEnEquipo(piloto)  { return todosLosPilotos.value.some(p => p.id === piloto.id) }
function formatearPrecio(p)        { return (p / 1_000_000).toFixed(1) + 'M' }
function etiquetaRol(rol)          { return { titular: 'Titular', team_manager: 'Team Manager', banquillo: 'Banquillo' }[rol] || rol }

function mostrarToast(msg) {
  toast.value = msg
  clearTimeout(timerToast)
  timerToast = setTimeout(() => (toast.value = ''), 3000)
}

function abrirCambioRol(piloto) { modalRol.value = piloto }

// ─── Acciones ─────────────────────────────────────────────────────────────────

async function cargarEquipo() {
  try {
    const { data } = await equipoService.getEquipo(ligaId)
    equipo.value             = data.equipo
    presupuestoInicial.value = data.presupuesto_inicial
    // El remaining_budget viene dentro de equipo pero también lo exponemos aparte
    if (equipo.value) equipo.value.presupuesto_restante = data.presupuesto_restante
  } catch {}
}

async function añadirPiloto(piloto) {
  if (pilotoYaEnEquipo(piloto)) return
  if (contarRol(rolSeleccionado.value) >= limiteRol(rolSeleccionado.value)) return

  try {
    await equipoService.añadirPiloto(ligaId, piloto.id, rolSeleccionado.value)
    await cargarEquipo()
    mostrarToast(`${piloto.last_name} añadido como ${etiquetaRol(rolSeleccionado.value)}`)

    // Avanzar al siguiente rol con hueco si el actual ya está lleno
    const siguiente = ROLES.find(r => contarRol(r.value) < r.max && r.value !== rolSeleccionado.value)
    if (contarRol(rolSeleccionado.value) >= limiteRol(rolSeleccionado.value) && siguiente) {
      rolSeleccionado.value = siguiente.value
    }
  } catch (e) {
    mostrarToast(e.response?.data?.message || 'Error al añadir piloto')
  }
}

async function eliminarPiloto(piloto) {
  try {
    await equipoService.eliminarPiloto(ligaId, piloto.id)
    await cargarEquipo()
    mostrarToast(`${piloto.last_name} eliminado`)
  } catch {}
}

async function cambiarRol(piloto, nuevoRol) {
  modalRol.value = null
  try {
    await equipoService.cambiarRol(ligaId, piloto.id, nuevoRol)
    await cargarEquipo()
    mostrarToast(`${piloto.last_name} → ${etiquetaRol(nuevoRol)}`)
  } catch (e) {
    mostrarToast(e.response?.data?.message || 'Error al cambiar rol')
  }
}

async function establecerEscuderia(escuderia) {
  try {
    await equipoService.establecerEscuderia(ligaId, escuderia.id)
    await cargarEquipo()
    mostrarToast(`${escuderia.name} seleccionada`)
  } catch (e) {
    mostrarToast(e.response?.data?.message || 'Error al seleccionar escudería')
  }
}

// ─── Montaje ──────────────────────────────────────────────────────────────────

onMounted(async () => {
  await Promise.all([f1Store.fetchPilotos(), f1Store.fetchEscuderias()])
  await cargarEquipo()
})
</script>
