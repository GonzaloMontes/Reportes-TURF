import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// Configuración para desarrollo local con backend PHP
export default defineConfig({
  plugins: [vue()],
  server: {
    port: 5173,
    strictPort: true,
    host: 'localhost',
    proxy: {
      // Redirige llamadas API al backend PHP
      '/api': {
        target: 'http://localhost:8000', // Puerto donde corre tu PHP server
        changeOrigin: true,
        secure: false,
        rewrite: (path) => path // Mantener la ruta /api tal como está
      }
    }
  },
  build: {
    outDir: '../dist-vue', // Construir fuera del directorio actual
    emptyOutDir: true
  }
})
