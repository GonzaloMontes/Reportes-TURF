<template>
  <!-- Badge de estado reutilizable -->
  <span :class="['inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold', clase]">
    <slot>{{ texto }}</slot>
  </span>
</template>

<script setup>
// EstadoBadge.vue: Badge de color para estados (ganadora, perdedora, anulada, pendiente, etc.)
// Props en español y estilos tailwind.
import { computed } from 'vue'

const props = defineProps({
  estado: { type: [String, Number], default: '' },
  // Modo: 'badge' (pill) o 'texto' (solo color). Por defecto badge.
  modo: { type: String, default: 'badge' }
})

const texto = computed(() => String(props.estado ?? '').trim())

const clase = computed(() => {
  const e = texto.value.toLowerCase()
  // Mapas de coincidencia flexibles
  if (['ganadora','ganado','ganada','win','winner','ganador'].some(s => e.includes(s))) return base('bg-green-100 text-green-800')
  if (['perdedora','perdio','perdida','lose','lost'].some(s => e.includes(s))) return base('bg-red-100 text-red-800')
  if (['anulada','anulado','void','anulado'].some(s => e.includes(s))) return base('bg-gray-100 text-gray-800')
  if (['pendiente','pending','espera'].some(s => e.includes(s))) return base('bg-yellow-100 text-yellow-800')
  return base('bg-blue-100 text-blue-800')
})

function base(color) {
  return props.modo === 'texto' ? color.replace('bg-','').replace(/\btext-\w+-/,'text-') : color
}
</script>
