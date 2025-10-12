<template>
  <!-- Vista: Informe de Parte de Venta -->
  <div class="bg-white rounded-lg shadow-md overflow-hidden">
    <!-- Encabezado -->
    <ReportHeader titulo="Resumen de Parte de Venta" origen="Admin" icono="fas fa-chart-pie" color="blue" descripcion="Consolidado de ventas, cancelaciones y retirados" />

    <div v-if="store.cargando" class="p-8 text-center">
      <i class="fas fa-spinner fa-spin text-2xl text-gray-400 mb-2"></i>
      <p class="text-gray-600">Cargando datos...</p>
    </div>

    <div v-else-if="store.error" class="p-8 text-center">
      <i class="fas fa-exclamation-triangle text-2xl text-red-400 mb-2"></i>
      <p class="text-red-600">{{ store.error }}</p>
    </div>

    <div v-else-if="!store.kpis || Object.keys(store.kpis).length === 0" class="p-8 text-center">
      <i class="fas fa-inbox text-2xl text-gray-400 mb-2"></i>
      <p class="text-gray-600">No hay datos disponibles para mostrar</p>
    </div>

    <div v-else class="p-6">
      <div class="max-w-4xl lg:max-w-5xl mx-auto">
        <div class="rounded-xl bg-slate-400 p-4 ring-1 ring-slate-500 max-h-[80vh] overflow-auto">
          <table class="w-full bg-white rounded-lg shadow-sm">
            <thead class="bg-slate-400 sticky top-0 z-10">
              <tr>
                <th colspan="2" class="px-4 sm:px-6 py-2 text-left text-xs text-slate-800">{{ agenciaSeleccion }}</th>
              </tr>
              <tr>
                <th class="px-4 sm:px-6 py-2.5 text-left text-sm font-medium text-gray-800 uppercase tracking-wide">Concepto</th>
                <th class="px-1 sm:px-2 py-2.5 text-right text-sm font-medium text-gray-800 uppercase tracking-wide">Valor</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="f in filas" :key="f.label"
                  :class="[f.total ? 'bg-blue-50' : '', 'odd:bg-gray-50 even:bg-white hover:bg-slate-100']">
                <td class="py-2.5 px-4 sm:px-6 text-sm text-gray-900 whitespace-nowrap" :class="f.total ? 'font-semibold uppercase' : 'font-normal'">{{ f.label }}</td>
                <td class="py-2.5 px-1 sm:px-2 text-sm text-right text-gray-900 tabular-nums" :class="f.total ? 'font-semibold' : 'font-medium'">{{ formatearMoneda(f.valor || 0) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
// Vista: Informe de Parte de Venta (formato compacto tipo tabla)
// Presenta KPIs del store en dos columnas: Concepto / Valor.
import { useReportesStore } from '../../../../stores/reportes'
import { formatearMoneda as fmtMoneda } from '../../../../composables/useFormato'
import ReportHeader from '../../../../components/comunes/ReportHeader.vue'
import { computed, ref, onMounted } from 'vue'
import { reportesApi } from '../../../../services/api'

const store = useReportesStore()

function formatearMoneda(valor) {
  return fmtMoneda(valor)
}

// Filas a renderizar (ordenadas y con totales destacados)
const filas = computed(() => [
  // Base
  { label: 'Venta Boletos', valor: store.kpis.ventas_boletos },
  { label: 'Cancelados', valor: store.kpis.cancelados },
  { label: 'Retirados', valor: store.kpis.retirados },
  // Totales intermedios
  { label: 'Venta Neta Boletos', valor: store.kpis.venta_neta_boletos, total: true },
  { label: 'Servicios', valor: store.kpis.servicios },
  { label: 'Venta Neta Boletos s/Servicios', valor: store.kpis.venta_neta_boletos_sin_servicios, total: true },
  // Pagos y compensaciones
  { label: 'Pagos', valor: store.kpis.pagos },
  { label: 'Retirados Pagados', valor: store.kpis.retirados_pagados },
  { label: 'Retirados a Pagar', valor: store.kpis.retirados_a_pagar },
  { label: 'Pagos Especiales', valor: store.kpis.pagos_especiales },
  { label: 'Pago Neto', valor: store.kpis.pago_neto, total: true },
  // Resultados
  { label: 'Resultado Neto', valor: store.kpis.resultado_neto, total: true },
  { label: 'Boleto a Pagar', valor: store.kpis.boleto_a_pagar },
  { label: 'Resultado Neto 2', valor: store.kpis.resultado_neto_2, total: true },
  // Cubiertas
  { label: 'Cubiertas A', valor: store.kpis.cubiertas_a },
  { label: 'Cubiertas De', valor: store.kpis.cubiertas_de },
  { label: 'Cobros Cubiertas A', valor: store.kpis.cobros_cubiertas_a },
  { label: 'Pagos Cubiertas De', valor: store.kpis.pagos_cubiertas_de },
  // Finales
  { label: 'Resultado Final (Ganancia)', valor: store.kpis.resultado_final, total: true },
  { label: 'Pagos Atrasados', valor: store.kpis.pagos_atrasados },
  { label: 'Pozos Vacantes', valor: store.kpis.pozos_vacantes },
  { label: 'Venta a Contabilizar', valor: store.kpis.venta_a_contabilizar, total: true },
])

// Catálogo de agencias (para resolver nombre desde el id)
const agenciasCat = ref([])
onMounted(async () => {
  try { agenciasCat.value = await reportesApi.obtenerAgencias() } catch (_) { agenciasCat.value = [] }
})

// Etiqueta de agencia seleccionada (muestra nombre si está disponible)
const agenciaSeleccion = computed(() => {
  const id = (store.filtros && store.filtros.agenciaId) || ''
  if (!id || id === '0') return 'Totales Agencia'
  const nombreDatos = store.datosReporte?.[0]?.nombre_agencia
  const nombreCat = (Array.isArray(agenciasCat.value)
    ? (agenciasCat.value.find(a => String(a.id_agencia) === String(id))?.nombre_agencia)
    : null)
  const nombre = nombreDatos || nombreCat || id
  return `Agencia seleccionada: ${nombre}`
})
</script>
