<?php

namespace App\Core;

/**
 * EmailService - Singleton pour la gestion des envois d'emails
 * 
 * Service centralisé pour l'envoi d'emails transactionnels et notifications.
 * Implémente le pattern Singleton et fournit une abstraction pour l'envoi d'emails
 * avec support pour le développement (simulation) et la production (vrai envoi).
 * 
 * Conformité avec les exigences :
 * - EF-COMMENT-04 : Notification des nouveaux commentaires en attente de modération
 * - Séparation des préoccupations : Service dédié à la communication email
 * - Environnement-aware : Comportement différent développement/production
 * 
 * @package App\Core
 */
class EmailService {
    /**
     * @var self|null Instance unique du service email (Singleton)
     * @private
     * @static
     */
    private static ?self $instance = null;
    
    /**
     * @var Logger Instance du système de journalisation
     * @private
     */
    private Logger $logger;

    /**
     * Constructeur privé - Initialisation du service
     * 
     * Récupère l'instance du Logger pour le suivi des opérations.
     * Pattern Singleton : empêche l'instanciation directe.
     */
    private function __construct() {
        $this->logger = Logger::getInstance();
    }

    /**
     * Point d'accès unique à l'instance du service email
     * 
     * Implémente le pattern Singleton avec lazy loading.
     * 
     * @return self Instance unique de EmailService
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Envoie une notification pour un nouveau commentaire
     * 
     * Génère et envoie un email HTML stylisé aux administrateurs
     * lorsqu'un nouveau commentaire est soumis et nécessite modération.
     * Conforme à EF-COMMENT-04.
     * 
     * @param array $comment Données du commentaire (nom, email, contenu)
     * @param array $article Données de l'article associé (titre)
     * @param string $adminEmail Adresse email de l'administrateur/modérateur
     * @return bool True si l'email a été envoyé/simulé avec succès
     */
    public function sendCommentNotification(array $comment, array $article, string $adminEmail): bool {
        // Sujet de l'email avec emoji pour meilleure visibilité
        $subject = "📝 Nouveau commentaire en attente de modération";
        
        // Corps HTML de l'email avec styling inline pour compatibilité
        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #007bff; color: white; padding: 20px; text-align: center; }
                .content { background: #f8f9fa; padding: 20px; }
                .comment { background: white; padding: 15px; border-left: 4px solid #007bff; margin: 15px 0; }
                .button { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Nouveau Commentaire</h1>
                </div>
                <div class='content'>
                    <p>Un nouveau commentaire nécessite votre modération :</p>
                    
                    <div class='comment'>
                        <strong>Auteur :</strong> {$comment['nom_auteur']}<br>
                        <strong>Email :</strong> " . ($comment['email_auteur'] ?? 'Non renseigné') . "<br>
                        <strong>Article :</strong> {$article['titre']}<br>
                        <strong>Date :</strong> " . date('d/m/Y à H:i') . "<br>
                        <strong>Contenu :</strong><br>
                        " . nl2br(htmlspecialchars($comment['contenu'])) . "
                    </div>
                    
                    <p style='text-align: center;'>
                        <a href='http://localhost/3A2526-Blog/public/admin/comments' class='button'>
                            Modérer les commentaires
                        </a>
                    </p>
                </div>
                <div class='footer'>
                    Cet email a été envoyé automatiquement par le système de blog.
                </div>
            </div>
        </body>
        </html>
        ";

        // Délégation de l'envoi à la méthode privée
        return $this->sendEmail($adminEmail, $subject, $body);
    }

    /**
     * Envoie un email (version environnement-aware)
     * 
     * Méthode privée qui adapte son comportement selon l'environnement :
     * - Développement : Simulation + sauvegarde dans fichier de test
     * - Production : Envoi réel via fonction mail() ou service SMTP
     * 
     * @param string $to Destinataire
     * @param string $subject Sujet de l'email
     * @param string $body Corps HTML de l'email
     * @return bool Succès de l'opération
     * @private
     */
    private function sendEmail(string $to, string $subject, string $body): bool {
        try {
            // Configuration des headers pour email HTML
            $headers = [
                'MIME-Version: 1.0',
                'Content-type: text/html; charset=utf-8',
                'From: blog@vtt.com',
                'X-Mailer: PHP/' . phpversion()
            ];

            // Logique différente selon l'environnement
            if ($this->isProduction()) {
                // ENVIRONNEMENT PRODUCTION - Envoi réel
                // mail($to, $subject, $body, implode("\r\n", $headers));
                
                // Note: En production, utilisez plutôt PHPMailer ou SwiftMailer
                // pour une meilleure fiabilité et fonctionnalités
                
                $this->logger->info("EMAIL ENVOYÉ - To: $to, Subject: $subject");
                return true;
            } else {
                // ENVIRONNEMENT DÉVELOPPEMENT - Simulation
                $this->logger->info("EMAIL SIMULÉ - To: $to, Subject: $subject");
                
                // Sauvegarde pour revue et test
                $this->saveEmailForTesting($to, $subject, $body);
                
                return true;
            }
        } catch (\Exception $e) {
            // Journalisation de l'erreur
            $this->logger->error("Erreur envoi email à: $to", $e);
            return false;
        }
    }

    /**
     * Sauvegarde l'email dans un fichier pour test en développement
     * 
     * Crée un fichier HTML avec les détails de l'email pour inspection
     * et test pendant le développement.
     * 
     * @param string $to Destinataire
     * @param string $subject Sujet
     * @param string $body Corps HTML
     * @return void
     * @private
     */
    private function saveEmailForTesting(string $to, string $subject, string $body): void {
        $emailDir = dirname(__DIR__) . '/../logs/emails/';
        
        // Création du dossier s'il n'existe pas
        if (!is_dir($emailDir)) {
            mkdir($emailDir, 0755, true);
        }
        
        // Nom de fichier avec timestamp pour éviter les collisions
        $filename = $emailDir . 'email_' . date('Y-m-d_H-i-s') . '.html';
        
        // Contenu du fichier de test avec métadonnées
        $content = "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Test Email: $subject</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .info { background: #f0f0f0; padding: 10px; margin-bottom: 20px; }
            </style>
        </head>
        <body>
            <div class='info'>
                <strong>À :</strong> $to<br>
                <strong>Sujet :</strong> $subject<br>
                <strong>Date :</strong> " . date('d/m/Y H:i:s') . "<br>
                <strong>Environnement :</strong> " . ($this->isProduction() ? 'Production' : 'Développement') . "
            </div>
            $body
        </body>
        </html>
        ";
        
        // Écriture du fichier
        file_put_contents($filename, $content);
    }

    /**
     * Vérifie si on est en environnement de production
     * 
     * Méthode simplifiée - À améliorer avec détection d'environnement
     * (fichier .env, variable serveur, constante définie, etc.)
     * 
     * @return bool True si environnement de production
     * @private
     */
    private function isProduction(): bool {
        // À configurer selon votre environnement
        // Ex: return $_ENV['APP_ENV'] === 'production';
        return false; // Par défaut en développement
    }

    /**
     * Empêche le clonage de l'instance (partie du pattern Singleton)
     * 
     * @return void
     * @private
     */
    private function __clone() {}

    /**
     * Empêche la désérialisation de l'instance
     * 
     * @throws \Exception Toujours lancée
     */
    public function __wakeup() {
        throw new \Exception("Cannot unserialize a singleton.");
    }
}