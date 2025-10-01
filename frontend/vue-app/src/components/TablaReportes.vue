<template>
  <div class="bg-white rounded-lg shadow">
    <!-- Encabezado de la tabla -->
    <div class="px-6 py-4 border-b border-gray-200">
      <h3 class="text-lg font-medium text-gray-900">{{ titulo }}</h3>
      <p v-if="totalRegistros" class="mt-1 text-sm text-gray-600">
        {{ totalRegistros }} registros encontrados
      </p>
    </div>

    <!-- Loading state -->
    <div v-if="cargando" class="p-8 text-center">
      <i class="fas fa-spinner fa-spin text-2xl text-gray-400 mb-2"></i>
      <p class="text-gray-600">Cargando datos...</p>
    </div>

    <!-- Error state -->
    <div v-else-if="error" class="p-8 text-center">
      <i class="fas fa-exclamation-triangle text-2xl text-red-400 mb-2"></i>
      <p class="text-red-600">{{ error }}</p>
    </div>

    <!-- Sin datos -->
    <div v-else-if="!datos || datos.length === 0" class="p-8 text-center">
      <i class="fas fa-inbox text-2xl text-gray-400 mb-2"></i>
      <p class="text-gray-600">No se encontraron tickets para mostrar</p>
    </div>

    <!-- Tabla con datos -->
    <div v-else class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th
              v-for="columna in columnasVisibles"
              :key="columna.key"
              class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider"
              :class="obtenerClasesEncabezado(columna)"
            >
              {{ columna.titulo }}
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <template v-for="(fila, index) in datos" :key="index">
            <tr class="hover:bg-gray-50" :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50'">
              <td
                v-for="columna in columnasVisibles"
                :key="columna.key"
                class="px-6 py-4 whitespace-nowrap text-sm"
                :class="obtenerClasesCelda(columna)"
              >
                <!-- Columna especial para numero_carrera con botones -->
                <div v-if="columna.key === 'numero_carrera' && (titulo === 'Carreras' || titulo === 'Sports y Carreras')" class="flex items-center space-x-2">
                  <span>Carrera {{ formatearValorCelda(fila[columna.key], columna) }}</span>
                  <!-- Botón Ver resultado para carreras admin -->
                  <button 
                    v-if="titulo === 'Carreras'"
                    @click="toggleResultados(fila.carrera_interna_id)"
                    class="text-blue-600 hover:text-blue-800 text-sm font-medium underline"
                  >
                    {{ carreraVisibleActual === fila.carrera_interna_id ? 'Ocultar' : 'Ver resultado' }}
                  </button>
                  <!-- Botón Ver Sports para sports-carreras agencia -->
                  <button 
                    v-if="titulo === 'Sports y Carreras'"
                    @click="toggleSportsResultados(fila.id_carrera)"
                    class="text-blue-600 hover:text-blue-800 text-sm font-medium underline"
                  >
                    {{ sportsVisibleActual === fila.id_carrera ? 'Ocultar' : 'Ver Sports' }}
                  </button>
                </div>
                <!-- Valor normal para otras columnas -->
                <span v-else :class="obtenerClasesEstado(fila[columna.key], columna)">
                  {{ formatearValorCelda(fila[columna.key], columna) }}
                </span>
              </td> 
            </tr>
            
            <!-- Fila desplegable para resultados de carrera admin -->
            <tr 
              v-if="titulo === 'Carreras' && carreraVisibleActual === fila.carrera_interna_id"
              class="bg-gray-100"
            >
              <td :colspan="columnasVisibles.length" class="px-6 py-4">
                <div v-if="cargandoResultados[fila.carrera_interna_id]" class="text-center py-4">
                  <span class="text-gray-600">Cargando resultados...</span>
                </div>
                <div v-else-if="resultadosCarreras[fila.carrera_interna_id] && resultadosCarreras[fila.carrera_interna_id].length > 0" class="bg-white rounded-lg shadow border">
                  <div class="px-4 py-3 border-b bg-blue-50">
                    <div class="flex justify-between items-center">
                      <span class="text-sm font-medium text-blue-800">{{ fila.numero_carrera }}</span>
                      <span class="text-sm text-gray-600">{{ fila.fecha }}</span>
                      <span class="text-sm text-blue-600">{{ fila.hipodromo || 'San Isidro' }}</span>
                      <button 
                        @click="carreraVisibleActual = null"
                        class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                      >
                        Finalizada
                      </button>
                    </div>
                  </div>
                  <div class="overflow-x-auto">
                    <table class="min-w-full">
                      <thead class="bg-gray-100">
                        <tr>
                          <th class="px-4 py-2 text-center text-xs font-medium text-gray-700 uppercase">Posición</th>
                          <th class="px-4 py-2 text-center text-xs font-medium text-gray-700 uppercase">Caballo</th>
                          <th class="px-4 py-2 text-center text-xs font-medium text-gray-700 uppercase">Ganador</th>
                          <th class="px-4 py-2 text-center text-xs font-medium text-gray-700 uppercase">Segundo</th>
                          <th class="px-4 py-2 text-center text-xs font-medium text-gray-700 uppercase">Tercero</th>
                        </tr>
                      </thead>
                      <tbody class="bg-white">
                        <tr 
                          v-for="resultado in resultadosCarreras[fila.carrera_interna_id]" 
                          :key="resultado.posicion_llegada"
                          class="border-b border-gray-200"
                        >
                          <td class="px-4 py-3 text-center font-medium text-gray-900">{{ resultado.posicion_llegada }}</td>
                          <td class="px-4 py-3 text-center text-gray-900">{{ resultado.id_caballo }}</td>
                          <td class="px-4 py-3 text-center text-green-600 font-medium">{{ formatearMoneda(resultado.sport_ganador) }}</td>
                          <td class="px-4 py-3 text-center text-blue-600 font-medium">{{ formatearMoneda(resultado.sport_segundo) }}</td>
                          <td class="px-4 py-3 text-center text-orange-600 font-medium">{{ formatearMoneda(resultado.sport_tercero) }}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
                <div v-else class="text-gray-600 text-center py-4">
                  No hay resultados disponibles para esta carrera
                </div>
              </td>
            </tr>
            
            <!-- Fila desplegable para sports de carrera agencia -->
            <tr 
              v-if="titulo === 'Sports y Carreras' && sportsVisibleActual === fila.id_carrera"
              class="bg-gray-100"
            >
              <td :colspan="columnasVisibles.length" class="px-6 py-4">
                <div v-if="cargandoSports[fila.id_carrera]" class="text-center py-4">
                  <span class="text-gray-600">Cargando sports...</span>
                </div>
                <div v-else-if="sportsCarreras[fila.id_carrera] && sportsCarreras[fila.id_carrera].length > 0" class="bg-white rounded-lg p-4 border">
                  <h4 class="font-semibold text-gray-800 mb-3">Resultados y Dividendos Sports</h4>
                  <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                      <thead class="bg-gray-100">
                        <tr>
                          <th class="px-3 py-2 text-left">Posición</th>
                          <th class="px-3 py-2 text-left">Caballo</th>
                          <th class="px-3 py-2 text-right">Ganador</th>
                          <th class="px-3 py-2 text-right">Segundo</th>
                          <th class="px-3 py-2 text-right">Tercero</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr 
                          v-for="resultado in sportsCarreras[fila.id_carrera]" 
                          :key="resultado.posicion_llegada"
                          class="border-b border-gray-200"
                        >
                          <td class="px-3 py-2 font-medium">{{ resultado.posicion_llegada }}°</td>
                          <td class="px-3 py-2">Caballo #{{ resultado.id_caballo }}</td>
                          <td class="px-3 py-2 text-right" :class="resultado.sport_ganador ? 'text-green-600 font-bold' : 'text-gray-400'">
                            {{ resultado.sport_ganador ? '$' + resultado.sport_ganador : '-' }}
                          </td>
                          <td class="px-3 py-2 text-right" :class="resultado.sport_segundo ? 'text-blue-600 font-bold' : 'text-gray-400'">
                            {{ resultado.sport_segundo ? '$' + resultado.sport_segundo : '-' }}
                          </td>
                          <td class="px-3 py-2 text-right" :class="resultado.sport_tercero ? 'text-orange-600 font-bold' : 'text-gray-400'">
                            {{ resultado.sport_tercero ? '$' + resultado.sport_tercero : '-' }}
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
                <div v-else class="text-gray-600 text-center py-4">
                  No hay resultados sports disponibles
                </div>
              </td>
            </tr>
          </template>
          <!-- Fila de totales solo para informe-agencias -->
          <tr v-if="totales && Object.keys(totales).length > 0" class="bg-gray-100 font-bold border-t-2 border-gray-300">
            <td v-for="(columna, index) in columnasVisibles" :key="'total-' + columna.key" 
                class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                :class="obtenerClasesTotales()">
              <span v-if="index === 0">Totales</span>
              <span v-else>{{ formatearValorCelda(totales[columna.key], columna) }}</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    
    <!-- Paginación -->
    <div v-if="mostrarPaginacion" class="px-6 py-3 border-t border-gray-200 bg-gray-50">
      <div class="flex items-center justify-between">
        <div class="text-sm text-gray-700">
          Mostrando página {{ paginaActual }} de {{ totalPaginas }}
        </div>
        <div class="flex space-x-2">
          <button
            @click="$emit('cambiar-pagina', paginaActual - 1)"
            :disabled="paginaActual <= 1"
            class="px-3 py-1 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Anterior
          </button>
          <button
            @click="$emit('cambiar-pagina', paginaActual + 1)"
            :disabled="paginaActual >= totalPaginas"
            class="px-3 py-1 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Siguiente
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { apiClient } from '../services/api'

