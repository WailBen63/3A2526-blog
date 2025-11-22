<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Models\CommentModel;
use App\Models\ArticleModel;

class CommentController extends BaseController {
    private CommentModel $commentModel;
    private ArticleModel $articleModel;

    public function __construct() {
        parent::__construct();
        $this->commentModel = new CommentModel();
        $this->articleModel = new ArticleModel();
    }

    /**
     * Traite la soumission d'un nouveau commentaire
     */
    public function store(int $articleId): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /3A2526-Blog/public/post/' . $articleId);
            exit;
        }

        // Vérifier que l'article existe et est public
        $article = $this->articleModel->findById($articleId);
        if (!$article || $article->statut !== 'Public') {
            $this->session->set('flash_error', 'Article non trouvé.');
            header('Location: /3A2526-Blog/public/');
            exit;
        }

        $nom_auteur = trim($_POST['nom_auteur'] ?? '');
        $email_auteur = trim($_POST['email_auteur'] ?? '');
        $contenu = trim($_POST['contenu'] ?? '');

        // Validation
        $errors = $this->validateComment($nom_auteur, $contenu);

        if (empty($errors)) {
            $commentId = $this->commentModel->create([
                'article_id' => $articleId,
                'nom_auteur' => $nom_auteur,
                'email_auteur' => !empty($email_auteur) ? $email_auteur : null,
                'contenu' => $contenu,
                'statut' => 'En attente' // Tous les commentaires nécessitent modération
            ]);

            if ($commentId) {
                $this->logger->info("Nouveau commentaire ID: $commentId sur article ID: $articleId");
                $this->session->set('flash_success', 'Votre commentaire a été soumis et est en attente de modération.');
            } else {
                $this->session->set('flash_error', 'Erreur lors de l\'envoi du commentaire.');
            }
        } else {
            foreach ($errors as $error) {
                $this->session->set('flash_error', $error);
            }
        }

        // Rediriger vers l'article
        header('Location: /3A2526-Blog/public/post/' . $articleId);
        exit;
    }

    /**
     * Validation des données du commentaire
     */
    private function validateComment(string $nom_auteur, string $contenu): array {
        $errors = [];

        if (empty($nom_auteur)) {
            $errors[] = "Le nom est obligatoire.";
        } elseif (strlen($nom_auteur) < 2) {
            $errors[] = "Le nom doit contenir au moins 2 caractères.";
        }

        if (empty($contenu)) {
            $errors[] = "Le commentaire ne peut pas être vide.";
        } elseif (strlen($contenu) < 10) {
            $errors[] = "Le commentaire doit contenir au moins 10 caractères.";
        } elseif (strlen($contenu) > 1000) {
            $errors[] = "Le commentaire ne peut pas dépasser 1000 caractères.";
        }

        return $errors;
    }
}