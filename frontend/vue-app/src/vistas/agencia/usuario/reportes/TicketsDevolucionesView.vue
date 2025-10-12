<template>
  <!-- Reporte Agencia (usuario): Tickets con Devolución -->
  <div class="w-full">
    <ReportHeader titulo="Tickets con Devolución" origen="Agencia" icono="fas fa-undo" color="green" />

    <TablaBase
      :columnas="columnas"
      :datos="store.datosReporte"
      :cargando="store.cargando"
      :error="store.error"
      :paginacion="store.paginacion"
      :ocultar-mensaje-sin-datos="true"
      @cambiar-pagina="store.cambiarPagina"
    >
      <!-- Fecha -->
      <template #cell-fecha="{ fila }">
        <span>{{ formatearFecha(pick(fila, ['fecha','fecha_ticket','fecha_devolucion'])) }}</span>
      </template>
      <!-- Nro Ticket -->
      <template #cell-numero_ticket="{ fila }">
        <span class="font-medium text-gray-900">{{ pick(fila, ['numero_ticket','nro_ticket','ticket','id_ticket']) }}</span>
      </template>
      <!-- Monto a Devolver / Devuelto -->
      <template #cell-monto_a_devolver="{ fila }">
        <span class="font-semibold">{{ formatearMoneda(pick(fila, ['monto_a_devolver','a_devolver','devolver'])) }}</span>
      </template>
      <template #cell-monto_devuelto="{ fila }">
        <span class="font-semibold">{{ formatearMoneda(pick(fila, ['monto_devuelto','devuelto','total_devuelto'])) }}</span>
      </template>
      <!-- Estado -->
      <template #cell-estado_devolucion="{ fila }">
        <EstadoBadge :estado="pick(fila, ['estado_devolucion','estado','status'])" />
      </template>
    </TablaBase>
  </div>
</template>

<script setup>
// TicketsDevolucionesView.vue: listado de tickets con devolución.
import { computed } from 'vue'
import { useReportesStore } from '../../../../stores/reportes'
import TablaBase from '../../../../components/comunes/TablaBase.vue'
import ReportHeader from '../../../../components/comunes/ReportHeader.vue'
import EstadoBadge from '../../../../components/comunes/EstadoBadge.vue'
import { formatearMoneda as fmtMoneda, formatearFecha as fmtFecha } from '../../../../composables/useFormato'

const store = useReportesStore()

// Columnas dinámicas con alineación básica
const columnas = computed(() => {
  const datos = store.datosReporte
  if (!Array.isArray(datos) || datos.length === 0) return []
  const primer = datos[0]
  if (!primer || typeof primer !== 'object') return []
  return Object.keys(primer).map(k => ({
    key: k,
    titulo: formatearTitulo(k),
    align: /^(monto|total|importe|valor)/i.test(k) ? 'right' : (/^(fecha|hora|numero|nro|id|estado)/i.test(k) ? 'center' : 'left')
  }))
})

// Utilidades locales
function formatearTitulo(k) { return k.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) }
function pick(obj, claves) { for (const c of claves) { if (obj && obj[c] !== undefined && obj[c] !== null && obj[c] !== '') return obj[c] } return '' }
function formatearMoneda(v) { return fmtMoneda(v) }
function formatearFecha(v) { return fmtFecha(v) }
</script>
