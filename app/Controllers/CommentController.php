<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\CommentModel;
use App\Models\ArticleModel;

/**
 * CommentController
 * Gère les interactions publiques liées aux commentaires
 * Conformité : EF-COMMENT-01, EF-COMMENT-02, EF-COMMENT-03
 */
class CommentController extends BaseController {

    private CommentModel $commentModel;
    private ArticleModel $articleModel;

    public function __construct() {
        parent::__construct();
        
        $this->commentModel = new CommentModel();
        $this->articleModel = new ArticleModel();
    }

    /**
     * Traite la soumission d'un nouveau commentaire (POST)
     */
    public function store(int $articleId): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /3A2526-Blog/public/post/' . $articleId);
            exit;
        }

        // Vérification de l'existence et du statut de l'article parent
        $article = $this->articleModel->findById($articleId);
        if (!$article || $article->statut !== 'Publié') {
            $this->session->set('flash_error', 'Article non trouvé.');
            header('Location: /3A2526-Blog/public/');
            exit;
        }

        $nom_auteur = trim($_POST['nom_auteur'] ?? '');
        $email_auteur = trim($_POST['email_auteur'] ?? '');
        $contenu = trim($_POST['contenu'] ?? '');

        // Validation des données saisies
        $errors = $this->validateComment($nom_auteur, $contenu);

        if (empty($errors)) {
            // Création avec statut "En attente" par défaut pour modération (EF-COMMENT-03)
            $commentId = $this->commentModel->create([
                'article_id' => $articleId,
                'nom_auteur' => $nom_auteur,
                'email_auteur' => !empty($email_auteur) ? $email_auteur : null,
                'contenu' => $contenu,
                'statut' => 'En attente'
            ]);

            if ($commentId) {
                $this->logger->info("Nouveau commentaire ID: $commentId sur article: $articleId");
                $this->session->set('flash_success', 'Votre commentaire est en attente de modération.');
            } else {
                $this->session->set('flash_error', 'Erreur lors de l\'envoi.');
            }
        } else {
            foreach ($errors as $error) {
                $this->session->set('flash_error', $error);
            }
        }

        header('Location: /3A2526-Blog/public/post/' . $articleId);
        exit;
    }

    /**
     * Valide les données du commentaire (contraintes métier et anti-spam)
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
            $errors[] = "Le commentaire est trop court (min 10 caractères).";
        } elseif (strlen($contenu) > 1000) {
            $errors[] = "Le commentaire est trop long (max 1000 caractères).";
        }

        return $errors;
    }
}