<?php

namespace TurfReports\Services;

use TurfReports\Services\ReportService;
use TurfReports\Core\DatabaseManager;

/**
 * Servicio de exportación a CSV, XLSX y PDF
 */
class ExportService
{
    private $reportService;
    private $exportDir;

    public function __construct()
    {
        $this->reportService = new ReportService();
        $this->exportDir = __DIR__ . '/../../storage/exports/';
        
        // Verificar y crear el directorio si no existe
        if (!is_dir($this->exportDir)) {
            error_log("ExportService: Creando directorio {$this->exportDir}");
            if (!mkdir($this->exportDir, 0755, true)) {
                error_log("ExportService: ERROR al crear directorio {$this->exportDir}");
            } else {
                error_log("ExportService: Directorio creado {$this->exportDir}");
                // Asegurar permisos correctos
                chmod($this->exportDir, 0755);
            }
        } else {
            error_log("ExportService: Directorio ya existe {$this->exportDir}");
            if (!is_writable($this->exportDir)) {
                error_log("ExportService: Directorio NO tiene permisos de escritura");
                chmod($this->exportDir, 0755);
                error_log("ExportService: Intentando asignar permisos 755 al directorio");
            } else {
                error_log("ExportService: Directorio tiene permisos de escritura");
            }
        }
    }

