<template>
  <!-- LayoutAgencia: Contenido principal para rol Agencia (usuario de agencia) -->
  <div class="w-full">
    <!-- Panel de filtros reutilizable -->
    <PanelFiltros
      :tipo-reporte="reportesStore.reporteActual || ''"
      :valores="reportesStore.filtros"
      :opciones="{ agencias: [], terminales: [], hipodromos, numerosCarreras }"
      @actualizar:filtros="v => (reportesStore.filtros = v)"
      @aplicar="() => reportesStore.cargarReporte(reportesStore.reporteActual)"
    />

    <!-- Render dinámico: vistas específicas o tabla genérica -->
  <component v-if="vistaComponente" :is="vistaComponente" />
  <TablaBase
    v-else
    :columnas="columnasGenericas"
    :datos="reportesStore.datosReporte"
    :cargando="reportesStore.cargando"
    :error="reportesStore.error"
    :paginacion="reportesStore.paginacion"
    @cambiar-pagina="reportesStore.cambiarPagina"
  />
  </div>
</template>

<script setup>
// LayoutAgencia.vue: estructura para vistas del rol Agencia (usuario)
import { ref, computed, onMounted } from 'vue'
import PanelFiltros from '../../../components/comunes/PanelFiltros.vue'
import TablaBase from '../../../components/comunes/TablaBase.vue'
import { useReportesStore } from '../../../stores/reportes'
import { reportesApi } from '../../../services/api'
// Vistas de reportes (AGENCIA - usuario)
import VentasDiariasView from '../usuario/reportes/VentasDiariasView.vue'
import TicketsDevolucionesView from '../usuario/reportes/TicketsDevolucionesView.vue'
import SportsCarrerasView from '../usuario/reportes/SportsCarrerasView.vue'
import TicketsAnuladosAgenciaView from '../usuario/reportes/TicketsAnuladosAgenciaView.vue'

const reportesStore = useReportesStore()

// Catálogos básicos (sin agencias/terminales)
const hipodromos = ref([])
const numerosCarreras = ref([])

onMounted(async () => {
  if (!reportesStore.filtros.fechaDesde || !reportesStore.filtros.fechaHasta) {
    reportesStore.establecerFechasHoy()
  }
  hipodromos.value = await reportesApi.obtenerHipodromos()
  numerosCarreras.value = await reportesApi.obtenerNumerosCarreras()
})

// Mapeo: tipo de reporte -> componente vista (rol agencia / usuario)
const vistaComponente = computed(() => {
  switch (reportesStore.reporteActual) {
    case 'ventas-diarias':
      return VentasDiariasView
    case 'tickets-devoluciones':
      return TicketsDevolucionesView
    case 'sports-carreras':
      return SportsCarrerasView
    case 'tickets-anulados-agencia':
      return TicketsAnuladosAgenciaView
    default:
      return null
  }
})

// Columnas genéricas
const columnasGenericas = computed(() => {
  const datos = reportesStore.datosReporte
  if (!Array.isArray(datos) || datos.length === 0) return []
  const primer = datos[0]
  if (!primer || typeof primer !== 'object') return []
  return Object.keys(primer).map(k => ({
    key: k,
    titulo: k.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()),
    align: typeof primer[k] === 'number' ? 'right' : 'left'
  }))
})
</script>
