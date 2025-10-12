<template>
  <!-- TablaBase.vue: tabla genérica reutilizable para todos los reportes -->
  <div class="bg-white rounded-lg shadow">
    <!-- Encabezado extra opcional -->
    <slot name="header-extra"></slot>

    <!-- Estado de carga -->
    <div v-if="cargando" class="p-8 text-center">
      <i class="fas fa-spinner fa-spin text-2xl text-gray-400 mb-2"></i>
      <p class="text-gray-600">Cargando datos...</p>
    </div>

    <!-- Estado de error -->
    <div v-else-if="error" class="p-8 text-center">
      <i class="fas fa-exclamation-triangle text-2xl text-red-400 mb-2"></i>
      <p class="text-red-600">{{ error }}</p>
    </div>

    <!-- Sin datos -->
    <div v-else-if="!datos || datos.length === 0" v-show="!ocultarMensajeSinDatos" class="p-8 text-center">
      <i class="fas fa-inbox text-2xl text-gray-400 mb-2"></i>
      <p class="text-gray-600">No hay datos para mostrar</p>
    </div>

    <!-- Tabla -->
    <div v-else class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th v-for="col in columnas" :key="col.key || col" class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider" :class="alinea(col)">
              {{ titulo(col) }}
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <!-- Slot de fila personalizada -->
          <template v-if="$slots.row">
            <slot name="row" :datos="datos" :columnas="columnas"></slot>
          </template>
          <template v-else>
            <template v-for="(fila, idx) in datos" :key="idx">
              <tr class="hover:bg-gray-50" :class="zebra(idx)">
                <td v-for="col in columnas" :key="col.key || col" class="px-6 py-3 text-sm" :class="[alinea(col), claseCeldas]">
                  <slot :name="'cell-' + (col.key || col)" :valor="valor(fila, col)" :fila="fila" :columna="col">
                    {{ valor(fila, col) }}
                  </slot>
                </td>
              </tr>
              <tr v-if="$slots['row-after']">
                <td :colspan="columnas.length" class="px-6 py-4">
                  <slot name="row-after" :fila="fila" :index="idx" :columnas="columnas"></slot>
                </td>
              </tr>
            </template>
          </template>
        </tbody>
      </table>

      <!-- Paginación simple -->
      <div v-if="paginacion && paginacion.totalPaginas > 1" class="px-6 py-3 border-t border-gray-200 bg-gray-50 flex items-center justify-between">
        <div class="text-sm text-gray-700">Página {{ paginacion.paginaActual }} de {{ paginacion.totalPaginas }}</div>
        <div class="flex gap-2">
          <button :disabled="paginacion.paginaActual <= 1" class="px-3 py-1 text-sm bg-white border border-gray-300 rounded-md disabled:opacity-50" @click="$emit('cambiar-pagina', paginacion.paginaActual - 1)">Anterior</button>
          <button :disabled="paginacion.paginaActual >= paginacion.totalPaginas" class="px-3 py-1 text-sm bg-white border border-gray-300 rounded-md disabled:opacity-50" @click="$emit('cambiar-pagina', paginacion.paginaActual + 1)">Siguiente</button>
        </div>
      </div>

      <slot name="footer"></slot>
    </div>
  </div>
</template>

<script setup>
// TablaBase.vue: tabla base sin lógica de negocio. Totalmente reusable.
// - columnas: array de claves o { key, titulo, align }
// - datos: array de registros
// - slots: row, cell-<key>, header-extra, footer

const props = defineProps({
  columnas: { type: Array, default: () => [] },
  datos: { type: Array, default: () => [] },
  cargando: { type: Boolean, default: false },
  error: { type: String, default: null },
  paginacion: { type: Object, default: null },
  ocultarMensajeSinDatos: { type: Boolean, default: false },
  // Clase CSS adicional para aplicar a todas las celdas (td)
  claseCeldas: { type: String, default: '' }
})

function titulo(col) {
  if (!col) return ''
  return typeof col === 'string' ? formatearTitulo(col) : (col.titulo || formatearTitulo(col.key))
}

function alinea(col) {
  const align = typeof col === 'string' ? 'left' : (col.align || 'left')
  return align === 'right' ? 'text-right' : (align === 'center' ? 'text-center' : 'text-left')
}

function valor(fila, col) {
  if (!fila) return ''
  const key = typeof col === 'string' ? col : col.key
  return fila?.[key]
}

function formatearTitulo(key) {
  return String(key || '')
    .replace(/_/g, ' ')
    .replace(/\b\w/g, l => l.toUpperCase())
}

// Zebra striping para mejorar legibilidad
function zebra(i) {
  return i % 2 === 0 ? 'bg-white' : 'bg-gray-50'
}
</script>
