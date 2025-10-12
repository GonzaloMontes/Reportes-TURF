<template>
  <!-- PanelFiltros.vue: filtros dinámicos reutilizables por tipo de reporte -->
  <div class="bg-white rounded-lg shadow mb-6 p-4">
    <h3 class="text-lg font-medium text-gray-900 mb-4">Filtros</h3>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-4">
      <!-- Fechas -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Desde</label>
        <input type="date" class="w-full px-3 py-2 border rounded-md" :value="valores.fechaDesde" @input="e => onEdit('fechaDesde', e.target.value)"/>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Hasta</label>
        <input type="date" class="w-full px-3 py-2 border rounded-md" :value="valores.fechaHasta" @input="e => onEdit('fechaHasta', e.target.value)"/>
      </div>

      <!-- Agencia (admin) -->
      <div v-if="mostrar('agencia')">
        <label class="block text-sm font-medium text-gray-700 mb-1">Agencia</label>
        <select class="w-full px-3 py-2 border rounded-md" :value="valores.agenciaId" @change="e => onEdit('agenciaId', e.target.value)">
          <option value="">Todas las agencias</option>
          <option v-for="a in opciones.agencias" :key="a.id_agencia" :value="a.id_agencia">{{ a.nombre_agencia }}</option>
        </select>
      </div>

      <!-- Terminal (admin) -->
      <div v-if="mostrar('terminal')">
        <label class="block text-sm font-medium text-gray-700 mb-1">Terminal</label>
        <select class="w-full px-3 py-2 border rounded-md" :value="valores.terminalId" @change="e => onEdit('terminalId', e.target.value)">
          <option value="">Todas las terminales</option>
          <option v-for="t in opciones.terminales" :key="t.id_usuario" :value="t.id_usuario">{{ t.nombre_usuario }}</option>
        </select>
      </div>

      <!-- Hipódromo / Carrera (según reporte) -->
      <div v-if="mostrar('hipodromo')">
        <label class="block text-sm font-medium text-gray-700 mb-1">Hipódromo</label>
        <select class="w-full px-3 py-2 border rounded-md" :value="valores.hipodromoId" @change="e => onEdit('hipodromoId', e.target.value)">
          <option value="">Todos</option>
          <option v-for="h in opciones.hipodromos" :key="h.id_hipodromo" :value="h.id_hipodromo">{{ h.nombre_hipodromo }}</option>
        </select>
      </div>
      <div v-if="mostrar('numeroCarrera')">
        <label class="block text-sm font-medium text-gray-700 mb-1">Número de Carrera</label>
        <select class="w-full px-3 py-2 border rounded-md" :value="valores.numeroCarrera" @change="e => onEdit('numeroCarrera', e.target.value)">
          <option value="">Todas</option>
          <option v-for="c in opciones.numerosCarreras" :key="c.numero_carrera" :value="c.numero_carrera">Carrera {{ c.numero_carrera }}</option>
        </select>
      </div>

      <!-- Búsqueda Usuario (AppWeb) -->
      <div v-if="mostrar('buscarUsuario')">
        <label class="block text-sm font-medium text-gray-700 mb-1">Buscar Usuario</label>
        <input type="text" placeholder="Nombre o apellido..." class="w-full px-3 py-2 border rounded-md" :value="valores.buscarUsuario" @input="e => onEdit('buscarUsuario', e.target.value)"/>
      </div>

      <!-- Botón aplicar -->
      <div class="flex items-end">
        <button @click="$emit('aplicar')" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
          <i class="fas fa-search mr-2"></i>Aplicar Filtros
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
// Panel de filtros: configurable por tipo de reporte.
const props = defineProps({
  tipoReporte: { type: String, required: true },
  valores: { type: Object, default: () => ({}) },
  opciones: { type: Object, default: () => ({ agencias: [], terminales: [], hipodromos: [], numerosCarreras: [] }) }
})

const emit = defineEmits(['actualizar:filtros', 'aplicar'])

function onEdit(key, value) {
  emit('actualizar:filtros', { ...props.valores, [key]: value })
}

// Qué controles mostrar por tipoReporte
function mostrar(control) {
  const t = props.tipoReporte
  const mapa = {
    // Mostrar selector de Agencia en estos reportes (compatibilidad con ambos nombres)
    agencia: ['informe-agencias','informe-por-agencia','informe-parte-venta','informe-caja','tickets-anulados','ventas-tickets'],
    terminal: ['informe-caja','tickets-anulados'],
    hipodromo: ['carreras'],
    numeroCarrera: ['carreras'],
    buscarUsuario: ['por-usuario']
  }
  return (mapa[control] || []).includes(t)
}
</script>
