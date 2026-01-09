<?php

namespace App\Core;

use PDO;

/**
 * BaseModel - Classe abstraite parente de tous les modèles
 * Centralise l'accès à la base de données et au service de logs
 */
abstract class BaseModel {
    protected PDO $db;
    protected Logger $logger;

    /**
     * Initialisation des services partagés (Patterns Singleton)
     */
    public function __construct() {
        // Récupération de la connexion PDO via le Singleton Database
        $this->db = Database::getInstance()->getConnection();
        
        // Initialisation du système de journalisation
        $this->logger = Logger::getInstance();
    }
}