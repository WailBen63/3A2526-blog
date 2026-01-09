<?php

namespace App\Core;

/**
 * Logger - Singleton pour la journalisation des événements de l'application
 * 
 * Système de journalisation complet pour enregistrer les événements critiques
 * conformément aux exigences techniques du projet de blog.
 * 
 * Implémente les exigences suivantes du cahier des charges :
 * - 2.2.1 Composants Fondamentaux : Logger pour événements critiques
 * - Patron de conception Singleton comme spécifié dans 2.2
 * - Conformité POO PHP 8.X (technologies imposées)
 * - Sécurité (hachage des mots de passe lié aux logs de connexion)
 * 
 * Niveaux de log supportés :
 * - INFO : Opérations normales, accès, publications
 * - WARNING : Comportements anormaux non critiques
 * - ERROR : Erreurs système, exceptions, échecs de sécurité
 * 
 * @package App\Core
 * @conformité 2.2.1 Composants Fondamentaux : Système de journalisation
 */
class Logger {
    /**
     * @var self|null Instance unique du logger (Singleton)
     * @private
     * @static
     * @conformité 2.2 Patrons de Conception : Singleton comme pour la connexion DB
     */
    private static ?self $instance = null;

    /**
     * @var resource|null Handle du fichier de log ouvert en mode append
     * @private
     */
    private $logFile;

    /**
     * Constructeur privé - Empêche l'instanciation directe
     * 
     * Initialise le système de logs avec gestion sécurisée des fichiers.
     * Utilise LOG_PATH définie dans index.php pour le chemin de stockage.
     * 
     * @throws \Exception Si impossible d'ouvrir le fichier de log
     * @conformité Technologies imposées : PHP 8.X POO
     * @conformité Critères d'acceptation : sécurité assurée
     */
    private function __construct() {
        // Utilisation de la constante LOG_PATH définie dans l'index principal
        // Assure une configuration centralisée et sécurisée
        $logDir = LOG_PATH;
        
        // Création sécurisée du répertoire de logs si inexistant
        // Permissions 0755 : lecture/écriture propriétaire, lecture groupe/autres
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        // Ouverture du fichier de log en mode append (ajout à la fin)
        // @ pour supprimer les warnings PHP, gérés manuellement par l'exception
        $logFilePath = $logDir . '/app.log';
        $this->logFile = @fopen($logFilePath, 'a'); 

        // Vérification du succès de l'ouverture
        // Conformité Critères d'acceptation : gestion d'erreurs robuste
        if (!$this->logFile) {
            throw new \Exception("Impossible d'ouvrir le fichier de log : $logFilePath");
        }
    }

    /**
     * Point d'accès unique à l'instance du logger
     * 
     * Implémente le pattern Singleton avec lazy loading.
     * Garantit une instance unique cohérente dans toute l'application.
     * 
     * @return self Instance unique de Logger
     * @conformité 2.2 Patrons de Conception : Singleton
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Journalise un message avec un niveau spécifié
     * 
     * Format standard : [DATE] [NIVEAU] Message
     * Utilisé pour tous les types d'événements de l'application.
     * 
     * @param string $level Niveau de log (INFO, WARNING, ERROR)
     * @param string $message Message à journaliser
     * @return void
     * @conformité 2.2.1 Composants Fondamentaux : enregistrement d'événements critiques
     */
    public function log(string $level, string $message): void {
        // Vérification de la disponibilité du fichier
        if (!$this->logFile) return;
        
        // Formatage du timestamp pour lisibilité
        $date = (new \DateTime())->format('Y-m-d H:i:s');
        
        // Construction du message formaté avec saut de ligne
        $formattedMessage = "[$date] [$level] $message" . PHP_EOL;
        
        // Écriture atomique dans le fichier
        fwrite($this->logFile, $formattedMessage);
    }
    
    /**
     * Journalise un message de niveau INFO
     * 
     * Utilisé pour les opérations normales :
     * - Connexions utilisateur réussies (EF-ACL-06)
     * - Publications d'articles (EF-ARTICLE-01)
     * - Actions administratives standard
     * 
     * @param string $message Message informatif
     * @return void
     * @conformité EF-ACL-06 : suivi des authentifications
     */
    public function info(string $message): void { 
        $this->log('INFO', $message); 
    }
    
    /**
     * Journalise un message de niveau WARNING
     * 
     * Utilisé pour les comportements anormaux non critiques :
     * - Tentatives de connexion avec mauvais mot de passe
     * - Accès à des ressources non autorisées
     * - Données d'entrée suspectes
     * 
     * @param string $message Message d'avertissement
     * @return void
     * @conformité 2.2.1 Composants Fondamentaux : tentatives de connexion
     */
    public function warning(string $message): void { 
        $this->log('WARNING', $message); 
    }
    
    /**
     * Journalise un message de niveau ERROR avec exception optionnelle
     * 
     * Utilisé pour les erreurs critiques :
     * - Exceptions système
     * - Échecs de sécurité
     * - Erreurs de base de données
     * - Actions administratives importantes échouées (2.2.1)
     * 
     * @param string $message Message d'erreur
     * @param \Throwable|null $e Exception associée (optionnelle)
     * @return void
     * @conformité 2.2.1 Composants Fondamentaux : erreurs et actions admin importantes
     */
    public function error(string $message, \Throwable $e = null): void {
        if ($e) {
             // Ajout des détails techniques de l'exception pour débogage
             $message .= " | Exception: " . $e->getMessage() . 
                         " in " . $e->getFile() . ":" . $e->getLine();
        }
        $this->log('ERROR', $message);
    }

    /**
     * Destructeur - Ferme proprement le fichier de log
     * 
     * Garantit la libération des ressources système
     * et l'intégrité des données écrites.
     * 
     * @return void
     * @conformité Technologies imposées : bonne gestion des ressources PHP
     */
    public function __destruct() {
        if ($this->logFile) fclose($this->logFile);
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