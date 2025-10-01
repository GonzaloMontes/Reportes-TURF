<template>
  <div class="bg-white rounded-lg shadow p-6">
    <div class="flex items-center">
      <div class="flex-shrink-0">
        <i :class="[icono, colorIcono, 'text-2xl']"></i>
      </div>
      <div class="ml-5 w-0 flex-1">
        <dl>
          <dt class="text-sm font-medium text-gray-500 truncate">
            {{ titulo }}
          </dt>
          <dd class="text-2xl font-bold break-words" 
              :class="{
                'text-orange-600': titulo === 'Total Vendido' || titulo === 'Total Usuarios',
                'text-green-600': titulo === 'Total Ganadores' || titulo === 'Ganancia' || titulo === 'Ganancia Casa',
                'text-red-600': titulo === 'Total Devoluciones' || titulo === 'Total Debitado',
                'text-blue-600': titulo === 'Total Pagados' || titulo === 'Total Premios' || titulo === 'Diferencia' || titulo === 'Total Apostado' || titulo === 'Total Acreditado' || titulo === 'Total Ingreso por Apuestas' || titulo === 'Total Premios Pagados' || titulo === 'Diferencia (Ganancia Casa)'
              }">
            {{ valorFormateado }}
          </dd>
        </dl>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

// Props del componente
const props = defineProps({
  titulo: {
    type: String,
    required: true
  },
  valor: {
    type: [Number, String],
    default: 0
  },
  tipo: {
    type: String,
    default: 'moneda', // 'moneda', 'numero', 'porcentaje'
    validator: (value) => ['moneda', 'numero', 'porcentaje'].includes(value)
  },
  icono: {
    type: String,
    default: 'fas fa-chart-line'
  },
  color: {
    type: String,
    default: 'blue', // 'blue', 'green', 'red', 'yellow'
    validator: (value) => ['blue', 'green', 'red', 'yellow', 'gray'].includes(value)
  }
})

/**
 * Formatear el valor según el tipo especificado
 */
const valorFormateado = computed(() => {
  const valorNumerico = parseFloat(props.valor) || 0
  
  switch (props.tipo) {
    case 'moneda':
      return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS'
      }).format(valorNumerico)
    
    case 'porcentaje':
      return `${valorNumerico.toFixed(2)}%`
    
    case 'numero':
    default:
      return new Intl.NumberFormat('es-AR').format(valorNumerico)
  }
})

/**
 * Color del icono según la prop color
 */
const colorIcono = computed(() => {
  const colores = {
    blue: 'text-blue-500',
    green: 'text-green-500',
    red: 'text-red-500',
    yellow: 'text-yellow-500',
    gray: 'text-gray-500'
  }
  return colores[props.color] || colores.blue
})
</script>
