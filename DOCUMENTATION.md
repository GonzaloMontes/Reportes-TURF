# TURF Reporting Application - Documentación Técnica

## 🏗️ Arquitectura del Sistema

La aplicación sigue una arquitectura cliente-servidor simple y robusta, utilizando tecnologías ligeras para maximizar el rendimiento y facilitar el mantenimiento.

### Diagrama de Arquitectura

```
┌──────────────────────────────────────────┐
│      Frontend (Vanilla JS, HTML, CSS)    │
│  • Dashboard con KPIs (Chart.js)         │
│  • Reportes interactivos y paginados     │
│  • Exportación de datos (CSV)            │
└────────────────────┬─────────────────────┘
                     │ HTTP/JSON (API Call)
┌────────────────────▼─────────────────────┐
│         Backend (PHP 8 Nativo)           │
│  • API RESTful simple                    │
│  • Lógica de negocio y autenticación     │
│  • Acceso a datos                        │
└────────────────────┬─────────────────────┘
                     │
┌────────────────────▼─────────────────────┐
│            Database (SQLite)             │
│  • Base de datos autocontenida en archivo│
│  • Almacenamiento de toda la aplicación  │
└──────────────────────────────────────────┘
```

## 🚀 Performance y Optimización

La performance se centra en la eficiencia del backend y la ligereza del frontend.

- **Paginación Server-Side**: El backend solo procesa y envía los datos de la página actual, minimizando la transferencia de datos y la carga en el cliente.
- **Queries Optimizadas**: Se utilizan consultas SQL directas y eficientes, con los índices adecuados en la base de datos SQLite para acelerar las búsquedas y filtros.
- **Frontend Ligero**: Al no usar frameworks pesados, el tiempo de carga y renderizado en el navegador es mínimo. La manipulación del DOM es directa y eficiente.
- **Cache (Opcional)**: Se implementa un sistema de caché simple basado en archivos (`api/core/CacheManager.php`) para consultas frecuentes, reduciendo la carga a la base de datos.

## 🔒 Seguridad Implementada

- **Autenticación Basada en Sesiones**: Se utiliza el manejo de sesiones nativo de PHP para una autenticación segura.
- **Protección CSRF**: Se implementan tokens CSRF para proteger contra ataques de falsificación de solicitudes entre sitios.
- **SQL Injection**: Se utilizan sentencias preparadas (prepared statements) en todas las consultas a la base de datos para prevenir inyección de SQL.
- **Control de Acceso por Rol (RBAC)**: El backend valida el rol del usuario en cada petición a rutas protegidas, asegurando que solo accedan a los datos permitidos.

## 🛠️ Instalación y Entorno Local

### Requisitos
- **PHP**: 8.0 o superior
- **Extensiones PHP**: `pdo`, `pdo_sqlite`

### Pasos para el Entorno Local

1.  **Clonar/Descargar** el repositorio.
2.  **Crear el archivo `.env`** a partir de `.env.example`.
3.  **Inicializar la base de datos**: Ejecutar el script para crear y poblar el archivo `turf_reports.sqlite`.
    ```bash
    php scripts/init_sqlite.php
    ```
4.  **Iniciar el servidor**: Utilizar el servidor web incorporado de PHP con el `router.php` para manejar las rutas de la API.
    ```bash
    php -S localhost:8000 router.php
    ```
5.  **Acceder a la aplicación** en `http://localhost:8000`.

## 📋 Mantenimiento

- **Backup**: Simplemente se debe copiar el archivo `database/turf_reports.sqlite` para tener un respaldo completo de la base de datos.
- **Logs**: Los errores de PHP se registran en el archivo `error_log` en la raíz o en el directorio `api/routes/`, dependiendo de dónde ocurra el error.

---

**Versión**: 1.1.0 (Arquitectura simplificada)
**Última actualización**: 2025-08-26
