<?php

namespace App\Core;

/**
 * AuthMiddleware
 * 
 * Middleware d'authentification et d'autorisation pour le contrôle d'accès.
 * Implémente le pattern Middleware pour sécuriser les routes et actions.
 * Fournit deux niveaux de protection : authentification et permissions.
 * 
 * Conformité avec les exigences :
 * - EF-ACL-05 : Contrôle d'accès basé sur les permissions
 * - EF-ACL-06 : Système d'authentification sécurisé
 * - Séparation des préoccupations : Middleware pattern
 * 
 * @package App\Core
 */
class AuthMiddleware {
    
    /**
     * Vérifie l'authentification de l'utilisateur (Middleware de base)
     * 
     * Vérifie si l'utilisateur est connecté en vérifiant la présence de user_id en session.
     * Si non authentifié :
     * - Stocke un message d'erreur en session
     * - Redirige vers la page de connexion
     * - Arrête l'exécution du script
     * 
     * Utilisation typique :
     * ```php
     * AuthMiddleware::requireAuth();
     * // Le code suivant ne s'exécute que si l'utilisateur est authentifié
     * ```
     * 
     * @return void
     * @throws void (Redirige plutôt que de lancer une exception)
     */
    public static function requireAuth(): void {
        // Récupération de l'instance singleton du gestionnaire de session
        $session = SessionManager::getInstance();
        
        // Vérification de la présence d'un ID utilisateur en session
        if (!$session->get('user_id')) {
            // Utilisateur non authentifié → préparation du feedback
            $session->set('flash_error', 'Veuillez vous connecter pour accéder à cette page.');
            
            // Redirection vers la page de connexion
            header('Location: /3A2526-Blog/public/login');
            exit; // Arrêt immédiat de l'exécution
        }
        
        // Si on arrive ici, l'utilisateur est authentifié
        // Le contrôleur ou l'action peut continuer normalement
    }

    /**
     * Vérifie une permission spécifique (Middleware avancé - RBAC)
     * 
     * Vérifie deux niveaux de sécurité :
     * 1. L'utilisateur est-il authentifié ? (appelle requireAuth())
     * 2. L'utilisateur a-t-il la permission requise ?
     * 
     * Si la permission est refusée :
     * - Retourne une erreur HTTP 403 (Forbidden)
     * - Affiche un message d'erreur explicite
     * - Arrête l'exécution du script
     * 
     * Utilisation typique :
     * ```php
     * AuthMiddleware::requirePermission('article_creer');
     * // Le code suivant ne s'exécute que si l'utilisateur a la permission
     * ```
     * 
     * @param string $permission Nom de la permission requise (ex: 'article_creer')
     * @return void
     * @throws void (Retourne HTTP 403 plutôt que de lancer une exception)
     */
    public static function requirePermission(string $permission): void {
        // Étape 1 : Vérification de l'authentification (pré-requis)
        self::requireAuth(); // Si échec, redirige vers /login
        
        // Si on arrive ici, l'utilisateur est authentifié
        // Récupération des instances nécessaires
        $session = SessionManager::getInstance();
        $userModel = new \App\Models\UserModel();
        
        // Étape 2 : Vérification de la permission spécifique
        // Interroge le modèle UserModel pour vérifier la permission
        if (!$userModel->hasPermission($session->get('user_id'), $permission)) {
            // Permission refusée → erreur HTTP 403 (Forbidden)
            http_response_code(403);
            
            // Message d'erreur explicite (peut être personnalisé pour la production)
            die("Accès refusé. Permission requise: $permission");
            
        
        }
        
        
    }
}