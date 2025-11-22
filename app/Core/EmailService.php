<?php
namespace App\Core;

class EmailService {
    private static ?self $instance = null;
    private Logger $logger;

    private function __construct() {
        $this->logger = Logger::getInstance();
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Envoie une notification pour un nouveau commentaire
     */
    public function sendCommentNotification(array $comment, array $article, string $adminEmail): bool {
        $subject = "📝 Nouveau commentaire en attente de modération";
        
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

        return $this->sendEmail($adminEmail, $subject, $body);
    }

    /**
     * Envoie un email (version simulée pour le développement)
     */
    private function sendEmail(string $to, string $subject, string $body): bool {
        try {
            // En développement, on simule l'envoi d'email et on logge
            // En production, vous utiliseriez PHPMailer, SwiftMailer, ou un service d'email
            
            $headers = [
                'MIME-Version: 1.0',
                'Content-type: text/html; charset=utf-8',
                'From: blog@vtt.com',
                'X-Mailer: PHP/' . phpversion()
            ];

            // Simulation d'envoi - En développement, on logge seulement
            if ($this->isProduction()) {
                // En production, décommentez cette ligne :
                // mail($to, $subject, $body, implode("\r\n", $headers));
                
                $this->logger->info("EMAIL ENVOYÉ - To: $to, Subject: $subject");
                return true;
            } else {
                // En développement, on logge et on simule le succès
                $this->logger->info("EMAIL SIMULÉ - To: $to, Subject: $subject");
                
                // Sauvegarder l'email dans un fichier pour test
                $this->saveEmailForTesting($to, $subject, $body);
                
                return true;
            }
        } catch (\Exception $e) {
            $this->logger->error("Erreur envoi email à: $to", $e);
            return false;
        }
    }

    /**
     * Sauvegarde l'email dans un fichier pour test en développement
     */
    private function saveEmailForTesting(string $to, string $subject, string $body): void {
        $emailDir = dirname(__DIR__) . '/../logs/emails/';
        
        if (!is_dir($emailDir)) {
            mkdir($emailDir, 0755, true);
        }
        
        $filename = $emailDir . 'email_' . date('Y-m-d_H-i-s') . '.html';
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
        
        file_put_contents($filename, $content);
    }

    /**
     * Vérifie si on est en environnement de production
     */
    private function isProduction(): bool {
        return false; // À changer en true pour la production
    }

    private function __clone() {}
    public function __wakeup() {
        throw new \Exception("Cannot unserialize a singleton.");
    }
}