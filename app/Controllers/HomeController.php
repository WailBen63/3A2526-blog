<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\ArticleModel;

/**
 * HomeController
 * 
 * Contrôleur principal pour les pages publiques du blog.
 * Gère l'affichage de la page d'accueil, les pages statiques et les erreurs.
 * 
 * Conformité avec les exigences :
 * - EF-ARTICLE-01 : Affichage des articles publiés sur l'accueil
 * - UX-RESPONSIVE-01 : Interface mobile-first pour toutes les pages
 * - UX-THEME-01/02 : Support des thèmes clair/foncé (via template)
 * - UX-ACCESSIBILITY-01 : Option police dyslexique (via template)
 * 
 * @package App\Controllers
 */
class HomeController extends BaseController {
    /**
     * @var ArticleModel Modèle pour récupérer les articles publiés
     */
    private ArticleModel $articleModel;

    /**
     * Constructeur
     * 
     * Initialise le modèle d'articles pour l'affichage public.
     * Hérite de BaseController pour accéder aux services communs.
     */
    public function __construct() {
        parent::__construct(); 
        
        // Initialisation du modèle via Dependency Injection
        $this->articleModel = new ArticleModel();
    }

    /**
     * Page d'accueil (Action Index)
     * 
     * Affiche la liste des articles publiés (statut "Public") sur la page d'accueil.
     * Utilise une méthode spécialisée du modèle pour optimiser les requêtes
     * et inclure les tags associés à chaque article.
     * 
     * Journalisation : Trace l'accès à l'accueil pour statistiques.
     * 
     * @return void
     */
    public function index(): void {
        // Journalisation pour monitoring et statistiques
        $this->logger->info("Page d'accueil demandée.");
        
        // Récupération des articles publiés avec leurs tags
        // Méthode optimisée avec jointure pour éviter le N+1 query problem
        $posts = $this->articleModel->findPublishedWithTags();

        // Rendu de la page d'accueil avec les articles
        $this->render('home.twig', [
            'page_title' => 'Accueil du Blog',
            'posts' => $posts
        ]);
    }
    
    /**
     * Page d'erreur 404 (Action Error404)
     * 
     * Affiche une page d'erreur 404 personnalisée lorsque l'URL demandée
     * n'existe pas. Journalise l'URL erronée pour débogage.
     * 
     * @return void
     */
    public function error404(): void {
        // Journalisation de l'erreur 404 (utile pour détecter les liens morts)
        $this->logger->warning("Page 404 : " . ($_GET['url'] ?? 'inconnue'));
        
        // Définition du code HTTP approprié
        http_response_code(404);
        
        // Rendu de la page d'erreur 404 personnalisée
        $this->render('errors/404.twig', [
            'page_title' => 'Page non trouvée'
        ]);
    }

    /**
     * Page "À Propos" (Action About)
     * 
     * Affiche une page statique présentant le blog, sa mission
     * et les technologies utilisées. Page purement informative.
     * 
     * @return void
     */
    public function about(): void {
        // Rendu de la page "À Propos" avec contenu statique
        $this->render('about.twig', [
            'page_title' => 'À Propos de ce Blog'
        ]);
    }

    /**
     * Page de contact (Action Contact)
     * 
     * Gère l'affichage et le traitement du formulaire de contact.
     * Implémente le pattern Post/Redirect/Get pour une expérience utilisateur optimale.
     * 
     * Sécurité : Validation et sanitisation des données utilisateur.
     * 
     * @return void
     */
    public function contact(): void {
        // Initialisation des variables
        $errors = [];
        
        // Récupération du message de succès de la session (s'il existe)
        $success_message = $this->session->get('contact_success_message');
        $this->session->remove('contact_success_message'); // Nettoyage après lecture

        // SECTION : Traitement de la soumission POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // ----- Étape 1 : Nettoyage et Validation -----
            $name = trim($_POST['name'] ?? '');
            $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
            $message = trim($_POST['message'] ?? '');

            // Validation des champs obligatoires
            if (empty($name)) $errors['name'] = "Le nom est requis.";
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = "L'adresse email est invalide.";
            if (empty($message)) $errors['message'] = "Le message est requis.";

            // ----- Étape 2 : Traitement si validation réussie -----
            if (empty($errors)) {
                // Construction du corps de l'email (simulé)
                $email_body = "Nom: $name\nEmail: $email\nMessage:\n$message";
                
                // Journalisation pour suivi
                $this->logger->info("Formulaire de contact soumis par $name ($email).");
                
                // ----- Étape 3 : Redirection (Post/Redirect/Get pattern) -----
                // Stockage du message de succès dans la session
                $this->session->set('contact_success_message', 'Votre message a bien été envoyé !');
                
                // Redirection vers la même page (GET) pour éviter re-soumission
                header('Location: /3A2526-blog/public/contact');
                exit;
            } else {
                // Journalisation des erreurs de validation
                $this->logger->warning("Erreur de validation du formulaire de contact.");
            }
        }

        // SECTION : Affichage du formulaire (GET ou après erreur)
        // ----------------------------------------------------------
        $this->render('contact.twig', [
            'page_title' => 'Contactez-nous',
            'errors' => $errors,
            'success_message' => $success_message,
            'old_input' => $_POST ?? []  // Conservation des données en cas d'erreur
        ]);
    }
}