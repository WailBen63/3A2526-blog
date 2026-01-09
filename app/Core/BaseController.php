<?php

namespace App\Core;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * BaseController - Classe de base abstraite pour tous les contrôleurs
 * 
 * Fournit les services communs et la configuration de base nécessaire
 * au fonctionnement standard des contrôleurs dans l'architecture MVC.
 * Implémente le pattern Template Method pour garantir une initialisation
 * cohérente de tous les contrôleurs enfants.
 * 
 * @package App\Core
 * @abstract
 */
abstract class BaseController {
    /**
     * @var Environment Instance du moteur de templates Twig
     * @protected
     */
    protected Environment $twig;
    
    /**
     * @var SessionManager Instance du gestionnaire de session (Singleton)
     * @protected
     */
    protected SessionManager $session;
    
    /**
     * @var Logger Instance du système de journalisation (Singleton)
     * @protected
     */
    protected Logger $logger;

    /**
     * Constructeur - Initialise les services partagés
     * 
     * Configure les dépendances communes à tous les contrôleurs :
     * 1. Moteur de templates Twig avec options de debug
     * 2. Services Singleton (SessionManager, Logger)
     * 3. Variables globales accessibles dans tous les templates
     * 
     * Pattern : Template Method - définit la structure d'initialisation standard
     * 
     * @throws \Exception En cas d'erreur d'initialisation de Twig
     */
    public function __construct() {
        // SECTION 1 : Configuration du moteur de templates Twig
        // -------------------------------------------------------
        // Initialisation du chargeur de fichiers avec le chemin vers les vues
        $loader = new FilesystemLoader(dirname(__DIR__) . '/Views');
        
        // Création de l'environnement Twig avec options de développement
        $this->twig = new Environment($loader, [
            // Option de cache (commentée en développement, à activer en production)
            // 'cache' => dirname(__DIR__) . '/cache/twig',
            
            // Mode debug activé pour le développement
            // - Affiche les erreurs détaillées dans les templates
            // - À désactiver en production pour des raisons de performance et sécurité
            'debug' => true,
        ]);

        // SECTION 2 : Récupération des services Singleton
        // ------------------------------------------------
        // Gestionnaire de session pour l'état utilisateur
        $this->session = SessionManager::getInstance();
        
        // Système de journalisation pour le suivi et le débogage
        $this->logger = Logger::getInstance();

        // SECTION 3 : Configuration des variables globales Twig
        // ------------------------------------------------------
        // Rend la session accessible dans TOUS les templates sans injection manuelle
        // Permet d'utiliser {{ session.get('user_id') }} directement dans les templates
        $this->twig->addGlobal('session', $this->session);
    }

    /**
     * Méthode utilitaire pour le rendu des templates Twig
     * 
     * Encapsule le rendu Twig avec gestion d'erreurs robuste.
     * Capture les exceptions et fournit un fallback utilisateur-friendly.
     * 
     * @param string $template Chemin relatif du template (ex: 'admin/articles/index.twig')
     * @param array $context Données à transmettre au template (tableau associatif)
     * @return void
     * 
     * @throws void Les exceptions sont interceptées et transformées en réponse HTTP 500
     */
    protected function render(string $template, array $context = []): void {
        try {
            // Rendu du template avec les données fournies
            echo $this->twig->render($template, $context);
        } catch (\Exception $e) {
            // SECTION : Gestion d'erreur de rendu
            // ------------------------------------
            // Journalisation de l'erreur avec stack trace complète
            $this->logger->error("Erreur de rendu Twig", $e);
            
            // Code HTTP 500 (Internal Server Error)
            http_response_code(500);
            
            // Message générique pour l'utilisateur (ne pas révéler de détails techniques)
            echo "Une erreur est survenue lors du rendu de la page."; 
        }
    }
}