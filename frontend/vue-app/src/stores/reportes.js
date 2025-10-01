import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { reportesApi } from '../services/api'

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
   * Cargar datos del reporte actual según el tipo
   */
  async function cargarReporte(tipoReporte, filtrosPersonalizados = {}) {
    try {
      cargando.value = true
      error.value = null
      reporteActual.value = tipoReporte

      // Combinar filtros usando la misma lógica que app.js
      const filtrosFinales = {}
      
      if (filtros.value.fechaDesde) filtrosFinales.fecha_desde = filtros.value.fechaDesde
      if (filtros.value.fechaHasta) filtrosFinales.fecha_hasta = filtros.value.fechaHasta
      if (filtros.value.agenciaId && filtros.value.agenciaId !== '0') filtrosFinales.agencia_id = filtros.value.agenciaId
      
      // Agregar filtros personalizados
      Object.assign(filtrosFinales, filtrosPersonalizados)
      
      console.log('DEBUG Vue - Filtros enviados:', filtrosFinales)
      console.log('DEBUG Vue - Tipo reporte:', tipoReporte)

      let respuesta = null

      // Mapear tipo de reporte a endpoint correspondiente
      switch (tipoReporte) {
        case 'ventas-tickets':
          respuesta = await reportesApi.obtenerVentasTickets(filtrosFinales)
          break
        case 'informe-agencias':
          respuesta = await reportesApi.obtenerInformeAgencias(filtrosFinales)
          break
        case 'caballos-retirados':
          respuesta = await reportesApi.obtenerCaballosRetirados(filtrosFinales)
          break
        case 'carreras':
          respuesta = await reportesApi.obtenerCarreras(filtrosFinales)
          break
        case 'tickets-anulados':
          respuesta = await reportesApi.obtenerTicketsAnulados(filtrosFinales)
          break
        case 'ventas-diarias':
          respuesta = await reportesApi.obtenerVentasDiarias(filtrosFinales)
          break
        case 'tickets-devoluciones':
          respuesta = await reportesApi.obtenerTicketsDevoluciones(filtrosFinales)
          break
        case 'sports-carreras':
          respuesta = await reportesApi.obtenerSportsCarreras(filtrosFinales)
          break
        case 'por-usuario':
        case 'economico':
        case 'apuestas':
        case 'dinero-remanente':
        case 'rendimiento-apuesta-carrera':
          // Los reportes de AppWeb se manejan directamente en LayoutPrincipal
          return
        default:
          throw new Error(`Tipo de reporte no reconocido: ${tipoReporte}`)
      }

      // Procesar respuesta según estructura
      if (respuesta) {
        console.log('DEBUG Vue - Respuesta completa:', respuesta)
        
        // Procesar según tipo de reporte
        if (tipoReporte === 'ventas-tickets') {
          datosReporte.value = respuesta.data || []
          kpis.value = {
            total_vendido: respuesta.total_vendido,
            total_ganadores: respuesta.total_ganadores,
            total_pagados: respuesta.total_pagados,
            total_devoluciones: respuesta.total_devoluciones,
            ganancia: respuesta.ganancia
          }
        } else if (tipoReporte === 'informe-agencias') {
          // Para informe-agencias, los datos pueden venir directamente como array
          datosReporte.value = Array.isArray(respuesta) ? respuesta : (respuesta.data || respuesta.agencias || [])
          kpis.value = respuesta.totales || {}
        } else if (tipoReporte === 'caballos-retirados') {
          // Para caballos-retirados, procesar KPIs específicos usando los campos correctos del backend
          datosReporte.value = respuesta.data || respuesta.caballos || []
          kpis.value = {
            total_general_devolver: respuesta.total_a_devolver || 0,
            total_devuelto: respuesta.total_devuelto || 0,
            total_general_apuestas: respuesta.total_general || 0
          }
        } else {
          datosReporte.value = respuesta.data || respuesta.datos || []
          kpis.value = respuesta.kpis || respuesta.summary || {}
        }
        
        console.log('DEBUG Vue - Datos procesados:', { 
          datos: datosReporte.value, 
          kpis: kpis.value,
          esArray: Array.isArray(respuesta)
        })
        
        // Actualizar paginación si existe
        if (respuesta.pagination) {
          paginacion.value = {
            paginaActual: respuesta.pagination.current_page || 1,
            totalPaginas: respuesta.pagination.total_pages || 1,
            totalRegistros: respuesta.pagination.total_records || 0,
            registrosPorPagina: respuesta.pagination.per_page || 100
          }
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
      numero_carrera: filtros.value.numeroCarrera
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
    datosReporte,
    kpis,
    cargando,
    error,
    filtros,
    paginacion,
    
    // Acciones
    configurarFechasPorDefecto,
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