// Estados para resultados desplegables - solo una carrera visible a la vez
const carreraVisibleActual = ref(null)
const resultadosCarreras = ref({})
const cargandoResultados = ref({})

// Estado para sports-carreras (agencia)
const sportsVisibleActual = ref(null)
const sportsCarreras = ref({})
const cargandoSports = ref({})

const props = defineProps({
  titulo: {
    type: String,
    default: 'Reporte'
  },
  datos: {
    type: Array,
    default: () => []
  },
  columnas: {
    type: Array,
    default: () => []
  },
  cargando: {
    type: Boolean,
    default: false
  },
  error: {
    type: String,
    default: null
  },
  paginaActual: {
    type: Number,
    default: 1
  },
  totalPaginas: {
    type: Number,
    default: 1
  },
  totalRegistros: {
    type: Number,
    default: 0
  },
  totales: {
    type: Object,
    default: null
  }
})

// Eventos emitidos
const emit = defineEmits(['cambiar-pagina'])

/**
 * Generar columnas automáticamente si no se proporcionan
 */
const columnasVisibles = computed(() => {
  if (props.columnas.length > 0) {
    return props.columnas
  }
  
  // Auto-generar columnas basadas en el primer registro
  if (props.datos.length > 0) {
    const primerRegistro = props.datos[0]
    return Object.keys(primerRegistro)
      .filter(key => key !== 'resultados_caballos' && key !== 'carrera_interna_id') // Ocultar columnas innecesarias
      .map(key => ({
        key,
        titulo: formatearTituloColumna(key),
        tipo: detectarTipoColumna(key, primerRegistro[key])
      }))
  }
  
  return []
})

