<template>
  <!-- Vista: Informe por Agencia (encabezado + tabla con totales) -->
  <ReportHeader titulo="Informe por Agencia" origen="Admin" icono="fas fa-building" color="indigo" />

  <!-- Subtítulo y cantidad de registros -->
  <div class="mb-2">
    <h4 class="text-base font-medium text-gray-900">Informe por Agencia</h4>
    <p class="text-sm text-gray-600">{{ filas.length }} registros encontrados</p>
  </div>

  <TablaBase
    :columnas="columnas"
    :datos="filas"
    :cargando="store.cargando"
    :error="store.error"
    :paginacion="store.paginacion"
    @cambiar-pagina="store.cambiarPagina"
  >
    <!-- Render personalizado de filas para incluir Fila de Totales -->
    <template #row="{ datos }">
      <tr v-for="(f, i) in datos" :key="'ag-' + i" class="hover:bg-gray-50" :class="i % 2 === 0 ? 'bg-white' : 'bg-gray-50'">
        <td class="px-6 py-3 text-sm text-center text-gray-900">{{ f.agencia }}</td>
        <td class="px-6 py-3 text-sm text-center text-gray-900">{{ formatearNumero(f.anulados) }}</td>
        <td class="px-6 py-3 text-sm text-center text-gray-900">{{ formatearMoneda(f.vendidos) }}</td>
        <td class="px-6 py-3 text-sm text-center text-gray-900">{{ formatearMoneda(f.ganadores) }}</td>
        <td class="px-6 py-3 text-sm text-center text-gray-900">{{ formatearMoneda(f.pagados) }}</td>
        <td class="px-6 py-3 text-sm text-center text-gray-900">{{ formatearMoneda(f.devoluciones) }}</td>
        <td class="px-6 py-3 text-sm text-center" :class="f.ganancia >= 0 ? 'text-green-700' : 'text-red-700'">{{ formatearMoneda(f.ganancia) }}</td>
      </tr>

      <!-- Fila de Totales -->
      <tr class="bg-gray-100 font-semibold border-t-2 border-gray-300">
        <td class="px-6 py-3 text-sm text-center text-gray-900">Totales</td>
        <td class="px-6 py-3 text-sm text-center text-gray-900">—</td>
        <td class="px-6 py-3 text-sm text-center text-black">{{ formatearMoneda(totales.vendidos) }}</td>
        <td class="px-6 py-3 text-sm text-center text-black">{{ formatearMoneda(totales.ganadores) }}</td>
        <td class="px-6 py-3 text-sm text-center text-black">{{ formatearMoneda(totales.pagados) }}</td>
        <td class="px-6 py-3 text-sm text-center text-black">{{ formatearMoneda(totales.devoluciones) }}</td>
        <td class="px-6 py-3 text-sm text-center" :class="totales.ganancia >= 0 ? 'text-black' : 'text-red-700'">{{ formatearMoneda(totales.ganancia) }}</td>
      </tr>
    </template>
  </TablaBase>
</template>

<script setup>
// Vista específica minimalista y reutilizable para "Informe por Agencia".
// - Usa TablaBase con columnas fijas y fila de totales.
import { computed } from 'vue'
import { useReportesStore } from '../../../../stores/reportes'
import TablaBase from '../../../../components/comunes/TablaBase.vue'
import ReportHeader from '../../../../components/comunes/ReportHeader.vue'
import { formatearMoneda as fmtMoneda, formatearNumero as fmtNumero } from '../../../../composables/useFormato'

const store = useReportesStore()

// Columnas fijas en el orden solicitado
const columnas = [
  { key: 'agencia', titulo: 'Agencia', align: 'center' },
  { key: 'anulados', titulo: 'Anulados', align: 'center' },
  { key: 'vendidos', titulo: 'Vendidos', align: 'center' },
  { key: 'ganadores', titulo: 'Ganadores', align: 'center' },
  { key: 'pagados', titulo: 'Pagados', align: 'center' },
  { key: 'devoluciones', titulo: 'Devoluciones', align: 'center' },
  { key: 'ganancia', titulo: 'Ganancia', align: 'center' },
]

// Normalizar filas del backend a claves estándar sin mutar store
const filas = computed(() => {
  const datos = Array.isArray(store.datosReporte) ? store.datosReporte : []
  return datos.map((r) => ({
    agencia: pick(r, ['agencia','nombre_agencia','agencia_nombre']) || '-',
    anulados: toNum(pick(r, ['anulados','total_anulados','tickets_anulados'])),
    vendidos: toNum(pick(r, ['vendidos','total_vendido','total_vendidos'])),
    ganadores: toNum(pick(r, ['ganadores','total_ganadores'])),
    pagados: toNum(pick(r, ['pagados','total_pagados'])),
    devoluciones: toNum(pick(r, ['devoluciones','total_devoluciones'])),
    ganancia: toNum(pick(r, ['ganancia','ganancia_total'])),
  }))
})

// Totales: usar kpis si vienen, sino sumar filas
const totales = computed(() => {
  const k = store.kpis || {}
  const f = filas.value
  const sum = (sel) => f.reduce((acc, x) => acc + (Number(x[sel]) || 0), 0)
  return {
    vendidos: toNum(k.total_vendido) || sum('vendidos'),
    ganadores: toNum(k.total_ganadores) || sum('ganadores'),
    pagados: toNum(k.total_pagados) || sum('pagados'),
    devoluciones: toNum(k.total_devoluciones) || sum('devoluciones'),
    ganancia: toNum(k.ganancia) || sum('ganancia'),
  }
})

// Utilidades locales simples
function pick(obj, keys) {
  for (const k of keys) {
    if (obj && obj[k] !== undefined && obj[k] !== null) return obj[k]
  }
  return null
}
function toNum(v) {
  const n = typeof v === 'number' ? v : parseFloat(String(v).replace(/\./g,'').replace(',', '.'))
  return Number.isFinite(n) ? n : 0
}

// Reexponer formateadores con nombre en español
function formatearMoneda(v) { return fmtMoneda(v) }
function formatearNumero(v) { return fmtNumero(v) }
</script>
