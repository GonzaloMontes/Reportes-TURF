<?php

namespace TurfReports\Services;

use TurfReports\Core\DatabaseManager;
use PDO;

/**
 * Servicio de reportes para AppWeb.
 * Maneja consultas específicas de la base de datos appweb.
 */
class AppWebReportService
{
    private $db;

    public function __construct()
    {
        $this->db = DatabaseManager::getInstance();
    }

    /**
     * Reporte Por Usuario - Rendimiento financiero de jugadores últimos 90 días
     * Muestra resumen económico: total apostado, premios ganados, diferencia y saldo actual
     */
    public function getPorUsuario($filtros = [])
    {
        $whereClauses = [];
        $params = [];

        // Filtro por nombre de usuario (opcional)
        if (!empty($filtros['buscar_usuario'])) {
            $whereClauses[] = "CONCAT(u.nombre, ' ', u.apellido) LIKE :buscar_usuario";
            $params[':buscar_usuario'] = '%' . $filtros['buscar_usuario'] . '%';
        }

        // Sin filtro de fecha por ahora hasta confirmar campo correcto
        // $whereClauses[] = "c.fecha >= DATE_SUB(NOW(), INTERVAL 90 DAY)";

        $whereSql = count($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';


        $query = "
            SELECT 
                u.id_usuario,
                u.nombre as nombre_completo,
                u.email,
                COALESCE(u.credito_disponible, 0) as saldo_credito,
                COALESCE(SUM(dj.total_apostado), 0) as total_apostado,
                COALESCE(SUM(CASE WHEN dj.premio = 'si' THEN dj.total_premio ELSE 0 END), 0) as total_premios,
                (COALESCE(SUM(CASE WHEN dj.premio = 'si' THEN dj.total_premio ELSE 0 END), 0) - COALESCE(SUM(dj.total_apostado), 0)) as diferencia,
                COUNT(DISTINCT j.nro_jugada) as total_jugadas
            FROM tbl_usuarios u
            LEFT JOIN tbl_jugadas j ON u.id_usuario = j.id_usuario
            LEFT JOIN tbl_detalle_jugada dj ON j.id_jugada = dj.id_jugada
            {$whereSql}
            GROUP BY u.id_usuario, u.nombre, u.email, u.credito_disponible
            HAVING total_apostado > 0
            ORDER BY diferencia DESC
        ";

        $registros = $this->db->query($query, 'appweb', $params)->fetchAll(PDO::FETCH_ASSOC);
        
        // Calcular KPIs del reporte
        $totalUsuarios = count($registros);
        $totalApostadoGeneral = array_sum(array_column($registros, 'total_apostado'));
        $totalPremiosGeneral = array_sum(array_column($registros, 'total_premios'));
        $gananciaCasa = $totalApostadoGeneral - $totalPremiosGeneral;
        
        return [
            'data' => $registros,
            'total_records' => $totalUsuarios,
            'kpis' => [
                'total_usuarios' => $totalUsuarios,
                'total_apostado' => $totalApostadoGeneral,
                'total_premios' => $totalPremiosGeneral,
                'ganancia_casa' => $gananciaCasa
            ]
        ];
    }

    /**
     * Obtiene el detalle de jugadas de un usuario específico
     * Para mostrar en la sub-tabla expandible
     */
    public function getDetalleUsuario($idUsuario, $filtros = [])
    {
        $whereClauses = ['j.id_usuario = :id_usuario'];
        $params = [':id_usuario' => $idUsuario];

        // Sin filtro de fecha por ahora hasta confirmar campo correcto
        // $whereClauses[] = "c.fecha >= DATE_SUB(NOW(), INTERVAL 90 DAY)";

        $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);

        $query = "
            SELECT 
                DATE(NOW()) as fecha_jugada,
                j.nro_jugada,
                c.numero_carrera,
                h.nombre_hipodromo as nombre_hipodromo,
                ta.nombre_apuesta as tipo_apuesta,
                dj.total_apostado as monto_apostado,
                CASE WHEN dj.premio = 'si' THEN dj.total_premio ELSE 0 END as premio_ganado,
                (CASE WHEN dj.premio = 'si' THEN dj.total_premio ELSE 0 END - dj.total_apostado) as resultado,
                dj.premio as gano_premio,
                GROUP_CONCAT(cj.nro_caballo ORDER BY cj.nro_caballo) as caballos_apostados
            FROM tbl_jugadas j
            INNER JOIN tbl_detalle_jugada dj ON j.id_jugada = dj.id_jugada
            INNER JOIN tbl_carreras c ON j.id_carrera = c.id_carrera
            INNER JOIN tbl_hipodromos h ON c.id_hipodromo = h.id_hipodromo
            INNER JOIN tbl_apuestas ta ON dj.id_apuesta = ta.id_apuesta
            LEFT JOIN tbl_caballos_jugadas cj ON dj.id_detalle_jugada = cj.id_detalle_jugada
            {$whereSql}
            GROUP BY j.nro_jugada, dj.id_detalle_jugada
            ORDER BY j.nro_jugada DESC, c.numero_carrera ASC
        ";

        $registros = $this->db->query($query, 'appweb', $params)->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'data' => $registros,
            'total_records' => count($registros)
        ];
    }

    /**
     * Reporte Económico - Flujo de caja (créditos y débitos) en cuentas de usuarios
     * Muestra movimiento de dinero: total acreditado, debitado y diferencia
     */
    public function getEconomico($filtros = [])
    {
        $whereClauses = [];
        $params = [];

        // Filtros de fecha
        if (!empty($filtros['fecha_desde'])) {
            $whereClauses[] = "DATE(b.fecha) >= :fecha_desde";
            $params[':fecha_desde'] = $filtros['fecha_desde'];
        }
        
        if (!empty($filtros['fecha_hasta'])) {
            $whereClauses[] = "DATE(b.fecha) <= :fecha_hasta";
            $params[':fecha_hasta'] = $filtros['fecha_hasta'];
        }

        $whereSql = count($whereClauses) > 0 ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

        // Consulta para obtener totales de créditos y débitos
        // Créditos: conceptos 1,2,3,6,9 / Débitos: conceptos 4,5,7
        $query = "
            SELECT 
                COALESCE(SUM(CASE WHEN b.id_concepto IN (1,2,3,6,9) THEN b.credito ELSE 0 END), 0) as total_acreditado,
                COALESCE(SUM(CASE WHEN b.id_concepto IN (4,5,7) THEN b.debito ELSE 0 END), 0) as total_debitado,
                (COALESCE(SUM(CASE WHEN b.id_concepto IN (1,2,3,6,9) THEN b.credito ELSE 0 END), 0) - 
                 COALESCE(SUM(CASE WHEN b.id_concepto IN (4,5,7) THEN b.debito ELSE 0 END), 0)) as diferencia
            FROM tbl_balances_usuarios b
            $whereSql
        ";

        $resultado = $this->db->query($query, 'appweb', $params)->fetch(PDO::FETCH_ASSOC);
        
        return [
            'data' => [], // No hay tabla de datos, solo KPIs
            'total_records' => 0,
            'kpis' => [
                'total_acreditado' => floatval($resultado['total_acreditado']),
                'total_debitado' => floatval($resultado['total_debitado']),
                'diferencia' => floatval($resultado['diferencia'])
            ]
        ];
    }

    /**
     * Reporte Dinero Remanente - Total de crédito disponible en todas las cuentas de usuarios
     * Muestra el pasivo total que la plataforma debe a sus usuarios
     */
    public function getDineroRemanente($filtros = [])
    {
        // Sin filtros - suma todo el crédito disponible de todos los usuarios
        $query = "
            SELECT 
                COALESCE(SUM(u.credito_disponible), 0) as total_dinero_remanente
            FROM tbl_usuarios u
        ";

        $resultado = $this->db->query($query, 'appweb')->fetch(PDO::FETCH_ASSOC);
        
        return [
            'data' => [], // No hay tabla de datos, solo KPI
            'total_records' => 0,
            'kpis' => [
                'total_dinero_remanente' => floatval($resultado['total_dinero_remanente'])
            ]
        ];
    }

    /**
     * Reporte Apuestas - Resumen financiero del día actual
     * Muestra total apostado, premios pagados y diferencia del día en curso
     */
    public function getApuestas($filtros = [])
    {
        $whereClauses = [];
        $params = [];

        // Filtros de fecha (si no se especifica, usar día actual)
        if (!empty($filtros['fecha_desde'])) {
            $whereClauses[] = "DATE(j.fecha) >= :fecha_desde";
            $params[':fecha_desde'] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $whereClauses[] = "DATE(j.fecha) <= :fecha_hasta";
            $params[':fecha_hasta'] = $filtros['fecha_hasta'];
        }
        
        // Si no hay filtros de fecha, usar día actual
        if (empty($whereClauses)) {
            $whereClauses[] = "DATE(j.fecha) = CURDATE()";
        }

        $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);

        $query = "
            SELECT 
                COALESCE(SUM(dj.total_apostado), 0) as total_ingreso_apuestas,
                COALESCE(SUM(CASE WHEN dj.premio = 'si' THEN dj.total_premio ELSE 0 END), 0) as total_premios_pagados,
                (COALESCE(SUM(dj.total_apostado), 0) - COALESCE(SUM(CASE WHEN dj.premio = 'si' THEN dj.total_premio ELSE 0 END), 0)) as diferencia
            FROM tbl_detalle_jugada dj
            INNER JOIN tbl_jugadas j ON dj.id_jugada = j.id_jugada
            $whereSql
        ";

        $resultado = $this->db->query($query, 'appweb', $params)->fetch(PDO::FETCH_ASSOC);
        
        return [
            'data' => [], // No hay tabla de datos, solo KPIs
            'total_records' => 0,
            'kpis' => [
                'total_ingreso_apuestas' => floatval($resultado['total_ingreso_apuestas']),
                'total_premios_pagados' => floatval($resultado['total_premios_pagados']),
                'diferencia' => floatval($resultado['diferencia'])
            ]
        ];
    }

    /**
     * Reporte Rendimiento Apuesta por Carrera - Detalle de apuestas por carrera específica
     * Muestra listado detallado de cada apuesta individual realizada en una carrera
     */
    public function getRendimientoApuestaCarrera($filtros = [])
    {
        $whereClauses = [];
        $params = [];

        // Filtro por fecha para cargar carreras del día
        if (!empty($filtros['fecha_desde'])) {
            $whereClauses[] = "DATE(j.fecha) >= :fecha_desde";
            $params[':fecha_desde'] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $whereClauses[] = "DATE(j.fecha) <= :fecha_hasta";
            $params[':fecha_hasta'] = $filtros['fecha_hasta'];
        }

        // Filtro por carrera específica (requerido para el detalle)
        if (!empty($filtros['numero_carrera'])) {
            $whereClauses[] = "j.id_carreara = :numero_carrera";
            $params[':numero_carrera'] = $filtros['numero_carrera'];
        }

        $whereSql = count($whereClauses) > 0 ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

        $query = "
            SELECT 
                j.nro_jugada,
                u.nombre as nombre_usuario,
                j.fecha,
                j.hora,
                a.nombre_apuesta as tipo_apuesta,
                GROUP_CONCAT(cj.nro_caballo ORDER BY cj.ubicacion_caballo SEPARATOR '-') as caballos_seleccionados,
                dj.total_apostado as monto_apostado,
                CASE 
                    WHEN j.premio = 'si' THEN dj.total_premio 
                    ELSE 0 
                END as monto_ganado,
                CASE 
                    WHEN j.cancelado = 1 THEN 'Cancelada'
                    WHEN j.anulado = 1 THEN 'Anulada'
                    WHEN j.premio = 'si' THEN 'Ganadora'
                    ELSE 'Perdedora'
                END as estado_apuesta
            FROM tbl_jugadas j
            INNER JOIN tbl_usuarios u ON j.id_usuario = u.id_usuario
            INNER JOIN tbl_detalle_jugada dj ON j.id_jugada = dj.id_jugada
            INNER JOIN tbl_apuestas a ON dj.id_apuesta = a.id_apuesta
            LEFT JOIN tbl_caballos_jugadas cj ON dj.id_detalle_jugada = cj.id_detalle_jugada
            $whereSql
            GROUP BY j.id_jugada, dj.id_detalle_jugada
            ORDER BY j.fecha DESC, j.hora DESC
        ";

        $registros = $this->db->query($query, 'appweb', $params)->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'data' => $registros,
            'total_records' => count($registros)
        ];
    }
}
