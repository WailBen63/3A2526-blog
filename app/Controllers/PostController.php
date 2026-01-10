<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\PostModel;
use App\Models\ArticleModel;
use App\Models\CommentModel;
use Parsedown;

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
    // 1. On récupère l'article
    $post = $this->postModel->findById($id);

    // 2. Sécurité : on vérifie s'il existe et s'il est 'Public'
    if (!$post || $post->statut !== 'Publié') {
        (new HomeController())->error404();
        return;
    }

    // 3. ON CHARGE LES COMMENTAIRES (C'est cette ligne qui devait manquer)
    // On récupère les commentaires approuvés pour cet article précis
    $post->comments = $this->commentModel->findApprovedByArticle($id);

    // 4. ON CHARGE LES TAGS
    $post->tags = $this->articleModel->getArticleTags($id);

    // 5. TRANSFORMATION DU TEXTE (MARKDOWN -> HTML)
    $parsedown = new Parsedown();
    $post->contenu = $parsedown->text($post->contenu); 

    // 6. ENVOI À LA VUE
    $this->render('post_show.twig', [
        'page_title' => $post->titre,
        'post' => $post
    ]);
}




}