<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h2 class="text-xl font-bold text-white">Mis Ligas</h2>
      <div class="flex gap-2">
        <button @click="mostrarUnirse = true" class="btn-outline text-sm">Unirme a liga</button>
        <button @click="mostrarCrear = true" class="btn-primary text-sm">+ Crear liga</button>
      </div>
    </div>

    <!-- Lista de ligas -->
    <div v-if="cargando" class="text-zinc-400 text-center py-10">Cargando...</div>

    <div v-else-if="ligas.length === 0" class="card text-center py-10">
      <div class="text-4xl mb-3">🏆</div>
      <h3 class="font-semibold text-white mb-1">Aún no estás en ninguna liga</h3>
      <p class="text-zinc-400 text-sm mb-4">Crea una liga o únete con un código de invitación</p>
      <div class="flex gap-2 justify-center">
        <button @click="mostrarUnirse = true" class="btn-outline text-sm">Unirme con código</button>
        <button @click="mostrarCrear = true" class="btn-primary text-sm">Crear liga</button>
      </div>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <RouterLink
        v-for="liga in ligas"
        :key="liga.id"
        :to="`/ligas/${liga.id}`"
        class="card hover:border-zinc-600 transition-colors"
      >
        <div class="flex items-start justify-between">
          <div>
            <h3 class="font-semibold text-white">{{ liga.nombre }}</h3>
            <p class="text-zinc-400 text-sm mt-0.5">{{ liga.miembros_count }} / {{ liga.max_miembros }} miembros</p>
          </div>
          <span class="text-xs bg-zinc-800 text-zinc-400 px-2 py-0.5 rounded font-mono">
            {{ liga.codigo }}
          </span>
        </div>
        <p v-if="liga.descripcion" class="text-zinc-500 text-sm mt-2 line-clamp-2">{{ liga.descripcion }}</p>
        <div class="mt-3 flex items-center justify-between">
          <span :class="liga.es_privada ? 'text-amber-400' : 'text-green-400'" class="text-xs font-medium">
            {{ liga.es_privada ? '🔒 Privada' : '🌐 Pública' }}
          </span>
          <div class="text-right">
            <span class="text-zinc-500 text-xs">T{{ liga.temporada }}</span>
            <span class="text-zinc-600 text-xs mx-1">·</span>
            <span class="text-red-400 text-xs font-medium">{{ (liga.presupuesto_inicial / 1_000_000).toFixed(0) }}M €</span>
          </div>
        </div>
      </RouterLink>
    </div>

    <!-- Modal: Crear liga -->
    <Teleport to="body">
      <div v-if="mostrarCrear" class="fixed inset-0 bg-black/70 z-50 flex items-center justify-center p-4" @click.self="mostrarCrear = false">
        <div class="card w-full max-w-md max-h-[90vh] overflow-y-auto">
          <h3 class="font-bold text-white text-lg mb-4">Crear nueva liga</h3>
          <form @submit.prevent="handleCrear" class="space-y-4">
            <div>
              <label class="block text-sm text-zinc-400 mb-1.5">Nombre de la liga</label>
              <input v-model="formularioCrear.nombre" type="text" class="input" placeholder="Mi Liga F1" required />
            </div>
            <div>
              <label class="block text-sm text-zinc-400 mb-1.5">Descripción (opcional)</label>
              <textarea v-model="formularioCrear.descripcion" class="input resize-none h-20" placeholder="Describe tu liga..."></textarea>
            </div>
            <div>
              <label class="block text-sm text-zinc-400 mb-1.5">Máximo de miembros</label>
              <input v-model.number="formularioCrear.max_miembros" type="number" class="input" min="2" max="50" />
            </div>
            <div>
              <label class="block text-sm text-zinc-400 mb-1.5">
                Presupuesto por equipo
                <span class="text-zinc-600 font-normal">— cada jugador empieza con este dinero</span>
              </label>
              <div class="flex items-center gap-2">
                <input
                  v-model.number="presupuestoMillon"
                  type="number"
                  class="input w-28 text-center"
                  min="10"
                  max="200"
                  step="5"
                />
                <span class="text-zinc-400 text-sm">millones €</span>
              </div>
              <p class="text-zinc-600 text-xs mt-1">
                Mín. 10M · Máx. 200M · Recomendado: 30–60M
              </p>
            </div>
            <p v-if="errorCrear" class="text-red-400 text-sm">{{ errorCrear }}</p>
            <div class="flex gap-2 justify-end pt-2">
              <button type="button" @click="mostrarCrear = false" class="btn-secondary text-sm">Cancelar</button>
              <button type="submit" class="btn-primary text-sm" :disabled="cargandoCrear">
                {{ cargandoCrear ? 'Creando...' : 'Crear liga' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Modal: Unirse -->
    <Teleport to="body">
      <div v-if="mostrarUnirse" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4" @click.self="mostrarUnirse = false">
        <div class="card w-full max-w-sm">
          <h3 class="font-bold text-white text-lg mb-4">Unirse a una liga</h3>
          <form @submit.prevent="handleUnirse" class="space-y-4">
            <div>
              <label class="block text-sm text-zinc-400 mb-1.5">Código de invitación</label>
              <input
                v-model="codigoUnirse"
                type="text"
                class="input uppercase font-mono tracking-widest"
                placeholder="XXXXXXXX"
                maxlength="8"
                required
              />
            </div>
            <p v-if="errorUnirse" class="text-red-400 text-sm">{{ errorUnirse }}</p>
            <div class="flex gap-2 justify-end pt-2">
              <button type="button" @click="mostrarUnirse = false" class="btn-secondary text-sm">Cancelar</button>
              <button type="submit" class="btn-primary text-sm" :disabled="cargandoUnirse">
                {{ cargandoUnirse ? 'Uniéndose...' : 'Unirse' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { ligaService } from '@/services/leagueService'

const ligas         = ref([])
const cargando      = ref(false)
const mostrarCrear  = ref(false)
const mostrarUnirse = ref(false)
const codigoUnirse  = ref('')
const errorUnirse   = ref('')
const cargandoUnirse = ref(false)
const errorCrear    = ref('')
const cargandoCrear = ref(false)
const formularioCrear  = ref({ nombre: '', descripcion: '', max_miembros: 20, es_privada: true })
const presupuestoMillon = ref(50)   // valor en millones que ve el usuario

async function cargarLigas() {
  cargando.value = true
  try {
    const { data } = await ligaService.getLigas()
    ligas.value = data
  } finally {
    cargando.value = false
  }
}

async function handleCrear() {
  errorCrear.value    = ''
  cargandoCrear.value = true
  try {
    await ligaService.crearLiga({
      ...formularioCrear.value,
      presupuesto_inicial: presupuestoMillon.value * 1_000_000,
    })
    mostrarCrear.value = false
    formularioCrear.value = { nombre: '', descripcion: '', max_miembros: 20, es_privada: true }
    presupuestoMillon.value = 50
    await cargarLigas()
  } catch (e) {
    errorCrear.value = e.response?.data?.message || 'Error al crear la liga'
  } finally {
    cargandoCrear.value = false
  }
}

async function handleUnirse() {
  errorUnirse.value   = ''
  cargandoUnirse.value = true
  try {
    await ligaService.unirseALiga(codigoUnirse.value)
    mostrarUnirse.value = false
    codigoUnirse.value  = ''
    await cargarLigas()
  } catch (e) {
    errorUnirse.value = e.response?.data?.message || 'Código inválido o liga llena'
  } finally {
    cargandoUnirse.value = false
  }
}

onMounted(cargarLigas)
</script>
