<?php

// --- LOGGING DE ERRORES ---
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../error_log');
// Opcional en desarrollo: mostrar errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- FIN LOGGING ---



// Cargar variables de entorno desde el archivo .env
$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $value = trim($value);
        if (substr($value, 0, 1) == '"' && substr($value, -1) == '"') {
            $value = substr($value, 1, -1);
        }
        $_ENV[trim($name)] = $value;
    }
}

// Configurar cookies de sesión compatibles con contexto cross-site en desarrollo
if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'None'
    ]);
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// require_once __DIR__ . '/../../vendor/autoload.php'; // TEMPORALMENTE DESHABILITADO
require_once __DIR__ . '/../Core/DatabaseManager.php';
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Core/CacheManager.php';
require_once __DIR__ . '/../Services/ReportService.php';
require_once __DIR__ . '/../Services/ExportService.php';
require_once __DIR__ . '/../Services/AppWebReportService.php';
require_once __DIR__ . '/../Controllers/AuthController.php';
require_once __DIR__ . '/../Controllers/ReportController.php';
require_once __DIR__ . '/../Controllers/AppWebReportController.php';

use TurfReports\Controllers\AuthController;
use TurfReports\Controllers\ReportController;

// Headers CORS y seguridad
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// CORS para desarrollo (ajustar en producción)
$origin = $_SERVER['HTTP_ORIGIN'] ?? null;
if (!$origin && isset($_SERVER['HTTP_REFERER'])) {
    $ref = parse_url($_SERVER['HTTP_REFERER']);
    if ($ref && isset($ref['scheme'], $ref['host'])) {
        $origin = $ref['scheme'] . '://' . $ref['host'] . (isset($ref['port']) ? ':' . $ref['port'] : '');
    }
}
if ($origin) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
    header('Vary: Origin');
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token, X-Requested-With');
    header('Access-Control-Allow-Credentials: true');
    exit(0);
}

// Rate limiting básico
$ip = $_SERVER['REMOTE_ADDR'];
$now = time();
$window = 60; // 1 minuto
$limit = 100; // 100 requests por minuto

if (!isset($_SESSION['rate_limit'])) {
    $_SESSION['rate_limit'] = [];
}

$_SESSION['rate_limit'] = array_filter($_SESSION['rate_limit'], function($timestamp) use ($now, $window) {
    return ($now - $timestamp) < $window;
});

if (count($_SESSION['rate_limit']) >= $limit) {
    http_response_code(429);
    echo json_encode(['error' => 'Rate limit exceeded']);
    exit;
}

$_SESSION['rate_limit'][] = $now;

// Router simple que utiliza el parámetro 'route' de la URL
$method = $_SERVER['REQUEST_METHOD'];
$route = $_GET['route'] ?? '';

$route = trim($route, '/');


