<?php
namespace App\Core;

use PDO;
use PDOException;

class Database {
    private static ?self $instance = null;
    private PDO $connection;

    private string $host = 'localhost';
    private string $db_name = 'blog_db';
    private string $username = 'root';
    private string $password = '';

    private function __construct() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4";
            $this->connection = new PDO($dsn, $this->username, $this->password);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            // Utiliser le Logger au lieu de die()
            if (class_exists('App\Core\Logger')) {
                Logger::getInstance()->error("Échec de la connexion BDD", $e);
            }
            // Afficher un message générique
            http_response_code(503); // Service Unavailable
            die("Erreur : Impossible de se connecter à la base de données.");
        }
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->connection;
    }

    private function __clone() {}
    public function __wakeup() {
        throw new \Exception("Cannot unserialize a singleton.");
    }
}
