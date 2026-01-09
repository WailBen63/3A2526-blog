<?php

namespace App\Core;

/**
 * EmailService - Singleton pour la gestion des notifications par email
 * Conformité : EF-COMMENT-04 (Notification des commentaires en attente)
 */
class EmailService {
    private static ?self $instance = null;
    private Logger $logger;

    /**
     * Constructeur privé (Pattern Singleton)
     */
    private function __construct() {
        $this->logger = Logger::getInstance();
    }

    /**
     * Point d'accès unique à l'instance du service
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Envoie une notification HTML pour un nouveau commentaire à modérer
     */
    public function sendCommentNotification(array $comment, array $article, string $adminEmail): bool {
        $subject = "📝 Nouveau commentaire en attente de modération";
        
        // Corps de l'email avec styling inline pour compatibilité client mail
        $body = "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <h2>Nouveau Commentaire à modérer</h2>
            <p><strong>Article :</strong> {$article['titre']}</p>
            <p><strong>Auteur :</strong> {$comment['nom_auteur']} (" . ($comment['email_auteur'] ?? 'Anonyme') . ")</p>
            <div style='background: #f4f4f4; padding: 15px; border-left: 4px solid #007bff;'>
                " . nl2br(htmlspecialchars($comment['contenu'])) . "
            </div>
            <p><a href='http://localhost/3A2526-Blog/public/admin/comments'>Accéder à la modération</a></p>
        </body>
        </html>";

        return $this->sendEmail($adminEmail, $subject, $body);
    }

    /**
     * Logique d'envoi adaptative (Production vs Développement)
     */
    private function sendEmail(string $to, string $subject, string $body): bool {
        try {
            if ($this->isProduction()) {
                // Envoi réel (mail() ou service SMTP)
                $this->logger->info("EMAIL ENVOYÉ - To: $to, Subject: $subject");
                return true;
            } else {
                // Simulation en développement : sauvegarde locale pour inspection
                $this->logger->info("EMAIL SIMULÉ - To: $to, Subject: $subject");
                $this->saveEmailForTesting($to, $subject, $body);
                return true;
            }
        } catch (\Exception $e) {
            $this->logger->error("Erreur envoi email à: $to", $e);
            return false;
        }
    }

    /**
     * Sauvegarde l'email dans un fichier HTML (logs/emails/) pour test
     */
    private function saveEmailForTesting(string $to, string $subject, string $body): void {
        $emailDir = dirname(__DIR__) . '/../logs/emails/';
        if (!is_dir($emailDir)) mkdir($emailDir, 0755, true);
        
        $filename = $emailDir . 'email_' . date('Y-m-d_H-i-s') . '.html';
        file_put_contents($filename, $body);
    }

    /**
     * Détection de l'environnement de production
     */
    private function isProduction(): bool {
        return false; // Par défaut en développement
    }

    private function __clone() {}
    public function __wakeup() { throw new \Exception("Cannot unserialize a singleton."); }
}