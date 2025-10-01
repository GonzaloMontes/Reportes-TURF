<template>
  <div id="app">
    <!-- Mostrar formulario de login si no está autenticado -->
    <FormularioLogin v-if="!authStore.estaAutenticado" />
    
    <!-- Mostrar aplicación principal si está autenticado -->
    <LayoutPrincipal v-else />
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useAuthStore } from './stores/auth'
import FormularioLogin from './components/FormularioLogin.vue'
import LayoutPrincipal from './components/LayoutPrincipal.vue'

// Store de autenticación
const authStore = useAuthStore()

// Verificar autenticación al cargar la app
onMounted(async () => {
  await authStore.verificarAutenticacion()
})
</script>

<style>
/* Estilos globales copiados del CSS original */
body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  margin: 0;
  padding: 0;
  background-color: #f5f5f5;
}

.hidden {
  display: none !important;
}

.card-shadow {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.nav-link.active {
  background-color: #dbeafe;
  color: #1d4ed8;
}

.loading-spinner {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>
