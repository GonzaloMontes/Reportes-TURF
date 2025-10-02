import axios from 'axios'

/**
 * Cliente API configurado para comunicarse con el backend PHP
 * Mantiene la misma estructura de endpoints que el JavaScript original
 */
// Log de la URL de API (útil para verificar dev/prod)
// En dev debe mostrar: https://reportes.turfsoft.net/api/
// En prod (build) debe mostrar: /api/
console.log('API URL:', import.meta.env.VITE_API_URL)

export const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  timeout: 10000,
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})

// Interceptor REQUEST: normaliza URL y loguea
apiClient.interceptors.request.use((config) => {
  // Normalizar URL para evitar dobles barras cuando baseURL termina en /
  if (typeof config.url === 'string') {
    config.url = config.url.replace(/^\/+/,'')
  }
  // Log de request (omitimos body en POST grandes)
  try {
    const { method, url, params, headers } = config
    // eslint-disable-next-line no-console
    console.debug('[API][request]', (method || 'GET').toUpperCase(), url, { params, headers: { 'Content-Type': headers?.['Content-Type'], 'Accept': headers?.['Accept'] } })
  } catch (_) {}
  return config
})

// Interceptor para manejo de errores
apiClient.interceptors.response.use(
  (response) => {
    try {
      // eslint-disable-next-line no-console
      console.debug('[API][response]', response.status, response.config?.url)
    } catch (_) {}
    return response.data
  },
  (error) => {
    // Log detallado del error para depurar CORS/Network
    try {
      if (error.response) {
        // eslint-disable-next-line no-console
        console.error('[API][error-response]', error.response.status, error.config?.url, error.response.data)
      } else if (error.request) {
        // eslint-disable-next-line no-console
        console.error('[API][no-response]', error.config?.url, 'Posible CORS/Network. Verifique Access-Control-Allow-Origin y credenciales.')
      } else {
        // eslint-disable-next-line no-console
        console.error('[API][setup-error]', error.message)
      }
    } catch (_) {}

    if (error.response?.status === 401) {
      // Sesión expirada - redirigir a login
      window.location.reload()
    }

    const mensaje = error.response?.data?.error || error.message || 'Error de conexión'
    throw new Error(mensaje)
  }
)

// Interceptor para limpiar parámetros undefined
apiClient.interceptors.request.use((config) => {
  if (config.params) {
    // Eliminar parámetros undefined o vacíos
    Object.keys(config.params).forEach(key => {
      if (config.params[key] === undefined || config.params[key] === '') {
        delete config.params[key]
      }
    })
  }
  return config
})

/**
 * Servicio de reportes - Mantiene la misma API que el JavaScript original
 */
export const reportesApi = {
  // Endpoints de autenticación
  verificarAuth: () => apiClient.get('/auth/verify'),
  login: (credenciales) => apiClient.post('/auth/login', credenciales),
  logout: () => apiClient.post('/auth/logout'),

  // Endpoints de datos maestros
  obtenerAgencias: () => apiClient.get('/reports/agencies'),
  obtenerHipodromos: () => apiClient.get('/reports/hipodromos'),
  obtenerNumerosCarreras: () => apiClient.get('/reports/numeros-carreras'),

  // Endpoints de reportes (Admin)
  obtenerVentasTickets: (filtros) => apiClient.get('/reports/lista-tickets', { params: filtros }),
  obtenerInformeAgencias: (filtros) => apiClient.get('/reports/informe-por-agencia', { params: filtros }),
  obtenerCaballosRetirados: (filtros) => apiClient.get('/reports/caballos-retirados', { params: filtros }),
  obtenerCarreras: (filtros) => apiClient.get('/reports/carreras', { params: filtros }),
  obtenerTicketsAnulados: (filtros) => apiClient.get('/reports/tickets-anulados', { params: filtros }),
  obtenerInformeParteVenta: (filtros) => apiClient.get('/reports/informe-parte-venta', { params: filtros }),

  // Endpoints de reportes (Agencia)
  obtenerVentasDiarias: (filtros) => apiClient.get('/reports/agencia/ventas-diarias', { params: filtros }),
  obtenerTicketsDevoluciones: (filtros) => apiClient.get('/reports/agencia/tickets-devoluciones', { params: filtros }),
  obtenerSportsCarreras: (filtros) => apiClient.get('/reports/agencia/sports-carreras', { params: filtros }),
  obtenerTicketsAnuladosAgencia: (filtros) => apiClient.get('/reports/agencia/tickets-anulados', { params: filtros })
}

export default apiClient
