import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'

// Crear instancia de Pinia para manejo de estado
const pinia = createPinia()

// Crear y montar la aplicación Vue
const app = createApp(App)
app.use(pinia)
app.mount('#app')
