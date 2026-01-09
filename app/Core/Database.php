<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * Database - Singleton pour la gestion de la connexion à la base de données
 * 
 * Implémente le pattern Singleton pour garantir une seule instance de connexion PDO
 * à la base de données MariaDB/MySQL sur toute la durée de vie de l'application.
 * 
 * Responsabilités :
 * - Configuration centralisée des paramètres de connexion
 * - Gestion unique de l'instance PDO
 * - Configuration des attributs PDO pour sécurité et performance
 * - Prévention des connexions multiples et des fuites de ressources
 * 
 * Conformité avec les exigences techniques :
 * - Utilisation de PDO pour des requêtes sécurisées et préparées
 * - Singleton pattern pour l'accès unique à la base de données
 * - Configuration optimisée pour MariaDB/MySQL avec UTF-8
 * 
 * @package App\Core
 */
class Database {
    /**
     * @var self|null Instance unique de la classe (pattern Singleton)
     * @private
     * @static
     */
    private static ?self $instance = null;
    
    /**
     * @var PDO Instance de connexion PDO à la base de données
     * @private
     */
    private PDO $connection;

    /**
     * Paramètres de configuration de la connexion
     * À externaliser dans un fichier de configuration en production
     */
    private string $host = 'localhost';      // Adresse du serveur MySQL/MariaDB
    private string $db_name = 'blog_db';     // Nom de la base de données
    private string $username = 'root';       // Identifiant de connexion (XAMPP par défaut)
    private string $password = '';           // Mot de passe de connexion (XAMPP par défaut)

    /**
     * Constructeur privé - Empêche l'instanciation directe
     * 
     * Initialise la connexion PDO avec configuration optimale :
     * - DSN avec charset UTF-8 pour support Unicode complet
     * - Mode erreur EXCEPTION pour meilleure gestion des erreurs
     * - Mode fetch par défaut en objets pour cohérence
     * 
     * Pattern : Singleton - constructeur privé pour contrôle d'instanciation
     * 
     * @throws PDOException Si la connexion à la base de données échoue
     */
    private function __construct() {
        try {
            // Construction du Data Source Name (DSN)
            // Format: mysql:host=HOST;dbname=DBNAME;charset=CHARSET
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4";
            
            // Création de l'instance PDO avec authentification
            $this->connection = new PDO($dsn, $this->username, $this->password);
            
            // SECTION : Configuration des attributs PDO
            // ------------------------------------------
            
            // 1. Mode de gestion des erreurs : lance des exceptions
            // Important pour le développement et la sécurité
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // 2. Mode de récupération par défaut : objets anonymes
            // Plus lisible que les tableaux associatifs
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
            
            // Autres attributs PDO recommandés (non activés ici) :
            // - ATTR_EMULATE_PREPARES = false pour de vraies prepared statements
            // - ATTR_STRINGIFY_FETCHES = false pour conserver les types
            // - ATTR_PERSISTENT = false pour éviter les problèmes de connexion persistante
            
        } catch (PDOException $e) {
            // Gestion d'erreur de connexion critique
            // En production, logguer l'erreur plutôt que de l'afficher
            die('Erreur de connexion : '. $e->getMessage());
        }
    }

    /**
     * Point d'accès unique à l'instance de la base de données
     * 
     * Implémente le pattern Singleton avec lazy loading :
     * - Crée l'instance uniquement au premier appel
     * - Retourne la même instance pour tous les appels suivants
     * - Garantit une seule connexion à la base de données
     * 
     * @return self Instance unique de Database
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Retourne l'objet PDO de connexion à la base de données
     * 
     * Permet aux modèles (via BaseModel) d'accéder à la connexion PDO
     * sans pouvoir modifier la configuration de la connexion.
     * 
     * @return PDO Instance PDO configurée et prête à l'emploi
     */
    public function getConnection(): PDO {
        return $this->connection;
    }

    /**
     * Empêche le clonage de l'instance (partie du pattern Singleton)
     * 
     * Méthode magique rendue privée pour empêcher la duplication
     * de l'instance via l'opérateur clone.
     * 
     * @return void
     * @private
     */
    private function __clone() {}

    /**
     * Empêche la désérialisation de l'instance (partie du pattern Singleton)
     * 
     * Méthode magique pour empêcher la recréation d'instance via
     * désérialisation, ce qui violerait le pattern Singleton.
     * 
     * @throws \Exception Toujours lancée pour prévenir la désérialisation
     */
    public function __wakeup() {
        throw new \Exception("Cannot unserialize a singleton.");
    }
}