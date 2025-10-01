<?php

namespace TurfReports\Core;

use TurfReports\Core\DatabaseManager;

class Auth
{
    private $db;
    private $sessionLifetime;

    const ROLE_ADMIN = 'admin';
    const ROLE_AGENCIA = 'agencia';

    public function __construct()
    {
        $this->db = DatabaseManager::getInstance();
        $this->sessionLifetime = $_ENV['SESSION_LIFETIME'] ?? 120; // minutos

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Configuración de sesión para producción
        // La autenticación real se maneja a través del método iniciarSesion()
    }

    /**
     * Inicia sesión del usuario validando credenciales contra la base de datos
     * @param string $nombreUsuario Nombre de usuario
     * @param string $contrasena Contraseña del usuario
     * @param bool $recordar Si debe recordar la sesión con cookie
     * @return array Resultado del login con éxito/error
     */
    public function iniciarSesion($nombreUsuario, $contrasena, $recordar = false)
    {
        try {
            // Validar credenciales contra la base de datos real
            $consulta = "SELECT id_usuario, nombre_usuario, login, contrasena, id_perfil, id_agencia 
                        FROM tbl_usuarios 
                        WHERE login = :login";
            
            $stmt = $this->db->query($consulta, 'agencias', [
                'login' => $nombreUsuario
            ]);
            
            $usuario = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            // Debug: Mostrar información de la consulta y validación MD5
            error_log('DEBUG Login - Usuario encontrado: ' . ($usuario ? 'SÍ' : 'NO'));
            if ($usuario) {
                $contrasenaHasheada = md5($contrasena);
                error_log('DEBUG Login - Login BD: ' . $usuario['login']);
                error_log('DEBUG Login - Login enviado: ' . $nombreUsuario);
                error_log('DEBUG Login - Contraseña BD: ' . $usuario['contrasena']);
                error_log('DEBUG Login - Contraseña enviada: ' . $contrasena);
                error_log('DEBUG Login - Contraseña MD5: ' . $contrasenaHasheada);
                error_log('DEBUG Login - Coinciden MD5: ' . ($usuario['contrasena'] === $contrasenaHasheada ? 'SÍ' : 'NO'));
                error_log('DEBUG Login - id_perfil: ' . $usuario['id_perfil'] . ' (tipo: ' . gettype($usuario['id_perfil']) . ')');
            }
            
            // Verificar si existe el usuario y la contraseña coincide (usando MD5)
            $contrasenaHasheada = md5($contrasena);
            if (!$usuario || $usuario['contrasena'] !== $contrasenaHasheada) {
                return [
                    'exito' => false,
                    'mensaje' => 'Credenciales inválidas'
                ];
            }
            
            // Determinar el rol basado en id_perfil (1=admin, 2=agencia)
            // Usar comparación estricta para evitar problemas de tipos
            $rol = ($usuario['id_perfil'] === 1 || $usuario['id_perfil'] === '1') 
                ? self::ROLE_ADMIN 
                : self::ROLE_AGENCIA;
            
            error_log('DEBUG Login - Rol asignado: ' . $rol);
            
            // Crear sesión del usuario autenticado
            $_SESSION['user_id'] = $usuario['id_usuario'];
            $_SESSION['username'] = $usuario['nombre_usuario'];
            $_SESSION['login'] = $usuario['login'];
            $_SESSION['role'] = $rol;
            $_SESSION['agencia_id'] = $usuario['id_agencia'];
                        $_SESSION['id_perfil'] = $usuario['id_perfil'];
            $_SESSION['login_time'] = time();
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            
            // Configurar cookie de recordar si se solicita
            if ($recordar) {
                $token = bin2hex(random_bytes(32));
                setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
                
                // Guardar token hasheado en base de datos
                $consultaToken = "UPDATE tbl_usuarios SET remember_token = :token WHERE id_usuario = :id";
                $this->db->query($consultaToken, 'agencias', [
                    'token' => hash('sha256', $token),
                    'id' => $usuario['id_usuario']
                ]);
            }
            
            return [
                'exito' => true,
                'usuario' => [
                    'id' => $usuario['id_usuario'],
                    'username' => $usuario['nombre_usuario'],
                    'login' => $usuario['login'],
                    'role' => $rol,
                    'agencia_id' => $usuario['id_agencia'],
                                        'id_perfil' => $usuario['id_perfil']
                ],
                'csrf_token' => $_SESSION['csrf_token']
            ];
            
        } catch (\Exception $e) {
            error_log('Error en iniciarSesion: ' . $e->getMessage());
            return [
                'exito' => false,
                'mensaje' => 'Error interno del servidor'
            ];
        }
    }

    /**
     * Método de compatibilidad para mantener la API existente
     */
    public function login($username, $password, $remember = false)
    {
        return $this->iniciarSesion($username, $password, $remember);
    }

