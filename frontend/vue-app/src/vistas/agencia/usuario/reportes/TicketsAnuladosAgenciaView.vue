<template>
  <!-- Reporte Agencia (usuario): Tickets Anulados -->
  <div class="w-full">
    <ReportHeader titulo="Tickets Anulados" origen="Agencia" icono="fas fa-times-circle" color="green" />

    <TablaBase
      :columnas="columnas"
      :datos="store.datosReporte"
      :cargando="store.cargando"
      :error="store.error"
      :paginacion="store.paginacion"
      :ocultar-mensaje-sin-datos="true"
      @cambiar-pagina="store.cambiarPagina"
    >
      <!-- Nombre Agencia -->
      <template #cell-nombre_agencia="{ fila }">
        <span class="text-black">{{ pick(fila, ['nombre_agencia','agencia','nombre_agencia_ticket','agencia_nombre']) || '-' }}</span>
      </template>
      <template #cell-numero_ticket="{ fila }">
        <span class="text-black">{{ pick(fila, ['numero_ticket','nro_ticket','ticket','id_ticket']) }}</span>
      </template>
      <template #cell-fecha="{ fila }">
        <span class="text-black">{{ formatearFecha(pick(fila, ['fecha','fecha_ticket','fecha_emision'])) }}</span>
      </template>
      <template #cell-hora="{ fila }">
        <span class="text-black">{{ pick(fila, ['hora','hora_ticket','hora_emision','time']) }}</span>
      </template>
      <template #cell-nombre_usuario="{ fila }">
        <span class="text-black">{{ pick(fila, ['nombre_usuario','usuario','terminal','nombre_terminal']) }}</span>
      </template>
      <template #cell-total_apostado="{ fila }">
        <span class="text-black font-semibold">{{ formatearMoneda(pick(fila, ['total_apostado','monto','importe','total','apostado'])) }}</span>
      </template>
    </TablaBase>
  </div>
</template>

<script setup>
// TicketsAnuladosAgenciaView.vue: listado de tickets anulados para rol agencia.
import { computed } from 'vue'
import { useReportesStore } from '../../../../stores/reportes'
import TablaBase from '../../../../components/comunes/TablaBase.vue'
import ReportHeader from '../../../../components/comunes/ReportHeader.vue'
import { formatearMoneda as fmtMoneda, formatearFecha as fmtFecha } from '../../../../composables/useFormato'

const store = useReportesStore()

const columnas = computed(() => {
  const datos = store.datosReporte
  if (!Array.isArray(datos) || datos.length === 0) return []
  const primer = datos[0] || {}
  const claves = Object.keys(primer)
  const cols = []
  // Insertar Nombre Agencia al inicio si no está
  if (!claves.includes('nombre_agencia')) {
    cols.push({ key: 'nombre_agencia', titulo: 'Nombre Agencia', align: 'left' })
  }
  // Resto de columnas desde los datos
  for (const k of claves) {
    cols.push({
      key: k,
      titulo: k.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()),
      align: /^(fecha|hora|numero|nro|id)/i.test(k) ? 'center' : (/^(total|monto|importe)/i.test(k) ? 'right' : 'left')
    })
  }
  return cols
})

function pick(obj, claves) { for (const c of claves) { if (obj && obj[c] !== undefined && obj[c] !== null && obj[c] !== '') return obj[c] } return '' }
function formatearMoneda(v) { return fmtMoneda(v) }
function formatearFecha(v) { return fmtFecha(v) }
</script>
