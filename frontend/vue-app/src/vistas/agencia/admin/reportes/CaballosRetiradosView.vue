<template>
  <!-- Vista: Caballos Retirados -->
  <ReportHeader titulo="Caballos Retirados" origen="Admin" icono="fas fa-horse" color="gray" />

  <!-- KPI Cards con colores específicos -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="rounded-lg shadow border p-4 bg-red-50 border-red-200">
      <h3 class="text-sm font-medium text-red-700">Total General a Devolver</h3>
      <p class="text-2xl font-bold text-red-700">{{ formatearMoneda(kpis.totalDevolver) }}</p>
    </div>
    <div class="rounded-lg shadow border p-4 bg-green-50 border-green-200">
      <h3 class="text-sm font-medium text-green-700">Total Devuelto</h3>
      <p class="text-2xl font-bold text-green-700">{{ formatearMoneda(kpis.totalDevuelto) }}</p>
    </div>
    <div class="rounded-lg shadow border p-4 bg-blue-50 border-blue-200">
      <h3 class="text-sm font-medium text-blue-700">Total General de Apuestas</h3>
      <p class="text-2xl font-bold text-blue-700">{{ formatearMoneda(kpis.totalApuestas) }}</p>
    </div>
  </div>

  <TablaBase
    :columnas="columnas"
    :datos="store.datosReporte"
    :cargando="store.cargando"
    :error="store.error"
    :paginacion="store.paginacion"
    @cambiar-pagina="store.cambiarPagina"
  >
    <!-- Slots para formateo personalizado -->
    <template #cell-fecha="{ fila }">
      <span class="text-gray-900">{{ formatearFecha(obtener(fila, ['fecha','fecha_ticket'])) }}</span>
    </template>
    <template #cell-nro_caballo="{ fila }">
      <span class="text-gray-900 font-semibold">{{ obtener(fila, ['nro_caballo','numero_caballo','id_caballo','caballo']) }}</span>
    </template>
    <template #cell-total_apostado="{ fila }">
      <span class="text-gray-900 font-semibold">{{ formatearMoneda(obtener(fila, ['total_apostado','monto_apostado','total','monto'])) }}</span>
    </template>
    <template #cell-monto_a_devolver="{ fila }">
      <span class="text-gray-900 font-semibold">{{ formatearNumero(obtener(fila, ['monto_a_devolver','a_devolver','devolver'])) }}</span>
    </template>
    <template #cell-monto_devuelto="{ fila }">
      <span class="text-gray-900 font-semibold">{{ formatearNumero(obtener(fila, ['monto_devuelto','devuelto','total_devuelto'])) }}</span>
    </template>
    <template #cell-estado_devolucion="{ fila }">
      <EstadoBadge :estado="obtener(fila, ['estado_devolucion','estado','status']) || '-'" />
    </template>
  </TablaBase>
</template>

<script setup>
// Vista simple y reutilizable para "Caballos Retirados".
import TablaBase from '../../../../components/comunes/TablaBase.vue'
import ReportHeader from '../../../../components/comunes/ReportHeader.vue'
import EstadoBadge from '../../../../components/comunes/EstadoBadge.vue'
import { useReportesStore } from '../../../../stores/reportes'
import { formatearFecha as fmtFecha, formatearMoneda as fmtMoneda, formatearNumero as fmtNumero } from '../../../../composables/useFormato'
import { computed } from 'vue'

const store = useReportesStore()

// Columnas fijas según diseño original
const columnas = [
  { key: 'fecha', titulo: 'Fecha', align: 'center' },
  { key: 'nro_caballo', titulo: 'Nro Caballo', align: 'center' },
  { key: 'total_apostado', titulo: 'Total Apostado', align: 'center' },
  { key: 'monto_a_devolver', titulo: 'Monto a Devolver', align: 'center' },
  { key: 'monto_devuelto', titulo: 'Monto Devuelto', align: 'center' },
  { key: 'estado_devolucion', titulo: 'Estado Devolucion', align: 'center' }
]

// KPIs calculados desde store.kpis o sumando filas
const kpis = computed(() => {
  const k = store.kpis || {}
  const datos = Array.isArray(store.datosReporte) ? store.datosReporte : []
  
  return {
    totalDevolver: toNum(k.total_a_devolver) || sumar(datos, ['monto_a_devolver','a_devolver','devolver']),
    totalDevuelto: toNum(k.total_devuelto) || sumar(datos, ['monto_devuelto','devuelto','total_devuelto']),
    totalApuestas: toNum(k.total_apostado) || sumar(datos, ['total_apostado','monto_apostado','total','monto'])
  }
})

// Utilidades locales
function obtener(obj, claves) {
  for (const k of claves) {
    if (obj && obj[k] !== undefined && obj[k] !== null && obj[k] !== '') return obj[k]
  }
  return ''
}
function formatearFecha(v) { return fmtFecha(v) }
function formatearMoneda(v) { return fmtMoneda(v) }
function formatearNumero(v) { return fmtNumero(v) }

function toNum(v) {
  const n = typeof v === 'number' ? v : parseFloat(String(v).replace(/\./g,'').replace(',', '.'))
  return Number.isFinite(n) ? n : 0
}

function sumar(datos, claves) {
  return datos.reduce((acc, fila) => {
    const val = obtener(fila, claves)
    return acc + toNum(val)
  }, 0)
}
</script>