/**
 * Determinar si mostrar controles de paginación
 */
const mostrarPaginacion = computed(() => {
  return props.totalPaginas > 1
})

/**
 * Formatear título de columna (convertir snake_case a título)
 */
function formatearTituloColumna(key) {
  return key
    .replace(/_/g, ' ')
    .replace(/\b\w/g, l => l.toUpperCase())
}

/**
 * Detectar tipo de columna basado en la clave y valor
 */
function detectarTipoColumna(key, valor) {
  const keyLower = key.toLowerCase()
  
  if (keyLower.includes('total') || keyLower.includes('precio') || 
      keyLower.includes('ganancia') || keyLower.includes('apostado')) {
    return 'moneda'
  }
  
  if (keyLower.includes('fecha')) {
    return 'fecha'
  }
  
  if (typeof valor === 'number') {
    return 'numero'
  }
  
  return 'texto'
}

/**
 * Formatear valor de celda según su tipo
 */
function formatearValorCelda(valor, columna) {
  if (valor === null || valor === undefined || valor === '') {
    return '-'
  }
  
  const tipo = columna.tipo || 'texto'
  
  switch (tipo) {
    case 'moneda':
      const valorNumerico = parseFloat(valor) || 0
      return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS'
      }).format(valorNumerico)
    
    case 'numero':
      return new Intl.NumberFormat('es-AR').format(parseFloat(valor) || 0)
    
    case 'fecha':
      try {
        // Manejar diferentes formatos de fecha
        if (typeof valor === 'string' && valor.includes('-')) {
          // Formato YYYY-MM-DD
          const fecha = new Date(valor + 'T00:00:00')
          if (!isNaN(fecha.getTime())) {
            return fecha.toLocaleDateString('es-AR')
          }
        }
        
        const fecha = new Date(valor)
        if (!isNaN(fecha.getTime())) {
          return fecha.toLocaleDateString('es-AR')
        }
        
        // Si no se puede parsear, devolver el valor original
        return valor
      } catch {
        return valor
      }
    
    case 'porcentaje':
      return `${parseFloat(valor) || 0}%`
    
    default:
      return valor
  }
}

