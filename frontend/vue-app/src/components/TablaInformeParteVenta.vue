<template>
  <div class="bg-white rounded-lg shadow-md overflow-hidden">
    <!-- Encabezado -->
    <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-blue-100">
      <h3 class="text-lg font-semibold text-gray-900 flex items-center">
        <i class="fas fa-chart-pie mr-2 text-blue-600"></i>
        Resumen de Parte de Venta
      </h3>
      <p class="text-sm text-gray-600 mt-1">Consolidado de ventas, cancelaciones y retirados</p>
    </div>

    <!-- Loading state -->
    <div v-if="cargando" class="p-8 text-center">
      <i class="fas fa-spinner fa-spin text-2xl text-gray-400 mb-2"></i>
      <p class="text-gray-600">Cargando datos...</p>
    </div>

    <!-- Error state -->
    <div v-else-if="error" class="p-8 text-center">
      <i class="fas fa-exclamation-triangle text-2xl text-red-400 mb-2"></i>
      <p class="text-red-600">{{ error }}</p>
    </div>

    <!-- Sin datos -->
    <div v-else-if="!kpis || Object.keys(kpis).length === 0" class="p-8 text-center">
      <i class="fas fa-inbox text-2xl text-gray-400 mb-2"></i>
      <p class="text-gray-600">No hay datos disponibles para mostrar</p>
    </div>

    <!-- Tabla con datos -->
    <div v-else class="p-6">
      <table class="min-w-full">
        <tbody class="divide-y divide-gray-200">
          <!-- Ventas Boletos -->
          <tr class="hover:bg-gray-50 transition-colors">
            <td class="py-4 px-6">
              <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg mr-4">
                  <i class="fas fa-ticket-alt text-green-600 text-xl"></i>
                </div>
                <span class="text-base font-medium text-gray-900">Ventas Boletos</span>
              </div>
            </td>
            <td class="py-4 px-6 text-right">
              <span class="text-xl font-bold text-green-600">
                {{ formatearMoneda(kpis.ventas_boletos || 0) }}
              </span>
            </td>
          </tr>

          <!-- Cancelados -->
          <tr class="hover:bg-gray-50 transition-colors">
            <td class="py-4 px-6">
              <div class="flex items-center">
                <div class="p-3 bg-orange-100 rounded-lg mr-4">
                  <i class="fas fa-ban text-orange-600 text-xl"></i>
                </div>
                <span class="text-base font-medium text-gray-900">Cancelados</span>
              </div>
            </td>
            <td class="py-4 px-6 text-right">
              <span class="text-xl font-bold text-orange-600">
                {{ formatearMoneda(kpis.cancelados || 0) }}
              </span>
            </td>
          </tr>

          <!-- Retirados -->
          <tr class="hover:bg-gray-50 transition-colors">
            <td class="py-4 px-6">
              <div class="flex items-center">
                <div class="p-3 bg-red-100 rounded-lg mr-4">
                  <i class="fas fa-horse text-red-600 text-xl"></i>
                </div>
                <span class="text-base font-medium text-gray-900">Retirados</span>
              </div>
            </td>
            <td class="py-4 px-6 text-right">
              <span class="text-xl font-bold text-red-600">
                {{ formatearMoneda(kpis.retirados || 0) }}
              </span>
            </td>
          </tr>

          <!-- Separador visual -->
          <tr>
            <td colspan="2" class="py-2">
              <div class="border-t-2 border-blue-300"></div>
            </td>
          </tr>

          <!-- Venta Neta Boletos (destacado) -->
          <tr class="bg-blue-50 hover:bg-blue-100 transition-colors">
            <td class="py-5 px-6">
              <div class="flex items-center">
                <div class="p-3 bg-blue-600 rounded-lg mr-4">
                  <i class="fas fa-chart-line text-white text-xl"></i>
                </div>
                <span class="text-lg font-bold text-gray-900">Venta Neta Boletos</span>
              </div>
            </td>
            <td class="py-5 px-6 text-right">
              <span :class="[
                'text-2xl font-bold',
                (kpis.venta_neta_boletos || 0) >= 0 ? 'text-blue-600' : 'text-red-600'
              ]">
                {{ formatearMoneda(kpis.venta_neta_boletos || 0) }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { defineProps } from 'vue'

/**
 * Componente para mostrar el Informe de Parte de Venta
 * Muestra un resumen consolidado de ventas, cancelaciones y retirados
 */
const props = defineProps({
  /**
   * Objeto con los KPIs del informe
   * @type {Object}
   * @property {number} ventas_boletos - Total de ventas de boletos
   * @property {number} cancelados - Total de tickets cancelados
   * @property {number} retirados - Total de devoluciones por caballos retirados
   * @property {number} venta_neta_boletos - Venta neta calculada
   */
  kpis: {
    type: Object,
    default: () => ({})
  },

  /**
   * Indica si los datos están cargando
   */
  cargando: {
    type: Boolean,
    default: false
  },

  /**
   * Mensaje de error si ocurre algún problema
   */
  error: {
    type: String,
    default: null
  }
})

/**
 * Formatea un valor numérico como moneda (pesos argentinos)
 * @param {number} valor - Valor a formatear
 * @returns {string} Valor formateado como moneda
 */
function formatearMoneda(valor) {
  return new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: 'ARS',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(valor || 0)
}
</script>

<style scoped>
/* Estilos adicionales si se necesitan en el futuro */
</style>
