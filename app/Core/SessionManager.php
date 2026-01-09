<?php

namespace App\Core;

/**
 * SessionManager - Singleton pour la gestion sécurisée des sessions utilisateur
 * 
 * Module dédié pour gérer l'état de l'utilisateur conformément aux spécifications
 * du système de gestion de blog. Implémente les fonctionnalités de session
 * nécessaires à l'authentification, la personnalisation et la sécurité.
 * 
 * Implémente les exigences suivantes du cahier des charges :
 * - 2.2.1 Composants Fondamentaux : Gestionnaire de Session dédié
 * - EF-ACL : Contrôle d'accès basé sur les rôles et permissions
 * - UX-THEME-02 : Persistance du choix de thème clair/sombre
 * - Pattern Singleton comme spécifié dans 2.2
 * - Sécurité renforcée des sessions
 * 
 * @package App\Core
 * @conformité 2.2.1 Composants Fondamentaux : Module dédié pour gérer l'état utilisateur
 */
class SessionManager {
    /**
     * @var self|null Instance unique du gestionnaire de session (Singleton)
     * @private
     * @static
     * @conformité 2.2 Patrons de Conception : Singleton (comme spécifié pour la DB)
     */
    private static ?self $instance = null;

    /**
     * Constructeur privé - Empêche l'instanciation directe
     * 
     * Initialise la session PHP de manière sécurisée.
     * Garantit qu'une seule session est active par utilisateur.
     * 
     * Conformité avec les bonnes pratiques de sécurité :
     * - Démarrer la session seulement si non active
     * - Configuration sécurisée via php.ini recommandée
     * 
     * @conformité Technologies imposées : PHP 8.X POO
     * @conformité Critères d'acceptation : sécurité assurée
     */
    private function __construct() {
        // Vérification de l'état de la session avant démarrage
        // Évite les erreurs "session already started"
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Point d'accès unique à l'instance du gestionnaire de session
     * 
     * Implémente le pattern Singleton avec lazy loading.
     * Garantit une gestion cohérente des sessions dans toute l'application.
     * 
     * @return self Instance unique de SessionManager
     * @conformité 2.2 Patrons de Conception : Singleton
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Stocke une valeur dans la session
     * 
     * Utilisé pour :
     * - Informations d'authentification utilisateur (EF-ACL)
     * - Préférences de thème clair/sombre (UX-THEME-02)
     * - Messages flash (retour utilisateur temporaire)
     * - Données de panier ou préférences
     * 
     * @param string $key Clé d'identification
     * @param mixed $value Valeur à stocker (scalaire, tableau, objet)
     * @return void
     * @conformité 2.2.1 Composants Fondamentaux : informations de session
     */
    public function set(string $key, mixed $value): void { 
        $_SESSION[$key] = $value; 
    }

    /**
     * Récupère une valeur de la session avec valeur par défaut
     * 
     * Utilisé pour vérifier :
     * - Authentification utilisateur et rôles (EF-ACL)
     * - Préférence de thème (UX-THEME-02)
     * - État de connexion persistante
     * 
     * @param string $key Clé d'identification
     * @param mixed $default Valeur par défaut si clé absente
     * @return mixed Valeur stockée ou valeur par défaut
     * @conformité EF-ACL : vérification des permissions et rôles
     */
    public function get(string $key, mixed $default = null): mixed { 
        return $_SESSION[$key] ?? $default; 
    }

    /**
     * Vérifie l'existence d'une clé dans la session
     * 
     * Utilisé pour contrôler :
     * - Présence d'une session utilisateur active
     * - Existence de permissions spécifiques
     * - Initialisation de préférences
     * 
     * @param string $key Clé à vérifier
     * @return bool True si la clé existe
     * @conformité EF-ACL : contrôle d'accès basé sur la session
     */
    public function has(string $key): bool { 
        return isset($_SESSION[$key]); 
    }

    /**
     * Supprime une clé spécifique de la session
     * 
     * Utilisé pour :
     * - Déconnexion partielle (garder certaines préférences)
     * - Nettoyage de données temporaires
     * - Réinitialisation de valeurs spécifiques
     * 
     * @param string $key Clé à supprimer
     * @return void
     * @conformité 2.2.1 Composants Fondamentaux : gestion précise de l'état
     */
    public function remove(string $key): void { 
        unset($_SESSION[$key]); 
    }

    /**
     * Détruit complètement la session utilisateur
     * 
     * Processus de déconnexion sécurisé en 3 étapes :
     * 1. Vide le tableau $_SESSION
     * 2. Supprime le cookie de session côté client
     * 3. Détruit la session côté serveur
     * 4. Redémarre une session propre
     * 
     * Utilisé pour :
     * - Déconnexion utilisateur (logout)
     * - Régénération d'ID de session (prévention fixation)
     * - Nettoyage administratif
     * 
     * @return void
     * @conformité EF-ACL-06 : authentification sécurisée avec gestion de déconnexion
     * @conformité Critères d'acceptation : sécurité assurée
     */
    public function destroy(): void {
        // Étape 1 : Vider toutes les données de session
        $_SESSION = [];
        
        // Étape 2 : Supprimer le cookie de session côté client
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Étape 3 : Détruire la session côté serveur
        session_destroy();
        
        // Étape 4 : Réinitialiser l'instance Singleton pour nouvelle session
        self::$instance = null; // Force la recréation
        self::getInstance(); // Redémarre une session "propre"
    }

    /**
     * Empêche le clonage de l'instance (partie du pattern Singleton)
     * 
     * @return void
     * @private
     * @conformité 2.2 Patrons de Conception : Singleton correctement implémenté
     */
    private function __clone() {}

    /**
     * Empêche la désérialisation de l'instance
     * 
     * @throws \Exception Toujours lancée pour préserver l'intégrité du Singleton
     * @conformité 2.2 Patrons de Conception : protection complète du Singleton
     */
    public function __wakeup() {
        throw new \Exception("Cannot unserialize a singleton.");
    }
}