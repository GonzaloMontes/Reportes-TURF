<template>
  <!-- Fondo con imagen difuminada -->
  <div class="min-h-screen flex items-center justify-center relative overflow-hidden bg-login">
    <!-- Contenedor del login -->
    <div class="relative z-10 w-full max-w-md px-6">
      <!-- Logo TURF -->
      <div class="flex justify-center mb-6">
        <img 
          src="/logo.png" 
          alt="Turfbet Logo" 
          class="h-36 w-auto drop-shadow-2xl"
          @error="handleLogoError"
        />
      </div>
      
      <!-- Contenedor visual: agrupa título + formulario -->
      <div class="bg-slate-200/90 backdrop-blur-sm ring-1 ring-slate-300 rounded-2xl p-6 shadow-xl space-y-4">
      <!-- Título -->
      <h1 class="text-3xl md:text-4xl font-bold text-[#1e3a5f] text-center mb-2">
        Reportes TURF
      </h1>
      
      <!-- Subtítulo -->
      <p class="text-center text-[#4a5568] text-base mb-8 font-normal">
        Inicia sesión para acceder a los reportes
      </p>
      
      <!-- Formulario -->
      <form @submit.prevent="manejarLogin" class="space-y-4">
        <!-- Mensaje de error -->
        <div 
          v-if="authStore.error" 
          class="bg-red-50/90 backdrop-blur-sm border border-red-400 text-red-800 px-4 py-3 rounded-xl shadow-lg"
        >
          <i class="fas fa-exclamation-triangle mr-2"></i>
          {{ authStore.error }}
        </div>

        <!-- Input Usuario -->
        <div class="relative">
          <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
            <i class="fas fa-user text-[#6b7280] text-xl"></i>
          </div>
          <input
            v-model="credenciales.usuario"
            type="text"
            required
            placeholder="carreras2024"
            class="w-full pl-14 pr-4 py-4 bg-white/95 backdrop-blur-sm border-0 rounded-2xl shadow-md text-gray-900 text-base placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-400/50 transition-all"
          />
        </div>

        <!-- Input Contraseña -->
        <div class="relative">
          <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
            <i class="fas fa-lock text-[#6b7280] text-xl"></i>
          </div>
          <input
            v-model="credenciales.contrasena"
            type="password"
            required
            placeholder="••••••"
            class="w-full pl-14 pr-4 py-4 bg-white/95 backdrop-blur-sm border-0 rounded-2xl shadow-md text-gray-900 text-base placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-400/50 transition-all"
          />
        </div>

        <!-- Checkbox Recordar sesión -->
        <div class="flex items-center pt-2">
          <input
            id="recordar"
            v-model="credenciales.recordar"
            type="checkbox"
            class="w-5 h-5 rounded border-2 border-gray-400 text-blue-600 focus:ring-2 focus:ring-blue-500/50 cursor-pointer"
          />
          <label 
            for="recordar" 
            class="ml-3 text-gray-900 font-medium cursor-pointer select-none"
          >
            Recordar sesión
          </label>
        </div>

        <!-- Botón Iniciar Sesión -->
        <button
          type="submit"
          :disabled="authStore.cargando"
          class="w-full py-4 mt-6 bg-gradient-to-r from-blue-600 to-blue-500 text-white text-lg font-semibold rounded-xl shadow-2xl hover:shadow-blue-500/50 hover:from-blue-500 hover:to-blue-400 hover:scale-[1.02] active:scale-[0.98] transition-all disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <i v-if="authStore.cargando" class="fas fa-spinner fa-spin mr-2"></i>
          {{ authStore.cargando ? 'Iniciando sesión...' : 'Iniciar Sesión' }}
        </button>
      </form>
      </div>
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
    console.error('Error en login:', resultado.mensaje)
  }
}

/**
 * Maneja error de carga del logo (fallback a icono)
 */
function handleLogoError(event) {
  // Si el logo no carga, mostrar un placeholder
  event.target.style.display = 'none'
  const placeholder = document.createElement('div')
  placeholder.innerHTML = '<i class="fas fa-horse text-7xl text-green-500 drop-shadow-lg"></i>'
  event.target.parentNode.appendChild(placeholder)
}
</script>

<style scoped>
/* Fondo con imagen */
.bg-login {
  background-image: url('/fondo.png');
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  position: relative;
}

/* Animación del spinner */
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.fa-spinner {
  animation: spin 1s linear infinite;
}
</style>
