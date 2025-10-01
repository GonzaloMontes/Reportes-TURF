<?php

namespace TurfReports\Controllers;

use TurfReports\Core\Auth;

/**
 * Controlador de autenticación
 */
class AuthController
{
    private $auth;

    public function __construct()
    {
        $this->auth = new Auth();
    }

    /**
     * POST /api/auth/login
     * Autenticación de usuario
     */
    public function login()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';
        $remember = $input['remember'] ?? false;
        
        if (empty($username) || empty($password)) {
            http_response_code(400);
            echo json_encode(['error' => 'Usuario y contraseña requeridos']);
            return;
        }
        
        $result = $this->auth->login($username, $password, $remember);
        
        if ($result['exito']) {
            echo json_encode([
                'success' => true,
                'user' => $result['usuario'],
                'csrf_token' => $this->auth->obtenerTokenCsrf()
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => $result['mensaje']]);
        }
    }

    /**
     * POST /api/auth/logout
     * Cerrar sesión
     */
    public function logout()
    {
        $this->auth->logout();
        echo json_encode(['success' => true]);
    }

    /**
     * GET /api/auth/status
     * Devuelve el estado de la sesión del usuario actual (autenticado, rol, permisos).
     */
    public function status()
    {
        if (!$this->auth->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['error' => 'No autenticado']);
            return;
        }
        
        echo json_encode([
            'user' => [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'role' => $_SESSION['role'],
                'agencia_id' => $_SESSION['agencia_id']
            ],
            'permissions' => $this->getUserPermissions()
        ]);
    }

    /**
     * GET /api/auth/csrf-token
     * Obtener token CSRF
     */
    public function getCsrfToken()
    {
        if (!$this->auth->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['error' => 'No autenticado']);
            return;
        }
        
        echo json_encode(['csrf_token' => $this->auth->getCsrfToken()]);
    }

    /**
     * Obtiene permisos del usuario actual
     */
    private function getUserPermissions()
    {
        $role = $_SESSION['role'] ?? '';
        
        $allPermissions = [
            'view_all_reports',
            'view_own_reports',
            'manage_races',
            'manage_users',
            'view_audit_logs',
            'export_reports',
            'export_own_reports',
            'change_race_status',
            'view_cancelled_tickets',
            'view_agency_reports',
            'view_daily_sales',
            'view_refunds',
            'view_sports_races'
        ];
        
        $userPermissions = [];
        foreach ($allPermissions as $permission) {
            if ($this->auth->hasPermission($permission)) {
                $userPermissions[] = $permission;
            }
        }
        
        return $userPermissions;
    }
}
