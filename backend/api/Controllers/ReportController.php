<?php

namespace TurfReports\Controllers;

use TurfReports\Core\Auth;
use TurfReports\Services\ReportService;
use TurfReports\Services\ExportService;

/**
 * Controlador para gestionar y servir los reportes del sistema.
 * Se encarga de manejar las solicitudes HTTP, aplicar filtros, verificar permisos y entregar los datos.
 */
class ReportController
{
    private $auth;
    private $reportService;
    private $exportService;

    public function __construct()
    {
        $this->auth = new Auth();
        $this->reportService = new ReportService();
        $this->exportService = new ExportService();
    }

    /**
     * Obtiene el ID de la agencia si el usuario actual es de tipo 'agencia'.
     * @return int|null ID de la agencia o null si no aplica.
     */
    private function obtenerAgenciaId()
    {
        // Corregido: Devolvemos el ID de la agencia para filtrar todos los tickets de la agencia
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'agencia') {
            return $_SESSION['agencia_id'] ?? null;
        }
        return null;
    }

    /**
     * Endpoint para el nuevo reporte de listado de tickets.
     * Utiliza la consulta validada de `direct_sql_test.php`.
     */
    public function getAgencies()
    {
        $this->auth->requireAuth();
        $data = $this->reportService->getAgencies();
        $this->respuestaJson($data);
    }

    /**
     * Obtiene la lista de hipódromos disponibles para filtros.
     */
    public function getHipodromos()
    {
        $this->auth->requireAuth();
        $data = $this->reportService->getHipodromos();
        $this->respuestaJson($data);
    }

    /**
     * Obtiene la lista de números de carrera disponibles para filtros.
     */
    public function getNumerosCarreras()
    {
        $this->auth->requireAuth();
        $data = $this->reportService->getNumerosCarreras();
        $this->respuestaJson($data);
    }

    /**
     * Endpoint para el nuevo reporte de listado de tickets.
     * Utiliza la consulta validada de `direct_sql_test.php`.
     */
    public function listaTickets()
    {
        $this->auth->requireAuth();
        $filtros = $this->obtenerFiltros();
        $usuarioId = $this->obtenerAgenciaId();

        // La lógica de roles y filtros se puede expandir aquí más adelante.

        $data = $this->reportService->getListaTickets($filtros, $usuarioId);
        $this->registrarAccesoReporte('lista_tickets', $filtros);
        $this->respuestaJson($data);
    }

    /**
     * Endpoint para exportar reportes a CSV o PDF.
     */
    public function topAgencias()
    {
        $this->auth->requirePermission('view_all_reports');
        $filtros = $this->obtenerFiltros();
        $data = $this->reportService->getTopAgencias($filtros);
        $this->respuestaJson($data);
    }

    /**
     * Endpoint para el reporte de Informe por Agencia.
     * Devuelve un resumen financiero y de actividad por cada agencia.
     */
    public function informePorAgencia()
    {
        $this->auth->requirePermission('view_agency_reports');
        $filtros = $this->obtenerFiltros();
        $tipoReporte = $_GET['report_type'] ?? 'informe-por-agencia';

        switch ($tipoReporte) {
            case 'informe-por-agencia':
                $data = $this->reportService->getInformePorAgencia($filtros);
                break;
            case 'caballos-retirados':
                $data = $this->reportService->getCaballosRetirados($filtros);
                break;
            case 'lista-tickets':
                $usuarioId = $this->obtenerAgenciaId();
                $data = $this->reportService->getListaTickets($filtros, $usuarioId);
                break;
            default:
                http_response_code(404);
                $this->respuestaJson(['error' => 'Reporte no encontrado']);
                return;
        }

        $this->registrarAccesoReporte($tipoReporte, $filtros);
        $this->respuestaJson($data);
    }

    /**
     * Endpoint para el reporte de caballos retirados.
     * Muestra todos los caballos retirados con información de devoluciones.
     */
    public function caballosRetirados()
    {
        try {
            $filtros = $this->obtenerFiltros();
            $datos = $this->reportService->getCaballosRetirados($filtros);
            $this->respuestaJson($datos);
        } catch (Exception $e) {
            $this->manejarError($e, 'Error al obtener el reporte de caballos retirados');
        }
    }

    /**
     * Endpoint para el reporte de carreras.
     * Muestra lista de carreras con filtros y paginación.
     */
    public function carreras()
    {
        try {
            $filtros = $this->obtenerFiltros();
            $datos = $this->reportService->getCarreras($filtros);
            $this->respuestaJson($datos);
        } catch (Exception $e) {
            $this->manejarError($e, 'Error al obtener el reporte de carreras');
        }
    }

    /**
     * Endpoint para obtener los resultados (sports) de una carrera específica.
     * Se usa para la funcionalidad desplegable de resultados.
     */
    public function resultadosCarrera()
    {
        try {
            $idCarrera = $_GET['id_carrera'] ?? null;
            
            if (empty($idCarrera)) {
                throw new Exception('ID de carrera requerido');
            }

            $datos = $this->reportService->getResultadosCarrera($idCarrera);
            $this->respuestaJson($datos);
        } catch (Exception $e) {
            $this->manejarError($e, 'Error al obtener los resultados de la carrera');
        }
    }

    /**
     * Endpoint para el reporte de tickets anulados.
     * Muestra tickets anulados con información del usuario, agencia y total apostado.
     */
    public function ticketsAnulados()
    {
        try {
            $filtros = $this->obtenerFiltros();
            $datos = $this->reportService->getTicketsAnulados($filtros);
            $this->respuestaJson($datos);
        } catch (Exception $e) {
            $this->manejarError($e, 'Error al obtener el reporte de tickets anulados');
        }
    }

    /**
     * Endpoint para los KPIs del dashboard.
     */
    public function getKpis()
    {
        $this->auth->requireAuth();
        $filtros = $this->obtenerFiltros();
        $usuarioId = $this->obtenerAgenciaId();
        $data = $this->reportService->getKpisData($filtros, $usuarioId);
        $this->registrarAccesoReporte('kpis', $filtros);
        $this->respuestaJson($data);
    }

    /**
     * Endpoint para los datos de ventas y tickets para gráficos.
     */
    public function getVentasTickets()
    {
        $this->auth->requireAuth();
        $filtros = $this->obtenerFiltros();
        $usuarioId = $this->obtenerAgenciaId();
        $data = $this->reportService->getVentasTicketsData($filtros, $usuarioId);
        $this->registrarAccesoReporte('ventas_tickets', $filtros);
        $this->respuestaJson($data);
    }

    /**
     * Endpoint para solicitar la exportación de un reporte.
     */
    public function export()
    {
        $this->auth->requireAuth();
        
        if (!$this->validarCsrf()) {
            http_response_code(403);
            $this->respuestaJson(['error' => 'Token CSRF inválido']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $tipoReporte = $input['report_type'] ?? '';
        $formato = $input['format'] ?? 'csv';
        $filtros = $input['filters'] ?? [];
        $usuarioId = $this->obtenerAgenciaId();

        // Validación de permisos según rol - Agencia o Admin
        $esAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
        
        if ($esAdmin) {
            // Admin necesita permiso para exportar reportes generales
            if (!$this->auth->hasPermission('export_reports')) {
                http_response_code(403);
                $this->respuestaJson(['error' => 'Admin sin permisos de exportación']);
                return;
            }
            
            // Reportes permitidos para rol admin
            $reportesPermitidosAdmin = ['informe_agencias', 'ventas_tickets', 'caballos_retirados', 'carreras', 'tickets_anulados'];
            if (!in_array($tipoReporte, $reportesPermitidosAdmin)) {
                http_response_code(403);
                $this->respuestaJson(['error' => 'Reporte no permitido para exportación por admin']);
                return;
            }
            
            // Log de exportación por admin
            error_log("DEBUG - export: Admin exportando reporte {$tipoReporte} en formato {$formato}");
            
        } elseif ($usuarioId) { // Es rol Agencia
            // Agencia necesita permiso para exportar sus propios reportes
            if (!$this->auth->hasPermission('export_own_reports')) {
                http_response_code(403);
                $this->respuestaJson(['error' => 'Agencia sin permisos de exportación']);
                return;
            }
            
            // Reportes permitidos para rol agencia
            $reportesPermitidos = ['ventas_diarias', 'tickets_anulados', 'sports_carreras', 'tickets_devoluciones'];
            if (!in_array($tipoReporte, $reportesPermitidos)) {
                http_response_code(403);
                $this->respuestaJson(['error' => 'Reporte no permitido para exportación por agencia']);
                return;
            }
            
            // Log de exportación por agencia
            error_log("DEBUG - export: Agencia ID {$usuarioId} exportando reporte {$tipoReporte} en formato {$formato}");
        }
        
        try {
            $nombreArchivo = $this->exportService->exportReport($tipoReporte, $formato, $filtros, $usuarioId);
            $this->registrarExportacionReporte($tipoReporte, $formato, $filtros);
            $this->respuestaJson([
                'success' => true,
                'filename' => $nombreArchivo,
                'download_url' => '/api/download/' . basename($nombreArchivo)
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            $this->respuestaJson(['error' => 'Error generando exportación: ' . $e->getMessage()]);
        }
    }

    /**
     * Endpoint para descargar un archivo de reporte ya generado.
     * @param string $nombreArchivo Nombre del archivo a descargar.
     */
    public function download($nombreArchivo)
    {
        $this->auth->requireAuth();
        
        // Añadir logs detallados
        error_log("DEBUG - download: nombreArchivo recibido={$nombreArchivo}");
        
        // Verificar si el directorio storage/exports existe
        $storageDir = __DIR__ . '/../../storage/exports/';
        if (!is_dir($storageDir)) {
            error_log("DEBUG - download: ¡DIRECTORIO NO EXISTE! {$storageDir}");
            // Crear directorio si no existe
            mkdir($storageDir, 0755, true);
            error_log("DEBUG - download: Directorio creado {$storageDir}");
        } else {
            error_log("DEBUG - download: Directorio existe {$storageDir}");
            error_log("DEBUG - download: Contenido directorio: " . implode(',', scandir($storageDir)));
        }
        
        // Verificar si el archivo existe
        $rutaArchivo = $storageDir . basename($nombreArchivo);
        error_log("DEBUG - download: Ruta archivo completa={$rutaArchivo}");
        error_log("DEBUG - download: ¿Archivo existe? " . (file_exists($rutaArchivo) ? 'SÍ' : 'NO'));
        
        // Si no existe el PDF, pero existe el HTML, renómbralo a PDF
        if (!file_exists($rutaArchivo) && strtolower(substr($rutaArchivo, -4)) === '.pdf') {
            $rutaHtml = substr($rutaArchivo, 0, -4) . '.html';
            error_log("DEBUG - download: Intentando buscar HTML alternativo: {$rutaHtml}");
            error_log("DEBUG - download: ¿HTML existe? " . (file_exists($rutaHtml) ? 'SÍ' : 'NO'));
            
            if (file_exists($rutaHtml)) {
                error_log("DEBUG - download: Renombrando {$rutaHtml} -> {$rutaArchivo}");
                if (rename($rutaHtml, $rutaArchivo)) {
                    error_log("DEBUG - download: Renombrado exitoso");
                } else {
                    error_log("DEBUG - download: Error al renombrar");
                }
            }
        }
        
        // Verificar permisos - primero imprimir resultado de validación
        $tienePermiso = $this->validarPermisoDescarga($nombreArchivo);
        error_log("DEBUG - download: ¿Tiene permiso? " . ($tienePermiso ? 'SÍ' : 'NO'));
        
        if (!file_exists($rutaArchivo) || !$tienePermiso) {
            http_response_code(404);
            $this->respuestaJson(['error' => 'Archivo no encontrado o sin permisos']);
            return;
        }
        
        $this->registrarDescarga($nombreArchivo);
        
        // Determinar si es un archivo HTML renombrado como PDF
        $esHtmlComoPdf = false;
        if (strtolower(substr($rutaArchivo, -4)) === '.pdf') {
            // Leer los primeros bytes para detectar si es HTML
            $handle = fopen($rutaArchivo, 'r');
            $primerosBytesStr = fread($handle, 30); // Lee los primeros 30 bytes
            fclose($handle);
            
            // Si comienza con <!DOCTYPE html o <html, es HTML
            if (stripos($primerosBytesStr, '<!DOCTYPE html') !== false || 
                stripos($primerosBytesStr, '<html') !== false) {
                $esHtmlComoPdf = true;
                error_log("DEBUG - download: Detectado HTML con extensión .pdf");
            }
        }
        
        // Configurar headers según el tipo real
        if (strtolower(substr($rutaArchivo, -4)) === '.pdf') {
            if ($esHtmlComoPdf) {
                // Es HTML pero con extensión .pdf - forzar inline para mejor compatibilidad
                header('Content-Type: text/html; charset=UTF-8');
                header('Content-Disposition: inline; filename="' . basename($nombreArchivo) . '"');
            } else {
                // PDF real
                header('Content-Type: application/pdf');
                header('Content-Disposition: inline; filename="' . basename($nombreArchivo) . '"');
            }
        } else {
            // Otros formatos como csv o xlsx
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($nombreArchivo) . '"');
        }
        
        // Headers adicionales para mejorar compatibilidad
        header('Content-Length: ' . filesize($rutaArchivo));
        header('Cache-Control: max-age=0, no-cache, no-store, must-revalidate');
        header('Pragma: public');
        header('Expires: 0');
        
        // Enviar archivo
        readfile($rutaArchivo);
        // unlink($rutaArchivo); // NO borrar tras la descarga
    }

    /**
     * Recoge y sanitiza los filtros de la URL (query string).
     * @return array Filtros sanitizados.
     */
    private function obtenerFiltros()
    {
        $filtros = [];
        if (isset($_GET['fecha_desde'])) $filtros['fecha_desde'] = htmlspecialchars($_GET['fecha_desde']);
        if (isset($_GET['fecha_hasta'])) $filtros['fecha_hasta'] = htmlspecialchars($_GET['fecha_hasta']);
        if (isset($_GET['agencia_id'])) $filtros['agencia_id'] = (int)$_GET['agencia_id'];
        if (isset($_GET['origen'])) $filtros['origen'] = htmlspecialchars($_GET['origen']);
        if (isset($_GET['estado_ticket'])) $filtros['estado_ticket'] = htmlspecialchars($_GET['estado_ticket']);
        if (isset($_GET['hipodromo'])) $filtros['hipodromo'] = htmlspecialchars($_GET['hipodromo']);
        if (isset($_GET['hipodromo_id'])) $filtros['hipodromo_id'] = (int)$_GET['hipodromo_id'];
        if (isset($_GET['numero_carrera'])) $filtros['numero_carrera'] = (int)$_GET['numero_carrera'];
        if (isset($_GET['carrera_id'])) $filtros['carrera_id'] = (int)$_GET['carrera_id'];
        if (isset($_GET['page'])) {
            $page = max(1, (int)$_GET['page']);
            $limit = (int)($_GET['limit'] ?? 100);
            $filtros['limit'] = $limit;
            $filtros['offset'] = ($page - 1) * $limit;
        }
        return $filtros;
    }

    /**
     * Valida el token CSRF de la cabecera.
     * @return bool True si es válido.
     */
    private function validarCsrf()
    {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return $this->auth->validateCsrfToken($token);
    }

    /**
     * Valida si el usuario actual tiene permiso para descargar un archivo.
     * @param string $nombreArchivo
     * @return bool True si tiene permiso.
     */
    private function validarPermisoDescarga($nombreArchivo)
    {
        $baseName = basename($nombreArchivo);
        $rolUsuario = $_SESSION['role'] ?? 'desconocido';
        
        // Log para depurar
        error_log("DEBUG - validarPermisoDescarga: nombreArchivo={$baseName}, rol={$rolUsuario}");
        
        $usuarioId = $this->obtenerAgenciaId();
        error_log("DEBUG - validarPermisoDescarga: agenciaId={$usuarioId}");
        
        // Si es admin, siempre tiene permiso
        if ($rolUsuario === 'admin') {
            error_log("DEBUG - validarPermisoDescarga: admin puede descargar cualquier archivo");
            return true;
        }
        
        // Para agencias, verificar directamente si el nombre contiene _ag{id}
        if ($usuarioId) {
            $sufijoAgencia = '_ag' . $usuarioId;
            $tieneSufijo = strpos($baseName, $sufijoAgencia) !== false;
            error_log("DEBUG - validarPermisoDescarga: ¿Nombre contiene {$sufijoAgencia}? " . ($tieneSufijo ? 'SÍ' : 'NO'));
            
            // Si es el sufijo correcto, permitir la descarga
            if ($tieneSufijo) {
                error_log("DEBUG - validarPermisoDescarga: permiso concedido por sufijo _ag{$usuarioId}");
                return true;
            }
            
            // Verificar también si contiene ag{id} como parte separada
            $partes = explode('_', $baseName);
            $sufijoCorto = 'ag' . $usuarioId;
            foreach ($partes as $parte) {
                if ($parte === $sufijoCorto) {
                    error_log("DEBUG - validarPermisoDescarga: permiso concedido por parte ag{$usuarioId}");
                    return true;
                }
            }
            
            error_log("DEBUG - validarPermisoDescarga: permiso denegado, no contiene _ag{$usuarioId}");
            return false;
        }
        
        error_log("DEBUG - validarPermisoDescarga: permiso concedido por defecto (caso no contemplado)");
        return true;
    }

    /**
     * Registra en el log el acceso a un reporte.
     */
    private function registrarAccesoReporte($tipoReporte, $filtros)
    {
        error_log(sprintf('Acceso a reporte: usuario=%d, reporte=%s, filtros=%s', $_SESSION['user_id'], $tipoReporte, json_encode($filtros)));
    }

    /**
     * Registra en el log la exportación de un reporte.
     */
    private function registrarExportacionReporte($tipoReporte, $formato, $filtros)
    {
        error_log(sprintf('Exportación de reporte: usuario=%d, reporte=%s, formato=%s, filtros=%s', $_SESSION['user_id'], $tipoReporte, $formato, json_encode($filtros)));
    }

    /**
     * Registra en el log la descarga de un archivo.
     */
    private function registrarDescarga($nombreArchivo)
    {
        error_log(sprintf('Descarga de archivo: usuario=%d, archivo=%s', $_SESSION['user_id'], basename($nombreArchivo)));
    }

    /**
     * Envía una respuesta JSON estandarizada.
     * @param mixed $data Datos a codificar en JSON.
     */
    private function respuestaJson($data)
    {
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    // --- MÉTODOS PARA REPORTES DE AGENCIA ---

    /**
 * Endpoint: Ventas diarias (rol Agencia)
 * Filtra por agencia (de la sesión) y permite fecha_desde / fecha_hasta (YYYY-MM-DD).
 */
    public function ventasDiariasAgencia(): void
    {
        $this->auth->requirePermission('view_own_reports');

        $agenciaId = $this->obtenerAgenciaId();
        if (!$agenciaId) {
            http_response_code(403);
            $this->respuestaJson(['error' => 'Agencia no identificada para el usuario actual.']);
            return;
        }

        $filtros = $this->obtenerFiltros(); // debe leer fecha_desde / fecha_hasta (YYYY-MM-DD)
        $data = $this->reportService->getVentasDiariasAgencia($agenciaId, $filtros);

        $this->respuestaJson($data);
    }


    /**
     * Endpoint para el reporte de tickets con devoluciones de una agencia.
     */
    public function ticketsDevolucionesAgencia()
    {
        $this->auth->requirePermission('view_refunds');
        $idAgencia = $_SESSION['agencia_id'] ?? null;
        if (!$idAgencia) {
            $this->respuestaJson(['error' => 'Usuario no asociado a una agencia.']);
            return;
        }
        $filtros = $this->obtenerFiltros();
        $data = $this->reportService->getTicketsDevolucionesAgencia($idAgencia, $filtros);
        $this->respuestaJson($data);
    }

    /**
     * Endpoint para el reporte de sports y carreras de una agencia.
     */
    public function sportsCarrerasAgencia()
    {
        $this->auth->requirePermission('view_sports_races');
        $idAgencia = $_SESSION['agencia_id'] ?? null;
        if (!$idAgencia) {
            $this->respuestaJson(['error' => 'Usuario no asociado a una agencia.']);
            return;
        }
        $filtros = $this->obtenerFiltros();
        $data = $this->reportService->getSportsCarrerasAgencia($idAgencia, $filtros);
        $this->respuestaJson($data);
    }

    /**
     * Endpoint para el reporte de tickets anulados de una agencia.
     */
    public function ticketsAnuladosAgencia()
    {
        $this->auth->requirePermission('view_cancelled_tickets');
        $idAgencia = $_SESSION['agencia_id'] ?? null;
        if (!$idAgencia) {
            $this->respuestaJson(['error' => 'Usuario no asociado a una agencia.']);
            return;
        }
        $filtros = $this->obtenerFiltros();
        $data = $this->reportService->getTicketsAnuladosAgencia($idAgencia, $filtros);
        $this->respuestaJson($data);
    }

}
