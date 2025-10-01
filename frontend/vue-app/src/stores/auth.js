import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { apiClient } from '../services/api'

/**
 * Store de autenticación - Maneja el estado del usuario y sesión
 */
export const useAuthStore = defineStore('auth', () => {
  // Estado reactivo
  const usuario = ref(null)
  const tokenCsrf = ref(null)
  const estaAutenticado = ref(false)
  const cargando = ref(false)
  const error = ref(null)

  /**
   * Verifica si existe una sesión activa al cargar la aplicación
   */
  async function verificarAutenticacion() {
    try {
      cargando.value = true
      error.value = null
      
      const respuesta = await apiClient.get('/auth/verify')
      
      if (respuesta.authenticated === true) {
        usuario.value = respuesta.user
        tokenCsrf.value = respuesta.csrf_token
        estaAutenticado.value = true
      } else {
        limpiarSesion()
      }
    } catch (err) {
      console.error('Error verificando autenticación:', err)
      limpiarSesion()
    } finally {
      cargando.value = false
    }
  }

  /**
   * Inicia sesión con credenciales de usuario
   */
  async function iniciarSesion(credenciales) {
    try {
      cargando.value = true
      error.value = null

      const respuesta = await apiClient.post('/auth/login', {
        username: credenciales.usuario,
        password: credenciales.contrasena,
        remember: credenciales.recordar || false
      })

      usuario.value = respuesta.user
      tokenCsrf.value = respuesta.csrf_token
      estaAutenticado.value = true

      return { exito: true }
    } catch (err) {
      error.value = err.message || 'Error al iniciar sesión'
      return { exito: false, mensaje: error.value }
    } finally {
      cargando.value = false
    }
  }

  /**
   * Cierra la sesión del usuario
   */
  async function cerrarSesion() {
    try {
      await apiClient.post('/auth/logout')
    } catch (err) {
      console.error('Error cerrando sesión:', err)
    } finally {
      limpiarSesion()
    }
  }

  /**
   * Limpia el estado de la sesión
   */
  function limpiarSesion() {
    usuario.value = null
    tokenCsrf.value = null
    estaAutenticado.value = false
    error.value = null
  }

  /**
   * Getters computados
   */
  const esAdmin = computed(() => usuario.value?.role === 'admin')
  const esAgencia = computed(() => usuario.value?.role === 'agencia')
  const nombreUsuario = computed(() => usuario.value?.username || '')
  const idAgencia = computed(() => usuario.value?.agencia_id || null)

  return {
    // Estado
    usuario,
    tokenCsrf,
    estaAutenticado,
    cargando,
    error,
    
    // Acciones
    verificarAutenticacion,
    iniciarSesion,
    cerrarSesion,
    limpiarSesion,
    
    // Getters
    esAdmin,
    esAgencia,
    nombreUsuario,
    idAgencia
  }
})
