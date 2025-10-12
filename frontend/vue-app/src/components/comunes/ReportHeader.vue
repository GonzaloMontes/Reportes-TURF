<template>
  <!-- Título reutilizable de reporte con chip de origen -->
  <div class="px-6 py-4 border-b border-gray-200 bg-white rounded-lg shadow mb-4">
    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
      <span v-if="origen" :class="['inline-flex items-center px-2 py-1 rounded-full text-xs font-medium mr-3', claseChip]">{{ origen }}</span>
      <i :class="[icono, claseIcono]" class="mr-2"></i>
      {{ titulo }}
    </h3>
    <p v-if="descripcion" class="text-sm text-gray-600">{{ descripcion }}</p>
  </div>
</template>

<script setup>
// ReportHeader.vue: Encabezado estándar para vistas de reportes.
// Props en español y clases utilitarias tailwind para fácil personalización.
import { computed } from 'vue'

const props = defineProps({
  // Texto principal del encabezado
  titulo: { type: String, required: true },
  // Nombre del origen (chip). Ej: "AppWeb", "Admin"
  origen: { type: String, default: '' },
  // Icono FontAwesome a mostrar (clase completa)
  icono: { type: String, default: 'fas fa-file-alt' },
  // Color semántico para chip e icono: green | blue | gray | indigo | purple
  color: { type: String, default: 'green' },
  // Descripción secundaria debajo del título (opcional)
  descripcion: { type: String, default: '' }
})

// Mapear color -> clases del chip
const claseChip = computed(() => ({
  green: 'bg-green-100 text-green-800',
  blue: 'bg-blue-100 text-blue-800',
  gray: 'bg-gray-100 text-gray-800',
  indigo: 'bg-indigo-100 text-indigo-800',
  purple: 'bg-purple-100 text-purple-800'
}[props.color] || 'bg-gray-100 text-gray-800'))

// Mapear color -> clase del icono
const claseIcono = computed(() => ({
  green: 'text-green-600',
  blue: 'text-blue-600',
  gray: 'text-gray-600',
  indigo: 'text-indigo-600',
  purple: 'text-purple-600'
}[props.color] || 'text-gray-600'))
</script>
