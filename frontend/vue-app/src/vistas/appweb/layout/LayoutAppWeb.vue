<template>
  <!-- LayoutAppWeb: Contenedor para reportes AppWeb -->
  <div class="w-full">
    <!-- Panel de filtros (reutilizable) -->
    <PanelFiltros
      :tipo-reporte="reportesStore.reporteActual || ''"
      :valores="reportesStore.filtros"
      :opciones="{ agencias: [], terminales: [], hipodromos: [], numerosCarreras: [] }"
      @actualizar:filtros="v => (reportesStore.filtros = v)"
      @aplicar="cargarReporteAppWeb"
    />

    <!-- Render dinámico por vista AppWeb -->
    <component v-if="vistaComponente" :is="vistaComponente" />

    <!-- Tabla genérica por defecto (fallback) -->
    <TablaBase
      v-else
      :columnas="columnasGenericas"
      :datos="reportesStore.datosReporte"
      :cargando="reportesStore.cargando"
      :error="reportesStore.error"
      :paginacion="reportesStore.paginacion"
      @cambiar-pagina="onCambiarPagina"
    />
  </div>
 </template>

<script setup>
// LayoutAppWeb.vue: Orquesta vistas AppWeb y carga datos desde endpoints /reports/appweb/*
import { computed, onMounted, watch } from 'vue'
import PanelFiltros from '../../../components/comunes/PanelFiltros.vue'
import TablaBase from '../../../components/comunes/TablaBase.vue'
import { useReportesStore } from '../../../stores/reportes'
import { apiClient } from '../../../services/api'

// Vistas AppWeb
import PorUsuarioView from '../reportes/PorUsuarioView.vue'
import EconomicoView from '../reportes/EconomicoView.vue'
import ApuestasView from '../reportes/ApuestasView.vue'
import DineroRemanenteView from '../reportes/DineroRemanenteView.vue'
import RendimientoApuestaCarreraView from '../reportes/RendimientoApuestaCarreraView.vue'

const reportesStore = useReportesStore()

// Determina vista a renderizar según reporteActual
const vistaComponente = computed(() => {
  switch (reportesStore.reporteActual) {
    case 'por-usuario':
      return PorUsuarioView
    case 'economico':
      return EconomicoView
    case 'apuestas':
      return ApuestasView
    case 'dinero-remanente':
      return DineroRemanenteView
    case 'rendimiento-apuesta-carrera':
      return RendimientoApuestaCarreraView
    default:
      return null
  }
})

// Manejar cambio de página: usar store y volver a cargar AppWeb
async function onCambiarPagina(pagina) {
  await reportesStore.cambiarPagina(pagina)
  await cargarReporteAppWeb()
}

// Carga datos para reportes AppWeb según filtros del store
async function cargarReporteAppWeb() {
  const tipo = reportesStore.reporteActual
  if (!tipo) return

  try {
    reportesStore.cargando = true
    reportesStore.error = null

    // Mapear filtros del store a parámetros esperados por backend
    const f = reportesStore.filtros || {}
    const params = {}
    if (f.fechaDesde) params.fecha_desde = f.fechaDesde
    if (f.fechaHasta) params.fecha_hasta = f.fechaHasta
    if (tipo === 'por-usuario' && f.buscarUsuario) params.buscar_usuario = f.buscarUsuario

    // Mapear tipo -> endpoint
    const endpoints = {
      'por-usuario': 'por-usuario',
      'economico': 'economico',
      'apuestas': 'apuestas',
      'dinero-remanente': 'dinero-remanente',
      'rendimiento-apuesta-carrera': 'rendimiento-apuesta-carrera'
    }
    const url = `/reports/appweb/${endpoints[tipo]}`

    const r = await apiClient.get(url, { params })

    // Normalizar a store
    reportesStore.datosReporte = r.data || r.datos || r.result || []
    reportesStore.kpis = r.kpis || r.summary || {}
    if (r.pagination || r.paginacion) {
      const p = r.pagination || r.paginacion
      reportesStore.paginacion = {
        paginaActual: p.current_page || 1,
        totalPaginas: p.total_pages || 1,
        totalRegistros: p.total_records || 0,
        registrosPorPagina: p.per_page || 100
      }
    } else if (typeof r.total_records === 'number') {
      reportesStore.paginacion.totalRegistros = r.total_records
    }
  } catch (e) {
    reportesStore.error = e.message || 'Error al cargar reporte AppWeb'
  } finally {
    reportesStore.cargando = false
  }
}

// Columnas genéricas (fallback)
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

// Cargar al montar si ya hay un reporte AppWeb activo
onMounted(async () => {
  if (!reportesStore.filtros.fechaDesde || !reportesStore.filtros.fechaHasta) {
    reportesStore.establecerFechasHoy()
  }
  const tipos = ['por-usuario','economico','apuestas','dinero-remanente','rendimiento-apuesta-carrera']
  if (tipos.includes(reportesStore.reporteActual)) {
    await cargarReporteAppWeb()
  }
})

// Si cambia el tipo de reporte AppWeb, recargar
watch(() => reportesStore.reporteActual, async (t) => {
  const tipos = ['por-usuario','economico','apuestas','dinero-remanente','rendimiento-apuesta-carrera']
  if (tipos.includes(t)) {
    await cargarReporteAppWeb()
  }
})
</script>
