<template>
  <!-- LayoutBase.vue: contenedor general del sistema -->
  <div class="min-h-screen bg-gray-50">
    <Navbar @toggle-menu="toggleSidebar" />

    <div class="flex">
      <SidebarReportes :menu-visible="menuVisible" @seleccionar="onSeleccionar" @toggle-menu="toggleSidebar" />

      <!-- Contenido principal -->
      <main class="flex-1 p-6 overflow-x-hidden w-full px-4 md:px-6">
        <!-- Delegación a layout según rol/perfil -->
        <component :is="contenidoLayout" />
      </main>
    </div>
  </div>
</template>

<script setup>
// Layout desacoplado que usa componentes comunes.
import { ref, computed, onMounted } from 'vue'
import Navbar from './Navbar.vue'
import SidebarReportes from './SidebarReportes.vue'
// Delegación a layouts por rol/perfil
import LayoutAdmin from '../vistas/agencia/layout/LayoutAdmin.vue'
import LayoutAgencia from '../vistas/agencia/layout/LayoutAgencia.vue'
import LayoutAppWeb from '../vistas/appweb/layout/LayoutAppWeb.vue'
import { useAuthStore } from '../stores/auth'
import { useReportesStore } from '../stores/reportes'

const authStore = useAuthStore()
const reportesStore = useReportesStore()

// Estado del menú lateral (persistente)
const menuVisible = ref(true)

// Alternar y persistir estado del sidebar
function toggleSidebar() {
  menuVisible.value = !menuVisible.value
  try { localStorage.setItem('sidebar_open', menuVisible.value ? '1' : '0') } catch (_) {}
}

async function onSeleccionar(id, origen = 'agencia') {
  // Actualizar origen/reporte actuales
  reportesStore.origenActual = origen
  reportesStore.reporteActual = id

  // Si es dashboard, limpiar estado y no llamar API
  if (id === 'dashboard') {
    reportesStore.limpiarEstado()
    return
  }

  // Evitar llamada para AppWeb (se integrará en su layout propio)
  if (origen !== 'appweb') {
    await reportesStore.cargarReporte(id)
  }
}

// Seleccionar layout según rol/perfil
const contenidoLayout = computed(() => {
  if (reportesStore.origenActual === 'appweb') return LayoutAppWeb
  return authStore.esAdmin ? LayoutAdmin : LayoutAgencia
})

onMounted(async () => {
  // Restaurar estado persistido del sidebar
  try {
    const saved = localStorage.getItem('sidebar_open')
    if (saved === '0' || saved === '1') menuVisible.value = saved === '1'
  } catch (_) {}
  if (!reportesStore.filtros.fechaDesde || !reportesStore.filtros.fechaHasta) {
    reportesStore.establecerFechasHoy()
  }
})
// Este layout base delega la lógica de catálogos y filtros a los sub-layouts.
</script>
