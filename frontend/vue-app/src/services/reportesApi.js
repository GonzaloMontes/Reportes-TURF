// services/reportesApi.js
// Gateway centralizado para obtener datos de reportes por tipo.
// Normaliza la respuesta del backend a la forma { kpis, data, paginacion }.

import { apiClient } from './api'

/**
 * Mapeo tipoReporte -> función que realiza la petición HTTP.
 * Usar nombres EXACTOS de los tipos para mantener coherencia con el store.
 */
const handlers = {
  // Admin
  'ventas-tickets': (f) => apiClient.get('/reports/lista-tickets', { params: f }),
  'informe-agencias': (f) => apiClient.get('/reports/informe-por-agencia', { params: f }),
  'informe-caja': (f) => apiClient.get('/reports/informe-por-agencia', { params: { ...f, report_type: 'informe-caja' } }),
  'caballos-retirados': (f) => apiClient.get('/reports/caballos-retirados', { params: f }),
  'carreras': (f) => apiClient.get('/reports/carreras', { params: f }),
  'tickets-anulados': (f) => apiClient.get('/reports/tickets-anulados', { params: f }),
  'informe-parte-venta': (f) => apiClient.get('/reports/informe-parte-venta', { params: f }),

  // Agencia
  'ventas-diarias': (f) => apiClient.get('/reports/agencia/ventas-diarias', { params: f }),
  'tickets-devoluciones': (f) => apiClient.get('/reports/agencia/tickets-devoluciones', { params: f }),
  'sports-carreras': (f) => apiClient.get('/reports/agencia/sports-carreras', { params: f }),
  'tickets-anulados-agencia': (f) => apiClient.get('/reports/agencia/tickets-anulados', { params: f }),
}

/**
 * Normaliza la respuesta a {kpis, data, paginacion}
 */
function normalizar(tipo, r) {
  const paginacion = r?.pagination || r?.paginacion || null

  switch (tipo) {
    case 'ventas-tickets': {
      return {
        kpis: {
          total_vendido: r.total_vendido,
          total_ganadores: r.total_ganadores,
          total_pagados: r.total_pagados,
          total_devoluciones: r.total_devoluciones,
          ganancia: r.ganancia,
        },
        data: r.data || [],
        paginacion,
      }
    }

    // KPIs y datos de Ventas Diarias (rol agencia)
    case 'ventas-diarias': {
      // Intentar leer KPIs desde distintas formas de respuesta sin romper compatibilidad
      const kpis = r.kpis || {
        total_vendidos: r.total_vendidos ?? r.totalVendido ?? 0,
        total_ganadores: r.total_ganadores ?? r.totalGanadores ?? 0,
        total_pagados: r.total_pagados ?? r.totalPagados ?? 0,
        total_devoluciones: r.total_devoluciones ?? r.totalDevoluciones ?? 0,
      }
      const data = r.data || r.detalle || r.rows || r.result || []
      return { kpis, data, paginacion }
    }

    // Datos de Sports y Carreras (rol agencia)
    case 'sports-carreras': {
      const kpis = r.kpis || {}
      const data = r.data || r.carreras || r.items || r.result || r.detalle || []
      return { kpis, data, paginacion }
    }

    case 'informe-agencias':
    case 'informe-caja': {
      const data = Array.isArray(r) ? r : (r.data || r.agencias || [])
      const kpis = r.kpis || r.totales || {}
      return { kpis, data, paginacion }
    }

    case 'caballos-retirados': {
      return {
        kpis: {
          total_general_devolver: r.total_a_devolver || 0,
          total_devuelto: r.total_devuelto || 0,
          total_general_apuestas: r.total_general || 0,
        },
        data: r.data || r.caballos || [],
        paginacion,
      }
    }

    default:
      return {
        kpis: r.kpis || r.summary || {},
        data: r.data || r.datos || r.result || r.items || r.detalle || [],
        paginacion,
      }
  }
}

export const reportesGateway = {
  /**
   * Obtiene datos para un tipo de reporte con filtros dados.
   * Devuelve un objeto normalizado { kpis, data, paginacion }.
   */
  async obtener(tipoReporte, filtros) {
    const fn = handlers[tipoReporte]
    if (!fn) throw new Error(`Reporte no soportado: ${tipoReporte}`)
    const r = await fn(filtros || {})
    return normalizar(tipoReporte, r)
  },
}
