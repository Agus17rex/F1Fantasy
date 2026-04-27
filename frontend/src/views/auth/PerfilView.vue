<template>
  <div class="max-w-xl mx-auto space-y-6">

    <!-- Avatar + nombre -->
    <div class="card flex items-center gap-5">
      <div class="w-16 h-16 rounded-full bg-red-600 flex items-center justify-center text-2xl font-black text-white flex-shrink-0">
        {{ authStore.user?.name?.charAt(0).toUpperCase() }}
      </div>
      <div>
        <h2 class="text-xl font-black text-white">{{ authStore.user?.name }}</h2>
        <p class="text-zinc-400 text-sm">@{{ authStore.user?.username }}</p>
        <p class="text-zinc-500 text-xs mt-0.5">{{ authStore.user?.email }}</p>
      </div>
    </div>

    <!-- Formulario de edición -->
    <div class="card space-y-5">
      <h3 class="font-semibold text-white">Editar perfil</h3>

      <form @submit.prevent="guardar" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm text-zinc-400 mb-1.5">Nombre</label>
            <input v-model="form.name" type="text" class="input" placeholder="Tu nombre" />
          </div>
          <div>
            <label class="block text-sm text-zinc-400 mb-1.5">Usuario</label>
            <div class="relative">
              <span class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-500 text-sm">@</span>
              <input v-model="form.username" type="text" class="input pl-7" placeholder="usuario" />
            </div>
          </div>
        </div>

        <div>
          <label class="block text-sm text-zinc-400 mb-1.5">Email</label>
          <input v-model="form.email" type="email" class="input" placeholder="tu@email.com" />
        </div>

        <!-- Mensaje resultado -->
        <p v-if="exito" class="text-green-400 text-sm bg-green-500/10 border border-green-500/20 rounded-lg px-4 py-3">
          ✓ {{ exito }}
        </p>
        <p v-if="errorMsg" class="text-red-400 text-sm bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-3">
          ✗ {{ errorMsg }}
        </p>

        <button type="submit" class="btn-primary w-full" :disabled="guardando">
          {{ guardando ? 'Guardando...' : 'Guardar cambios' }}
        </button>
      </form>
    </div>

    <!-- Cambio de contraseña -->
    <div class="card space-y-5">
      <h3 class="font-semibold text-white">Cambiar contraseña</h3>

      <form @submit.prevent="cambiarPassword" class="space-y-4">
        <div>
          <label class="block text-sm text-zinc-400 mb-1.5">Contraseña actual</label>
          <input v-model="passForm.password_actual" type="password" class="input" placeholder="••••••••" />
        </div>
        <div>
          <label class="block text-sm text-zinc-400 mb-1.5">Nueva contraseña</label>
          <input v-model="passForm.password" type="password" class="input" placeholder="Mínimo 8 caracteres" />
        </div>
        <div>
          <label class="block text-sm text-zinc-400 mb-1.5">Confirmar nueva contraseña</label>
          <input v-model="passForm.password_confirmation" type="password" class="input" placeholder="Repite la contraseña" />
        </div>

        <p v-if="exitoPass" class="text-green-400 text-sm bg-green-500/10 border border-green-500/20 rounded-lg px-4 py-3">
          ✓ {{ exitoPass }}
        </p>
        <p v-if="errorPass" class="text-red-400 text-sm bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-3">
          ✗ {{ errorPass }}
        </p>

        <button type="submit" class="btn-secondary w-full" :disabled="guardandoPass">
          {{ guardandoPass ? 'Cambiando...' : 'Cambiar contraseña' }}
        </button>
      </form>
    </div>

    <!-- Cerrar sesión -->
    <div class="card">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="font-semibold text-white">Cerrar sesión</h3>
          <p class="text-zinc-500 text-sm mt-0.5">Salir de tu cuenta en este dispositivo</p>
        </div>
        <button @click="handleLogout" class="btn-secondary text-sm border-red-500/30 text-red-400 hover:bg-red-500/10 hover:border-red-500">
          Cerrar sesión
        </button>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const router    = useRouter()

// Formulario de datos básicos
const form     = ref({ name: '', username: '', email: '' })
const guardando = ref(false)
const exito     = ref('')
const errorMsg  = ref('')

// Formulario de contraseña
const passForm      = ref({ password_actual: '', password: '', password_confirmation: '' })
const guardandoPass = ref(false)
const exitoPass     = ref('')
const errorPass     = ref('')

onMounted(() => {
  // Pre-rellenar con los datos actuales del usuario
  form.value = {
    name:     authStore.user?.name     || '',
    username: authStore.user?.username || '',
    email:    authStore.user?.email    || '',
  }
})

async function guardar() {
  guardando.value = true
  exito.value     = ''
  errorMsg.value  = ''
  try {
    const data = await authStore.actualizarPerfil(form.value)
    exito.value = data.message || 'Perfil actualizado correctamente'
  } catch (e) {
    const errors = e.response?.data?.errors
    if (errors) {
      errorMsg.value = Object.values(errors).flat().join(' · ')
    } else {
      errorMsg.value = e.response?.data?.message || 'Error al guardar'
    }
  } finally {
    guardando.value = false
  }
}

async function cambiarPassword() {
  guardandoPass.value = true
  exitoPass.value     = ''
  errorPass.value     = ''
  try {
    const data = await authStore.actualizarPerfil(passForm.value)
    exitoPass.value = data.message || 'Contraseña cambiada correctamente'
    passForm.value  = { password_actual: '', password: '', password_confirmation: '' }
  } catch (e) {
    const errors = e.response?.data?.errors
    if (errors) {
      errorPass.value = Object.values(errors).flat().join(' · ')
    } else {
      errorPass.value = e.response?.data?.message || 'Error al cambiar contraseña'
    }
  } finally {
    guardandoPass.value = false
  }
}

async function handleLogout() {
  await authStore.logout()
  router.push('/login')
}
</script>
