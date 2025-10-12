<template>
  <!-- Componente de tarjeta KPI reutilizable -->
  <div :class="['rounded-lg shadow border p-4 flex items-center justify-between', fondoPorColor]">
    <div>
      <h3 class="text-sm font-medium" :class="textoPorColor">{{ titulo }}</h3>
      <p class="text-2xl font-bold" :class="textoPorColorSecundario">{{ valorFormateado }}</p>
    </div>
    <i :class="[icono, iconoPorColor]" :style="{ fontSize: '1.75rem' }"></i>
  </div>
</template>

<script setup>
// CardKPI.vue: tarjeta para mostrar métricas con formato uniforme.
// Props en español para favorecer la coherencia del proyecto.
import { computed } from 'vue'
import { formatearMoneda, formatearNumero } from '../../composables/useFormato'

const props = defineProps({
  titulo: { type: String, required: true },
  valor: { type: [Number, String], default: 0 },
  icono: { type: String, default: 'fas fa-chart-line' },
  formato: { type: String, default: 'numero' }, // 'moneda' | 'numero' | 'texto'
  color: { type: String, default: 'info' }, // 'success' | 'danger' | 'info' | 'warning'
  // Fondo visual de la tarjeta: 'tinte' (por defecto) o 'blanco'
  fondo: { type: String, default: 'tinte' }
})

const valorFormateado = computed(() => {
  if (props.formato === 'moneda') return formatearMoneda(props.valor)
  if (props.formato === 'numero') return formatearNumero(props.valor)
  return String(props.valor ?? '')
})

// Paleta de colores semántica
const fondoPorColor = computed(() => {
  // Fondo blanco solicitado para algunos reportes AppWeb
  if (props.fondo === 'blanco') return 'bg-white border-gray-200'
  // Fondo con tinte por defecto
  return ({
    success: 'bg-green-50 border-green-200',
    danger: 'bg-red-50 border-red-200',
    info: 'bg-blue-50 border-blue-200',
    warning: 'bg-yellow-50 border-yellow-200'
  }[props.color] || 'bg-gray-50 border-gray-200')
})

const textoPorColor = computed(() => ({
  success: 'text-green-700',
  danger: 'text-red-700',
  info: 'text-blue-700',
  warning: 'text-yellow-700'
}[props.color] || 'text-gray-700'))

const textoPorColorSecundario = computed(() => ({
  success: 'text-green-700',
  danger: 'text-red-700',
  info: 'text-blue-700',
  warning: 'text-yellow-700'
}[props.color] || 'text-gray-800'))

const iconoPorColor = computed(() => ({
  success: 'text-green-600',
  danger: 'text-red-600',
  info: 'text-blue-600',
  warning: 'text-yellow-600'
}[props.color] || 'text-gray-600'))
</script>
