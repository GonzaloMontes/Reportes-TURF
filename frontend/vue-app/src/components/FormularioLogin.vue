<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          Sistema de Reportes TURF
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          Inicia sesión para acceder a los reportes
        </p>
      </div>
      
      <form class="mt-8 space-y-6" @submit.prevent="manejarLogin">
        <!-- Mensaje de error -->
        <div v-if="authStore.error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
          {{ authStore.error }}
        </div>

        <div class="rounded-md shadow-sm -space-y-px">
          <div>
            <label for="usuario" class="sr-only">Usuario</label>
            <input
              id="usuario"
              v-model="credenciales.usuario"
              name="usuario"
              type="text"
              required
              class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
              placeholder="Usuario"
            />
          </div>
          <div>
            <label for="contrasena" class="sr-only">Contraseña</label>
            <input
              id="contrasena"
              v-model="credenciales.contrasena"
              name="contrasena"
              type="password"
              required
              class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
              placeholder="Contraseña"
            />
          </div>
        </div>

        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <input
              id="recordar"
              v-model="credenciales.recordar"
              name="recordar"
              type="checkbox"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
            <label for="recordar" class="ml-2 block text-sm text-gray-900">
              Recordar sesión
            </label>
          </div>
        </div>

        <div>
          <button
            type="submit"
            :disabled="authStore.cargando"
            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
          >
            <span v-if="authStore.cargando" class="loading-spinner mr-2">
              <i class="fas fa-spinner"></i>
            </span>
            {{ authStore.cargando ? 'Iniciando sesión...' : 'Iniciar Sesión' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useAuthStore } from '../stores/auth'

// Store de autenticación
const authStore = useAuthStore()

// Estado del formulario
const credenciales = ref({
  usuario: '',
  contrasena: '',
  recordar: false
})

/**
 * Maneja el envío del formulario de login
 */
async function manejarLogin() {
  const resultado = await authStore.iniciarSesion(credenciales.value)
  
  if (!resultado.exito) {
    // El error ya se maneja en el store
    console.error('Error en login:', resultado.mensaje)
  }
  // Si es exitoso, App.vue automáticamente cambiará a la vista principal
}
</script>
