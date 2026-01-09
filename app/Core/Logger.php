<?php

namespace App\Core;

/**
 * Logger - Singleton pour la journalisation des événements de l'application
 * Gère l'enregistrement des erreurs, avertissements et informations système
 * Conformité : 2.2.1 Composants Fondamentaux
 */
class Logger {
    private static ?self $instance = null;
    private $logFile;

    /**
     * Constructeur privé (Pattern Singleton)
     * Initialise le répertoire et ouvre le fichier app.log en mode append
     */
    private function __construct() {
        $logDir = LOG_PATH;
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logFilePath = $logDir . '/app.log';
        $this->logFile = @fopen($logFilePath, 'a'); 

        if (!$this->logFile) {
            throw new \Exception("Impossible d'ouvrir le fichier de log : $logFilePath");
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
     * Méthode générique de journalisation formatée : [DATE] [NIVEAU] Message
     */
    public function log(string $level, string $message): void {
        if (!$this->logFile) return;
        
        $date = (new \DateTime())->format('Y-m-d H:i:s');
        $formattedMessage = "[$date] [$level] $message" . PHP_EOL;
        
        fwrite($this->logFile, $formattedMessage);
    }
    
    /**
     * Journalise un événement informatif (ex: connexions, publications)
     */
    public function info(string $message): void { 
        $this->log('INFO', $message); 
    }
    
    /**
     * Journalise un avertissement (ex: échecs de connexion, accès non autorisés)
     */
    public function warning(string $message): void { 
        $this->log('WARNING', $message); 
    }
    
    /**
     * Journalise une erreur critique avec détails de l'exception si fournie
     */
    public function error(string $message, \Throwable $e = null): void {
        if ($e) {
             $message .= " | Exception: " . $e->getMessage() . 
                         " in " . $e->getFile() . ":" . $e->getLine();
        }
        $this->log('ERROR', $message);
    }

    /**
     * Libération propre de la ressource système à la destruction de l'objet
     */
    public function __destruct() {
        if ($this->logFile) fclose($this->logFile);
    }

    private function __clone() {}

    public function __wakeup() {
        throw new \Exception("Cannot unserialize a singleton.");
    }
}