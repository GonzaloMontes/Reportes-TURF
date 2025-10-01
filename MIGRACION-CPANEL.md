# Guía de Migración a cPanel - TURF Reporting App

## 📋 Preparación Local

### 1. Ejecutar Build de Producción
```bash
# Ejecutar el script de build
build-production.bat
```

### 2. Archivos que se suben a cPanel
Subir TODO el contenido de la carpeta `dist/` generada:

```
dist/
├── index.html              # Frontend Vue compilado
├── assets/                 # CSS, JS, imágenes
├── api/                    # Backend PHP completo
├── .htaccess              # Configuración Apache
└── .env                   # Variables de entorno
```

## 🗄️ Configuración de Base de Datos

### 1. En cPanel - MySQL Databases
- Crear nueva base de datos
- Crear usuario con todos los privilegios
- Importar estructura desde `api/database/schema.sql`

### 2. Actualizar .env
```env
DB_HOST=localhost
DB_NAME=tu_cpanel_db_name
DB_USER=tu_cpanel_db_user  
DB_PASS=tu_cpanel_db_pass
APP_URL=https://tu-dominio.com
```

## 📁 Estructura en cPanel

### Directorio público_html/
```
public_html/
├── index.html              # Página principal Vue
├── assets/                 # Archivos estáticos
│   ├── css/
│   ├── js/
│   └── images/
├── api/                    # API PHP
│   ├── Controllers/
│   ├── Services/
│   ├── Routes/
│   ├── Config/
│   └── Utils/
├── .htaccess              # Configuración Apache
└── .env                   # Variables de entorno
```

## 🔧 Configuraciones Importantes

### 1. Permisos de Archivos
- Archivos: 644
- Directorios: 755
- .htaccess: 644
- .env: 600 (más restrictivo)

### 2. PHP Version
- Usar PHP 8.0 o superior
- Habilitar extensiones: PDO, PDO_MySQL, JSON, CURL

### 3. Variables de cPanel
En cPanel > PHP Selector > Options:
```
memory_limit = 256M
max_execution_time = 60
upload_max_filesize = 10M
post_max_size = 10M
```

## 🚀 Proceso de Despliegue

### Paso 1: Preparar archivos localmente
1. Ejecutar `build-production.bat`
2. Verificar carpeta `dist/`

### Paso 2: Configurar base de datos
1. Crear DB en cPanel
2. Importar estructura
3. Actualizar `.env`

### Paso 3: Subir archivos
1. Comprimir carpeta `dist/` en ZIP
2. Subir via File Manager de cPanel
3. Extraer en `public_html/`

### Paso 4: Verificar funcionamiento
1. Acceder a tu dominio
2. Probar login
3. Verificar reportes
4. Revisar logs de errores

## 🔍 Troubleshooting

### Error 500 - Internal Server Error
- Verificar permisos de .htaccess
- Revisar logs de error de cPanel
- Verificar configuración PHP

### API no responde
- Verificar rutas en .htaccess
- Comprobar conexión a base de datos
- Revisar archivo .env

### Frontend no carga
- Verificar que index.html esté en raíz
- Comprobar rutas de assets
- Revisar Content Security Policy

### Base de datos no conecta
- Verificar credenciales en .env
- Comprobar que el usuario tenga permisos
- Verificar nombre de host (usualmente localhost)

## 📊 Monitoreo Post-Despliegue

### Logs importantes
- cPanel Error Logs
- `api/logs/app.log` (si existe)
- Browser Developer Console

### Verificaciones
- [ ] Login funciona
- [ ] Reportes cargan correctamente
- [ ] KPIs muestran datos
- [ ] Exportación funciona
- [ ] Responsive design OK

## 🔒 Seguridad en Producción

### Headers de seguridad (ya configurados en .htaccess)
- HTTPS forzado
- X-Frame-Options
- Content Security Policy
- X-XSS-Protection

### Archivos protegidos
- .env (no accesible via web)
- Logs y archivos sensibles
- Directorios de sistema bloqueados

## 📞 Soporte

Si encuentras problemas:
1. Revisar logs de error de cPanel
2. Verificar configuración de .htaccess
3. Comprobar permisos de archivos
4. Validar conexión a base de datos

---
**Nota**: Esta app está optimizada para cPanel con Apache y PHP 8+
