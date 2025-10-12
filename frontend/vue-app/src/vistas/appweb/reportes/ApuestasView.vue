<template>
  <!-- Reporte AppWeb: Apuestas -->
  <div class="w-full">
    <!-- Encabezado del reporte -->
    <ReportHeader titulo="Apuestas" origen="AppWeb" icono="fas fa-dice" color="green" />
    <div v-if="kpis && Object.keys(kpis).length > 0" class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
      <CardKPI titulo="Total Ingreso por Apuestas" :valor="kpis.total_ingreso_apuestas" formato="moneda" icono="fas fa-coins" color="info" fondo="blanco" />
      <CardKPI titulo="Total Premios Pagados" :valor="kpis.total_premios_pagados" formato="moneda" icono="fas fa-trophy" color="success" fondo="blanco" />
      <CardKPI titulo="Diferencia (Ganancia Casa)" :valor="kpis.diferencia" formato="moneda" icono="fas fa-chart-line" :color="Number(kpis.diferencia) >= 0 ? 'success' : 'danger'" fondo="blanco" />
    </div>
    <TablaBase :columnas="columnas" :datos="store.datosReporte" :cargando="store.cargando" :error="store.error" :paginacion="store.paginacion" :ocultar-mensaje-sin-datos="true" @cambiar-pagina="store.cambiarPagina" />
  </div>
</template>

<script setup>
// Vista AppWeb: Apuestas
import { computed } from 'vue'
import TablaBase from '../../../components/comunes/TablaBase.vue'
import CardKPI from '../../../components/comunes/CardKPI.vue'
import ReportHeader from '../../../components/comunes/ReportHeader.vue'
import { useReportesStore } from '../../../stores/reportes'

const store = useReportesStore()
const kpis = computed(() => store.kpis || {})

const columnas = computed(() => {
  const datos = store.datosReporte
  if (!Array.isArray(datos) || datos.length === 0) return []
  const primer = datos[0]
  if (!primer || typeof primer !== 'object') return []
  return Object.keys(primer).map(k => ({
    key: k,
    titulo: k.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()),
    align: typeof primer[k] === 'number' ? 'right' : 'left'
  }))
})
</script>
