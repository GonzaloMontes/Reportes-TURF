<?php

namespace TurfReports\Services;

use TurfReports\Core\DatabaseManager;
use PDO;

/**
 * Servicio de reportes reestructurado.
 * Enfocado en consultas validadas y correctas.
 */
class ReportService
{
    private $db;

    public function __construct()
    {
        $this->db = DatabaseManager::getInstance();
    }

    public function getAgencies()
    {
        $query = "SELECT id_agencia, nombre_agencia FROM tbl_agencias ORDER BY nombre_agencia ASC";
        return $this->db->query($query, 'agencias')->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene la lista de hipódromos disponibles.
     * @return array Lista de hipódromos con id_hipodromo y nombre_hipodromo
     */
    public function getHipodromos()
    {
        $query = "SELECT id_hipodromo, nombre_hipodromo FROM tbl_hipodromos ORDER BY nombre_hipodromo ASC";
        return $this->db->query($query, 'agencias')->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene la lista de números de carrera disponibles.
     * @return array Lista de números de carrera únicos
     */
    public function getNumerosCarreras()
    {
        $query = "SELECT DISTINCT numero_carrera FROM tbl_carreras ORDER BY numero_carrera ASC";
        return $this->db->query($query, 'agencias')->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el resumen de ventas de tickets.
     *
     * @param array $filters Filtros para la consulta.
     * @param int|null $usuarioId ID del usuario para filtrar.
     * @return array Resumen de ventas.
     */
    public function getListaTickets(array $filters = [], $usuarioId = null)
    {
        $params = [];
        $whereClauses = [];

        if (!empty($filters['fecha_desde'])) {
            $whereClauses[] = "STR_TO_DATE(t.fecha, '%d/%m/%Y') >= :fecha_desde";
            $params[':fecha_desde'] = $filters['fecha_desde'];
        }
        if (!empty($filters['fecha_hasta'])) {
            $whereClauses[] = "STR_TO_DATE(t.fecha, '%d/%m/%Y') <= :fecha_hasta";
            $params[':fecha_hasta'] = $filters['fecha_hasta'];
        }

        if (!empty($filters['agencia_id'])) {
            $whereClauses[] = "u.id_agencia = :agencia_id";
            $params[':agencia_id'] = $filters['agencia_id'];
        }

        $whereSql = count($whereClauses) > 0 ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

        $query = "
            SELECT 
                SUM(dt.total_apostado) as total_vendido,
                SUM(CASE WHEN dt.premio = 'si' THEN dt.total_premio ELSE 0 END) as total_ganadores,
                SUM(CASE WHEN dt.premio = 'si' AND LOWER(TRIM(t.pagado)) = 'si' THEN dt.total_premio ELSE 0 END) as total_pagados,
                0 as total_devoluciones
            FROM 
                tbl_tickets t
            JOIN 
                tbl_detalle_tickets dt ON t.id_ticket = dt.id_ticket
            JOIN
                tbl_usuarios u ON t.id_usuario = u.id_usuario
            {$whereSql}
        ";

        $summary = $this->db->query($query, 'agencias', $params)->fetch(PDO::FETCH_ASSOC);

        $totalVendido = (float)($summary['total_vendido'] ?? 0);
        $totalGanadores = (float)($summary['total_ganadores'] ?? 0);
        $totalPagados = (float)($summary['total_pagados'] ?? 0);
        $totalDevoluciones = (float)($summary['total_devoluciones'] ?? 0);
        $ganancia = $totalVendido - $totalPagados;

        return [
            'total_vendido' => $totalVendido,
            'total_ganadores' => $totalGanadores,
            'total_pagados' => $totalPagados,
            'total_devoluciones' => $totalDevoluciones,
            'ganancia' => $ganancia
        ];
    }

    /**
     * Obtiene los datos para el reporte 'Informe por Agencia'.
     *
     * @param array $filters Filtros para la consulta (fecha_desde, fecha_hasta).
     * @return array Datos del reporte, incluyendo un resumen por agencia y totales generales.
     */
    public function getInformePorAgencia(array $filters = [])
    {
        $params = [];

        // 1. CONSTRUCCIÓN DE FILTROS DE FECHA
        // Se preparan las condiciones de fecha para ser inyectadas en el LEFT JOIN.
        // Esto asegura que todas las agencias se listen, incluso si no tienen tickets en el rango de fechas.
        $joinConditions = [];
        if (!empty($filters['fecha_desde'])) {
            // La columna `fecha` es de tipo texto con formato 'd/m/Y', por lo que se convierte para la comparación.
            $joinConditions[] = "STR_TO_DATE(t.fecha, '%d/%m/%Y') >= :fecha_desde";
            $params[':fecha_desde'] = $filters['fecha_desde'];
        }
        if (!empty($filters['fecha_hasta'])) {
            $joinConditions[] = "STR_TO_DATE(t.fecha, '%d/%m/%Y') <= :fecha_hasta";
            $params[':fecha_hasta'] = $filters['fecha_hasta'];
        }

        // Se genera el fragmento SQL para los filtros de fecha.
        $joinSql = "";
        if (!empty($joinConditions)) {
            $joinSql = "AND " . implode(' AND ', $joinConditions);
        }

        // 2. CONSTRUCCIÓN DE LA CONSULTA PRINCIPAL
        // Esta consulta obtiene los datos de ventas, premios y devoluciones agrupados por agencia.
        $query = "
            SELECT
                a.nombre_agencia AS agencia,
                COUNT(DISTINCT CASE WHEN t.anulado = 1 THEN t.id_ticket END) AS anulados,
                COALESCE(SUM(dt.total_apostado), 0) AS vendidos,
                COALESCE(SUM(CASE WHEN dt.premio = 'si' THEN dt.total_premio ELSE 0 END), 0) AS ganadores,
                COALESCE(SUM(CASE WHEN dt.premio = 'si' AND t.pagado = 'si' THEN dt.total_premio ELSE 0 END), 0) AS pagados,
                COALESCE(SUM(CASE WHEN td.id_ticket IS NOT NULL THEN dt.total_apostado ELSE 0 END), 0) AS devoluciones
            FROM
                tbl_agencias a
            LEFT JOIN
                tbl_usuarios u ON a.id_agencia = u.id_agencia
            LEFT JOIN
                tbl_tickets t ON u.id_usuario = t.id_usuario {$joinSql} -- Los filtros de fecha se aplican aquí
            LEFT JOIN
                tbl_detalle_tickets dt ON t.id_ticket = dt.id_ticket
            LEFT JOIN
                tbl_tickets_devoluciones td ON t.id_ticket = td.id_ticket
            GROUP BY
                a.id_agencia, a.nombre_agencia
            ORDER BY
                a.nombre_agencia;
        ";

        // 3. EJECUCIÓN DE LA CONSULTA
        $agenciasData = $this->db->query($query, 'agencias', $params)->fetchAll(PDO::FETCH_ASSOC);

        // 4. CÁLCULO DE TOTALES Y GANANCIA
        // Se calculan los totales generales y la ganancia neta para cada agencia y para el resumen.
        $totales = [
            'vendidos' => 0,
            'ganadores' => 0,
            'pagados' => 0,
            'devoluciones' => 0,
            'ganancia' => 0
        ];

        foreach ($agenciasData as &$agencia) {
            // La ganancia se calcula como: vendidos - (premios ganadores + devoluciones)
            $agencia['ganancia'] = $agencia['vendidos'] - $agencia['pagados'] - $agencia['devoluciones'];
            
            // Se acumulan los totales para el resumen general.
            $totales['vendidos'] += $agencia['vendidos'];
            $totales['ganadores'] += $agencia['ganadores'];
            $totales['pagados'] += $agencia['pagados'];
            $totales['devoluciones'] += $agencia['devoluciones'];
            $totales['ganancia'] += $agencia['ganancia'];
        }

        // 5. RETORNO DE DATOS
        // Se devuelven los datos de las agencias y los totales para ser consumidos por el frontend.
        return [
            'agencias' => $agenciasData,
            'totales' => $totales
        ];
    }

    /**
     * Obtiene los datos para el reporte de 'Caballos Retirados a Último Momento'.
     * 
     * Este reporte muestra todos los caballos retirados que generaron devoluciones,
     * incluyendo fecha, número de carrera, número de caballo y total apostado a devolver.
     *
     * @param array $filters Filtros para la consulta (fecha_desde, fecha_hasta, limit, offset).
     * @return array Un array con los registros paginados, el total de registros y el total a devolver.
     */
    public function getCaballosRetirados(array $filters = [])
    {
        $params = [];
        $whereClauses = [];

        // 1. CONSTRUCCIÓN DE FILTROS
        // Se aplican los filtros de fecha sobre la tabla de caballos retirados último momento
        if (!empty($filters['fecha_desde'])) {
            $whereClauses[] = "cr.fecha >= :fecha_desde";
            $params[':fecha_desde'] = $filters['fecha_desde'];
        }
        if (!empty($filters['fecha_hasta'])) {
            $whereClauses[] = "cr.fecha <= :fecha_hasta";
            $params[':fecha_hasta'] = $filters['fecha_hasta'];
        }

        $whereSql = count($whereClauses) > 0 ? 'AND ' . implode(' AND ', $whereClauses) : '';

        // 2. CONSULTA PARA OBTENER TOTALES: REGISTROS, TOTAL A DEVOLVER Y TOTAL DEVUELTO
        $totalQuery = "
            SELECT
                COUNT(DISTINCT CONCAT(cr.fecha, '-', cr.nro_caballo, '-', cr.id_carrera)) as total_records,
                COALESCE(SUM(CASE WHEN crd.devolucion = 0 THEN dt.total_apostado ELSE 0 END), 0) as total_a_devolver,
                COALESCE(SUM(CASE WHEN crd.devolucion = 1 THEN dt.total_apostado ELSE 0 END), 0) as total_devuelto,
                COALESCE(SUM(dt.total_apostado), 0) as total_general
            FROM
                tbl_caballos_retirados_ultimo_momento cr
            LEFT JOIN
                tbl_caballos_retirados_um_detalle crd ON cr.id_caballo_retirado_ultimo_momento = crd.id_caballo_retirado_ultimo_momento
            LEFT JOIN
                tbl_detalle_tickets dt ON crd.id_detalle_ticket = dt.id_detalle_ticket
            WHERE 1=1
            {$whereSql}
        ";
        $totals = $this->db->query($totalQuery, 'agencias', $params)->fetch(PDO::FETCH_ASSOC);

        // 3. CONSULTA PARA OBTENER LOS DATOS PAGINADOS
        $params[':limit'] = (int)($filters['limit'] ?? 100);
        $params[':offset'] = (int)($filters['offset'] ?? 0);

        // 3.1 CONSULTA PARA OBTENER TODOS LOS CABALLOS RETIRADOS (INCLUSO SIN APUESTAS)
        $dataQuery = "
            SELECT 
                cr.fecha,
                cr.nro_caballo,
                COALESCE(SUM(dt.total_apostado), 0) as total_apostado,
                COALESCE(SUM(CASE WHEN crd.devolucion = 0 THEN dt.total_apostado ELSE 0 END), 0) as monto_a_devolver,
                COALESCE(SUM(CASE WHEN crd.devolucion = 1 THEN dt.total_apostado ELSE 0 END), 0) as monto_devuelto,
                CASE 
                    WHEN SUM(dt.total_apostado) IS NULL OR SUM(dt.total_apostado) = 0 THEN 'Sin Apuestas'
                    WHEN SUM(CASE WHEN crd.devolucion = 0 THEN 1 ELSE 0 END) > 0 
                         AND SUM(CASE WHEN crd.devolucion = 1 THEN 1 ELSE 0 END) > 0 
                    THEN 'Parcial'
                    WHEN SUM(CASE WHEN crd.devolucion = 1 THEN 1 ELSE 0 END) > 0 
                    THEN 'Devuelto'
                    ELSE 'Pendiente'
                END as estado_devolucion
            FROM 
                tbl_caballos_retirados_ultimo_momento cr
            LEFT JOIN 
                tbl_caballos_retirados_um_detalle crd ON cr.id_caballo_retirado_ultimo_momento = crd.id_caballo_retirado_ultimo_momento
            LEFT JOIN 
                tbl_detalle_tickets dt ON crd.id_detalle_ticket = dt.id_detalle_ticket
            WHERE 1=1
            {$whereSql}
            GROUP BY
                cr.fecha, cr.nro_caballo, cr.id_carrera
            ORDER BY
                cr.fecha DESC, cr.nro_caballo ASC
            LIMIT :limit OFFSET :offset
        ";
        
        $registros = $this->db->query($dataQuery, 'agencias', $params)->fetchAll(PDO::FETCH_ASSOC);

        // 4. DEBUG: MOSTRAR CONSULTA Y REGISTROS OBTENIDOS
        error_log("DEBUG - Consulta SQL ejecutada: " . $dataQuery);
        error_log("DEBUG - Parámetros: " . json_encode($params));
        error_log("DEBUG - Registros obtenidos: " . count($registros) . " de " . $totals['total_records'] . " totales");
        
        // 5. FORMATEAR FECHAS EN PHP PARA MAYOR CONTROL
        foreach ($registros as &$registro) {
            $registro['fecha'] = $this->formatearFecha($registro['fecha']);
        }

        // 5. RETORNO DE DATOS ESTRUCTURADOS CON NUEVOS TOTALES
        return [
            'data' => $registros,
            'total_records' => (int)$totals['total_records'],
            'total_general' => (float)$totals['total_general'],
            'total_a_devolver' => (float)$totals['total_a_devolver'],
            'total_devuelto' => (float)$totals['total_devuelto']
        ];
    }

    /**
     * Obtiene el reporte de carreras con filtros y paginación.
     * 
     * @param array $filters Filtros aplicados (fecha_desde, fecha_hasta, numero_carrera, hipodromo_id)
     * @return array Datos estructurados con carreras y totales
     */
    public function getCarreras($filters = [])
    {
        // 1. CONSTRUCCIÓN DE FILTROS DINÁMICOS
        $whereClauses = [];
        $params = [];
        
        // Log de depuración para verificar filtros recibidos
        error_log("DEBUG Carreras - Filtros recibidos: " . json_encode($filters));

        // Filtro por número de carrera
        if (!empty($filters['numero_carrera'])) {
            $whereClauses[] = "c.numero_carrera = :numero_carrera";
            $params[':numero_carrera'] = (int)$filters['numero_carrera'];
        }

        // Filtros de fecha - convertir formato DD/MM/YYYY a fecha comparable
        if (!empty($filters['fecha_desde'])) {
            $whereClauses[] = "STR_TO_DATE(c.fecha, '%d/%m/%Y') >= STR_TO_DATE(:fecha_desde, '%Y-%m-%d')";
            $params[':fecha_desde'] = $filters['fecha_desde'];
        }
        if (!empty($filters['fecha_hasta'])) {
            $whereClauses[] = "STR_TO_DATE(c.fecha, '%d/%m/%Y') <= STR_TO_DATE(:fecha_hasta, '%Y-%m-%d')";
            $params[':fecha_hasta'] = $filters['fecha_hasta'];
        }

        // Filtro por hipódromo
        if (!empty($filters['hipodromo_id'])) {
            $whereClauses[] = "c.id_hipodromo = :hipodromo_id";
            $params[':hipodromo_id'] = (int)$filters['hipodromo_id'];
        }

        $whereSql = count($whereClauses) > 0 ? 'WHERE ' . implode(' AND ', $whereClauses) : '';
        
        // Log de depuración para verificar consulta WHERE construida
        error_log("DEBUG Carreras - WHERE SQL: " . $whereSql);
        error_log("DEBUG Carreras - Parámetros: " . json_encode($params));

        // 2. CONSULTA PARA OBTENER EL TOTAL DE REGISTROS
        $totalQuery = "
            SELECT COUNT(*) as total_records
            FROM tbl_carreras c
            INNER JOIN tbl_hipodromos h ON c.id_hipodromo = h.id_hipodromo
            INNER JOIN tbl_estados_carreras ec ON c.id_estado = ec.id_estado
            {$whereSql}
        ";
        $totals = $this->db->query($totalQuery, 'agencias', $params)->fetch(PDO::FETCH_ASSOC);

        // 3. CONSULTA PARA OBTENER LOS DATOS PAGINADOS
        $params[':limit'] = (int)($filters['limit'] ?? 100);
        $params[':offset'] = (int)($filters['offset'] ?? 0);

        $dataQuery = "
            SELECT 
                c.numero_carrera,
                c.fecha,
                h.nombre_hipodromo,
                ec.estado_carrera,
                c.id_carrera as carrera_interna_id
            FROM 
                tbl_carreras c
            INNER JOIN 
                tbl_hipodromos h ON c.id_hipodromo = h.id_hipodromo
            INNER JOIN 
                tbl_estados_carreras ec ON c.id_estado = ec.id_estado
            {$whereSql}
            ORDER BY
                STR_TO_DATE(c.fecha, '%d/%m/%Y') DESC, c.numero_carrera ASC
            LIMIT :limit OFFSET :offset
        ";
        
        $registros = $this->db->query($dataQuery, 'agencias', $params)->fetchAll(PDO::FETCH_ASSOC);
        
        // Log de depuración para verificar datos obtenidos
        error_log("DEBUG Carreras - Registros obtenidos: " . count($registros));
        if (count($registros) > 0) {
            error_log("DEBUG Carreras - Primer registro: " . json_encode($registros[0]));
        }

        // 4. FORMATEAR FECHAS EN PHP
        foreach ($registros as &$registro) {
            // Formatear fecha desde DD/MM/YYYY a formato más legible
            if (!empty($registro['fecha'])) {
                $fechaOriginal = $registro['fecha'];
                $fechaFormateada = \DateTime::createFromFormat('d/m/Y', $fechaOriginal);
                if ($fechaFormateada) {
                    $registro['fecha'] = $fechaOriginal; // Mantener formato original por ahora
                }
            }
        }

        // 5. RETORNO DE DATOS ESTRUCTURADOS
        return [
            'data' => $registros,
            'total_records' => (int)$totals['total_records']
        ];
    }

    /**
     * Obtiene los resultados de una carrera específica para mostrar detalles
     * @param int $idCarrera ID de la carrera
     * @return array Resultados de la carrera con caballos y dividendos
     */
    public function getResultadosCarrera($idCarrera)
    {
        error_log("DEBUG getResultadosCarrera - ID Carrera: " . $idCarrera);
        
        $params = [':id_carrera' => $idCarrera];
        
        // Consulta para obtener resultados de caballos en la carrera
        $queryResultados = "
            SELECT 
                cs.id_caballo,
                cs.posicion_llegada,
                cs.sport_ganador,
                cs.sport_segundo,
                cs.sport_tercero,
                c.numero_carrera,
                c.fecha,
                h.nombre_hipodromo
            FROM tbl_caballos_sports cs
            INNER JOIN tbl_carreras c ON cs.id_carrera = c.id_carrera
            INNER JOIN tbl_hipodromos h ON c.id_hipodromo = h.id_hipodromo
            WHERE cs.id_carrera = :id_carrera
            ORDER BY cs.posicion_llegada ASC
        ";
        
        $resultados = $this->db->query($queryResultados, 'agencias', $params)->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("DEBUG getResultadosCarrera - Resultados encontrados: " . count($resultados));
        
        return [
            'resultados_caballos' => $resultados
        ];
    }

    /**
     * Formatea una fecha desde diferentes formatos posibles a dd/mm/yyyy.
     * 
     * @param string $fecha La fecha en formato original
     * @return string La fecha formateada en dd/mm/yyyy
     */
    private function formatearFecha($fecha)
    {
        if (empty($fecha) || is_null($fecha)) {
            return '-';
        }

        // Debug adicional
        error_log("DEBUG formatearFecha - Input: " . var_export($fecha, true) . " (tipo: " . gettype($fecha) . ")");

        // Convertir a string si no lo es
        $fecha = (string)$fecha;

        // Si ya está en formato dd/mm/yyyy, devolverla tal como está
        if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $fecha)) {
            error_log("DEBUG formatearFecha - Ya en formato correcto: " . $fecha);
            return $fecha;
        }

        // Intentar diferentes formatos de fecha
        $formatos = [
            'Y-m-d',        // 2025-01-07
            'Y-m-d H:i:s',  // 2025-01-07 10:30:00
            'd/m/Y',        // 07/01/2025
            'm/d/Y',        // 01/07/2025
            'd-m-Y',        // 07-01-2025
            'Y/m/d',        // 2025/01/07
            'd.m.Y',        // 07.01.2025
        ];

        foreach ($formatos as $formato) {
            $fechaObj = \DateTime::createFromFormat($formato, $fecha);
            if ($fechaObj !== false && $fechaObj->format($formato) === $fecha) {
                $resultado = $fechaObj->format('d/m/Y');
                error_log("DEBUG formatearFecha - Formato '$formato' funcionó: $fecha -> $resultado");
                return $resultado;
            }
        }

        // Si no se puede parsear, intentar con strtotime
        $timestamp = strtotime($fecha);
        if ($timestamp !== false) {
            $resultado = date('d/m/Y', $timestamp);
            error_log("DEBUG formatearFecha - strtotime funcionó: $fecha -> $resultado");
            return $resultado;
        }

        // Si todo falla, devolver la fecha original
        error_log("DEBUG formatearFecha - No se pudo formatear, devolviendo original: " . $fecha);
        return $fecha;
    }

    /**
     * Obtiene el reporte de tickets anulados con información detallada.
     * 
     * @param array $filters Filtros aplicados (fecha_desde, fecha_hasta, agencia_id)
     * @return array Datos estructurados con tickets anulados y totales
     */
    public function getTicketsAnulados($filters = [])
    {
        // 1. CONSTRUCCIÓN DE FILTROS DINÁMICOS
        $whereClauses = [];
        $params = [];
        
        // Log de depuración para verificar filtros recibidos
        error_log("DEBUG Tickets Anulados - Filtros recibidos: " . json_encode($filters));

        // Filtros de fecha - aplicados sobre la fecha del ticket
        if (!empty($filters['fecha_desde'])) {
            $whereClauses[] = "STR_TO_DATE(t.fecha, '%d/%m/%Y') >= STR_TO_DATE(:fecha_desde, '%Y-%m-%d')";
            $params[':fecha_desde'] = $filters['fecha_desde'];
        }
        if (!empty($filters['fecha_hasta'])) {
            $whereClauses[] = "STR_TO_DATE(t.fecha, '%d/%m/%Y') <= STR_TO_DATE(:fecha_hasta, '%Y-%m-%d')";
            $params[':fecha_hasta'] = $filters['fecha_hasta'];
        }

        // Filtro por agencia
        if (!empty($filters['agencia_id'])) {
            $whereClauses[] = "u.id_agencia = :agencia_id";
            $params[':agencia_id'] = (int)$filters['agencia_id'];
        }

        $whereSql = count($whereClauses) > 0 ? 'AND ' . implode(' AND ', $whereClauses) : '';
        
        // Log de depuración para verificar consulta WHERE construida
        error_log("DEBUG Tickets Anulados - WHERE construido: " . $whereSql);

        // 2. CONSULTA PARA OBTENER TOTALES
        $totalQuery = "
            SELECT
                COUNT(DISTINCT t.id_ticket) as total_records,
                COALESCE(SUM(dt.total_apostado), 0) as total_anulado
            FROM 
                tbl_tickets t
            INNER JOIN 
                tbl_usuarios u ON t.id_usuario = u.id_usuario
            INNER JOIN 
                tbl_agencias a ON u.id_agencia = a.id_agencia
            INNER JOIN 
                tbl_detalle_tickets dt ON t.id_ticket = dt.id_ticket
            WHERE 
                t.anulado = 1
                {$whereSql}
        ";
        $totals = $this->db->query($totalQuery, 'agencias', $params)->fetch(PDO::FETCH_ASSOC);

        // 3. CONSULTA PARA OBTENER LOS DATOS PAGINADOS
        $params[':limit'] = (int)($filters['limit'] ?? 100);
        $params[':offset'] = (int)($filters['offset'] ?? 0);

        $dataQuery = "
            SELECT 
                t.nro_ticket,
                t.fecha,
                t.hora,
                u.nombre_usuario,
                a.nombre_agencia,
                SUM(dt.total_apostado) as total_apostado
            FROM 
                tbl_tickets t
            INNER JOIN 
                tbl_usuarios u ON t.id_usuario = u.id_usuario
            INNER JOIN 
                tbl_agencias a ON u.id_agencia = a.id_agencia
            INNER JOIN 
                tbl_detalle_tickets dt ON t.id_ticket = dt.id_ticket
            WHERE 
                t.anulado = 1
                {$whereSql}
            GROUP BY
                t.id_ticket, t.nro_ticket, t.fecha, t.hora, u.nombre_usuario, a.nombre_agencia
            ORDER BY
                STR_TO_DATE(t.fecha, '%d/%m/%Y') DESC, t.hora DESC
            LIMIT :limit OFFSET :offset
        ";
        
        $registros = $this->db->query($dataQuery, 'agencias', $params)->fetchAll(PDO::FETCH_ASSOC);
        
        // Log de depuración para verificar datos obtenidos
        error_log("DEBUG Tickets Anulados - Registros obtenidos: " . count($registros));
        if (count($registros) > 0) {
            error_log("DEBUG Tickets Anulados - Primer registro: " . json_encode($registros[0]));
        }

        // 4. FORMATEAR FECHAS EN PHP
        foreach ($registros as &$registro) {
            $registro['fecha'] = $this->formatearFecha($registro['fecha']);
        }

        // 5. RETORNO DE DATOS ESTRUCTURADOS
        return [
            'data' => $registros,
            'total_records' => (int)$totals['total_records'],
            'total_anulado' => (float)$totals['total_anulado']
        ];
    }

    /**
     * Obtiene el reporte de sports y carreras para una agencia específica.
     * @param int $idAgencia ID de la agencia.
     * @param array $filtros Filtros de fecha.
     * @return array
     */
    public function getSportsCarrerasAgencia($idAgencia, $filtros = [])
    {
        // Debug: Log parámetros de entrada
        error_log("DEBUG getSportsCarrerasAgencia - ID Agencia: " . $idAgencia . ", Filtros: " . json_encode($filtros));
        
        $params = [];
        $whereClauses = ["c.id_estado = 3"]; // Solo carreras finalizadas

        // Filtros de fecha
        if (!empty($filtros['fecha_desde'])) {
            $whereClauses[] = "STR_TO_DATE(c.fecha, '%d/%m/%Y') >= STR_TO_DATE(:fecha_desde, '%Y-%m-%d')";
            $params[':fecha_desde'] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $whereClauses[] = "STR_TO_DATE(c.fecha, '%d/%m/%Y') <= STR_TO_DATE(:fecha_hasta, '%Y-%m-%d')";
            $params[':fecha_hasta'] = $filtros['fecha_hasta'];
        }

        $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);

        // Debug: Log consulta SQL
        $query = "
            SELECT 
                c.id_carrera,
                c.fecha, 
                c.numero_carrera, 
                h.nombre_hipodromo,
                c.empate_puesto,
                ec.estado_carrera
            FROM tbl_carreras c
            INNER JOIN tbl_hipodromos h ON c.id_hipodromo = h.id_hipodromo
            INNER JOIN tbl_estados_carreras ec ON c.id_estado = ec.id_estado
            {$whereSql}
            ORDER BY h.nombre_hipodromo, c.numero_carrera ASC
        ";
        
        error_log("DEBUG getSportsCarrerasAgencia - Query: " . $query);
        error_log("DEBUG getSportsCarrerasAgencia - Params: " . json_encode($params));

        try {
            $carreras = $this->db->query($query, 'agencias', $params)->fetchAll(PDO::FETCH_ASSOC);
            error_log("DEBUG getSportsCarrerasAgencia - Carreras encontradas: " . count($carreras));
            
            if (empty($carreras)) {
                return ['carreras' => []];
            }

            // Obtener resultados de caballos para las carreras encontradas
            $carreraIds = array_column($carreras, 'id_carrera');
            $placeholders = str_repeat('?,', count($carreraIds) - 1) . '?';

            $queryResultados = "
                SELECT 
                    cs.id_carrera,
                    cs.id_caballo,
                    cs.posicion_llegada,
                    cs.sport_ganador,
                    cs.sport_segundo,
                    cs.sport_tercero
                FROM tbl_caballos_sports cs
                WHERE cs.id_carrera IN ({$placeholders})
                ORDER BY cs.id_carrera, cs.posicion_llegada ASC
            ";

            $resultados = $this->db->query($queryResultados, 'agencias', $carreraIds)->fetchAll(PDO::FETCH_ASSOC);
            error_log("DEBUG getSportsCarrerasAgencia - Resultados encontrados: " . count($resultados));

            // Organizar resultados por carrera
            $resultadosPorCarrera = [];
            foreach ($resultados as $resultado) {
                $resultadosPorCarrera[$resultado['id_carrera']][] = $resultado;
            }

            // Combinar datos
            foreach ($carreras as &$carrera) {
                $idCarrera = $carrera['id_carrera'];
                $carrera['resultados_caballos'] = $resultadosPorCarrera[$idCarrera] ?? [];
            }

            return ['carreras' => $carreras];
            
        } catch (Exception $e) {
            error_log("DEBUG getSportsCarrerasAgencia - Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtiene el reporte de tickets con devoluciones para una agencia específica.
     * @param int $idAgencia ID de la agencia.
     * @param array $filtros Filtros de fecha.
     * @return array
     */
    public function getTicketsDevolucionesAgencia($idAgencia, $filtros = [])
    {
        $params = [':id_agencia' => $idAgencia];
        $whereClauses = ["u.id_agencia = :id_agencia"];

        if (!empty($filtros['fecha_desde'])) {
            $whereClauses[] = "STR_TO_DATE(t.fecha, '%d/%m/%Y') >= STR_TO_DATE(:fecha_desde, '%Y-%m-%d')";
            $params[':fecha_desde'] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $whereClauses[] = "STR_TO_DATE(t.fecha, '%d/%m/%Y') <= STR_TO_DATE(:fecha_hasta, '%Y-%m-%d')";
            $params[':fecha_hasta'] = $filtros['fecha_hasta'];
        }

        $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);

        $query = "
            SELECT t.nro_ticket, t.fecha, dt.total_apostado
            FROM tbl_tickets_devoluciones td
            JOIN tbl_tickets t ON td.id_ticket = t.id_ticket
            JOIN tbl_detalle_tickets dt ON t.id_ticket = dt.id_ticket
            JOIN tbl_usuarios u ON t.id_usuario = u.id_usuario
            {$whereSql}
            ORDER BY STR_TO_DATE(t.fecha, '%d/%m/%Y') DESC
        ";
        
        $data = $this->db->query($query, 'agencias', $params)->fetchAll(PDO::FETCH_ASSOC);
        return ['data' => $data];
    }

    /**
     * Obtiene el reporte de ventas diarias para una agencia específica.
     * @param int $idAgencia ID de la agencia.
     * @param array $filtros Filtros de fecha.
     * @return array
     */
    public function getVentasDiariasAgencia(int $idAgencia, array $filtros = []): array
    {
        $params = [':id_agencia' => $idAgencia];
        $whereClauses = ["u.id_agencia = :id_agencia"];

        if (!empty($filtros['fecha_desde'])) {
            $whereClauses[] = "STR_TO_DATE(t.fecha, '%d/%m/%Y') >= STR_TO_DATE(:fecha_desde, '%Y-%m-%d')";
            $params[':fecha_desde'] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $whereClauses[] = "STR_TO_DATE(t.fecha, '%d/%m/%Y') <= STR_TO_DATE(:fecha_hasta, '%Y-%m-%d')";
            $params[':fecha_hasta'] = $filtros['fecha_hasta'];
        }

        $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);

        $sql = "
            SELECT 
                t.nro_ticket,
                SUM(dt.total_premio) AS total_premio,
                COALESCE((
                    SELECT SUM(dt_ret.total_apostado) 
                    FROM tbl_caballos_retirados_um_detalle crd 
                    JOIN tbl_detalle_tickets dt_ret ON crd.id_detalle_ticket = dt_ret.id_detalle_ticket 
                    WHERE dt_ret.id_ticket = t.id_ticket AND crd.devolucion = 1
                ), 0) AS devoluciones,
                SUM(dt.total_apostado) AS total_ticket
            FROM tbl_tickets t
            JOIN tbl_usuarios u ON t.id_usuario = u.id_usuario
            LEFT JOIN tbl_detalle_tickets dt ON t.id_ticket = dt.id_ticket
            {$whereSql}
            GROUP BY t.id_ticket, t.nro_ticket
            HAVING NOT (
                (total_premio IS NULL OR total_premio = 0) AND 
                devoluciones = 0 AND 
                (total_ticket IS NULL OR total_ticket = 0)
            )
            ORDER BY t.nro_ticket ASC
        ";

        $listado = $this->db->query($sql, 'agencias', $params)->fetchAll(\PDO::FETCH_ASSOC);

        // Calcular totales para las cartas
        $sqlTotales = "
            SELECT 
                SUM(dt.total_apostado) as total_vendidos,
                SUM(CASE WHEN dt.premio = 'si' THEN dt.total_premio ELSE 0 END) as total_ganadores,
                SUM(CASE WHEN dt.premio = 'si' AND LOWER(TRIM(t.pagado)) = 'si' THEN dt.total_premio ELSE 0 END) as total_pagados
            FROM tbl_tickets t
            JOIN tbl_usuarios u ON t.id_usuario = u.id_usuario
            LEFT JOIN tbl_detalle_tickets dt ON t.id_ticket = dt.id_ticket
            {$whereSql}
        ";
        
        // Calcular devoluciones usando caballos retirados último momento (devolucion = 1)
        $sqlDevoluciones = "
            SELECT COALESCE(SUM(dt.total_apostado), 0) as total_devoluciones
            FROM tbl_caballos_retirados_um_detalle crd
            JOIN tbl_detalle_tickets dt ON crd.id_detalle_ticket = dt.id_detalle_ticket
            JOIN tbl_tickets t ON dt.id_ticket = t.id_ticket
            JOIN tbl_usuarios u ON t.id_usuario = u.id_usuario
            WHERE crd.devolucion = 1
            " . str_replace('WHERE', 'AND', $whereSql) . "
        ";

        $totales = $this->db->query($sqlTotales, 'agencias', $params)->fetch(\PDO::FETCH_ASSOC);
        $devoluciones = $this->db->query($sqlDevoluciones, 'agencias', $params)->fetch(\PDO::FETCH_ASSOC);
        
        // Combinar resultados
        $totales['total_devoluciones'] = $devoluciones['total_devoluciones'];

        return [
            'listado' => $listado,
            'totales' => $totales
        ];
    }

    /**
     * Obtiene el reporte de tickets anulados para una agencia específica.
     * @param int $idAgencia ID de la agencia.
     * @param array $filtros Filtros de fecha.
     * @return array
     */
    public function getTicketsAnuladosAgencia($idAgencia, $filtros = [])
    {
        $params = [':id_agencia' => $idAgencia];
        $whereClauses = ["u.id_agencia = :id_agencia", "t.anulado = 1"];

        if (!empty($filtros['fecha_desde'])) {
            $whereClauses[] = "STR_TO_DATE(t.fecha, '%d/%m/%Y') >= STR_TO_DATE(:fecha_desde, '%Y-%m-%d')";
            $params[':fecha_desde'] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $whereClauses[] = "STR_TO_DATE(t.fecha, '%d/%m/%Y') <= STR_TO_DATE(:fecha_hasta, '%Y-%m-%d')";
            $params[':fecha_hasta'] = $filtros['fecha_hasta'];
        }

        $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);

        $query = "
            SELECT t.nro_ticket, t.fecha, t.hora, u.nombre_usuario, SUM(dt.total_apostado) as total_apostado
            FROM tbl_tickets t
            JOIN tbl_usuarios u ON t.id_usuario = u.id_usuario
            JOIN tbl_detalle_tickets dt ON t.id_ticket = dt.id_ticket
            {$whereSql}
            GROUP BY t.id_ticket, t.nro_ticket, t.fecha, t.hora, u.nombre_usuario
            ORDER BY STR_TO_DATE(t.fecha, '%d/%m/%Y') DESC, t.hora DESC
        ";
        
        $data = $this->db->query($query, 'agencias', $params)->fetchAll(PDO::FETCH_ASSOC);
        return ['data' => $data];
    }
}
