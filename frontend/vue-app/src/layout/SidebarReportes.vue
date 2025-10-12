<template>
  <!-- SidebarReportes.vue: menú lateral de reportes -->
  <aside :class="[
    // Contenedor lateral con animación de ancho y transform para mobile
    'bg-white shadow-sm border-r border-gray-200 transition-all duration-300 ease-in-out',
    'fixed inset-y-0 left-0 z-50 overflow-y-auto md:static md:inset-0 md:overflow-hidden',
    menuVisible
      ? 'translate-x-0 w-80 md:w-80 lg:w-96'
      : '-translate-x-full md:translate-x-0 w-80 md:w-12 lg:w-12'
  ]">
    <!-- Barra superior con flecha para colapsar/expandir -->
    <div class="sticky top-0 bg-white/90 backdrop-blur p-2 flex justify-end border-b border-gray-100">
      <!-- Botón de alternar menú lateral -->
      <button @click="$emit('toggle-menu')" class="p-2 rounded hover:bg-gray-100" title="Colapsar/Expandir menú">
        <i :class="menuVisible ? 'fas fa-chevron-left' : 'fas fa-chevron-right'" class="text-gray-700"></i>
      </button>
    </div>

    <!-- Contenido del menú (se oculta cuando está colapsado en escritorio) -->
    <div v-show="menuVisible" class="mt-1">
      <nav class="mt-4 px-2">
        <ul class="space-y-1">
          <li>
            <a href="#" @click.prevent="$emit('seleccionar','dashboard')" :class="linkCls('dashboard')">
              <i class="fas fa-tachometer-alt mr-3"></i>
              Menu
            </a>
          </li>
        </ul>
      </nav>

      <!-- Reportes disponibles -->
      <div class="bg-white rounded-lg shadow-md p-6 m-2">
        <h2 class="text-xl font-semibold mb-4">Reportes Disponibles</h2>

        <!-- Sección Agencia -->
        <div class="mb-6">
          <h3 class="text-lg font-medium text-gray-700 mb-3 flex items-center">
            <i class="fas fa-building mr-2"></i>
            Agencia
          </h3>
          <div class="space-y-2">
            <button v-for="reporte in reportesAgencia" :key="reporte.id" @click="$emit('seleccionar', reporte.id, 'agencia')" :class="btnCls(reporte.id, 'agencia')">
              <i :class="reporte.icono" class="text-xl mr-3"></i>
              <span class="font-medium">{{ reporte.nombre }}</span>
            </button>

            <!-- Menú Informe -->
            <div v-if="esAdmin" class="border-2 border-gray-200 rounded-lg">
              <button @click="menuInformeAbierto = !menuInformeAbierto" :class="['w-full p-4 rounded-lg transition-colors text-left flex items-center justify-between', (menuInformeAbierto || ['informe-parte-venta','informe-caja'].includes(reporteActual)) ? 'bg-blue-50 text-blue-700' : 'hover:bg-gray-50']">
                <div class="flex items-center">
                  <i class="fas fa-file-invoice text-xl mr-3"></i>
                  <span class="font-medium">Informe</span>
                </div>
                <i :class="menuInformeAbierto ? 'fas fa-chevron-up' : 'fas fa-chevron-down'" class="text-sm"></i>
              </button>
              <div v-show="menuInformeAbierto" class="bg-gray-50 px-4 pb-2">
                <button @click="$emit('seleccionar', 'informe-parte-venta', 'agencia')" :class="btnSubCls('informe-parte-venta')">
                  <i class="fas fa-chart-pie text-lg mr-3"></i>
                  <span>Parte de Venta</span>
                </button>
                <button @click="$emit('seleccionar', 'informe-caja', 'agencia')" :class="btnSubCls('informe-caja')">
                  <i class="fas fa-cash-register text-lg mr-3"></i>
                  <span>Informe de Caja</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Sección AppWeb (solo admin) -->
        <div v-if="esAdmin" class="mb-6">
          <h3 class="text-lg font-medium text-gray-700 mb-3 flex items-center">
            <i class="fas fa-globe-americas mr-2"></i>
            AppWeb
          </h3>
          <div class="space-y-2">
            <button v-for="reporte in reportesAppWeb" :key="reporte.id" @click="$emit('seleccionar', reporte.id, 'appweb')" :class="btnCls(reporte.id, 'appweb')">
              <i :class="reporte.icono" class="text-xl mr-3"></i>
              <span class="font-medium">{{ reporte.nombre }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useReportesStore } from '../stores/reportes'

const props = defineProps({ menuVisible: { type: Boolean, default: false } })

const authStore = useAuthStore()
const reportesStore = useReportesStore()
const menuInformeAbierto = ref(false)

const esAdmin = computed(() => authStore.esAdmin)
const reporteActual = computed(() => reportesStore.reporteActual)

const reportesAgencia = computed(() => {
  if (authStore.esAdmin) {
    return [
      { id: 'informe-agencias', nombre: 'Informe por Agencia', icono: 'fas fa-building' },
      { id: 'ventas-tickets', nombre: 'Ventas de Tickets', icono: 'fas fa-ticket-alt' },
      { id: 'caballos-retirados', nombre: 'Caballos Retirados', icono: 'fas fa-horse' },
      { id: 'carreras', nombre: 'Carreras', icono: 'fas fa-flag-checkered' },
      { id: 'tickets-anulados', nombre: 'Tickets Anulados', icono: 'fas fa-times-circle' }
    ]
  }
  return [
    { id: 'ventas-diarias', nombre: 'Ventas Diarias', icono: 'fas fa-calendar-day' },
    { id: 'tickets-devoluciones', nombre: 'Tickets Devoluciones', icono: 'fas fa-undo' },
    { id: 'sports-carreras', nombre: 'Sports y Carreras', icono: 'fas fa-flag-checkered' },
    { id: 'tickets-anulados-agencia', nombre: 'Tickets Anulados', icono: 'fas fa-times-circle' }
  ]
})

const reportesAppWeb = computed(() => [
  { id: 'por-usuario', nombre: 'Por Usuario', icono: 'fas fa-user' },
  { id: 'economico', nombre: 'Económico', icono: 'fas fa-chart-line' },
  { id: 'apuestas', nombre: 'Apuestas', icono: 'fas fa-coins' },
  { id: 'dinero-remanente', nombre: 'Dinero Remanente', icono: 'fas fa-wallet' },
  { id: 'rendimiento-apuesta-carrera', nombre: 'Rendimiento Apuesta por Carrera', icono: 'fas fa-trophy' }
])

function linkCls(target) {
  return [
    'group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors',
    reporteActual.value === target ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100'
  ]
}

function btnCls(id, origen) {
  const activo = reportesStore.reporteActual === id && reportesStore.origenActual === origen
  return [
    'w-full p-4 rounded-lg border-2 transition-colors text-left flex items-center',
    activo ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'
  ]
}

function btnSubCls(id) {
  const activo = reportesStore.reporteActual === id
  return [
    'w-full p-3 mt-2 rounded-lg border transition-colors text-left flex items-center',
    activo ? 'border-blue-500 bg-white text-blue-700 font-medium' : 'border-gray-200 bg-white hover:border-blue-300'
  ]
}
</script>
