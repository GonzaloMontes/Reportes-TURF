<template>
  <!-- Reporte AppWeb: Por Usuario -->
  <div class="w-full">
    <!-- Encabezado del reporte -->
    <ReportHeader titulo="Rendimiento por Usuario" origen="AppWeb" icono="fas fa-users" color="green" :descripcion="totalUsuarios + ' usuarios encontrados'" />
    <!-- KPIs principales -->
    <div v-if="kpis && Object.keys(kpis).length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
      <CardKPI titulo="Total Usuarios" :valor="kpis.total_usuarios" formato="numero" icono="fas fa-users" color="info" />
      <CardKPI titulo="Total Apostado" :valor="kpis.total_apostado" formato="moneda" icono="fas fa-coins" color="info" />
      <CardKPI titulo="Total Premios" :valor="kpis.total_premios" formato="moneda" icono="fas fa-trophy" color="success" />
      <CardKPI titulo="Ganancia Casa" :valor="kpis.ganancia_casa" :formato="Number(kpis.ganancia_casa) >= 0 ? 'moneda' : 'moneda'" icono="fas fa-chart-line" color="success" />
    </div>

    <!-- Tabla de rendimiento por usuario -->
    <TablaBase
      :columnas="columnas"
      :datos="store.datosReporte"
      :cargando="store.cargando"
      :error="store.error"
      :paginacion="store.paginacion"
      @cambiar-pagina="store.cambiarPagina"
    >
      <!-- Usuario -->
      <template #cell-usuario="{ fila }">
        <div class="text-center">
          <div class="text-sm font-medium text-gray-900">{{ pick(fila, ['nombre_completo','usuario','nombre']) }}</div>
          <div class="text-xs text-gray-500">{{ pick(fila, ['email','correo']) }}</div>
        </div>
      </template>
      <!-- Saldo -->
      <template #cell-saldo_credito="{ fila }">
        <span class="text-gray-900">{{ formatearMoneda(pick(fila, ['saldo_credito','saldo','balance'])) }}</span>
      </template>
      <!-- Apostado -->
      <template #cell-total_apostado="{ fila }">
        <span class="font-semibold">{{ formatearMoneda(pick(fila, ['total_apostado','apostado','monto_apostado'])) }}</span>
      </template>
      <!-- Premios -->
      <template #cell-total_premios="{ fila }">
        <span class="font-semibold">{{ formatearMoneda(pick(fila, ['total_premios','premios','premio_total'])) }}</span>
      </template>
      <!-- Diferencia -->
      <template #cell-diferencia="{ fila }">
        <span :class="Number(pick(fila, ['diferencia','ganancia'])) >= 0 ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold'">
          {{ formatearMoneda(pick(fila, ['diferencia','ganancia'])) }}
        </span>
      </template>
      <!-- Jugadas -->
      <template #cell-total_jugadas="{ fila }">
        <span class="text-gray-900">{{ pick(fila, ['total_jugadas','jugadas','cantidad']) }}</span>
      </template>
      <!-- Acciones -->
      <template #cell-acciones="{ fila }">
        <button @click="toggleDetalleUsuario(pick(fila, ['id_usuario','id']))" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
          <i :class="usuariosExpandidos.has(pick(fila, ['id_usuario','id'])) ? 'fas fa-chevron-up' : 'fas fa-chevron-down'" class="mr-1"></i>
          {{ usuariosExpandidos.has(pick(fila, ['id_usuario','id'])) ? 'Ocultar' : 'Ver detalles' }}
        </button>
      </template>

      <!-- Fila extra (detalle) debajo de cada fila -->
      <template #row-after="{ fila }">
        <div v-if="usuariosExpandidos.has(pick(fila, ['id_usuario','id']))" class="bg-white rounded-lg p-4 border">
          <h4 class="text-sm font-medium text-gray-900 mb-3">Historial de Jugadas</h4>
          <!-- Loading -->
          <div v-if="!detallesUsuarios[pick(fila, ['id_usuario','id'])]" class="text-center py-4 text-gray-600">
            <i class="fas fa-spinner fa-spin mr-2"></i>Cargando detalles...
          </div>
          <!-- Tabla de detalles -->
          <div v-else-if="detallesUsuarios[pick(fila, ['id_usuario','id'])]?.length > 0" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-100">
                <tr>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nro Jugada</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Carrera</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Hipódromo</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tipo Apuesta</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Apostado</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Premio</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Resultado</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Caballos</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr v-for="detalle in detallesUsuarios[pick(fila, ['id_usuario','id'])]" :key="detalle.nro_jugada" class="text-sm">
                  <td class="px-4 py-2">{{ detalle.fecha_jugada }}</td>
                  <td class="px-4 py-2 font-medium">{{ detalle.nro_jugada }}</td>
                  <td class="px-4 py-2">{{ detalle.numero_carrera }}</td>
                  <td class="px-4 py-2">{{ detalle.nombre_hipodromo }}</td>
                  <td class="px-4 py-2">{{ detalle.tipo_apuesta }}</td>
                  <td class="px-4 py-2">{{ formatearMoneda(detalle.monto_apostado) }}</td>
                  <td class="px-4 py-2">{{ formatearMoneda(detalle.premio_ganado) }}</td>
                  <td class="px-4 py-2 font-medium" :class="Number(detalle.resultado) >= 0 ? 'text-green-600' : 'text-red-600'">{{ formatearMoneda(detalle.resultado) }}</td>
                  <td class="px-4 py-2">
                    <span v-if="detalle.caballos_apostados" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                      {{ detalle.caballos_apostados }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <!-- Sin datos -->
          <div v-else class="text-center py-4 text-gray-500">No hay jugadas registradas para este usuario</div>
        </div>
      </template>
    </TablaBase>
  </div>
</template>

<script setup>
// Vista AppWeb: Por Usuario
import { ref, computed } from 'vue'
import TablaBase from '../../../components/comunes/TablaBase.vue'
import CardKPI from '../../../components/comunes/CardKPI.vue'
import ReportHeader from '../../../components/comunes/ReportHeader.vue'
import { useReportesStore } from '../../../stores/reportes'
import { apiClient } from '../../../services/api'
import { formatearMoneda as fmtMoneda } from '../../../composables/useFormato'

const store = useReportesStore()

// KPIs derivados del store
const kpis = computed(() => store.kpis || {})

// Total de usuarios mostrados (para encabezado)
const totalUsuarios = computed(() => Array.isArray(store.datosReporte) ? store.datosReporte.length : 0)

// Columnas fijas
const columnas = [
  { key: 'usuario', titulo: 'Usuario', align: 'center' },
  { key: 'saldo_credito', titulo: 'Saldo Crédito', align: 'center' },
  { key: 'total_apostado', titulo: 'Total Apostado', align: 'center' },
  { key: 'total_premios', titulo: 'Total Premios', align: 'center' },
  { key: 'diferencia', titulo: 'Diferencia', align: 'center' },
  { key: 'total_jugadas', titulo: 'Jugadas', align: 'center' },
  { key: 'acciones', titulo: 'Acciones', align: 'center' }
]

// Estado para detalles expandidos
const usuariosExpandidos = ref(new Set())
const detallesUsuarios = ref({})

function pick(obj, claves) {
  for (const k of claves) {
    if (obj && obj[k] !== undefined && obj[k] !== null && obj[k] !== '') return obj[k]
  }
  return ''
}
function formatearMoneda(v) { return fmtMoneda(v) }

// Alternar detalle de un usuario y cargarlo si hace falta
async function toggleDetalleUsuario(idUsuario) {
  const id = Number(idUsuario)
  if (!id) return
  if (usuariosExpandidos.value.has(id)) {
    usuariosExpandidos.value.delete(id)
    return
  }
  usuariosExpandidos.value.add(id)
  if (!detallesUsuarios.value[id]) {
    try {
      const r = await apiClient.get(`/reports/appweb/detalle-usuario/${id}`)
      detallesUsuarios.value[id] = r.data || []
    } catch (e) {
      detallesUsuarios.value[id] = []
    }
  }
}
</script>
