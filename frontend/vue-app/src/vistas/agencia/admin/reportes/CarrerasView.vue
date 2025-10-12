<template>
  <!-- Vista: Carreras -->
  <ReportHeader titulo="Carreras" origen="Admin" icono="fas fa-flag-checkered" color="gray" />

  <TablaBase
    :columnas="columnas"
    :datos="store.datosReporte"
    :cargando="store.cargando"
    :error="store.error"
    :paginacion="store.paginacion"
    @cambiar-pagina="store.cambiarPagina"
  >
    <!-- Fila personalizada con botón Ver resultado y detalle desplegable -->
    <template #row="{ datos }">
      <template v-for="(fila, i) in datos" :key="obtenerIdCarrera(fila)">
        <tr class="hover:bg-gray-50" :class="i % 2 === 0 ? 'bg-white' : 'bg-gray-50'">
          <!-- Número de carrera + botón detalle -->
          <td class="px-6 py-3 text-sm text-left text-gray-900">
            <span>Carrera {{ fila[kNumero] }}</span>
            <button class="ml-2 text-blue-600 hover:text-blue-800 text-sm font-medium underline"
                    @click="toggleResultados(obtenerIdCarrera(fila))">
              {{ carreraVisible === obtenerIdCarrera(fila) ? 'Ocultar' : 'Ver resultado' }}
            </button>
          </td>
          <!-- Fecha -->
          <td class="px-6 py-3 text-sm text-center text-gray-900">{{ formatearFecha(fila[kFecha]) }}</td>
          <!-- Hipódromo -->
          <td class="px-6 py-3 text-sm text-center text-gray-900">{{ fila[kHipodromo] }}</td>
          <!-- Favorito -->
          <td class="px-6 py-3 text-sm text-center text-gray-900">{{ fila[kFavorito] || '—' }}</td>
          <!-- Borrados -->
          <td class="px-6 py-3 text-sm text-center text-gray-900">{{ (Number(fila[kBorrados] || 0) === 0) ? 'Corren todos' : Number(fila[kBorrados] || 0) }}</td>
        </tr>

        <!-- Detalle de resultados -->
        <tr v-if="carreraVisible === obtenerIdCarrera(fila)" class="bg-gray-100">
          <td :colspan="5" class="px-6 py-4">
            <div v-if="cargando" class="text-center py-4 text-gray-600">Cargando resultados...</div>
            <div v-else-if="resultados.length > 0" class="bg-white rounded-lg shadow border">
              <div class="overflow-x-auto">
                <table class="min-w-full">
                  <thead class="bg-gray-100">
                    <tr>
                      <th class="px-4 py-2 text-center text-xs font-medium text-gray-700 uppercase">Posición</th>
                      <th class="px-4 py-2 text-center text-xs font-medium text-gray-700 uppercase">Caballo</th>
                      <th class="px-4 py-2 text-center text-xs font-medium text-gray-700 uppercase">Apuesta</th>
                      <th class="px-4 py-2 text-center text-xs font-medium text-gray-700 uppercase">Vales</th>
                      <th class="px-4 py-2 text-center text-xs font-medium text-gray-700 uppercase">Div.Orig</th>
                      <th class="px-4 py-2 text-center text-xs font-medium text-gray-700 uppercase">Div.Inc</th>
                      <th class="px-4 py-2 text-center text-xs font-medium text-gray-700 uppercase">Total</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white">
                    <tr v-for="r in resultados" :key="(r.posicion != null ? 'pos-' + r.posicion : 'ap-' + (r.apuesta || '-'))" class="border-b border-gray-200 odd:bg-gray-50 even:bg-white">
                      <!-- Posición (soporta extendido y fallback) -->
                      <td class="px-4 py-3 text-center text-gray-900">{{ r.posicion ?? r.posicion_llegada }}</td>
                      <!-- Caballo: mostrar NÚMERO, no nombre -->
                      <td class="px-4 py-3 text-center text-gray-900">{{ r.nro_caballo ?? r.id_caballo }}</td>
                      <!-- Apuesta (extendido) o '-' en fallback -->
                      <td class="px-4 py-3 text-center text-gray-900">{{ r.apuesta ?? '-' }}</td>
                      <!-- Vales (extendido) o 0 en fallback) -->
                      <td class="px-4 py-3 text-center text-gray-900">{{ Number(r.vales ?? 0) }}</td>
                      <!-- Div.Puro / Div.Inc / Total (extendido) o 0 en fallback -->
                      <td class="px-4 py-3 text-center text-gray-900">{{ formatearMoneda(r.div_orig ?? 0) }}</td>
                      <td class="px-4 py-3 text-center text-gray-900">{{ formatearMoneda(r.div_inc ?? (r.div_orig ?? 0)) }}</td>
                      <td class="px-4 py-3 text-center text-gray-900">{{ formatearMoneda(r.total ?? 0) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div v-else class="text-center py-4 text-gray-600">No hay resultados disponibles</div>
          </td>
        </tr>
      </template>
    </template>
  </TablaBase>
</template>

<script setup>
// Vista específica para "Carreras": columnas fijas + despliegue de resultados.
import { computed, ref } from 'vue'
import { useReportesStore } from '../../../../stores/reportes'
import TablaBase from '../../../../components/comunes/TablaBase.vue'
import ReportHeader from '../../../../components/comunes/ReportHeader.vue'
import { formatearFecha as fmtFecha, formatearMoneda as fmtMoneda } from '../../../../composables/useFormato'
// Estado eliminado del listado principal para simplificar columnas
import { apiClient } from '../../../../services/api'

const store = useReportesStore()

// Claves flexibles según backend
const primer = computed(() => (Array.isArray(store.datosReporte) && store.datosReporte[0]) || {})
const kNumero = computed(() => pickKey(primer.value, ['numero_carrera','nro_carrera']))
const kFecha = computed(() => pickKey(primer.value, ['fecha','fecha_carrera','fecha_evento','fecha_inicio']))
const kHipodromo = computed(() => pickKey(primer.value, ['nombre_hipodromo','hipodromo','hipodromo_nombre']))
// Nuevos campos enriquecidos por backend
const kFavorito = computed(() => pickKey(primer.value, ['favorito']))
const kBorrados = computed(() => pickKey(primer.value, ['borrados']))
const kIdCarrera = computed(() => pickKey(primer.value, ['carrera_interna_id','id_carrera','id']))

// Encabezados fijos en el orden solicitado
const columnas = computed(() => [
  { key: 'numero_carrera', titulo: 'Numero Carrera' },
  { key: 'fecha', titulo: 'Fecha', align: 'center' },
  { key: 'nombre_hipodromo', titulo: 'Nombre Hipodromo', align: 'center' },
  { key: 'favorito', titulo: 'Favorito', align: 'center' },
  { key: 'borrados', titulo: 'Borrados', align: 'center' }
])

// Estado para detalle de resultados
const carreraVisible = ref(null)
const resultados = ref([])
const cargando = ref(false)

function formatearFecha(v) { return fmtFecha(v) }
function formatearMoneda(v) { return fmtMoneda(v) }
function pickKey(obj, keys) { return keys.find(k => obj && Object.prototype.hasOwnProperty.call(obj, k)) || keys[0] }
function obtenerIdCarrera(fila) { return fila[kIdCarrera.value] }

// EstadoBadge maneja los colores del estado

// Cargar/alternar resultados de una carrera
async function toggleResultados(idCarrera) {
  if (!idCarrera) return
  if (carreraVisible.value === idCarrera) {
    carreraVisible.value = null
    return
  }
  carreraVisible.value = idCarrera
  resultados.value = []
  cargando.value = true
  try {
    const r = await apiClient.get(`/reports/resultados-carrera`, { params: { id_carrera: idCarrera } })
    resultados.value = r.resultados_caballos || []
  } catch (e) {
    resultados.value = []
  } finally {
    cargando.value = false
  }
}
</script>
