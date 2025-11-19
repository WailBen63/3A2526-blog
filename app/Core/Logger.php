<?php
namespace App\Core;

class Logger {
    private static ?self $instance = null;
    private $logFile;

    private function __construct() {
        $logDir = LOG_PATH; // Constante définie dans index.php
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logFilePath = $logDir . '/app.log';
        $this->logFile = @fopen($logFilePath, 'a'); 

        if (!$this->logFile) {
            throw new \Exception("Impossible d'ouvrir le fichier de log : $logFilePath");
        }
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function log(string $level, string $message): void {
        if (!$this->logFile) return;
        $date = (new \DateTime())->format('Y-m-d H:i:s');
        $formattedMessage = "[$date] [$level] $message" . PHP_EOL;
        fwrite($this->logFile, $formattedMessage);
    }
    
    public function info(string $message): void { $this->log('INFO', $message); }
    public function warning(string $message): void { $this->log('WARNING', $message); }
    public function error(string $message, \Throwable $e = null): void {
        if ($e) {
             $message .= " | Exception: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine();
        }
        $this->log('ERROR', $message);
    }

    public function __destruct() {
        if ($this->logFile) fclose($this->logFile);
    }

    private function __clone() {}
    public function __wakeup() {
        throw new \Exception("Cannot unserialize a singleton.");
    }
}