try {
    // Rutas de autenticación
    if ($route === 'auth/login' && $method === 'POST') {
        (new AuthController())->login();
    } elseif ($route === 'auth/logout' && $method === 'POST') {
        (new AuthController())->logout();
    } elseif ($route === 'auth/verify' && $method === 'GET') {
        (new AuthController())->verify();
    } elseif ($route === 'auth/status' && $method === 'GET') {
        (new AuthController())->status();
    } elseif ($route === 'auth/csrf-token' && $method === 'GET') {
        (new AuthController())->getCsrfToken();
    } elseif ($route === 'reports/agencies' && $method === 'GET') {
        (new \TurfReports\Core\Auth())->requireAuth();
        (new ReportController())->getAgencies();
    } elseif ($route === 'reports/hipodromos' && $method === 'GET') {
        (new \TurfReports\Core\Auth())->requireAuth();
        (new ReportController())->getHipodromos();
    } elseif ($route === 'reports/numeros-carreras' && $method === 'GET') {
        (new \TurfReports\Core\Auth())->requireAuth();
        (new ReportController())->getNumerosCarreras();
    } elseif ($route === 'reports/lista-tickets' && $method === 'GET') {
        (new \TurfReports\Core\Auth())->requireAuth();
        (new ReportController())->listaTickets();
    } elseif ($route === 'reports/informe-por-agencia' && $method === 'GET') {
        (new ReportController())->informePorAgencia();
    } elseif ($route === 'reports/caballos-retirados' && $method === 'GET') {
        (new \TurfReports\Core\Auth())->requireAuth();
        (new ReportController())->caballosRetirados();
    } elseif ($route === 'reports/carreras' && $method === 'GET') {
        (new \TurfReports\Core\Auth())->requireAuth();
        (new ReportController())->carreras();
    } elseif ($route === 'reports/resultados-carrera' && $method === 'GET') {
        (new \TurfReports\Core\Auth())->requireAuth();
        (new ReportController())->resultadosCarrera();
    } elseif ($route === 'reports/tickets-anulados' && $method === 'GET') {
        (new \TurfReports\Core\Auth())->requireAuth();
        (new ReportController())->ticketsAnulados();
    // --- RUTAS PARA REPORTES DE AGENCIA ---
    } elseif ($route === 'reports/agencia/ventas-diarias' && $method === 'GET') {
        (new ReportController())->ventasDiariasAgencia();
    } elseif ($route === 'reports/agencia/tickets-devoluciones' && $method === 'GET') {
        (new ReportController())->ticketsDevolucionesAgencia();
    } elseif ($route === 'reports/agencia/sports-carreras' && $method === 'GET') {
        (new ReportController())->sportsCarrerasAgencia();
    } elseif ($route === 'reports/agencia/tickets-anulados' && $method === 'GET') {
        (new ReportController())->ticketsAnuladosAgencia();
    } elseif ($route === 'reports/export' && $method === 'POST') {
        (new ReportController())->export();
    } elseif (preg_match('/^download\/(.+)$/', $route, $m) && $method === 'GET') {
        (new ReportController())->download($m[1]);
    
    // --- RUTAS PARA REPORTES DE APPWEB ---
    } elseif ($route === 'reports/appweb/por-usuario' && $method === 'GET') {
        (new \TurfReports\Controllers\AppWebReportController())->porUsuario();
    } elseif ($route === 'reports/appweb/economico' && $method === 'GET') {
        (new \TurfReports\Controllers\AppWebReportController())->economico();
    } elseif ($route === 'reports/appweb/apuestas' && $method === 'GET') {
        (new \TurfReports\Controllers\AppWebReportController())->apuestas();
    } elseif ($route === 'reports/appweb/dinero-remanente' && $method === 'GET') {
        (new \TurfReports\Controllers\AppWebReportController())->dineroRemanente();
    } elseif ($route === 'reports/appweb/rendimiento-apuesta-carrera' && $method === 'GET') {
        (new \TurfReports\Controllers\AppWebReportController())->rendimientoApuestaCarrera();
    } elseif (preg_match('/^reports\/appweb\/detalle-usuario\/(\d+)$/', $route, $matches) && $method === 'GET') {
        (new \TurfReports\Controllers\AppWebReportController())->detalleUsuario($matches[1]);
    } elseif ($route === 'hipodromos' && $method === 'GET') {
        (new \TurfReports\Core\Auth())->requireAuth();
        // Endpoint para obtener lista de hipódromos
        try {
            $db = new \TurfReports\Core\DatabaseManager();
            $query = "SELECT id_hipodromo, nombre_hipodromo FROM tbl_hipodromos ORDER BY nombre_hipodromo ASC";
            $hipodromos = $db->query($query, 'agencias')->fetchAll(PDO::FETCH_ASSOC);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $hipodromos
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    } elseif ($route === 'numeros-carreras' && $method === 'GET') {
        (new \TurfReports\Core\Auth())->requireAuth();
        // Endpoint para obtener números de carrera disponibles
        try {
            $db = new \TurfReports\Core\DatabaseManager();
            $query = "SELECT DISTINCT numero_carrera FROM tbl_carreras ORDER BY numero_carrera ASC";
            $numeros = $db->query($query, 'agencias')->fetchAll(PDO::FETCH_ASSOC);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $numeros
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    } else {
        // Si ninguna ruta coincide, o el método no es el adecuado
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint no encontrado o método no permitido.']);
    }
} catch (\Exception $e) {
    error_log('API Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Error interno del servidor',
        'message' => $_ENV['APP_DEBUG'] ? $e->getMessage() : 'Contacte al administrador'
    ]);
}
