<template>
  <div class="min-h-screen bg-zinc-950 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <!-- Logo -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center gap-1 mb-2">
          <span class="text-red-500 font-black text-4xl tracking-tight">F1</span>
          <span class="text-white font-black text-4xl tracking-tight">FANTASY</span>
        </div>
        <p class="text-zinc-400 text-sm">Inicia sesión para continuar</p>
      </div>

      <!-- Form -->
      <div class="card">
        <form @submit.prevent="handleLogin" class="space-y-4">
          <div>
            <label class="block text-sm text-zinc-400 mb-1.5">Email</label>
            <input
              v-model="form.email"
              type="email"
              class="input"
              placeholder="tu@email.com"
              required
            />
          </div>

          <div>
            <label class="block text-sm text-zinc-400 mb-1.5">Contraseña</label>
            <input
              v-model="form.password"
              type="password"
              class="input"
              placeholder="••••••••"
              required
            />
          </div>

          <p v-if="error" class="text-red-400 text-sm bg-red-500/10 border border-red-500/20 rounded-lg px-3 py-2 leading-relaxed">
            {{ error }}
          </p>

          <button type="submit" class="btn-primary w-full" :disabled="loading">
            <span v-if="loading">Iniciando sesión...</span>
            <span v-else>Iniciar sesión</span>
          </button>
        </form>

        <p class="text-center text-zinc-500 text-sm mt-5">
          ¿No tienes cuenta?
          <RouterLink to="/register" class="text-red-400 hover:text-red-300 font-medium ml-1">
            Regístrate
          </RouterLink>
        </p>
      </div>

      <!-- Aviso de red -->
      <div class="mt-4 rounded-xl border border-amber-500/20 bg-amber-500/5 px-4 py-3 flex gap-3">
        <span class="text-amber-400 text-lg flex-shrink-0 mt-0.5">⚠️</span>
        <div class="text-xs text-zinc-400 space-y-1">
          <p class="text-amber-400 font-semibold">¿No puedes iniciar sesión?</p>
          <p>Si accedes desde el móvil u otro dispositivo, asegúrate de estar conectado a la <span class="text-white font-medium">misma red WiFi</span> que el servidor.</p>
          <p>El servidor debe estar encendido y ejecutándose en el PC principal.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const router    = useRouter()

const form    = ref({ email: '', password: '' })
const loading = ref(false)
const error   = ref('')

async function handleLogin() {
  error.value   = ''
  loading.value = true
  try {
    await authStore.login(form.value)
    const redirect = router.currentRoute.value.query.redirect || '/'
    router.push(redirect)
  } catch (e) {
    const status = e.response?.status

    if (!e.response) {
      // Sin respuesta del servidor: caído, red incorrecta, CORS…
      error.value = '⚠️ No se puede conectar con el servidor. Comprueba que estás en la misma red WiFi que el servidor y que éste está en marcha.'
    } else if (status === 401) {
      error.value = 'Email o contraseña incorrectos. Revisa tus datos e inténtalo de nuevo.'
    } else if (status === 422) {
      // Errores de validación — Laravel devuelve { errors: { campo: [...] } }
      const errors = e.response.data?.errors
      if (errors) {
        error.value = Object.values(errors).flat().join(' ')
      } else {
        error.value = e.response.data?.message || 'Datos inválidos.'
      }
    } else if (status === 429) {
      error.value = 'Demasiados intentos fallidos. Espera unos minutos antes de volver a intentarlo.'
    } else if (status >= 500) {
      error.value = `Error interno del servidor (${status}). Contacta al administrador si persiste.`
    } else {
      error.value = e.response?.data?.message || `Error inesperado (${status ?? 'sin respuesta'}).`
    }
  } finally {
    loading.value = false
  }
}
</script>