    /**
     * Exporta reporte en formato especificado
     */
    public function exportReport($reportType, $format, $filters = [], $agenciaId = null)
    {
        error_log("ExportReport: Iniciando exportación reportType={$reportType}, format={$format}, agenciaId={$agenciaId}");
        
        // Validar que el directorio existe
        if (!is_dir($this->exportDir) || !is_writable($this->exportDir)) {
            error_log("ExportReport: ERROR - Directorio no existe o no es escribible: {$this->exportDir}");
            // Intentar crear/arreglar
            if (!is_dir($this->exportDir)) {
                mkdir($this->exportDir, 0755, true);
                error_log("ExportReport: Creado directorio: {$this->exportDir}");
            }
            if (!is_writable($this->exportDir)) {
                chmod($this->exportDir, 0755);
                error_log("ExportReport: Ajustados permisos: {$this->exportDir}");
            }
        }
        
        $data = $this->getReportData($reportType, $filters, $agenciaId);
        error_log("ExportReport: Obtenidos datos para exportar, registros: " . count($data));
        
        $filename = $this->generateFilename($reportType, $format, $agenciaId);
        error_log("ExportReport: Nombre archivo generado: {$filename}");
        
        $filepath = null;
        try {
            switch ($format) {
                case 'csv':
                    $filepath = $this->exportToCsv($data, $filename);
                    break;
                case 'xlsx':
                    $filepath = $this->exportToXlsx($data, $filename);
                    break;
                case 'pdf':
                    $filepath = $this->exportToPdf($data, $filename, $reportType);
                    break;
                default:
                    throw new \Exception('Formato no soportado: ' . $format);
            }
            
            // Verificar que el archivo realmente se creó
            error_log("ExportReport: Ruta de archivo generada: {$filepath}");
            if (file_exists($filepath)) {
                error_log("ExportReport: ✅ Archivo confirmado existente: {$filepath}");
                error_log("ExportReport: ℹ️ Tamaño archivo: " . filesize($filepath) . " bytes");
            } else {
                error_log("ExportReport: ❌ ERROR - El archivo NO existe: {$filepath}");
            }
            
            return $filepath;
            
        } catch (\Exception $e) {
            error_log("ExportReport: ❌ ERROR - Excepción al exportar: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtiene los datos del reporte específico para exportar, según el tipo y rol
     * 
     * @param string $reportType Tipo de reporte (ventas_diarias, tickets_anulados, etc)
     * @param array $filters Filtros aplicados al reporte
     * @param int|null $agenciaId ID de agencia si aplica, o null para admin
     * @return array Datos planos listos para exportar
     * @throws \Exception Si el tipo de reporte no es válido o soportado
     */
    private function getReportData($reportType, $filters, $agenciaId)
    {
        // Remover límites para exportación completa (queremos todos los datos)
        unset($filters['limit'], $filters['offset']);
        error_log("DEBUG - getReportData: Obteniendo datos para reporte {$reportType}, agenciaId={$agenciaId}");
        
        // Determinar si estamos en contexto de admin o agencia
        $esAdmin = $agenciaId === null;
        
        switch ($reportType) {
            // ======== REPORTES DE AGENCIA ========
            case 'ventas_diarias':
                if (!$esAdmin && $agenciaId) {
                    $respVD = $this->reportService->getVentasDiariasAgencia((int)$agenciaId, $filters);
                    return $respVD['listado'] ?? [];
                }
                break;
                
            case 'tickets_anulados':
                if (!$esAdmin && $agenciaId) {
                    // Para agencia: usa getTicketsAnuladosAgencia
                    $respTA = $this->reportService->getTicketsAnuladosAgencia((int)$agenciaId, $filters);
                    return $respTA['data'] ?? [];
                } else {
                    // Para admin: usa getTicketsAnulados general
                    $respTA = $this->reportService->getTicketsAnulados($filters);
                    return $respTA['data'] ?? [];
                }
                
            case 'tickets_devoluciones':
                if (!$esAdmin && $agenciaId) {
                    $respTD = $this->reportService->getTicketsDevolucionesAgencia((int)$agenciaId, $filters);
                    return $respTD['data'] ?? [];
                }
                break;
                
            case 'sports_carreras':
                if (!$esAdmin && $agenciaId) {
                    $respSC = $this->reportService->getSportsCarrerasAgencia((int)$agenciaId, $filters);
                    return $respSC['carreras'] ?? [];
                }
                break;
                
            // ======== REPORTES DE ADMIN ========
            case 'informe_agencias':
                if ($esAdmin) {
                    $respIA = $this->reportService->getInformePorAgencia($filters);
                    // Contiene agencias[] y totales[], combinamos en un solo array
                    return $respIA['agencias'] ?? [];
                }
                break;
                
            case 'ventas_tickets':
                if ($esAdmin) {
                    // Lista de tickets con datos detallados - Solo admin puede exportar todos los tickets
                    // getListaTickets solo devuelve totales, necesitamos obtener la lista detallada
                    $tickets = $this->getVentasTicketsData($filters);
                    error_log("DEBUG - ExportService: Exportando ventas_tickets, tickets obtenidos: ". count($tickets));
                    return $tickets;
                }
                break;
                
            case 'caballos_retirados':
                $respCR = $this->reportService->getCaballosRetirados($filters);
                return $respCR['data'] ?? [];
                
            case 'carreras':
                if ($esAdmin) {
                    $respC = $this->reportService->getCarreras($filters);
                    return $respC['data'] ?? [];
                }
                break;
                
            default:
                error_log("ERROR - getReportData: Tipo de reporte no válido: {$reportType}");
                throw new \Exception('Tipo de reporte no válido o no soportado: ' . $reportType);
        }
        
        // Si llegamos aquí es que el tipo de reporte no era válido para el rol
        error_log("ERROR - getReportData: Tipo de reporte no compatible con rol: {$reportType}");
        throw new \Exception('Tipo de reporte no compatible con tu rol: ' . $reportType);
    }

    /**
     * Exporta a CSV
     */
    private function exportToCsv($data, $filename)
    {
        $filepath = $this->exportDir . $filename;
        $file = fopen($filepath, 'w');
        
        // BOM para UTF-8
        fwrite($file, "\xEF\xBB\xBF");
        
        if (!empty($data)) {
            // Headers
            $headers = array_keys($data[0]);
            fputcsv($file, $headers, ';');
            
            // Data rows
            foreach ($data as $row) {
                fputcsv($file, $row, ';');
            }
        }
        
        fclose($file);
        return $filepath;
    }

    /**
     * Exporta a XLSX (usando biblioteca simple)
     */
    private function exportToXlsx($data, $filename)
    {
        $filepath = $this->exportDir . $filename;
        
        // Crear XML básico para XLSX
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
        $xml .= '<Worksheet ss:Name="Reporte">' . "\n";
        $xml .= '<Table>' . "\n";
        
        if (!empty($data)) {
            // Headers
            $xml .= '<Row>' . "\n";
            foreach (array_keys($data[0]) as $header) {
                $xml .= '<Cell><Data ss:Type="String">' . htmlspecialchars($header) . '</Data></Cell>' . "\n";
            }
            $xml .= '</Row>' . "\n";
            
            // Data rows
            foreach ($data as $row) {
                $xml .= '<Row>' . "\n";
                foreach ($row as $value) {
                    $type = is_numeric($value) ? 'Number' : 'String';
                    $xml .= '<Cell><Data ss:Type="' . $type . '">' . htmlspecialchars($value) . '</Data></Cell>' . "\n";
                }
                $xml .= '</Row>' . "\n";
            }
        }
        
        $xml .= '</Table>' . "\n";
        $xml .= '</Worksheet>' . "\n";
        $xml .= '</Workbook>';
        
        file_put_contents($filepath, $xml);
        return $filepath;
    }

    /**
     * Exporta a PDF (usando HTML to PDF simple)
     */
    private function exportToPdf($data, $filename, $reportType)
    {
        $filepath = $this->exportDir . $filename;
        
        $html = $this->generatePdfHtml($data, $reportType);
        
        // Usar wkhtmltopdf si está disponible, sino HTML básico
        if ($this->isWkhtmltopdfAvailable()) {
            $tempHtml = tempnam(sys_get_temp_dir(), 'report_') . '.html';
            file_put_contents($tempHtml, $html);
            
            $command = sprintf(
                'wkhtmltopdf --page-size A4 --orientation Landscape "%s" "%s"',
                $tempHtml,
                $filepath
            );
            
            exec($command, $output, $returnCode);
            unlink($tempHtml);
            
            if ($returnCode !== 0) {
                throw new \Exception('Error generando PDF');
            }
        } else {
            // Fallback: guardar como HTML, pero renombrar como .pdf para que el navegador lo descargue como PDF
            $htmlPath = str_replace('.pdf', '.html', $filepath);
            file_put_contents($htmlPath, $html);
            // Renombrar a .pdf
            rename($htmlPath, $filepath);
        }
        
        return $filepath;
    }

    /**
     * Genera HTML para PDF
     */
    private function generatePdfHtml($data, $reportType)
    {
        $title = $this->getReportTitle($reportType);
        $date = date('d/m/Y H:i');
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . $title . '</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .date { text-align: right; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f5f5f5; font-weight: bold; }
        .number { text-align: right; }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>TURF - ' . $title . '</h1>
        <div class="date">Generado: ' . $date . '</div>
    </div>
    
    <table>';
        
        if (!empty($data)) {
            // Headers
            $html .= '<thead><tr>';
            foreach (array_keys($data[0]) as $header) {
                $html .= '<th>' . htmlspecialchars(ucfirst(str_replace('_', ' ', $header))) . '</th>';
            }
            $html .= '</tr></thead>';
            
            // Data rows
            $html .= '<tbody>';
            foreach ($data as $row) {
                $html .= '<tr>';
                foreach ($row as $key => $value) {
                    $class = is_numeric($value) ? 'number' : '';
                    $html .= '<td class="' . $class . '">' . htmlspecialchars($value) . '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</tbody>';
        }
        
        $html .= '</table>
</body>
</html>';
        
        return $html;
    }

    /**
     * Genera nombre de archivo único
     */
    private function generateFilename($reportType, $format, $agenciaId = null)
    {
        $timestamp = date('Y-m-d_H-i-s');
        $agenciaSuffix = $agenciaId ? '_ag' . $agenciaId : '';
        
        return sprintf(
            '%s%s_%s.%s',
            $reportType,
            $agenciaSuffix,
            $timestamp,
            $format
        );
    }

    /**
     * Obtiene título descriptivo del reporte para mostrar en la exportación
     * 
     * @param string $reportType Tipo de reporte
     * @return string Título formateado del reporte
     */
    private function getReportTitle($reportType)
    {
        // Mapeo completo de todos los tipos de reporte a sus títulos
        $titles = [
            // Reportes de Admin (AppWeb)
            'ventas_tickets' => 'Ventas de Tickets',
            'informe_agencias' => 'Informe por Agencias',
            'caballos_retirados' => 'Caballos Retirados Último Momento',
            'tickets_anulados' => 'Tickets Anulados',
            'carreras' => 'Listado de Carreras',
            
            // Reportes de Agencia
            'ventas_diarias' => 'Ventas Diarias',
            'sports_carreras' => 'Sports y Carreras',
            'tickets_devoluciones' => 'Tickets con Devolución'
        ];
        
        // Si no existe un título específico, devolver un genérico
        $titulo = $titles[$reportType] ?? 'Reporte';
        
        // Log para depurar
        error_log("DEBUG - getReportTitle: Título para {$reportType}: {$titulo}");
        
        return $titulo;
    }

    /**
     * Obtiene datos detallados de tickets para exportación
     * 
     * @param array $filters Filtros a aplicar
     * @return array Lista de tickets con información detallada
     */
    private function getVentasTicketsData($filters)
    {
        $params = [];
        $whereClauses = [];

        // Aplicar filtros de fecha
        if (!empty($filters['fecha_desde'])) {
            $whereClauses[] = "STR_TO_DATE(t.fecha, '%d/%m/%Y') >= :fecha_desde";
            $params[':fecha_desde'] = $filters['fecha_desde'];
        }
        if (!empty($filters['fecha_hasta'])) {
            $whereClauses[] = "STR_TO_DATE(t.fecha, '%d/%m/%Y') <= :fecha_hasta";
            $params[':fecha_hasta'] = $filters['fecha_hasta'];
        }

        // Filtro de agencia (si viene)
        if (!empty($filters['agencia_id'])) {
            $whereClauses[] = "u.id_agencia = :agencia_id";
            $params[':agencia_id'] = $filters['agencia_id'];
        }

        // Construir WHERE
        $whereSql = count($whereClauses) > 0 ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

        // Consulta SQL para datos detallados
        $query = "
            SELECT 
                t.nro_ticket as 'Nro Ticket',
                t.fecha as 'Fecha',
                t.hora as 'Hora',
                a.nombre_agencia as 'Agencia',
                u.nombre_usuario as 'Usuario',
                dt.total_apostado as 'Monto Apostado',
                CASE WHEN dt.premio = 'si' THEN dt.total_premio ELSE 0 END as 'Premio',
                CASE WHEN t.pagado = 'si' THEN 'Pagado' ELSE 'Pendiente' END as 'Estado',
                CASE WHEN t.anulado = 1 THEN 'Si' ELSE 'No' END as 'Anulado'
            FROM 
                tbl_tickets t
            JOIN 
                tbl_detalle_tickets dt ON t.id_ticket = dt.id_ticket
            JOIN
                tbl_usuarios u ON t.id_usuario = u.id_usuario
            JOIN
                tbl_agencias a ON u.id_agencia = a.id_agencia
            {$whereSql}
            ORDER BY 
                STR_TO_DATE(t.fecha, '%d/%m/%Y') DESC, t.hora DESC
        ";

        // Ejecutar consulta
        error_log("DEBUG - getVentasTicketsData: Ejecutando consulta para obtener tickets");
        $db = DatabaseManager::getInstance();
        $stmt = $db->query($query, 'agencias', $params);
        $tickets = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        error_log("DEBUG - getVentasTicketsData: Tickets obtenidos: " . count($tickets));
        
        return $tickets;
    }

    /**
     * Verifica si wkhtmltopdf está disponible
     */
    private function isWkhtmltopdfAvailable()
    {
        exec('wkhtmltopdf --version', $output, $returnCode);
        return $returnCode === 0;
    }
}
