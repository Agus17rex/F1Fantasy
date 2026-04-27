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

          <p v-if="error" class="text-red-400 text-sm bg-red-500/10 border border-red-500/20 rounded-lg px-3 py-2">
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
    error.value = e.response?.data?.message || 'Error al iniciar sesión'
  } finally {
    loading.value = false
  }
}
</script>