/**
 * Alternar visualización de resultados de carrera
 */
async function toggleResultados(idCarrera) {
  console.log('DEBUG - toggleResultados llamado con ID:', idCarrera)
  
  if (!idCarrera || idCarrera === 'undefined') {
    console.error('ID de carrera inválido:', idCarrera)
    return
  }
  
  if (carreraVisibleActual.value === idCarrera) {
    carreraVisibleActual.value = null
  } else {
    carreraVisibleActual.value = idCarrera
    
    if (!resultadosCarreras.value[idCarrera]) {
      cargandoResultados.value[idCarrera] = true
      try {
        const respuesta = await apiClient.get(`/reports/resultados-carrera?id_carrera=${idCarrera}`)
        resultadosCarreras.value[idCarrera] = respuesta.resultados_caballos || []
      } catch (error) {
        console.error('Error al cargar resultados:', error)
        resultadosCarreras.value[idCarrera] = []
      } finally {
        cargandoResultados.value[idCarrera] = false
      }
    }
  }
}

/**
 * Alternar visualización de sports de carrera
 */
async function toggleSportsResultados(idCarrera) {
  console.log('DEBUG - toggleSportsResultados llamado con ID:', idCarrera)
  
  if (!idCarrera || idCarrera === 'undefined') {
    console.error('ID de carrera inválido:', idCarrera)
    return
  }
  
  if (sportsVisibleActual.value === idCarrera) {
    sportsVisibleActual.value = null
  } else {
    sportsVisibleActual.value = idCarrera
    
    // Los datos ya vienen en la carrera desde el backend
    if (props.datos) {
      const carrera = props.datos.find(c => c.id_carrera === idCarrera)
      if (carrera && carrera.resultados_caballos) {
        sportsCarreras.value[idCarrera] = carrera.resultados_caballos
      }
    }
  }
}

/**
 * Formatear valor como moneda
 */
function formatearMoneda(valor) {
  return new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: 'ARS'
  }).format(parseFloat(valor) || 0)
}

/**
 * Obtener clases CSS para estados con colores distintivos
 */
