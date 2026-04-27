import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authService } from '@/services/authService'

export const useAuthStore = defineStore('auth', () => {
  const user  = ref(null)
  const token = ref(localStorage.getItem('auth_token'))

  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const isAdmin         = computed(() => user.value?.role === 'admin')

  async function login(credentials) {
    const { data } = await authService.login(credentials)
    token.value = data.token
    user.value  = data.user
    localStorage.setItem('auth_token', data.token)
  }

  async function register(userData) {
    const { data } = await authService.register(userData)
    token.value = data.token
    user.value  = data.user
    localStorage.setItem('auth_token', data.token)
  }

  async function logout() {
    try {
      await authService.logout()
    } finally {
      user.value  = null
      token.value = null
      localStorage.removeItem('auth_token')
    }
  }

  async function fetchUser() {
    try {
      const { data } = await authService.me()
      user.value = data
    } catch {
      user.value  = null
      token.value = null
      localStorage.removeItem('auth_token')
    }
  }

  async function actualizarPerfil(datos) {
    const { data } = await authService.actualizarPerfil(datos)
    user.value = data.user
    return data
  }

  return { user, token, isAuthenticated, isAdmin, login, register, logout, fetchUser, actualizarPerfil }
})
