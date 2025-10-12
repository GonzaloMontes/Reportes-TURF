<template>
  <!-- Reporte Agencia (usuario): Ventas Diarias -->
  <div class="w-full">
    <!-- Encabezado -->
    <ReportHeader titulo="Ventas Diarias" origen="Agencia" icono="fas fa-calendar-day" color="green" />

    <!-- KPIs específicos para Ventas Diarias (siempre visibles) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-6">
      <!-- Total Vendido -->
      <CardKPI titulo="Total Vendido" :valor="kpisVD.total_vendidos" formato="moneda" icono="fas fa-dollar-sign" color="orange" fondo="blanco"/>
      <!-- Total Ganadores -->
      <CardKPI titulo="Total Ganadores" :valor="kpisVD.total_ganadores" formato="moneda" icono="fas fa-trophy" color="green" fondo="blanco"/>
      <!-- Total Pagados -->
      <CardKPI titulo="Total Pagados" :valor="kpisVD.total_pagados" formato="moneda" icono="fas fa-hand-holding-usd" color="blue" fondo="blanco"/>
      <!-- Total Devoluciones -->
      <CardKPI titulo="Total Devoluciones" :valor="kpisVD.total_devoluciones" formato="moneda" icono="fas fa-undo-alt" color="red" fondo="blanco"/>
      <!-- Ganancia -->
      <CardKPI titulo="Ganancia" :valor="kpisVD.ganancia" formato="moneda" icono="fas fa-chart-line" color="purple" fondo="blanco"/>
    </div>



    <!-- Tabla dinámica -->
    <TablaBase
      :columnas="columnas"
      :datos="store.datosReporte"
      :cargando="store.cargando"
      :error="store.error"
      :paginacion="store.paginacion"
      :ocultar-mensaje-sin-datos="true"
      @cambiar-pagina="store.cambiarPagina"
    >
      <!-- Formatos comunes -->
      <template #cell-fecha="{ fila }">
        <span>{{ formatearFecha(pick(fila, ['fecha','fecha_ticket','fecha_emision'])) }}</span>
      </template>
      <template #cell-total="{ fila }">
        <span class="font-semibold">{{ formatearMoneda(pick(fila, ['total','monto','importe','valor'])) }}</span>
      </template>
      <template #cell-total_vendido="{ fila }">
        <span class="font-semibold">{{ formatearMoneda(pick(fila, ['total_vendido','vendidos'])) }}</span>
      </template>
      <template #cell-total_pagados="{ fila }">
        <span class="font-semibold">{{ formatearMoneda(pick(fila, ['total_pagados','pagados'])) }}</span>
      </template>
      <template #cell-total_ganadores="{ fila }">
        <span class="font-semibold">{{ formatearMoneda(pick(fila, ['total_ganadores','ganadores'])) }}</span>
      </template>
    </TablaBase>
  </div>
</template>

<script setup>
// VentasDiariasView.vue: Vista dinámica para ventas por día.
import { computed } from 'vue'
import { useReportesStore } from '../../../../stores/reportes'
import TablaBase from '../../../../components/comunes/TablaBase.vue'
import CardKPI from '../../../../components/comunes/CardKPI.vue'
import ReportHeader from '../../../../components/comunes/ReportHeader.vue'
import { formatearMoneda as fmtMoneda, formatearFecha as fmtFecha } from '../../../../composables/useFormato'

const store = useReportesStore()
const tieneKpis = computed(() => store.kpis && Object.keys(store.kpis).length > 0)

// KPIs normalizados para Ventas Diarias y cálculo de ganancia
const kpisVD = computed(() => {
  const k = store.kpis || {}
  const vd = Number(k.total_vendidos || 0)
  const pg = Number(k.total_pagados || 0)
  const dv = Number(k.total_devoluciones || 0)
  const gn = vd - pg - dv
  const existe = [vd, pg, dv].some(v => v > 0) || ['total_vendidos','total_pagados','total_devoluciones','total_ganadores'].some(c => c in k)
  return {
    existe,
    total_vendidos: vd,
    total_ganadores: Number(k.total_ganadores || 0),
    total_pagados: pg,
    total_devoluciones: dv,
    ganancia: gn
  }
})

// Columnas dinámicas centradas si son fechas/ids y a la derecha si son números
const columnas = computed(() => {
  const datos = store.datosReporte
  if (!Array.isArray(datos) || datos.length === 0) return []
  const primer = datos[0]
  if (!primer || typeof primer !== 'object') return []
  return Object.keys(primer).map(k => ({
    key: k,
    titulo: formatearTitulo(k),
    align: typeof primer[k] === 'number' ? 'right' : (/^(fecha|hora|id|nro|numero)/i.test(k) ? 'center' : 'left')
  }))
})

// Utilidades locales
function formatearTitulo(k) { return k.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) }
function pick(obj, claves) { for (const c of claves) { if (obj && obj[c] !== undefined && obj[c] !== null && obj[c] !== '') return obj[c] } return '' }
function formatearMoneda(v) { return fmtMoneda(v) }
function formatearFecha(v) { return fmtFecha(v) }

// Heurísticas simples para KPIs
function tipoKpi() { return 'moneda' }
function iconoKpi() { return 'fas fa-chart-line' }
function colorKpi() { return 'info' }
</script>
