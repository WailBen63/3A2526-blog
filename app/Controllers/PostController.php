<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Models\PostModel;

class PostController extends BaseController {
    private PostModel $postModel;

    public function __construct() {
        parent::__construct(); 
        $this->postModel = new PostModel();
    }

    /**
     * Affiche un article spécifique par son ID.
     */
    public function show(int $id): void {
        $post = $this->postModel->findById($id);

        if (!$post) {
            // Si l'article n'existe pas, on redirige vers la 404
            (new HomeController())->error404();
            return;
        }

        $this->render('post_show.twig', [
            'page_title' => $post->title,
            'post' => $post
        ]);
    }
}
