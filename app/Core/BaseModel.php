<?php

namespace App\Core;

use PDO;

/**
 * BaseModel - Classe de base abstraite pour tous les modèles de l'application
 * 
 * Fournit l'accès aux services fondamentaux nécessaires aux modèles :
 * - Connexion à la base de données via PDO (Singleton)
 * - Système de journalisation pour le suivi des opérations
 * 
 * Implémente le pattern Template Method pour garantir une initialisation
 * cohérente de tous les modèles enfants dans l'architecture MVC.
 * 
 * @package App\Core
 * @abstract
 */
abstract class BaseModel {
    /**
     * @var PDO Instance de connexion à la base de données
     * @protected
     */
    protected PDO $db;
    
    /**
     * @var Logger Instance du système de journalisation (Singleton)
     * @protected
     */
    protected Logger $logger;

    /**
     * Constructeur - Initialise les services partagés pour les modèles
     * 
     * Configure les dépendances communes à tous les modèles :
     * 1. Connexion PDO à la base de données via le Singleton Database
     * 2. Service de journalisation pour le suivi des opérations et erreurs
     * 
     * Pattern : Template Method - définit la structure d'initialisation standard
     * Pattern : Singleton - réutilisation d'instances uniques pour performance
     * 
     * @throws \PDOException Si la connexion à la base de données échoue
     */
    public function __construct() {
        // SECTION 1 : Connexion à la base de données
        // -------------------------------------------
        // Récupération de l'instance unique de Database (Singleton)
        // puis obtention de l'objet PDO configuré pour les requêtes SQL
        $this->db = Database::getInstance()->getConnection();
        
        // SECTION 2 : Initialisation du système de journalisation
        // --------------------------------------------------------
        // Récupération de l'instance unique du Logger (Singleton)
        // pour permettre la journalisation des opérations et erreurs
        $this->logger = Logger::getInstance();
    }
}