<?php

namespace App\Core;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * BaseController - Classe abstraite parente de tous les contrôleurs
 * Centralise la configuration de Twig, de la Session et du Logger
 */
abstract class BaseController {
    protected Environment $twig;
    protected SessionManager $session;
    protected Logger $logger;

    /**
     * Initialisation des services partagés via Singletons
     */
    public function __construct() {
        // Configuration du moteur de templates Twig
        $loader = new FilesystemLoader(dirname(__DIR__) . '/Views');
        $this->twig = new Environment($loader, [
            'debug' => true,
        ]);

        // Récupération des instances des services (Singletons)
        $this->session = SessionManager::getInstance();
        $this->logger = Logger::getInstance();

        // Injection de la session en tant que variable globale Twig
        $this->twig->addGlobal('session', $this->session);
    }

    /**
     * Méthode de rendu des vues Twig avec gestion d'erreurs 500
     */
    protected function render(string $template, array $context = []): void {
        try {
            echo $this->twig->render($template, $context);
        } catch (\Exception $e) {
            // Journalisation de l'erreur et affichage d'un message générique
            $this->logger->error("Erreur de rendu Twig", $e);
            http_response_code(500);
            echo "Une erreur est survenue lors du rendu de la page."; 
        }
    }
}