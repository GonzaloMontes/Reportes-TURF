<?php

namespace TurfReports\Controllers;

use TurfReports\Core\Auth;
use TurfReports\Services\AppWebReportService;

/**
 * Controlador para reportes de AppWeb.
 * Maneja endpoints específicos para la base de datos appweb.
 */
class AppWebReportController
{
    private $auth;
    private $appWebReportService;

    public function __construct()
    {
        $this->auth = new Auth();
        $this->appWebReportService = new AppWebReportService();
    }

    /**
     * Obtiene filtros comunes de la request
     */
    private function obtenerFiltros()
    {
        return [
            'fecha_desde' => $_GET['fecha_desde'] ?? null,
            'fecha_hasta' => $_GET['fecha_hasta'] ?? null,
            'buscar_usuario' => $_GET['buscar_usuario'] ?? null,
            'limit' => (int)($_GET['limit'] ?? 100),
            'offset' => (int)($_GET['offset'] ?? 0)
        ];
    }

    /**
     * Envía respuesta JSON
     */
    private function respuestaJson($data)
    {
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    /**
     * Maneja errores y envía respuesta de error
     */
    private function manejarError($exception, $mensaje = 'Error interno del servidor')
    {
        error_log($mensaje . ': ' . $exception->getMessage());
        http_response_code(500);
        $this->respuestaJson(['error' => $mensaje]);
    }

    /**
     * Reporte Por Usuario - AppWeb
     */
    public function porUsuario()
    {
        try {
            $this->auth->requireAuth();
            $filtros = $this->obtenerFiltros();
            $datos = $this->appWebReportService->getPorUsuario($filtros);
            $this->respuestaJson($datos);
        } catch (Exception $e) {
            $this->manejarError($e, 'Error al obtener reporte por usuario');
        }
    }

    /**
     * Reporte Económico - AppWeb
     */
    public function economico()
    {
        try {
            $this->auth->requireAuth();
            $filtros = $this->obtenerFiltros();
            $datos = $this->appWebReportService->getEconomico($filtros);
            $this->respuestaJson($datos);
        } catch (Exception $e) {
            $this->manejarError($e, 'Error al obtener reporte económico');
        }
    }

    /**
     * Reporte Apuestas - AppWeb
     */
    public function apuestas()
    {
        try {
            $this->auth->requireAuth();
            $filtros = $this->obtenerFiltros();
            $datos = $this->appWebReportService->getApuestas($filtros);
            $this->respuestaJson($datos);
        } catch (Exception $e) {
            $this->manejarError($e, 'Error al obtener reporte de apuestas');
        }
    }

    /**
     * Reporte Dinero Remanente - AppWeb
     */
    public function dineroRemanente()
    {
        try {
            $this->auth->requireAuth();
            $filtros = $this->obtenerFiltros();
            $datos = $this->appWebReportService->getDineroRemanente($filtros);
            $this->respuestaJson($datos);
        } catch (Exception $e) {
            $this->manejarError($e, 'Error al obtener reporte de dinero remanente');
        }
    }

    /**
     * Reporte Rendimiento Apuesta por Carrera - AppWeb
     */
    public function rendimientoApuestaCarrera()
    {
        try {
            $this->auth->requireAuth();
            $filtros = $this->obtenerFiltros();
            $datos = $this->appWebReportService->getRendimientoApuestaCarrera($filtros);
            $this->respuestaJson($datos);
        } catch (Exception $e) {
            $this->manejarError($e, 'Error al obtener reporte de rendimiento por carrera');
        }
    }

    /**
     * Obtiene el detalle de jugadas de un usuario específico
     * Para la funcionalidad "Ver detalles usuario"
     */
    public function detalleUsuario($idUsuario)
    {
        try {
            $this->auth->requireAuth();
            $filtros = $this->obtenerFiltros();
            $datos = $this->appWebReportService->getDetalleUsuario($idUsuario, $filtros);
            $this->respuestaJson($datos);
        } catch (Exception $e) {
            $this->manejarError($e, 'Error al obtener detalle del usuario');
        }
    }
}
