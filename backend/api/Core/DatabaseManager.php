<?php

namespace TurfReports\Core;

use PDO;
use PDOException;

/**
 * Gestor de conexiones a múltiples bases de datos.
 * Orquestador que maneja conexiones independientes para agencias y appweb.
 */
class DatabaseManager
{
    private static $instance = null;
    private $connections = [];

    private function __construct()
    {
        // Inicializar conexiones disponibles
        $this->initializeConnections();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Inicializa las conexiones a las diferentes bases de datos
     */
    private function initializeConnections()
    {
        // Conexión para base de datos de agencias
        $this->connections['agencias'] = $this->createConnection([
            'host' => $_ENV['DB_AGENCIAS_HOST'],
            'port' => $_ENV['DB_AGENCIAS_PORT'] ?? 3306,
            'dbname' => $_ENV['DB_AGENCIAS_NAME'],
            'username' => $_ENV['DB_AGENCIAS_USER'],
            'password' => $_ENV['DB_AGENCIAS_PASS']
        ]);

        // Conexión para base de datos de appweb
        $this->connections['appweb'] = $this->createConnection([
            'host' => $_ENV['DB_APPWEB_HOST'],
            'port' => $_ENV['DB_APPWEB_PORT'] ?? 3306,
            'dbname' => $_ENV['DB_APPWEB_NAME'],
            'username' => $_ENV['DB_APPWEB_USER'],
            'password' => $_ENV['DB_APPWEB_PASS']
        ]);
    }

    /**
     * Crea una conexión PDO con los parámetros especificados
     */
    private function createConnection($config)
    {
        try {
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset=utf8mb4";
            
            return new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            throw new \Exception("Error de conexión a {$config['dbname']}: " . $e->getMessage());
        }
    }

    /**
     * Ejecuta una consulta en la base de datos especificada
     */
    public function query($sql, $database = 'agencias', $params = [])
    {
        if (!isset($this->connections[$database])) {
            throw new \Exception("Base de datos '{$database}' no configurada");
        }

        try {
            $stmt = $this->connections[$database]->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new \Exception("Error en consulta ({$database}): " . $e->getMessage());
        }
    }

    /**
     * Obtiene la conexión específica para una base de datos
     */
    public function getConnection($database = 'agencias')
    {
        if (!isset($this->connections[$database])) {
            throw new \Exception("Base de datos '{$database}' no configurada");
        }
        return $this->connections[$database];
    }

    /**
     * Cierra todas las conexiones activas.
     */
    public function closeConnections()
    {
        $this->connections = [];
    }
}
