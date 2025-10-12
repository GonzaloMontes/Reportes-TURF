<template>
  <!-- Navbar.vue: encabezado superior de la aplicación -->
  <header class="bg-white shadow-sm border-b border-gray-200">
    <div class="flex justify-between items-center px-4 py-3">
      <div class="flex items-center">
        <button @click="$emit('toggle-menu')" class="md:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100">
          <i class="fas fa-bars"></i>
        </button>
        <h1 class="ml-2 text-xl font-semibold text-gray-900">Sistema de Reportes TURF</h1>
      </div>
      <div class="flex items-center space-x-4">
        <span class="text-sm text-gray-700">
          {{ authStore.nombreUsuario }} ({{ authStore.usuario?.role }})
        </span>
        <button @click="authStore.cerrarSesion" class="text-sm text-gray-500 hover:text-gray-700">
          <i class="fas fa-sign-out-alt mr-1"></i>
          Cerrar Sesión
        </button>
      </div>
    </div>
  </header>
</template>

<script setup>
// Navbar sin lógica de negocio. Solo muestra identidad y botón de menú.
import { useAuthStore } from '../stores/auth'

const authStore = useAuthStore()
</script>
