# TURF Reporting Application - Documentación Técnica

## 🐛 Solución Error CORS y 404 en Desarrollo Local (Docker)

### **Problema Identificado**
Al ejecutar la app en Docker (`http://localhost:5173`), el login fallaba con:
- ❌ Error CORS en peticiones API
- ❌ Error 404 Not Found en `/api/auth/login`
- ❌ Network Error en frontend

### **Causa Raíz**
1. **Nginx configuración incorrecta**: `SCRIPT_FILENAME` apuntaba a `/var/www/html/api/Routes/api.php` pero el volumen Docker monta `./backend/api` directamente en `/var/www/html`
2. **CORS duplicados**: Nginx y PHP agregaban headers CORS causando conflictos
3. **Cookies incompatibles**: `SameSite=None` requiere HTTPS pero estábamos en HTTP local
4. **Variable de entorno**: Frontend apuntaba a producción en lugar de localhost

### **Solución Aplicada**

#### 1. **nginx/default.conf** - Ruta corregida
```nginx
location ~ ^/api/(.*)$ {
    include fastcgi_params;
    fastcgi_pass turf_php:9000;
    fastcgi_param SCRIPT_FILENAME /var/www/html/Routes/api.php;  # ← Corregido
    fastcgi_param QUERY_STRING route=$1&$args;
}
```

#### 2. **backend/api/Routes/api.php** - CORS y sesiones
```php
// CORS para desarrollo local
$allowedOrigins = ['http://localhost:5173', 'http://localhost:8080'];

// Cookies compatibles con HTTP local
session_set_cookie_params([
    'secure' => false,      // HTTP permitido
    'samesite' => 'Lax'     // Compatible con localhost
]);
```

#### 3. **frontend/vue-app/.env.development** - URL local
```
VITE_API_URL=http://localhost:8080/api
```

### **Resultado**
✅ Login funcional en `http://localhost:5173`  
✅ Sin errores CORS  
✅ Peticiones API exitosas  
✅ Sesiones persistentes

---

## 🔐 Análisis: Problema de Roles - Admin vs Agencia

### **Problema Reportado**
Usuario `carrerras2024` con password `admin123` debería tener rol **ADMIN** pero se le asigna rol **AGENCIA**.

### **Causas Probables Identificadas**

#### **1. Error en Base de Datos - `id_perfil` Incorrecto**
**Ubicación**: `Auth.php` línea 71
```php
$rol = ($usuario['id_perfil'] == 1) ? self::ROLE_ADMIN : self::ROLE_AGENCIA;
```

**Problema**: El usuario `carrerras2024` tiene `id_perfil = 2` en la base de datos.

**Verificación**:
```sql
SELECT id_usuario, login, nombre_usuario, id_perfil, id_agencia 
FROM tbl_usuarios 
WHERE login = 'carrerras2024';
```

**Resultado esperado**:
- Si `id_perfil = 1` → ROL ADMIN ✅
- Si `id_perfil = 2` → ROL AGENCIA ❌ (problema actual)

#### **2. Comparación Débil en PHP**
**Código actual**: `($usuario['id_perfil'] == 1)`  
**Problema**: Usa comparación débil `==` que puede causar conversiones de tipo inesperadas.

**Solución recomendada**:
```php
$rol = ($usuario['id_perfil'] === 1) ? self::ROLE_ADMIN : self::ROLE_AGENCIA;
```

#### **3. Posible Confusión de Credenciales**
**Verificar**: 
- ¿El usuario es `carrerras2024` o `carreras2024`? (con doble 'r')
- ¿La contraseña es exactamente `admin123`?

### **Pasos para Depurar**

#### **Paso 1: Verificar datos en Base de Datos**
```sql
-- Conectar a la base de datos agencias
SELECT id_usuario, login, nombre_usuario, id_perfil, id_agencia, contrasena
FROM tbl_usuarios 
WHERE login LIKE '%carrera%';
```

#### **Paso 2: Activar logs de depuración**
El código ya tiene logs en `Auth.php` líneas 50-58:
```php
error_log('DEBUG Login - id_perfil: ' . $usuario['id_perfil']);
error_log('DEBUG Login - Rol asignado: ' . $rol);
```

**Ver logs**:
```powershell
docker logs turf_php | Select-String "DEBUG Login"
```

#### **Paso 3: Verificar hash MD5 de contraseña**
```php
echo md5('admin123'); // Debería coincidir con el valor en BD
```

### **Solución Definitiva**

#### **Opción A: Corregir `id_perfil` en Base de Datos**
```sql
UPDATE tbl_usuarios 
SET id_perfil = 1 
WHERE login = 'carrerras2024';
```

#### **Opción B: Mejorar validación en código**
```php
// Auth.php línea 71 - Usar comparación estricta
$rol = ($usuario['id_perfil'] === 1 || $usuario['id_perfil'] === '1') 
    ? self::ROLE_ADMIN 
    : self::ROLE_AGENCIA;
```

### **Buenas Prácticas Recomendadas**

1. **Usar comparación estricta (`===`)** en validaciones críticas
2. **Agregar logs detallados** en procesos de autenticación
3. **Validar tipos de datos** antes de comparaciones
4. **Documentar estructura de perfiles**:
   ```
   id_perfil = 1 → ADMIN
   id_perfil = 2 → AGENCIA
   ```
5. **Crear constantes** para IDs de perfiles:
   ```php
   const PERFIL_ADMIN = 1;
   const PERFIL_AGENCIA = 2;
   ```

### **Verificación Final**
Después de aplicar la solución, verificar:
1. Login con `carrerras2024` / `admin123`
2. Revisar en DevTools → Application → Session Storage → `usuario.role` debe ser `"admin"`
3. Verificar que se muestran reportes de ADMIN y AppWeb

---
