<?php

namespace App\Core;

/**
 * SessionManager - Singleton pour la gestion sécurisée des sessions
 * Gère l'état utilisateur, l'authentification et les messages flash
 * Conformité : 2.2.1 Composants Fondamentaux
 */
class SessionManager {
    private static ?self $instance = null;

    /**
     * Constructeur privé (Pattern Singleton)
     * Initialise la session PHP de manière sécurisée si elle n'est pas déjà active
     */
    private function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Point d'accès unique à l'instance (Lazy Loading)
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Stocke une donnée en session (Auth, Thème, Flash messages)
     */
    public function set(string $key, mixed $value): void { 
        $_SESSION[$key] = $value; 
    }

    /**
     * Récupère une donnée de session avec valeur par défaut optionnelle
     */
    public function get(string $key, mixed $default = null): mixed { 
        return $_SESSION[$key] ?? $default; 
    }

    /**
     * Vérifie si une clé existe en session
     */
    public function has(string $key): bool { 
        return isset($_SESSION[$key]); 
    }

    /**
     * Supprime une clé spécifique de la session
     */
    public function remove(string $key): void { 
        unset($_SESSION[$key]); 
    }

    /**
     * Détruit complètement la session (Logout sécurisé)
     * Supprime les données, le cookie client et invalide l'instance
     */
    public function destroy(): void {
        $_SESSION = [];
        
        // Suppression du cookie de session côté navigateur
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
        self::$instance = null; // Invalidation de l'instance pour repartir à neuf
    }

    private function __clone() {}

    public function __wakeup() {
        throw new \Exception("Cannot unserialize a singleton.");
    }
}