function obtenerClasesEstado(valor, columna) {
  // Solo aplicar colores si es una columna de estado
  const columnasEstado = ['estado', 'estado_carrera', 'estado_devolucion', 'resultado_apuesta'];
  const esColumnaEstado = columnasEstado.some(col => 
    columna.key.toLowerCase().includes(col) || 
    columna.titulo.toLowerCase().includes('estado') ||
    columna.titulo.toLowerCase().includes('resultado')
  );

  if (!esColumnaEstado) return '';

  const valorLower = String(valor).toLowerCase();

  // Colores para Caballos Retirados
  if (props.titulo === 'Caballos Retirados') {
    switch (valorLower) {
      case 'pendiente': return 'text-yellow-600 font-semibold';
      case 'devuelto': return 'text-green-600 font-semibold';
      case 'parcial': return 'text-blue-600 font-semibold';
      case 'sin apuesta': return 'text-gray-600 font-semibold';
      default: return 'font-semibold';
    }
  }

  // Colores para Carreras
  if (props.titulo === 'Carreras') {
    switch (valorLower) {
      case 'apuesta': return 'text-blue-600 font-semibold';
      case 'espera': return 'text-yellow-600 font-semibold';
      case 'finalizada': return 'text-green-600 font-semibold';
      default: return 'font-semibold';
    }
  }

  // Colores para Rendimiento Apuesta por Carrera
  if (props.titulo === 'Rendimiento Apuesta por Carrera') {
    switch (valorLower) {
      case 'perdedora': return 'text-red-600 font-semibold';
      case 'cancelada': return 'text-gray-600 font-semibold';
      case 'ganadora': return 'text-green-600 font-semibold';
      default: return 'font-semibold';
    }
  }

  return 'font-semibold';
}

/**
 * Obtener clases CSS para los encabezados de la tabla
 */
function obtenerClasesEncabezado(columna) {
  const reportesCentrados = [
    'Informe por Agencia',
    'Caballos Retirados', 
    'Tickets Anulados',
    'Rendimiento por Usuario',
    'Rendimiento Apuesta por Carrera'
  ];

  if (reportesCentrados.includes(props.titulo)) {
    return 'text-center';
  }

  // Centrado específico para encabezados del reporte 'Carreras'
  if (props.titulo === 'Carreras' && (columna.key === 'fecha' || columna.key === 'nombre_hipodromo' || columna.key === 'estado_carrera')) {
    return 'text-center';
  }

  return 'text-left';
}

/**
 * Obtener clases CSS para la fila de totales
 */
function obtenerClasesTotales() {
  const reportesCentrados = [
    'Informe por Agencia',
    'Caballos Retirados', 
    'Tickets Anulados',
    'Rendimiento por Usuario',
    'Rendimiento Apuesta por Carrera'
  ];

  return reportesCentrados.includes(props.titulo) ? 'text-center' : '';
}

/**
 * Obtener clases CSS para la celda según el tipo
 */
function obtenerClasesCelda(columna) {
  const tipo = columna.tipo || 'texto';

  // Reportes con valores centrados
  const reportesCentrados = [
    'Informe por Agencia',
    'Caballos Retirados', 
    'Tickets Anulados',
    'Rendimiento por Usuario',
    'Rendimiento Apuesta por Carrera',
    
  ];

  if (reportesCentrados.includes(props.titulo)) {
    return 'text-center font-medium text-gray-900';
  }

  // Centrado específico para columnas del reporte 'Carreras'
  if (props.titulo === 'Carreras' && (columna.key === 'fecha' || columna.key === 'nombre_hipodromo' || columna.key === 'estado_carrera')) {
    return 'text-center font-medium text-gray-900';
  }

  // Lógica original para los demás reportes.
  switch (tipo) {
    case 'moneda':
    case 'numero':
      return 'text-right font-medium text-gray-900';
    
    case 'fecha':
      return 'text-gray-600';
    
    default:
      return 'text-gray-900';
  }
}
</script>
