<template>
  <div class="min-h-screen flex items-center justify-center relative overflow-hidden bg-login">
    
    <div class="relative z-10 w-full max-w-md p-8 md:p-10 rounded-2xl"> 
      
      <div class="flex justify-center mb-6">
        <img 
          src="/logo.png" 
          alt="Turfbet Logo" 
          class="h-32 w-auto drop-shadow-2xl" 
          @error="handleLogoError"
        />
      </div>
      
      <h1 class="text-3xl md:text-4xl font-bold text-gray-900 text-center mb-1">
        Sistema de Reportes TURF
      </h1>
      
      <p class="text-center text-gray-700 text-base mb-8 font-normal">
        Inicia sesión para acceder a los reportes
      </p>
      
      <form @submit.prevent="manejarLogin" class="space-y-4">
        
        <div 
          v-if="authStore.error" 
          class="bg-red-50/90 backdrop-blur-sm border border-red-400 text-red-800 px-4 py-3 rounded-xl shadow-lg"
        >
          <i class="fas fa-exclamation-triangle mr-2"></i>
          {{ authStore.error }}
        </div>

        <div class="relative">
          <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
            <i class="fas fa-user text-gray-500 text-xl"></i> 
          </div>
          <input
            v-model="credenciales.usuario"
            type="text"
            required
            placeholder="Usuario"
            class="w-full pl-14 pr-4 py-4 bg-white/95 backdrop-blur-sm border-2 border-gray-300 rounded-xl shadow-md text-gray-900 text-base placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
          />
        </div>

        <div class="relative">
          <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
            <i class="fas fa-lock text-gray-500 text-xl"></i>
          </div>
          <input
            v-model="credenciales.contrasena"
            type="password"
            required
            placeholder="••••••"
            class="w-full pl-14 pr-4 py-4 bg-white/95 backdrop-blur-sm border-2 border-gray-300 rounded-xl shadow-md text-gray-900 text-base placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
          />
        </div>

        <div class="flex items-center pt-2 pb-2">
          <input
            id="recordar"
            v-model="credenciales.recordar"
            type="checkbox"
            class="w-5 h-5 rounded border-2 border-gray-400 text-blue-600 focus:ring-2 focus:ring-blue-500/50 cursor-pointer"
          />
          <label 
            for="recordar" 
            class="ml-3 text-gray-800 font-normal cursor-pointer select-none"
          >
            Recordar sesión
          </label>
        </div>

        <button
          type="submit"
          :disabled="authStore.cargando"
          class="w-full py-4 mt-4 bg-gradient-to-r from-[#5e72e4] to-[#4c63d2] text-white text-lg font-semibold rounded-xl shadow-xl hover:shadow-blue-500/60 hover:from-[#4c63d2] hover:to-[#3e53c0] hover:scale-[1.01] active:scale-[0.99] transition-all disabled:opacity-50 disabled:cursor-not-allowed"
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
// Se asume que la ruta es correcta, sino usar 'api/stores/auth' o similar.
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
/* Fondo con imagen difuminada */
.bg-login {
  position: relative;
}

/* Aplica la imagen de fondo con blur */
.bg-login::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  /* Usar la ruta correcta para la imagen de fondo (asumo /fondo.png o /login-bg.jpg) */
  background-image: url('/fondo.png'); 
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  filter: blur(5px); /* Se ajusta el blur a 5px para que sea más sutil pero visible */
  z-index: -1;
}

/* Animación del spinner (se mantiene para consistencia) */
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