<template>
  <!-- Reporte AppWeb: Económico -->
  <div class="w-full">
    <!-- Encabezado del reporte -->
    <ReportHeader titulo="Económico" origen="AppWeb" icono="fas fa-dollar-sign" color="green" />
    <!-- KPIs principales -->
    <div v-if="kpis && Object.keys(kpis).length > 0" class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
      <CardKPI titulo="Total Acreditado" :valor="kpis.total_acreditado" formato="moneda" icono="fas fa-arrow-up" color="info" fondo="blanco" />
      <CardKPI titulo="Total Debitado" :valor="kpis.total_debitado" formato="moneda" icono="fas fa-arrow-down" color="danger" fondo="blanco" />
      <CardKPI titulo="Diferencia" :valor="kpis.diferencia" formato="moneda" icono="fas fa-balance-scale" :color="Number(kpis.diferencia) >= 0 ? 'success' : 'danger'" fondo="blanco" />
    </div>

    <!-- Tabla genérica -->
    <TablaBase
      :columnas="columnas"
      :datos="store.datosReporte"
      :cargando="store.cargando"
      :error="store.error"
      :paginacion="store.paginacion"
      :ocultar-mensaje-sin-datos="true"
      @cambiar-pagina="store.cambiarPagina"
    />
  </div>
</template>

<script setup>
// Vista AppWeb: Económico
import { computed } from 'vue'
import TablaBase from '../../../components/comunes/TablaBase.vue'
import CardKPI from '../../../components/comunes/CardKPI.vue'
import ReportHeader from '../../../components/comunes/ReportHeader.vue'
import { useReportesStore } from '../../../stores/reportes'

const store = useReportesStore()

// KPIs derivados del store
const kpis = computed(() => store.kpis || {})

// Columnas genéricas calculadas desde los datos
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
