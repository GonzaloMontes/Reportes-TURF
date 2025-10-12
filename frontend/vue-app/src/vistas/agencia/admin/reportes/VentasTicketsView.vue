<template>
  <!-- Vista: Ventas de Tickets -->
  <ReportHeader titulo="Ventas de Tickets" origen="Admin" icono="fas fa-ticket-alt" color="gray" />

  <!-- KPI Cards con iconos específicos -->
  <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <CardKPI 
      titulo="Total Vendido" 
      :valor="kpis.totalVendido" 
      formato="moneda" 
      icono="fas fa-dollar-sign" 
      color="info" 
    />
    <CardKPI 
      titulo="Total Ganadores" 
      :valor="kpis.totalGanadores" 
      formato="moneda" 
      icono="fas fa-trophy" 
      color="success" 
    />
    <CardKPI 
      titulo="Total Pagados" 
      :valor="kpis.totalPagados" 
      formato="moneda" 
      icono="fas fa-money-bill-wave" 
      color="info" 
    />
    <CardKPI 
      titulo="Ganancia Neta" 
      :valor="kpis.gananciaNeta" 
      formato="moneda" 
      icono="fas fa-chart-line" 
      color="success" 
    />
  </div>

  <TablaBase
    :columnas="columnas"
    :datos="store.datosReporte"
    :cargando="store.cargando"
    :error="store.error"
    :paginacion="store.paginacion"
    :ocultar-mensaje-sin-datos="true"
    @cambiar-pagina="store.cambiarPagina"
  >
    <!-- Slots para mapear claves alternativas del backend -->
    <template #cell-numero_ticket="{ fila }">
      <span class="text-gray-900">{{ obtener(fila, ['numero_ticket','nro_ticket','ticket','id_ticket']) }}</span>
    </template>
    <template #cell-fecha="{ fila }">
      <span class="text-gray-900">{{ formatearFecha(obtener(fila, ['fecha','fecha_ticket','fecha_emision','created_at'])) }}</span>
    </template>
    <template #cell-agencia="{ fila }">
      <span class="text-gray-900">{{ obtener(fila, ['nombre_agencia','agencia','agencia_nombre']) }}</span>
    </template>
    <template #cell-terminal="{ fila }">
      <span class="text-gray-900">{{ obtener(fila, ['nombre_usuario','usuario','terminal','nombre_terminal']) }}</span>
    </template>
    <template #cell-total="{ fila }">
      <span class="text-gray-900 font-semibold">{{ formatearMoneda(obtener(fila, ['total','monto','importe','valor'])) }}</span>
    </template>
  </TablaBase>
</template>

<script setup>
// Vista simple y reutilizable para "Ventas de Tickets".
// - Usa TablaBase con columnas fijas y slots para mapear claves alternativas.
// - Usa CardKPI para mostrar métricas principales.
import { computed } from 'vue'
import TablaBase from '../../../../components/comunes/TablaBase.vue'
import CardKPI from '../../../../components/comunes/CardKPI.vue'
import ReportHeader from '../../../../components/comunes/ReportHeader.vue'
import { useReportesStore } from '../../../../stores/reportes'
import { formatearFecha as fmtFecha, formatearMoneda as fmtMoneda } from '../../../../composables/useFormato'

const store = useReportesStore()

// KPIs calculados desde store.kpis o fallback a 0
const kpis = computed(() => {
  const k = store.kpis || {}
  return {
    totalVendido: toNum(k.total_vendido) || toNum(k.vendidos) || 0,
    totalGanadores: toNum(k.total_ganadores) || toNum(k.ganadores) || 0,
    totalPagados: toNum(k.total_pagados) || toNum(k.pagados) || 0,
    gananciaNeta: toNum(k.ganancia) || toNum(k.ganancia_neta) || 0
  }
})

function toNum(v) {
  const n = typeof v === 'number' ? v : parseFloat(String(v).replace(/\./g,'').replace(',', '.'))
  return Number.isFinite(n) ? n : 0
}

// Columnas fijas en orden lógico
const columnas = [
  { key: 'fecha', titulo: 'Fecha', align: 'center' },
  { key: 'numero_ticket', titulo: 'N° Ticket', align: 'center' },
  { key: 'agencia', titulo: 'Agencia' },
  { key: 'terminal', titulo: 'Terminal', align: 'center' },
  { key: 'total', titulo: 'Total', align: 'right' }
]

// Utilidades locales
function obtener(obj, claves) {
  for (const k of claves) {
    if (obj && obj[k] !== undefined && obj[k] !== null && obj[k] !== '') return obj[k]
  }
  return ''
}
function formatearFecha(v) { return fmtFecha(v) }
function formatearMoneda(v) { return fmtMoneda(v) }
</script>
