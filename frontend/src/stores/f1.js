import { defineStore } from 'pinia'
import { ref } from 'vue'
import { f1Service } from '@/services/f1Service'

export const useF1Store = defineStore('f1', () => {
  const pilotos    = ref([])
  const escuderias = ref([])
  const carreras   = ref([])
  const proximaCarrera = ref(null)
  const cargando   = ref(false)
  const error      = ref(null)

  async function fetchPilotos() {
    cargando.value = true
    try {
      const { data } = await f1Service.getPilotos()
      pilotos.value = data
    } catch (e) {
      error.value = e.message
    } finally {
      cargando.value = false
    }
  }

  async function fetchEscuderias() {
    cargando.value = true
    try {
      const { data } = await f1Service.getEscuderias()
      escuderias.value = data
    } catch (e) {
      error.value = e.message
    } finally {
      cargando.value = false
    }
  }

  async function fetchCarreras() {
    cargando.value = true
    try {
      const { data } = await f1Service.getCarreras()
      carreras.value = data
    } catch (e) {
      error.value = e.message
    } finally {
      cargando.value = false
    }
  }

  async function fetchProximaCarrera() {
    try {
      const { data } = await f1Service.getProximaCarrera()
      proximaCarrera.value = data
    } catch (e) {
      error.value = e.message
    }
  }

  return {
    pilotos, escuderias, carreras, proximaCarrera, cargando, error,
    fetchPilotos, fetchEscuderias, fetchCarreras, fetchProximaCarrera,
  }
})
