# TURF Reporting Application

Sistema de reportería para TURF con arquitectura escalable, diseño BI y performance optimizada.

## Tecnologías del Proyecto

Este proyecto utiliza una selección de tecnologías enfocadas en la simplicidad, el rendimiento y la facilidad de mantenimiento, evitando dependencias complejas.

### Backend

*   **Tecnología:** **PHP 8 (Nativo)**
    *   **Uso:** Procesa toda la lógica de negocio, gestiona la autenticación de usuarios, interactúa con la base de datos y expone una API REST para que el frontend consuma los datos.
    *   **¿Por qué lo usamos?:** Se eligió PHP nativo (sin frameworks como Laravel o Symfony) para mantener la aplicación ligera, con un despliegue muy sencillo y un control total sobre el código. Esto reduce la sobrecarga y facilita el mantenimiento a largo plazo.
    *   **Rendimiento:** Al no tener la carga de un framework completo, las respuestas de la API son extremadamente rápidas, limitadas únicamente por la velocidad de las consultas a la base de datos.

### Frontend

*   **Tecnología:** **JavaScript (Vanilla ES6+)**
    *   **Uso:** Gestiona toda la interactividad en el navegador, incluyendo la carga de datos desde la API, la renderización de reportes, filtros, paginación y gráficos.
    *   **¿Por qué lo usamos?:** Utilizar JavaScript "puro" (sin frameworks como React o Vue) elimina la necesidad de compilación y reduce el peso de los archivos. El código es más transparente y no depende de librerías externas.
    *   **Rendimiento:** La carga inicial es casi instantánea y la manipulación del DOM se realiza de forma directa y eficiente, resultando en una experiencia de usuario fluida.

### Base de Datos

*   **Tecnología:** **SQLite**
    *   **Uso:** Almacena toda la información de la aplicación.
    *   **¿Por qué lo usamos?:** SQLite es una base de datos basada en un único archivo, lo que la hace increíblemente portátil, fácil de respaldar y perfecta para entornos donde no se desea gestionar un servicio de base de datos completo (como MySQL).
    *   **Rendimiento:** Para el volumen de datos y la concurrencia de esta aplicación, el rendimiento de SQLite es excelente.

### Visualización de Datos

*   **Tecnología:** **Chart.js**
    *   **Uso:** Renderiza los gráficos en el dashboard.
    *   **¿Por qué lo usamos?:** Es una librería ligera y fácil de integrar con JavaScript vanilla. Ofrece gráficos atractivos con muy poco esfuerzo.
    *   **Rendimiento:** Es muy eficiente y no impacta negativamente la carga de la página.

## Estructura del Proyecto

```
/api/              # Backend (PHP nativo)
/frontend/         # Frontend (JS, HTML, CSS)
/database/         # Scripts SQL y base de datos SQLite
/config/           # Configuraciones de la aplicación
.env               # Variables de entorno
router.php         # Enrutador para el servidor de desarrollo de PHP
```

## Instalación Local

1.  Asegúrate de tener **PHP 8** o superior instalado.
2.  Clona o descarga el repositorio.
3.  Copia el archivo `.env.example` a `.env` y ajusta las variables si es necesario.
4.  Para inicializar la base de datos SQLite por primera vez, ejecuta: `php scripts/init_sqlite.php`
5.  Inicia el servidor de desarrollo desde la raíz del proyecto:
    ```bash
    php -S localhost:8000 router.php
    ```
6.  Abre tu navegador y ve a `http://localhost:8000`.

** Reactivar login

Cómo reactivar el login en el futuro
Abre el archivo 
api/Core/Auth.php
.
Busca el método check().
Elimina la línea return true;.
Descomenta el bloque de código original que dejé guardado.

Cómo reactivar el login en el futuro
Abre el archivo 
api/Core/Auth.php
.
Busca el método 
isAuthenticated()
.
Elimina la línea return true; y las líneas de comentario que la rodean.
Descomenta el bloque de código original que empieza con if (isset($_SESSION['user_id'])).

Cómo reactivar el login en el futuro
Abre el archivo 
api/Core/Auth.php
.
Busca el método 
isAuthenticated()
.
Elimina las líneas de desactivación temporal.
Descomenta el bloque de código original que he dejado guardado.