<template>
  <!-- Vista: Tickets Anulados -->
  <ReportHeader titulo="Tickets Anulados" origen="Admin" icono="fas fa-times-circle" color="gray" />

  <TablaBase
    :columnas="columnas"
    :datos="store.datosReporte"
    :cargando="store.cargando"
    :error="store.error"
    :paginacion="store.paginacion"
    @cambiar-pagina="store.cambiarPagina"
  >
    <!-- Nro ticket -->
    <template #cell-numero_ticket="{ fila }">
      <span class="text-black">{{ obtener(fila, ['numero_ticket','nro_ticket','ticket','id_ticket']) }}</span>
    </template>

    <!-- Fecha -->
    <template #cell-fecha="{ fila }">
      <span class="text-black">{{ formatearFecha(obtener(fila, ['fecha','fecha_ticket','fecha_creacion'])) }}</span>
    </template>

    <!-- Hora -->
    <template #cell-hora="{ fila }">
      <span class="text-black">{{ obtener(fila, ['hora','hora_ticket','hora_creacion','time']) }}</span>
    </template>

    <!-- Nombre Usuario -->
    <template #cell-nombre_usuario="{ fila }">
      <span class="text-black">{{ obtener(fila, ['nombre_usuario','usuario','nombre','terminal','nombre_terminal']) }}</span>
    </template>

    <!-- Nombre Agencia -->
    <template #cell-nombre_agencia="{ fila }">
      <span class="text-black">{{ obtener(fila, ['nombre_agencia','agencia','agencia_nombre']) }}</span>
    </template>

    <!-- Total apostado (moneda) -->
    <template #cell-total_apostado="{ fila }">
      <span class="text-black">{{ formatearMoneda(obtener(fila, ['total_apostado','monto','importe','total','apostado'])) }}</span>
    </template>
  </TablaBase>
</template>

<script setup>
// Vista simple para "Tickets Anulados": columnas fijas requeridas por negocio; texto negro.
import { useReportesStore } from '../../../../stores/reportes'
import TablaBase from '../../../../components/comunes/TablaBase.vue'
import ReportHeader from '../../../../components/comunes/ReportHeader.vue'
import { formatearMoneda as fmtMoneda, formatearFecha as fmtFecha } from '../../../../composables/useFormato'

const store = useReportesStore()

// Columnas fijas en el orden solicitado por negocio
const columnas = [
  { key: 'numero_ticket', titulo: 'Nro Ticket', align: 'center' },
  { key: 'fecha', titulo: 'Fecha', align: 'center' },
  { key: 'hora', titulo: 'Hora', align: 'center' },
  { key: 'nombre_usuario', titulo: 'Nombre Usuario', align: 'center' },
  { key: 'nombre_agencia', titulo: 'Nombre Agencia', align: 'center' },
  { key: 'total_apostado', titulo: 'Total Apostado', align: 'center' }
]

// Utilidades simples
function obtener(obj, claves) {
  for (const k of claves) {
    if (obj && obj[k] !== undefined && obj[k] !== null && obj[k] !== '') return obj[k]
  }
  return ''
}
function formatearMoneda(v) { return fmtMoneda(v) }
function formatearFecha(v) { return fmtFecha(v) }
</script>
