<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * Database - Singleton pour la gestion de la connexion PDO
 * Garantit une instance unique pour toute la durée de vie de l'application
 */
class Database {
    private static ?self $instance = null;
    private PDO $connection;

    // Paramètres de configuration (localhost/XAMPP)
    private string $host = 'localhost';
    private string $db_name = 'blog_db';
    private string $username = 'root';
    private string $password = '';

    /**
     * Constructeur privé pour empêcher l'instanciation directe
     * Configure PDO avec le charset UTF-8 et le mode d'erreur Exception
     */
    private function __construct() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4";
            $this->connection = new PDO($dsn, $this->username, $this->password);
            
            // Configuration des attributs pour la sécurité et la lisibilité
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
            
        } catch (PDOException $e) {
            die('Erreur de connexion : '. $e->getMessage());
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
     * Retourne l'objet PDO pour les requêtes SQL des modèles
     */
    public function getConnection(): PDO {
        return $this->connection;
    }

    /**
     * Sécurité : Empêche le clonage et la désérialisation du Singleton
     */
    private function __clone() {}

    public function __wakeup() {
        throw new \Exception("Impossible de désérialiser un singleton.");
    }
}