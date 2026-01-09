<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\PostModel;
use App\Models\ArticleModel;
use App\Models\CommentModel;

/**
 * PostController
 * 
 * Contrôleur pour l'affichage détaillé des articles sur la partie publique.
 * Gère la page individuelle d'un article avec ses métadonnées, tags et commentaires.
 * 
 * Conformité avec les exigences :
 * - EF-ARTICLE-01 : Affichage individuel des articles
 * - EF-ARTICLE-04 : Affichage des tags associés à l'article
 * - EF-COMMENT-01 : Affichage des commentaires approuvés associés à l'article
 * - UX-RESPONSIVE-01 : Interface mobile-first pour l'affichage article
 * 
 * @package App\Controllers
 */
class PostController extends BaseController {
    /**
     * @var PostModel Modèle pour la récupération des articles (version publique)
     */
    private PostModel $postModel;
    
    /**
     * @var ArticleModel Modèle pour la récupération des tags d'article
     */
    private ArticleModel $articleModel;
    
    /**
     * @var CommentModel Modèle pour la récupération des commentaires approuvés
     */
    private CommentModel $commentModel;

    /**
     * Constructeur
     * 
     * Initialise les modèles nécessaires pour l'affichage complet d'un article.
     * Utilise trois modèles différents pour séparer les responsabilités.
     */
    public function __construct() {
        parent::__construct(); 
        
        // Initialisation des modèles via Dependency Injection
        // Séparation des responsabilités : chaque modèle gère un aspect
        $this->postModel = new PostModel();        // Données article de base
        $this->articleModel = new ArticleModel();  // Tags et relations
        $this->commentModel = new CommentModel();  // Commentaires approuvés
    }

    /**
     * Affiche un article spécifique par son ID (Action Show)
     * 
     * Affiche la page complète d'un article incluant :
     * - Les détails de l'article (titre, contenu, date, auteur)
     * - Les tags associés (pour navigation thématique)
     * - Les commentaires approuvés (pour interaction)
     * 
     * Vérifications de sécurité :
     * - L'article doit exister
     * - L'article doit avoir le statut "Public" (pas "Brouillon" ni "Archivé")
     * 
     * @param int $id ID de l'article à afficher
     * @return void
     */
    public function show(int $id): void {
        // SECTION 1 : Récupération et validation de l'article
        // ----------------------------------------------------
        // Récupération de l'article via le modèle PostModel
        $post = $this->postModel->findById($id);

        // Vérification double : existence ET statut "Public"
        // Important pour la sécurité : ne pas afficher les brouillons/archivés
        if (!$post || $post->statut !== 'Public') {
            // Article non trouvé ou non public → page 404
            // Utilisation du HomeController pour respecter la séparation des responsabilités
            (new HomeController())->error404();
            return;
        }

        // SECTION 2 : Enrichissement des données de l'article
        // ----------------------------------------------------
        // ----- Sous-section 2.1 : Chargement des tags -----
        // Récupère les tags associés à l'article pour affichage et navigation
        // Conforme à EF-ARTICLE-04 : association d'articles à des tags
        $post->tags = $this->articleModel->getArticleTags($id);
        
        // ----- Sous-section 2.2 : Chargement des commentaires -----
        // Récupère uniquement les commentaires approuvés (statut = 'Approuvé')
        // Conforme à EF-COMMENT-01 : affichage des commentaires associés
        // et EF-COMMENT-03 : ne montre que les commentaires approuvés
        $post->comments = $this->commentModel->findApprovedByArticle($id);
        
        // Points d'extension possibles :
        // - Pagination des commentaires (si nombreux)
        // - Tri des commentaires (plus récents, plus votés)
        // - Système de réponses imbriquées

        // SECTION 3 : Rendu de la vue
        // ---------------------------
        $this->render('post_show.twig', [
            'page_title' => $post->titre,  // Titre de page dynamique (bon pour SEO)
            'post' => $post                // Toutes les données de l'article enrichies
        ]);
        
        // Journalisation optionnelle pour statistiques :
        // $this->logger->info("Article consulté ID: $id - Titre: " . $post->titre);
    }
}