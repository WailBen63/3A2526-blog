<?php

namespace App\Core;

use App\Models\UserModel;

/**
 * AuthMiddleware
 * Gère les contrôles d'accès (authentification et autorisations RBAC)
 * Conformité : EF-ACL-05, EF-ACL-06
 */
class AuthMiddleware {
    
    /**
     * Vérifie si l'utilisateur est authentifié
     * Redirige vers la connexion en cas d'échec
     */
    public static function requireAuth(): void {
        $session = SessionManager::getInstance();
        
        if (!$session->get('user_id')) {
            $session->set('flash_error', 'Veuillez vous connecter pour accéder à cette page.');
            header('Location: /3A2526-Blog/public/login');
            exit;
        }
    }

    /**
     * Vérifie si l'utilisateur possède une permission spécifique
     * Retourne une erreur 403 en cas de droits insuffisants
     */
    public static function requirePermission(string $permission): void {
        // Pré-requis : l'utilisateur doit d'abord être connecté
        self::requireAuth();
        
        $session = SessionManager::getInstance();
        $userModel = new UserModel();
        
        // Vérification des droits via le modèle User (système RBAC)
        if (!$userModel->hasPermission($session->get('user_id'), $permission)) {
            http_response_code(403);
            die("Accès refusé. Permission requise : $permission");
        }
    }
}