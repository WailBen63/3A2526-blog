<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Models\TagModel;
use App\Models\ArticleModel;

class TagController extends BaseController {
    private TagModel $tagModel;
    private ArticleModel $articleModel;

    public function __construct() {
        parent::__construct();
        $this->tagModel = new TagModel();
        $this->articleModel = new ArticleModel();
    }

    /**
     * Affiche tous les articles d'un tag spécifique
     */
    public function show(string $slug): void {
        $tag = $this->tagModel->findBySlug($slug);

        if (!$tag) {
            (new HomeController())->error404();
            return;
        }

        // Récupérer les articles associés à ce tag (uniquement publiés)
        $articles = $this->articleModel->findPublishedByTag($tag->id);

        $this->render('tag_show.twig', [
            'page_title' => "Articles tagués : " . $tag->nom_tag,
            'tag' => $tag,
            'articles' => $articles
        ]);
    }

    /**
     * Affiche le cloud de tags
     */
    public function index(): void {
        $tags = $this->tagModel->findPopular(20); // 20 tags les plus populaires

        $this->render('tags_index.twig', [
            'page_title' => 'Tous les tags',
            'tags' => $tags
        ]);
    }
}