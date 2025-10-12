<template>
  <!-- Reporte Agencia (usuario): Sports y Carreras -->
  <div class="w-full">
    <ReportHeader titulo="Sports y Carreras" origen="Agencia" icono="fas fa-flag-checkered" color="green" />

    <TablaBase
      :columnas="columnas"
      :datos="store.datosReporte"
      :cargando="store.cargando"
      :error="store.error"
      :paginacion="store.paginacion"
      :ocultar-mensaje-sin-datos="true"
      @cambiar-pagina="store.cambiarPagina"
    >
      <!-- Fecha centrada -->
      <template #cell-fecha="{ fila }">
        <span>{{ formatearFecha(pick(fila, ['fecha','fecha_carrera','fecha_evento','fecha_inicio'])) }}</span>
      </template>
      <!-- Estado con badge -->
      <template #cell-estado="{ fila }">
        <EstadoBadge :estado="pick(fila, ['estado','estado_carrera','status'])" />
      </template>
    </TablaBase>
  </div>
</template>

<script setup>
// SportsCarrerasView.vue: listado de carreras (agencia-usuario) con estado y fecha.
import { computed } from 'vue'
import { useReportesStore } from '../../../../stores/reportes'
import TablaBase from '../../../../components/comunes/TablaBase.vue'
import ReportHeader from '../../../../components/comunes/ReportHeader.vue'
import EstadoBadge from '../../../../components/comunes/EstadoBadge.vue'
import { formatearFecha as fmtFecha } from '../../../../composables/useFormato'

const store = useReportesStore()

// Columnas dinámicas con centrado para id/fecha/estado
const columnas = computed(() => {
  const datos = store.datosReporte
  if (!Array.isArray(datos) || datos.length === 0) return []
  const primer = datos[0]
  if (!primer || typeof primer !== 'object') return []
  return Object.keys(primer).map(k => ({
    key: k,
    titulo: k.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()),
    align: /^(fecha|hora|estado|numero|nro|id)/i.test(k) ? 'center' : 'left'
  }))
})

function pick(obj, claves) { for (const c of claves) { if (obj && obj[c] !== undefined && obj[c] !== null && obj[c] !== '') return obj[c] } return '' }
function formatearFecha(v) { return fmtFecha(v) }
</script>
