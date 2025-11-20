<?php
namespace App\Core;

class AuthMiddleware {
    
    public static function requireAuth(): void {
        $session = SessionManager::getInstance();
        
        if (!$session->get('user_id')) {
            header('Location: /3A2526-blog/public/login');
            exit;
        }
    }

    public static function requirePermission(string $permission): void {
        self::requireAuth();
        
        $session = SessionManager::getInstance();
        $userModel = new \App\Models\UserModel();
        
        if (!$userModel->hasPermission($session->get('user_id'), $permission)) {
            http_response_code(403);
            die("Accès refusé. Permission requise: $permission");
        }
    }
}