    /**
     * Verifica si el usuario actual está autenticado
     * @return bool True si está autenticado, false en caso contrario
     */
    public function estaAutenticado()
    {
        // Verificar si existe sesión activa
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['login_time'])) {
            return false;
        }
        
        // Verificar si la sesión no ha expirado
        $tiempoTranscurrido = (time() - $_SESSION['login_time']) / 60; // en minutos
        if ($tiempoTranscurrido > $this->sessionLifetime) {
            $this->cerrarSesion();
            return false;
        }
        
        return true;
    }

    /**
     * Método de compatibilidad para mantener la API existente
     */
    public function isAuthenticated()
    {
        return $this->estaAutenticado();
    }

    /**
     * Verifica si el usuario actual tiene un permiso específico
     * @param string $permiso Nombre del permiso a verificar
     * @return bool True si tiene el permiso, false en caso contrario
     */
    public function tienePermiso($permiso)
    {
        if (!$this->estaAutenticado()) {
            return false;
        }
        
        $rol = $_SESSION['role'] ?? '';
        $permisos = $this->obtenerPermisosDelRol($rol);
        return in_array($permiso, $permisos);
    }

    /**
     * Método de compatibilidad para mantener la API existente
     */
    public function hasPermission($permission)
    {
        return $this->tienePermiso($permission);
    }

    /**
     * Obtiene los permisos disponibles para un rol específico
     * @param string $rol Rol del usuario (admin o agencia)
     * @return array Lista de permisos del rol
     */
    private function obtenerPermisosDelRol($rol)
    {
        $permisos = [
            self::ROLE_ADMIN => [
                'view_all_reports', 'manage_races', 'manage_users',
                'view_audit_logs', 'export_reports', 'change_race_status',
                'view_cancelled_tickets', 'view_agency_reports', 'view_daily_sales',
                'view_refunds', 'view_sports_races', 'ver_todos_reportes', 
                'gestionar_carreras', 'gestionar_usuarios', 'ver_logs_auditoria', 
                'exportar_reportes', 'cambiar_estado_carrera', 'ver_tickets_cancelados', 
                'ver_reportes_agencias', 'ver_ventas_diarias', 'ver_reembolsos', 
                'ver_carreras_deportivas'
            ],
            self::ROLE_AGENCIA => [
                'view_own_reports', 'view_daily_sales', 'view_cancelled_tickets',
                'view_refunds', 'view_sports_races', 'export_own_reports',
                'ver_reportes_propios', 'ver_ventas_diarias', 'ver_tickets_cancelados',
                'ver_reembolsos', 'ver_carreras_deportivas', 'exportar_reportes_propios'
            ]
        ];
        return $permisos[$rol] ?? [];
    }

    /**
     * Método de compatibilidad para mantener la API existente
     */
    private function getRolePermissions($role)
    {
        return $this->obtenerPermisosDelRol($role);
    }

    /**
     * Requiere que el usuario esté autenticado, sino devuelve error 401
     */
    public function requerirAutenticacion()
    {
        if (!$this->estaAutenticado()) {
            http_response_code(401);
            echo json_encode(['error' => 'No autenticado']);
            exit;
        }
    }

    /**
     * Método de compatibilidad para mantener la API existente
     */
    public function requireAuth()
    {
        $this->requerirAutenticacion();
    }

    /**
     * Requiere que el usuario tenga un permiso específico, sino devuelve error 403
     * @param string $permiso Nombre del permiso requerido
     */
    public function requerirPermiso($permiso)
    {
        if (!$this->tienePermiso($permiso)) {
            http_response_code(403);
            echo json_encode(['error' => 'Sin permisos suficientes']);
            exit;
        }
    }

    /**
     * Método de compatibilidad para mantener la API existente
     */
    public function requirePermission($permission)
    {
        $this->requerirPermiso($permission);
    }

    /**
     * Cierra la sesión del usuario actual
     * Destruye la sesión y limpia las cookies de recordar
     */
    public function cerrarSesion()
    {
        // Limpiar cookie de recordar si existe
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', false, true);
            
            // Limpiar token de la base de datos si el usuario está autenticado
            if (isset($_SESSION['user_id'])) {
                try {
                    $consulta = "UPDATE tbl_usuarios SET remember_token = NULL WHERE id_usuario = :id";
                    $this->db->query($consulta, 'agencias', ['id' => $_SESSION['user_id']]);
                } catch (\Exception $e) {
                    error_log('Error al limpiar token de recordar: ' . $e->getMessage());
                }
            }
        }
        
        // Destruir sesión
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
        
        // Iniciar nueva sesión limpia
        session_start();
    }

    /**
     * Método de compatibilidad para mantener la API existente
     */
    public function logout()
    {
        $this->cerrarSesion();
    }

    /**
     * Obtiene el token CSRF actual
     * @return string Token CSRF
     */
    public function obtenerTokenCsrf()
    {
        return $_SESSION['csrf_token'] ?? '';
    }

    /**
     * Método de compatibilidad para mantener la API existente
     */
    public function getCsrfToken()
    {
        return $this->obtenerTokenCsrf();
    }

    /**
     * Valida el token CSRF recibido contra el almacenado en sesión
     * @param string $token
     * @return bool
     */
    public function validateCsrfToken($token)
    {
        if (!isset($_SESSION['csrf_token'])) return false;
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}
