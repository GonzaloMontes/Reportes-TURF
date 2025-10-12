<template>
  <!-- Vista: Informe de Caja -->
  <div class="bg-white rounded-lg shadow-md overflow-hidden">
    <ReportHeader titulo="Informe de Caja" origen="Admin" icono="fas fa-cash-register" color="indigo" descripcion="Ingreso, egreso y saldo consolidado por Agencia y Terminal" />

    <div v-if="store.cargando" class="p-8 text-center">
      <i class="fas fa-spinner fa-spin text-2xl text-gray-400 mb-2"></i>
      <p class="text-gray-600">Cargando datos...</p>
    </div>

    <div v-else-if="store.error" class="p-8 text-center">
      <i class="fas fa-exclamation-triangle text-2xl text-red-400 mb-2"></i>
      <p class="text-red-600">{{ store.error }}</p>
    </div>

    <div v-else-if="!store.datosReporte || store.datosReporte.length === 0" class="p-8 text-center">
      <i class="fas fa-inbox text-2xl text-gray-400 mb-2"></i>
      <p class="text-gray-600">No hay datos para las condiciones seleccionadas</p>
    </div>

    <div v-else class="p-6">

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <CardKPI titulo="Total Ingreso" :valor="store.kpis.total_ingreso || 0" formato="moneda" icono="fas fa-arrow-down" color="success" />
        <CardKPI titulo="Total Egreso" :valor="store.kpis.total_egreso || 0" formato="moneda" icono="fas fa-arrow-up" color="danger" />
        <CardKPI titulo="Total Saldo"  :valor="store.kpis.total_saldo  || 0" formato="moneda" icono="fas fa-balance-scale" :color="(store.kpis.total_saldo || 0) >= 0 ? 'info' : 'danger'" />
      </div>

      <div class="overflow-x-auto bg-white rounded-lg border">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-sm font-semibold text-gray-600 uppercase tracking-wider">Concepto</th>
              <th class="px-6 py-3 text-right text-sm font-semibold text-gray-600 uppercase tracking-wider">Cantidad</th>
              <th class="px-6 py-3 text-right text-sm font-semibold text-gray-600 uppercase tracking-wider">Ingreso</th>
              <th class="px-6 py-3 text-right text-sm font-semibold text-gray-600 uppercase tracking-wider">Egreso</th>
              <th class="px-6 py-3 text-right text-sm font-semibold text-gray-600 uppercase tracking-wider">Saldo</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <template v-for="ag in store.datosReporte" :key="ag.id_agencia">
              <tr class="bg-gray-100">
                <td class="px-6 py-3 text-base font-semibold text-gray-800" colspan="5">
                  Agencia: {{ ag.nombre_agencia }}
                </td>
              </tr>

              <template v-for="term in ag.terminales" :key="term.id_usuario">
                <tr>
                  <td class="px-6 py-3 text-base font-medium text-gray-900" colspan="5">
                    Terminal: {{ term.nombre_usuario }}
                  </td>
                </tr>

                <tr v-for="c in term.conceptos" :key="c.concepto + '-' + term.id_usuario" class="hover:bg-gray-50">
                  <td class="px-6 py-3 text-base text-gray-700">{{ c.concepto }}</td>
                  <td class="px-6 py-3 text-base text-right text-gray-900">{{ c.cantidad }}</td>
                  <td class="px-6 py-3 text-base text-right text-green-700">{{ formatearMoneda(c.ingreso) }}</td>
                  <td class="px-6 py-3 text-base text-right text-red-700">{{ formatearMoneda(c.egreso) }}</td>
                  <td class="px-6 py-3 text-base text-right text-gray-900">—</td>
                </tr>

                <tr class="bg-indigo-50">
                  <td class="px-6 py-3 text-base font-semibold text-gray-900">Totales {{ term.nombre_usuario }}</td>
                  <td class="px-6 py-3 text-base text-right text-gray-900">—</td>
                  <td class="px-6 py-3 text-base text-right text-green-700">{{ formatearMoneda(term.totales_terminal.ingreso) }}</td>
                  <td class="px-6 py-3 text-base text-right text-red-700">{{ formatearMoneda(term.totales_terminal.egreso) }}</td>
                  <td class="px-6 py-3 text-base text-right" :class="term.totales_terminal.saldo >= 0 ? 'text-blue-700' : 'text-red-700'">
                    {{ formatearMoneda(term.totales_terminal.saldo) }}
                  </td>
                </tr>
              </template>

              <tr class="bg-gray-100">
                <td class="px-6 py-3 text-base font-semibold text-gray-900">Totales {{ ag.nombre_agencia }}</td>
                <td class="px-6 py-3 text-base text-right text-gray-900">—</td>
                <td class="px-6 py-3 text-base text-right text-green-700">{{ formatearMoneda(calcularTotalesAgencia(ag).ingreso) }}</td>
                <td class="px-6 py-3 text-base text-right text-red-700">{{ formatearMoneda(calcularTotalesAgencia(ag).egreso) }}</td>
                <td class="px-6 py-3 text-base text-right" :class="calcularTotalesAgencia(ag).saldo >= 0 ? 'text-blue-700' : 'text-red-700'">
                  {{ formatearMoneda(calcularTotalesAgencia(ag).saldo) }}
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
// Vista: Informe de Caja integrado directamente (sin componente intermedio).
// Muestra estructura jerárquica: Agencia > Terminal > Conceptos con totales por nivel.
import { useReportesStore } from '../../../../stores/reportes'
import CardKPI from '../../../../components/comunes/CardKPI.vue'
import ReportHeader from '../../../../components/comunes/ReportHeader.vue'
import { formatearMoneda as fmtMoneda } from '../../../../composables/useFormato'

const store = useReportesStore()

function formatearMoneda(valor) {
  return fmtMoneda(valor)
}

// Calcula los totales de agencia sumando todas sus terminales
function calcularTotalesAgencia(agencia) {
  if (!agencia) return { ingreso: 0, egreso: 0, saldo: 0 }

  // Preferir totales del backend si existen
  if (agencia.totales_agencia && typeof agencia.totales_agencia === 'object') {
    const { ingreso = 0, egreso = 0, saldo = 0 } = agencia.totales_agencia
    return {
      ingreso: Number(ingreso) || 0,
      egreso: Number(egreso) || 0,
      saldo: Number(saldo) || 0
    }
  }

  // Sumar totales por terminal
  if (Array.isArray(agencia.terminales) && agencia.terminales.length > 0) {
    let ingreso = 0, egreso = 0, saldo = 0
    for (const t of agencia.terminales) {
      if (t?.totales_terminal) {
        ingreso += Number(t.totales_terminal.ingreso) || 0
        egreso += Number(t.totales_terminal.egreso) || 0
        saldo  += Number(t.totales_terminal.saldo)  || 0
      } else if (Array.isArray(t?.conceptos)) {
        let ing = 0, egr = 0
        for (const c of t.conceptos) {
          ing += Number(c?.ingreso) || 0
          egr += Number(c?.egreso) || 0
        }
        ingreso += ing
        egreso += egr
        saldo  += (ing - egr)
      }
    }
    return { ingreso, egreso, saldo }
  }

  return { ingreso: 0, egreso: 0, saldo: 0 }
}
</script>
