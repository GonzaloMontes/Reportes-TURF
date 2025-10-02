<template>
  <!-- Fondo con efecto de césped difuminado -->
  <div class="min-h-screen flex items-center justify-center relative overflow-hidden bg-login">
    <!-- Capa de blur simulando pista -->
    <div class="absolute inset-0 blur-layer"></div>
    
    <!-- Overlay suave para contraste -->
    <div class="absolute inset-0 bg-gradient-to-b from-white/20 via-transparent to-white/10"></div>
    
    <!-- Contenedor del login -->
    <div class="relative z-10 w-full max-w-md px-6">
      <!-- Logo TURF -->
      <div class="flex justify-center mb-8">
        <img 
          src="/logo.png" 
          alt="Turfbet Logo" 
          class="h-32 w-auto drop-shadow-lg"
          @error="handleLogoError"
        />
      </div>
      
      <!-- Título -->
      <h1 class="text-4xl md:text-5xl font-bold text-gray-900 text-center mb-12 drop-shadow-md">
        Sistema de Reportes TURF
      </h1>
      
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
          <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
            <i class="fas fa-user text-gray-500 text-lg"></i>
          </div>
          <input
            v-model="credenciales.usuario"
            type="text"
            required
            placeholder="carreras2024"
            class="w-full pl-12 pr-4 py-4 bg-white/95 backdrop-blur-sm border-0 rounded-xl shadow-lg text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 transition-all"
          />
        </div>

        <!-- Input Contraseña -->
        <div class="relative">
          <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
            <i class="fas fa-lock text-gray-500 text-lg"></i>
          </div>
          <input
            v-model="credenciales.contrasena"
            type="password"
            required
            placeholder="••••••"
            class="w-full pl-12 pr-4 py-4 bg-white/95 backdrop-blur-sm border-0 rounded-xl shadow-lg text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 transition-all"
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
          class="w-full py-4 mt-6 bg-gradient-to-r from-[#4169e1] to-[#3a5fcd] text-white text-lg font-semibold rounded-xl shadow-2xl hover:shadow-blue-500/50 hover:from-[#3a5fcd] hover:to-[#2e4db8] hover:scale-[1.02] active:scale-[0.98] transition-all disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <i v-if="authStore.cargando" class="fas fa-spinner fa-spin mr-2"></i>
          {{ authStore.cargando ? 'Iniciando sesión...' : 'Iniciar Sesión' }}
        </button>
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
/* Fondo verde césped con efecto blur - replica la imagen */
.bg-login {
  background: linear-gradient(
    to bottom,
    #d4d4d4 0%,
    #c8d2b4 15%,
    #b4c878 35%,
    #9cb860 55%,
    #8caa5a 75%,
    #7a9850 100%
  );
  position: relative;
}

/* Capa de desenfoque para simular profundidad de campo */
.blur-layer {
  background: 
    radial-gradient(ellipse at 50% 30%, rgba(255, 255, 255, 0.4) 0%, transparent 50%),
    radial-gradient(ellipse at 20% 60%, rgba(180, 200, 120, 0.3) 0%, transparent 40%),
    radial-gradient(ellipse at 80% 70%, rgba(140, 170, 90, 0.2) 0%, transparent 45%);
  filter: blur(40px);
  pointer-events: none;
  z-index: 0;
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
