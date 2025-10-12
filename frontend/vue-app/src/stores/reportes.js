import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { reportesGateway } from '../services/reportesApi'
import { useAuthStore } from './auth'

// Enumeración de tipos de reporte para evitar strings "mágicos"
export const TIPOS_REPORTE = {
  VENTAS_TICKETS: 'ventas-tickets',
  INFORME_AGENCIAS: 'informe-agencias',
  INFORME_CAJA: 'informe-caja',
  CABALLOS_RETIRADOS: 'caballos-retirados',
  CARRERAS: 'carreras',
  TICKETS_ANULADOS: 'tickets-anulados',
  INFORME_PARTE_VENTA: 'informe-parte-venta',
  // Agencia
  VENTAS_DIARIAS: 'ventas-diarias',
  TICKETS_DEVOLUCIONES: 'tickets-devoluciones',
  SPORTS_CARRERAS: 'sports-carreras',
}

/**
 * Store de reportes - Maneja el estado de los reportes y datos
 */
export const useReportesStore = defineStore('reportes', () => {
  // Estado reactivo
  const reporteActual = ref('')
  const origenActual = ref('agencia') // 'agencia' o 'appweb'
  const datosReporte = ref([])
  const kpis = ref({})
  const cargando = ref(false)
  const error = ref(null)

  // Filtros
  const filtros = ref({
    fechaDesde: '',
    fechaHasta: '',
    agenciaId: '',
    hipodromoId: '',
    numeroCarrera: '',
    terminalId: '',
    buscarUsuario: ''
  })

  // Paginación
  const paginacion = ref({
    paginaActual: 1,
    registrosPorPagina: 50,
    totalRegistros: 0
  })

  /**
   * Configurar fechas por defecto (últimos 30 días)
   */
  function configurarFechasPorDefecto() {
    const hoy = new Date()
    const haceTreintaDias = new Date(hoy.getTime() - (30 * 24 * 60 * 60 * 1000))
    
    filtros.value.fechaDesde = haceTreintaDias.toISOString().split('T')[0]
    filtros.value.fechaHasta = hoy.toISOString().split('T')[0]
  }

  /**
   * Establecer fecha desde y hasta con la fecha de hoy (YYYY-MM-DD)
   * Útil para reportes que deben iniciar con el día actual.
   */
  function establecerFechasHoy() {
    const hoy = new Date().toISOString().split('T')[0]
    filtros.value.fechaDesde = hoy
    filtros.value.fechaHasta = hoy
  }

  /**
   * Cargar datos del reporte actual según el tipo
   */
  async function cargarReporte(tipoReporte, filtrosPersonalizados = {}) {
    try {
      cargando.value = true
      error.value = null
      reporteActual.value = tipoReporte

      // Combinar filtros en el formato que espera el backend
      const filtrosFinales = {}
      
      if (filtros.value.fechaDesde) filtrosFinales.fecha_desde = filtros.value.fechaDesde
      if (filtros.value.fechaHasta) filtrosFinales.fecha_hasta = filtros.value.fechaHasta
      if (filtros.value.agenciaId && filtros.value.agenciaId !== '0') filtrosFinales.agencia_id = filtros.value.agenciaId
      // Filtros específicos de Carreras (backend espera estos nombres)
      if (filtros.value.hipodromoId) filtrosFinales.hipodromo_id = filtros.value.hipodromoId
      if (filtros.value.numeroCarrera) filtrosFinales.numero_carrera = filtros.value.numeroCarrera
      // Filtro específico de Tickets Anulados (terminal)
      if (filtros.value.terminalId) filtrosFinales.terminal_id = filtros.value.terminalId
      
      // Enforzar alcance por agencia para usuarios de agencia (sobrescribe cualquier valor)
      try {
        const auth = useAuthStore()
        if (auth.esAgencia && auth.idAgencia) filtrosFinales.agencia_id = auth.idAgencia
      } catch (_) {}

      // Agregar filtros personalizados
      Object.assign(filtrosFinales, filtrosPersonalizados)
      
      console.log('DEBUG Vue - Filtros enviados:', filtrosFinales)
      console.log('DEBUG Vue - Tipo reporte:', tipoReporte)
      // Los reportes de AppWeb se manejan directamente desde LayoutPrincipal
      const tiposAppWeb = ['por-usuario','economico','apuestas','dinero-remanente','rendimiento-apuesta-carrera']
      if (tiposAppWeb.includes(tipoReporte)) return

      // Nuevo flujo: usar gateway normalizado
      const respuesta = await reportesGateway.obtener(tipoReporte, filtrosFinales)

      datosReporte.value = respuesta.data || []
      kpis.value = respuesta.kpis || {}
      if (respuesta.paginacion) {
        const p = respuesta.paginacion
        paginacion.value = {
          paginaActual: p.current_page || p.paginaActual || 1,
          totalPaginas: p.total_pages || p.totalPaginas || 1,
          totalRegistros: p.total_records || p.totalRegistros || 0,
          registrosPorPagina: p.per_page || p.registrosPorPagina || 100
        }
      }
    
    } catch (err) {
      error.value = err.message || 'Error cargando reporte'
      console.error('Error cargando reporte:', err)
    } finally {
      cargando.value = false
    }
  }

  /**
   * Aplicar filtros y recargar reporte actual
   */
  async function aplicarFiltros(nuevosFiltros = {}) {
    // Actualizar filtros
    Object.assign(filtros.value, nuevosFiltros)
    
    // Resetear paginación
    filtros.value.offset = 0
    paginacion.value.paginaActual = 1
    
    // Recargar reporte actual
    if (reporteActual.value !== 'dashboard') {
      await cargarReporte(reporteActual.value)
    }
  }

  /**
   * Cambiar página en reportes paginados
   */
  async function cambiarPagina(numeroPagina) {
    if (numeroPagina < 1 || numeroPagina > paginacion.value.totalPaginas) {
      return
    }
    
    paginacion.value.paginaActual = numeroPagina
    filtros.value.offset = (numeroPagina - 1) * filtros.value.limit
    
    await cargarReporte(reporteActual.value)
  }

  /**
   * Formatear moneda para mostrar en la UI
   */
  function formatearMoneda(valor) {
    if (!valor || isNaN(valor)) return '$0.00'
    return new Intl.NumberFormat('es-AR', {
      style: 'currency',
      currency: 'ARS'
    }).format(valor)
  }

  /**
   * Formatear fecha para mostrar en la UI
   */
  function formatearFecha(fecha) {
    if (!fecha) return ''
    return new Date(fecha).toLocaleDateString('es-AR')
  }

  /**
   * Obtener filtros actuales
   */
  function obtenerFiltros() {
    return {
      fecha_desde: filtros.value.fechaDesde,
      fecha_hasta: filtros.value.fechaHasta,
      agencia_id: filtros.value.agenciaId,
      hipodromo_id: filtros.value.hipodromoId,
      numero_carrera: filtros.value.numeroCarrera,
      terminal_id: filtros.value.terminalId
    }
  }

  /**
   * Construir query string desde filtros
   */
  function construirQueryString(filtrosObj) {
    const params = new URLSearchParams()
    
    Object.entries(filtrosObj).forEach(([key, value]) => {
      if (value && value !== '0' && value !== '') {
        params.append(key, value)
      }
    })
    
    return params.toString()
  }

  /**
   * Limpiar estado del store
   */
  function limpiarEstado() {
    reporteActual.value = 'dashboard'
    datosReporte.value = []
    kpis.value = {}
    error.value = null
    paginacion.value = {
      paginaActual: 1,
      totalPaginas: 1,
      totalRegistros: 0,
      registrosPorPagina: 100
    }
  }

  // Getters computados
  const tieneKpis = computed(() => Object.keys(kpis.value).length > 0)
  const tieneDatos = computed(() => datosReporte.value.length > 0)
  const esPaginado = computed(() => paginacion.value.totalPaginas > 1)

  return {
    // Estado
    reporteActual,
    origenActual,
    datosReporte,
    kpis,
    cargando,
    error,
    filtros,
    paginacion,
    
    // Acciones
    configurarFechasPorDefecto,
    establecerFechasHoy,
    cargarReporte,
    aplicarFiltros,
    cambiarPagina,
    limpiarEstado,
    obtenerFiltros,
    construirQueryString,
    
    // Utilidades
    formatearMoneda,
    formatearFecha,
    
    // Getters
    tieneKpis,
    tieneDatos,
    esPaginado
  }
})
