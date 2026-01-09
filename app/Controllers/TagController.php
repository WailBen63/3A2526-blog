<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\TagModel;
use App\Models\ArticleModel;

/**
 * TagController
 * Gestion de la navigation par tags sur la partie publique
 * Conformité : EF-TAG-01, EF-TAG-02, EF-ARTICLE-04
 */
class TagController extends BaseController {

    private TagModel $tagModel;
    private ArticleModel $articleModel;

    public function __construct() {
        parent::__construct();
        
        // Initialisation des modèles pour les tags et les articles associés
        $this->tagModel = new TagModel();
        $this->articleModel = new ArticleModel();
    }

    /**
     * Affiche tous les articles associés à un tag spécifique (via son slug)
     */
    public function show(string $slug): void {
        // Recherche du tag par son slug pour des URLs SEO-friendly
        $tag = $this->tagModel->findBySlug($slug);

        // Sécurité : Vérification de l'existence du tag
        if (!$tag) {
            (new HomeController())->error404();
            return;
        }

        // Récupération uniquement des articles publiés liés à ce tag
        $articles = $this->articleModel->findPublishedByTag($tag->id);

        $this->render('tag_show.twig', [
            'page_title' => "Articles tagués : " . $tag->nom_tag,
            'tag' => $tag,
            'articles' => $articles
        ]);
    }

    /**
     * Affiche l'index des tags (Nuage de tags / Tag Cloud)
     */
    public function index(): void {
        // Récupération des tags les plus populaires (avec le plus d'articles)
        $tags = $this->tagModel->findPopular(20);

        $this->render('tags_index.twig', [
            'page_title' => 'Tous les tags',
            'tags' => $tags
        ]);
    }
}