<?php

namespace App\Controllers;

use Parsedown;
use App\Core\BaseController;
use App\Models\ArticleModel;



/**
 * HomeController
 * Contrôleur principal pour les pages publiques du blog
 */
class HomeController extends BaseController {

    private ArticleModel $articleModel;

    public function __construct() {
        parent::__construct(); 
        $this->articleModel = new ArticleModel();
    }

    /**
     * Page d'accueil : affiche les articles publiés avec leurs tags
     */
    public function index(?int $tagId = null): void {
    // Si on a un tagId, on utilise ta fonction findPublishedByTag
    if ($tagId) {
        $posts = $this->articleModel->findPublishedByTag($tagId);
        $title = "Articles du tag";
    } else {
        $posts = $this->articleModel->findPublished();
        $title = "Accueil du Blog";
    }

    $parsedown = new Parsedown();
    foreach ($posts as $post) {
        $html = $parsedown->text($post->contenu);
        $post->extrait = strip_tags($html);
    }

    $this->render('home.twig', [
        'page_title' => $title,
        'posts' => $posts
    ]);
}
    
    /**
     * Gestion de l'erreur 404
     */
    public function error404(): void {
        $this->logger->warning("Page 404 : " . ($_GET['url'] ?? 'inconnue'));
        http_response_code(404);
        
        $this->render('errors/404.twig', [
            'page_title' => 'Page non trouvée'
        ]);
    }

    /**
     * Page statique "À Propos"
     */
    public function about(): void {
        $this->render('about.twig', [
            'page_title' => 'À Propos de ce Blog'
        ]);
    }

    /**
     * Page de contact : gestion du formulaire et traitement des messages
     */
    public function contact(): void {
        $errors = [];
        $success_message = $this->session->get('contact_success_message');
        $this->session->remove('contact_success_message');

        // Traitement de la soumission du formulaire
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
            $message = trim($_POST['message'] ?? '');

            // Validation des données
            if (empty($name)) $errors['name'] = "Le nom est requis.";
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = "L'adresse email est invalide.";
            if (empty($message)) $errors['message'] = "Le message est requis.";

            if (empty($errors)) {
                $this->logger->info("Formulaire de contact soumis par $name ($email).");
                
                // Redirection après succès (Pattern PRG)
                $this->session->set('contact_success_message', 'Votre message a bien été envoyé !');
                header('Location: /3A2526-blog/public/contact');
                exit;
            } else {
                $this->logger->warning("Erreur de validation du formulaire de contact.");
            }
        }

        $this->render('contact.twig', [
            'page_title' => 'Contactez-nous',
            'errors' => $errors,
            'success_message' => $success_message,
            'old_input' => $_POST ?? []
        ]);
    }
}