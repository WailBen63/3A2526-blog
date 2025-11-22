<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Models\PostModel;
use App\Models\ArticleModel;
use App\Models\CommentModel;

class PostController extends BaseController {
    private PostModel $postModel;
    private ArticleModel $articleModel;
    private CommentModel $commentModel;

    public function __construct() {
        parent::__construct(); 
        $this->postModel = new PostModel();
        $this->articleModel = new ArticleModel();
        $this->commentModel = new CommentModel();
    }

    /**
     * Affiche un article spécifique par son ID.
     */
    public function show(int $id): void {
        $post = $this->postModel->findById($id);

        // Vérifier si l'article existe ET est Public (pas Brouillon ni Archivé)
        if (!$post || $post->statut !== 'Public') {
            (new HomeController())->error404();
            return;
        }

        // Charger les tags de l'article
        $post->tags = $this->articleModel->getArticleTags($id);
        
        // Charger les commentaires approuvés de l'article
        $post->comments = $this->commentModel->findApprovedByArticle($id);

        $this->render('post_show.twig', [
            'page_title' => $post->titre,
            'post' => $post
        ]);
    }
    
}