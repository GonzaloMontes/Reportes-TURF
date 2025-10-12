// composables/useFormato.js
// Utilidades de formateo centralizadas para toda la app.
// Mantener funciones puras y con valores por defecto en español (AR).

/**
 * Formatea un valor numérico como moneda.
 * - Maneja null/undefined/NaN devolviendo "$ 0,00".
 * - Locale y currency parametrizables (por defecto ARS).
 */
export function formatearMoneda(valor, currency = 'ARS', locale = 'es-AR') {
  const numero = Number(valor)
  if (!isFinite(numero)) return new Intl.NumberFormat(locale, { style: 'currency', currency }).format(0)
  return new Intl.NumberFormat(locale, {
    style: 'currency',
    currency,
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(numero)
}

/**
 * Formatea un valor numérico general.
 * - Devuelve "0" si el valor no es válido.
 */
export function formatearNumero(valor, locale = 'es-AR', options = {}) {
  const numero = Number(valor)
  if (!isFinite(numero)) return new Intl.NumberFormat(locale, options).format(0)
  return new Intl.NumberFormat(locale, options).format(numero)
}

/**
 * Formatea fechas a formato local.
 * - Acepta strings YYYY-MM-DD, DD/MM/YYYY o Date.
 * - Si no puede parsear, devuelve el valor original.
 */
export function formatearFecha(input, locale = 'es-AR') {
  if (!input) return ''

  // Caso Date directo
  if (input instanceof Date && !isNaN(input.getTime())) {
    return input.toLocaleDateString(locale)
  }

  // Intentar YYYY-MM-DD
  if (typeof input === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(input)) {
    const d = new Date(input + 'T00:00:00')
    if (!isNaN(d.getTime())) return d.toLocaleDateString(locale)
  }

  // Intentar DD/MM/YYYY
  if (typeof input === 'string' && /^\d{2}\/\d{2}\/\d{4}$/.test(input)) {
    const [dd, mm, yyyy] = input.split('/').map(Number)
    const d = new Date(yyyy, mm - 1, dd)
    if (!isNaN(d.getTime())) return d.toLocaleDateString(locale)
  }

  // Intento genérico
  try {
    const d = new Date(input)
    if (!isNaN(d.getTime())) return d.toLocaleDateString(locale)
  } catch (_) {}

  return String(input)
}
