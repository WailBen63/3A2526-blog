<?php
namespace App\Core;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

abstract class BaseController {
    protected Environment $twig;
    protected SessionManager $session;
    protected Logger $logger;

    public function __construct() {
        // 1. Initialiser Twig
        $loader = new FilesystemLoader(dirname(__DIR__) . '/Views');
        $this->twig = new Environment($loader, [
            // 'cache' => dirname(__DIR__) . '/cache/twig',
        ]);

        // 2. Récupérer les Singletons
        $this->session = SessionManager::getInstance();
        $this->logger = Logger::getInstance();
        
        // 3. Rendre la session accessible dans tous les templates Twig
        $this->twig->addGlobal('session', $this->session);
    }

    /**
     * Méthode d'aide pour rendre une vue Twig.
     */
    protected function render(string $template, array $context = []): void {
        try {
            echo $this->twig->render($template, $context);
        } catch (\Exception $e) {
            $this->logger->error("Erreur de rendu Twig", $e);
            http_response_code(500);
            echo "Une erreur est survenue lors du rendu de la page."; 
        }
    }
}
