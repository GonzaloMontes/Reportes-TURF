# Migración Vue.js - Sistema de Reportes TURF

## Instalación y Configuración

```bash
cd frontend/vue-app
npm install
npm run dev
```

La aplicación Vue se ejecutará en `http://localhost:3000` mientras tu backend PHP sigue en su puerto original.

## Estructura del Proyecto

```
vue-app/
├── src/
│   ├── components/          # Componentes Vue reutilizables
│   │   ├── FormularioLogin.vue
│   │   ├── LayoutPrincipal.vue
│   │   ├── KPICard.vue
│   │   └── TablaReportes.vue
│   ├── stores/             # Manejo de estado con Pinia
│   │   ├── auth.js         # Autenticación y usuario
│   │   └── reportes.js     # Estado de reportes
│   ├── services/           # Servicios de API
│   │   └── api.js          # Cliente HTTP configurado
│   ├── composables/        # Lógica reutilizable
│   │   └── useReportes.js  # Hook para cargar reportes
│   └── App.vue             # Componente raíz
├── package.json
├── vite.config.js
└── index.html
```

## Fases de Migración

### ✅ FASE 1: Base y Login (Completada)
- [x] Configuración Vue + Vite
- [x] Store de autenticación (Pinia)
- [x] Componente de login
- [x] Servicio API

### 🔄 FASE 2: Componentes Core (En progreso)
- [ ] Layout principal con menú
- [ ] Componentes KPI reutilizables
- [ ] Sistema de filtros

### ⏳ FASE 3: Reportes
- [ ] Migrar cada reporte individualmente
- [ ] Integración Chart.js
- [ ] Sistema de exportación

## Comandos Útiles

```bash
# Desarrollo
npm run dev

# Construir para producción
npm run build

# Vista previa de build
npm run preview
```

## Notas Importantes

- **Backend PHP**: No requiere cambios, mantiene todos los endpoints
- **CSS**: Se reutilizan los estilos existentes
- **API**: Misma estructura de respuestas JSON
- **Desarrollo paralelo**: La app original sigue funcionando
