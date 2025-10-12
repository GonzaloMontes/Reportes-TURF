<template>
  <!-- LayoutAdmin: Contenido principal para rol Admin (Agencia) -->
  <div class="w-full">
    <!-- Panel de filtros reutilizable -->
    <PanelFiltros
      :tipo-reporte="reportesStore.reporteActual || 'informe-caja'"
      :valores="reportesStore.filtros"
      :opciones="{ agencias, terminales, hipodromos, numerosCarreras }"
      @actualizar:filtros="v => (reportesStore.filtros = v)"
      @aplicar="() => reportesStore.cargarReporte(reportesStore.reporteActual)"
    />

    <!-- KPIs estándar si existen (ocultar en informes con KPIs propios) -->
    <div
      v-if="reportesStore.kpis && Object.keys(reportesStore.kpis).length > 0 && !['informe-caja','informe-parte-venta','informe-agencias','caballos-retirados','ventas-tickets'].includes(reportesStore.reporteActual)"
      class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6 mb-6"
    >
      <CardKPI
        v-for="(valor, clave) in reportesStore.kpis"
        :key="clave"
        :titulo="formatearTituloKPI(clave)"
        :valor="valor"
        :formato="obtenerTipoKPI(clave)"
        :icono="obtenerIconoKPI(clave)"
        :color="obtenerColorKPI(clave)"
      />
    </div>

    <!-- Render dinámico por vista (reportes Admin) -->
    <component v-if="vistaComponente" :is="vistaComponente" />

    <!-- Tabla genérica por defecto -->
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
// LayoutAdmin.vue: estructura para vistas de reportes de Admin (Agencia)
import { ref, computed, onMounted, watch } from 'vue'
import PanelFiltros from '../../../components/comunes/PanelFiltros.vue'
import TablaBase from '../../../components/comunes/TablaBase.vue'
import CardKPI from '../../../components/comunes/CardKPI.vue'

// Vistas de reportes (ADMIN)
import InformeCajaView from '../admin/reportes/InformeCajaView.vue'
import InformeParteVentaView from '../admin/reportes/InformeParteVentaView.vue'
import InformePorAgenciaView from '../admin/reportes/InformePorAgenciaView.vue'
import CarrerasView from '../admin/reportes/CarrerasView.vue'
import TicketsAnuladosView from '../admin/reportes/TicketsAnuladosView.vue'
import VentasTicketsView from '../admin/reportes/VentasTicketsView.vue'
import CaballosRetiradosView from '../admin/reportes/CaballosRetiradosView.vue'

import { useAuthStore } from '../../../stores/auth'
import { useReportesStore } from '../../../stores/reportes'
import { reportesApi } from '../../../services/api'

const authStore = useAuthStore()
const reportesStore = useReportesStore()

// Catálogos (admin)
const agencias = ref([])
const terminales = ref([])
const hipodromos = ref([])
const numerosCarreras = ref([])

async function cargarCatalogos() {
  if (authStore.esAdmin) {
    agencias.value = await reportesApi.obtenerAgencias()
    if (reportesStore.filtros.agenciaId) await cargarTerminalesParaAgencia(reportesStore.filtros.agenciaId)
  }
  hipodromos.value = await reportesApi.obtenerHipodromos()
  numerosCarreras.value = await reportesApi.obtenerNumerosCarreras()
}

async function cargarTerminalesParaAgencia(agenciaId) {
  if (!authStore.esAdmin || !agenciaId) {
    terminales.value = []
    return
  }
  try {
    terminales.value = await reportesApi.obtenerTerminales({ agencia_id: agenciaId })
  } catch (_) {
    terminales.value = []
  }
}

function formatearTituloKPI(clave) {
  return clave.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}
function obtenerTipoKPI() { return 'moneda' }
function obtenerIconoKPI() { return 'fas fa-chart-bar' }
function obtenerColorKPI() { return 'info' }

// Mapeo: tipo de reporte -> componente vista
const vistaComponente = computed(() => {
  switch (reportesStore.reporteActual) {
    case 'informe-caja':
      return InformeCajaView
    case 'informe-parte-venta':
      return InformeParteVentaView
    case 'informe-agencias':
      return InformePorAgenciaView
    case 'carreras':
      return CarrerasView
    case 'tickets-anulados':
      return TicketsAnuladosView
    case 'ventas-tickets':
      return VentasTicketsView
    case 'caballos-retirados':
      return CaballosRetiradosView
    default:
      return null
  }
})

// Columnas genéricas cuando no hay vista específica
const columnasGenericas = computed(() => {
  const datos = reportesStore.datosReporte
  if (!Array.isArray(datos) || datos.length === 0) return []
  const primer = datos[0]
  if (!primer || typeof primer !== 'object') return []
  const excluir = new Set(['resultados_caballos', 'carrera_interna_id'])
  return Object.keys(primer)
    .filter(k => !excluir.has(k))
    .map(k => ({
      key: k,
      titulo: k.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()),
      align: esNumero(primer[k]) ? 'right' : 'left'
    }))
})

function esNumero(v) {
  if (v === null || v === undefined) return false
  const n = typeof v === 'number' ? v : parseFloat(String(v).replace(/\./g, '').replace(',', '.'))
  return Number.isFinite(n)
}

onMounted(async () => {
  if (!reportesStore.filtros.fechaDesde || !reportesStore.filtros.fechaHasta) {
    reportesStore.establecerFechasHoy()
  }
  await cargarCatalogos()
})

watch(() => reportesStore.filtros.agenciaId, async (nuevo) => {
  reportesStore.filtros.terminalId = ''
  await cargarTerminalesParaAgencia(nuevo)
})

watch(() => reportesStore.reporteActual, async (tipo) => {
  const requiereTerminal = ['informe-caja', 'tickets-anulados'].includes(tipo)
  if (requiereTerminal && reportesStore.filtros.agenciaId) {
    await cargarTerminalesParaAgencia(reportesStore.filtros.agenciaId)
  } else if (!requiereTerminal) {
    terminales.value = []
  }
})
</script>
