<template>
  <!-- Reporte AppWeb: Dinero Remanente -->
  <div class="w-full">
    <!-- Encabezado del reporte -->
    <ReportHeader titulo="Dinero Remanente" origen="AppWeb" icono="fas fa-wallet" color="green" />
    <div v-if="kpis && Object.keys(kpis).length > 0" class="flex justify-center mb-8">
      <div class="bg-gradient-to-r from-purple-500 to-purple-600 text-white p-8 rounded-lg shadow-lg text-center max-w-md">
        <h3 class="text-lg font-semibold mb-2">TOTAL DINERO REMANENTE</h3>
        <p class="text-4xl font-bold">{{ formatearMoneda(kpis.total_dinero_remanente) }}</p>
        <p class="text-sm mt-2 opacity-90">Pasivo total hacia usuarios</p>
      </div>
    </div>
    <TablaBase :columnas="columnas" :datos="store.datosReporte" :cargando="store.cargando" :error="store.error" :paginacion="store.paginacion" :ocultar-mensaje-sin-datos="true" @cambiar-pagina="store.cambiarPagina" />
  </div>
</template>

<script setup>
// Vista AppWeb: Dinero Remanente
import { computed } from 'vue'
import TablaBase from '../../../components/comunes/TablaBase.vue'
import ReportHeader from '../../../components/comunes/ReportHeader.vue'
import { useReportesStore } from '../../../stores/reportes'
import { formatearMoneda as fmtMoneda } from '../../../composables/useFormato'

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

function formatearMoneda(v) { return fmtMoneda(v) }
</script>
