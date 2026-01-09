<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\TagModel;
use App\Models\ArticleModel;

/**
 * TagController
 * 
 * Contrôleur pour la gestion des tags (catégories thématiques) sur la partie publique.
 * Permet la navigation par tags et l'affichage des articles associés à un tag spécifique.
 * 
 * Conformité avec les exigences :
 * - EF-ARTICLE-04 : Association d'articles à un ou plusieurs tags
 * - EF-TAG-01/02 : Navigation et affichage des tags
 * - UX-RESPONSIVE-01 : Interface mobile-first pour la navigation par tags
 * 
 * @package App\Controllers
 */
class TagController extends BaseController {
    /**
     * @var TagModel Modèle pour les opérations sur les tags
     */
    private TagModel $tagModel;
    
    /**
     * @var ArticleModel Modèle pour la récupération des articles par tag
     */
    private ArticleModel $articleModel;

    /**
     * Constructeur
     * 
     * Initialise les modèles nécessaires pour la navigation par tags.
     * Séparation des responsabilités : TagModel pour les tags, ArticleModel pour les articles.
     */
    public function __construct() {
        parent::__construct();
        
        // Initialisation des modèles via Dependency Injection
        $this->tagModel = new TagModel();        // Gestion des tags
        $this->articleModel = new ArticleModel(); // Articles associés aux tags
    }

    /**
     * Affiche tous les articles d'un tag spécifique (Action Show)
     * 
     * Affiche une page listant tous les articles publiés associés à un tag donné.
     * Utilise le slug du tag pour une URL SEO-friendly (ex: /tag/vtt-enduro).
     * 
     * Fonctionnalités :
     * - Vérification de l'existence du tag
     * - Récupération des articles publiés seulement (statut = 'Public')
     * - Affichage avec pagination potentielle
     * 
     * @param string $slug Slug du tag (URL-friendly)
     * @return void
     */
    public function show(string $slug): void {
        // SECTION 1 : Récupération et validation du tag
        // -------------------------------------------------
        // Recherche du tag par son slug (plus SEO-friendly que par ID)
        $tag = $this->tagModel->findBySlug($slug);

        // Vérification de l'existence du tag
        if (!$tag) {
            // Tag non trouvé → page 404
            // Réutilisation du HomeController pour consistance
            (new HomeController())->error404();
            return;
        }

        // SECTION 2 : Récupération des articles associés
        // -----------------------------------------------
        // Récupère uniquement les articles PUBLIÉS associés à ce tag
        // Important : exclusion des brouillons et articles archivés
        $articles = $this->articleModel->findPublishedByTag($tag->id);

        // SECTION 3 : Rendu de la vue
        // ---------------------------
        $this->render('tag_show.twig', [
            'page_title' => "Articles tagués : " . $tag->nom_tag, // Titre dynamique pour SEO
            'tag' => $tag,                                         // Données du tag
            'articles' => $articles                                // Liste des articles
        ]);
        
        // Journalisation optionnelle pour analytics :
        // $this->logger->info("Tag consulté: {$tag->nom_tag} (slug: $slug)");
    }

    /**
     * Affiche le cloud de tags (Action Index)
     * 
     * Affiche une page avec tous les tags populaires du blog
     * sous forme de "tag cloud" (nuage de tags).
     * 
     * Le tag cloud :
     * - Affiche les tags les plus utilisés
     * - Peut utiliser une taille de police proportionnelle à la popularité
     * - Permet une navigation visuelle intuitive
     * 
     * @return void
     */
    public function index(): void {
        // Récupération des 20 tags les plus populaires
        // "Populaire" = tags avec le plus d'articles associés
        $tags = $this->tagModel->findPopular(20);

        // Rendu de la page du nuage de tags
        $this->render('tags_index.twig', [
            'page_title' => 'Tous les tags',
            'tags' => $tags
        ]);
        
        // Points d'extension possibles :
        // - Pagination si beaucoup de tags
        // - Tri alphabétique ou par popularité
        // - Filtre par première lettre
    }
}