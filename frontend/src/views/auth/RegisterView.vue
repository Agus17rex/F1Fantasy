<template>
  <div class="min-h-screen bg-zinc-950 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <div class="text-center mb-8">
        <div class="inline-flex items-center gap-1 mb-2">
          <span class="text-red-500 font-black text-4xl tracking-tight">F1</span>
          <span class="text-white font-black text-4xl tracking-tight">FANTASY</span>
        </div>
        <p class="text-zinc-400 text-sm">Crea tu cuenta y únete a la competición</p>
      </div>

      <div class="card">
        <form @submit.prevent="handleRegister" class="space-y-4">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm text-zinc-400 mb-1.5">Nombre</label>
              <input v-model="form.nombre" type="text" class="input" placeholder="Tu nombre" required />
            </div>
            <div>
              <label class="block text-sm text-zinc-400 mb-1.5">Usuario</label>
              <input v-model="form.usuario" type="text" class="input" placeholder="usuario123" required />
            </div>
          </div>

          <div>
            <label class="block text-sm text-zinc-400 mb-1.5">Email</label>
            <input v-model="form.email" type="email" class="input" placeholder="tu@email.com" required />
          </div>

          <div>
            <label class="block text-sm text-zinc-400 mb-1.5">Contraseña</label>
            <input v-model="form.password" type="password" class="input" placeholder="Mínimo 8 caracteres" required />
          </div>

          <div>
            <label class="block text-sm text-zinc-400 mb-1.5">Confirmar contraseña</label>
            <input v-model="form.password_confirmation" type="password" class="input" placeholder="Repite tu contraseña" required />
          </div>

          <p v-if="error" class="text-red-400 text-sm bg-red-500/10 border border-red-500/20 rounded-lg px-3 py-2">
            {{ error }}
          </p>

          <button type="submit" class="btn-primary w-full" :disabled="loading">
            <span v-if="loading">Creando cuenta...</span>
            <span v-else>Crear cuenta</span>
          </button>
        </form>

        <p class="text-center text-zinc-500 text-sm mt-5">
          ¿Ya tienes cuenta?
          <RouterLink to="/login" class="text-red-400 hover:text-red-300 font-medium ml-1">
            Inicia sesión
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

const form = ref({
  nombre: '', usuario: '', email: '',
  password: '', password_confirmation: '',
})
const loading = ref(false)
const error   = ref('')

async function handleRegister() {
  error.value   = ''
  loading.value = true
  try {
    await authStore.register(form.value)
    router.push('/')
  } catch (e) {
    const errors = e.response?.data?.errors
    if (errors) {
      error.value = Object.values(errors).flat().join(' ')
    } else {
      error.value = e.response?.data?.message || 'Error al registrarse'
    }
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.card {
  border-color: rgba(255, 24, 1, 0.28);
  box-shadow: 0 0 14px rgba(255, 24, 1, 0.06);
}
.input {
  border-color: rgba(255, 24, 1, 0.20);
}
.input:focus {
  border-color: rgba(255, 24, 1, 0.7);
  box-shadow: 0 0 0 2px rgba(255, 24, 1, 0.12);
}
</style>
