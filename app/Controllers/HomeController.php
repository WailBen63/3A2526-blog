<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Models\ArticleModel;

class HomeController extends BaseController {
    private ArticleModel $articleModel;

    public function __construct() {
        parent::__construct(); 
        $this->articleModel = new ArticleModel();
    }

    /**
     * Affiche la page d'accueil avec tous les articles PUBLIÉS.
     */
    public function index(): void {
    $this->logger->info("Page d'accueil demandée.");
    $posts = $this->articleModel->findPublishedWithTags(); // ← UTILISER la nouvelle méthode

    $this->render('home.twig', [
        'page_title' => 'Accueil du Blog',
        'posts' => $posts
    ]);
}
    
    /**
     * Affiche la page 404.
     */
    public function error404(): void {
        $this->logger->warning("Page 404 : " . ($_GET['url'] ?? 'inconnue'));
        
        http_response_code(404);
        $this->render('errors/404.twig', [
            'page_title' => 'Page non trouvée'
        ]);
    }

    /**
     * Affiche la page "À Propos".
     */
    public function about(): void {
        $this->render('about.twig', [
            'page_title' => 'À Propos de ce Blog'
        ]);
    }

    /**
     * Gère l'affichage et la soumission du formulaire de contact.
     */
    public function contact(): void {
        $errors = [];
        $success_message = $this->session->get('contact_success_message');
        $this->session->remove('contact_success_message');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 1. Nettoyage et Validation
            $name = trim($_POST['name'] ?? '');
            $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
            $message = trim($_POST['message'] ?? '');

            if (empty($name)) $errors['name'] = "Le nom est requis.";
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = "L'adresse email est invalide.";
            if (empty($message)) $errors['message'] = "Le message est requis.";

            if (empty($errors)) {
                // 2. Traitement (Envoi d'email - simulé ici)
                $email_body = "Nom: $name\nEmail: $email\nMessage:\n$message";
                
                $this->logger->info("Formulaire de contact soumis par $name ($email).");
                
                // 3. Redirection (Post/Redirect/Get pattern)
                $this->session->set('contact_success_message', 'Votre message a bien été envoyé !');
                header('Location: /3A2526-blog/public/contact');
                exit;
            } else {
                $this->logger->warning("Erreur de validation du formulaire de contact.");
            }
        }

        // 4. Affichage du formulaire
        $this->render('contact.twig', [
            'page_title' => 'Contactez-nous',
            'errors' => $errors,
            'success_message' => $success_message,
            'old_input' => $_POST ?? []
        ]);
    }

    
}