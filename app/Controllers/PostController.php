<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\PostModel;
use App\Models\ArticleModel;
use App\Models\CommentModel;

/**
 * PostController
 * Contrôleur pour l'affichage public détaillé des articles
 * Conformité : EF-ARTICLE-01, EF-ARTICLE-04, EF-COMMENT-01
 */
class PostController extends BaseController {

    private PostModel $postModel;
    private ArticleModel $articleModel;
    private CommentModel $commentModel;

    public function __construct() {
        parent::__construct(); 
        
        // Initialisation des modèles pour l'article, ses tags et ses commentaires
        $this->postModel = new PostModel();
        $this->articleModel = new ArticleModel();
        $this->commentModel = new CommentModel();
    }

    /**
     * Affiche un article spécifique par son ID (Action Show)
     */
    public function show(int $id): void {
        // Récupération de l'article via le modèle dédié
        $post = $this->postModel->findById($id);

        // Sécurité : Vérification de l'existence et du statut "Public" uniquement
        if (!$post || $post->statut !== 'Public') {
            (new HomeController())->error404();
            return;
        }

        // Chargement des tags associés (EF-ARTICLE-04)
        $post->tags = $this->articleModel->getArticleTags($id);
        
        // Chargement des commentaires approuvés (EF-COMMENT-01)
        $post->comments = $this->commentModel->findApprovedByArticle($id);
        
        // Rendu de la vue avec les données enrichies
        $this->render('post_show.twig', [
            'page_title' => $post->titre,
            'post' => $post
        ]);
    }
}