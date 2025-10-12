<template>
  <!-- Reporte AppWeb: Rendimiento Apuesta por Carrera -->
  <div class="w-full">
    <!-- Encabezado del reporte -->
    <ReportHeader titulo="Rendimiento Apuesta por Carrera" origen="AppWeb" icono="fas fa-balance-scale" color="green" />
    <!-- KPIs opcionales si vienen del backend -->
    <div v-if="kpis && Object.keys(kpis).length > 0" class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
      <CardKPI titulo="Total Apostado" :valor="kpis.total_apostado" formato="moneda" icono="fas fa-coins" color="info" fondo="blanco" />
      <CardKPI titulo="Total Premios" :valor="kpis.total_premios" formato="moneda" icono="fas fa-trophy" color="success" fondo="blanco" />
      <CardKPI titulo="Diferencia" :valor="kpis.diferencia" formato="moneda" icono="fas fa-chart-line" :color="Number(kpis.diferencia) >= 0 ? 'success' : 'danger'" fondo="blanco" />
    </div>

    <!-- Tabla genérica con formateo básico -->
    <TablaBase
      :columnas="columnas"
      :datos="store.datosReporte"
      :cargando="store.cargando"
      :error="store.error"
      :paginacion="store.paginacion"
      :clase-celdas="'text-center font-semibold'"
      :ocultar-mensaje-sin-datos="true"
      @cambiar-pagina="store.cambiarPagina"
    >
      <!-- Formateo de moneda en claves frecuentes -->
      <template #cell-total_apostado="{ fila }">
        <span class="font-semibold">{{ formatearMoneda(pick(fila, ['total_apostado','apostado','monto_apostado'])) }}</span>
      </template>
      <template #cell-total_premios="{ fila }">
        <span class="font-semibold">{{ formatearMoneda(pick(fila, ['total_premios','premios','monto_premios'])) }}</span>
      </template>
      <template #cell-diferencia="{ fila }">
        <span :class="Number(pick(fila, ['diferencia','ganancia'])) >= 0 ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold'">
          {{ formatearMoneda(pick(fila, ['diferencia','ganancia'])) }}
        </span>
      </template>
      <!-- Estado de apuesta con colores -->
      <template #cell-estado_apuesta="{ fila }">
        <EstadoBadge :estado="pick(fila, ['estado_apuesta','estado'])" />
      </template>
      <template #cell-estado="{ fila }">
        <EstadoBadge :estado="pick(fila, ['estado_apuesta','estado'])" />
      </template>
      <!-- Fecha con formato dd/mm/aaaa -->
      <template #cell-fecha="{ fila }">
        <span>{{ formatearFecha(pick(fila, ['fecha','fecha_jugada'])) }}</span>
      </template>
      <template #cell-fecha_jugada="{ fila }">
        <span>{{ formatearFecha(pick(fila, ['fecha_jugada','fecha'])) }}</span>
      </template>
    </TablaBase>
  </div>
</template>

<script setup>
// Vista AppWeb: Rendimiento Apuesta por Carrera
import { computed } from 'vue'
import TablaBase from '../../../components/comunes/TablaBase.vue'
import CardKPI from '../../../components/comunes/CardKPI.vue'
import ReportHeader from '../../../components/comunes/ReportHeader.vue'
import EstadoBadge from '../../../components/comunes/EstadoBadge.vue'
import { useReportesStore } from '../../../stores/reportes'
import { formatearMoneda as fmtMoneda, formatearFecha as fmtFecha } from '../../../composables/useFormato'

const store = useReportesStore()
const kpis = computed(() => store.kpis || {})

// Columnas dinámicas centradas: derivadas del primer registro
const columnas = computed(() => {
  const datos = store.datosReporte
  if (!Array.isArray(datos) || datos.length === 0) return []
  const primer = datos[0]
  if (!primer || typeof primer !== 'object') return []
  return Object.keys(primer).map(k => ({
    key: k,
    titulo: k.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()),
    align: 'center' // todas centradas según requerimiento
  }))
})

function pick(obj, claves) {
  for (const k of claves) {
    if (obj && obj[k] !== undefined && obj[k] !== null && obj[k] !== '') return obj[k]
  }
  return ''
}
function formatearMoneda(v) { return fmtMoneda(v) }
function formatearFecha(v) { return fmtFecha(v) }

// EstadoBadge maneja los colores; no se requiere claseEstado local
</script>
