<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\ArticleModel;
use App\Models\CommentModel;
use App\Models\UserModel;

/**
 * AdminController
 * 
 * Contrôleur principal pour le tableau de bord d'administration.
 * Fournit une vue d'ensemble des statistiques et activités du blog.
 * 
 * Conformité avec les exigences :
 * - EF-ADMIN-01 : Vue synthétique des statistiques clés
 * - EF-ADMIN-03 : Affichage d'un fil d'activité récent
 * - EF-ACL-05 : Accès conditionné par les permissions admin_access
 * 
 * @package App\Controllers
 */
class AdminController extends BaseController {
    /**
     * @var ArticleModel Modèle pour les opérations sur les articles
     */
    private ArticleModel $articleModel;
    
    /**
     * @var CommentModel Modèle pour les opérations sur les commentaires
     */
    private CommentModel $commentModel;
    
    /**
     * @var UserModel Modèle pour les opérations sur les utilisateurs
     */
    private UserModel $userModel;

    /**
     * Constructeur
     * 
     * Initialise les modèles nécessaires pour le tableau de bord.
     * Note: L'authentification est gérée par le routeur via AuthMiddleware.
     * 
     * Pattern : Dependency Injection (injection des dépendances via constructeur)
     */
    public function __construct() {
        parent::__construct();
        
        // Initialisation des modèles via Composition plutôt qu'Héritage
        // Respecte le principe de responsabilité unique (SOLID)
        $this->articleModel = new ArticleModel();
        $this->commentModel = new CommentModel();
        $this->userModel = new UserModel();
        
        // Note: La vérification de permission 'admin_access' est gérée
        // dans le routeur principal (index.php) via AuthMiddleware
    }

    /**
     * Tableau de bord (Action Dashboard)
     * 
     * Affiche le panneau d'administration principal avec :
     * - Statistiques globales du blog
     * - Articles récemment publiés
     * - Commentaires récents nécessitant modération
     * 
     * Cette méthode centralise les données nécessaires pour la prise de décision
     * administrative et suit le pattern Facade en simplifiant l'accès aux données.
     * 
     * @return void
     */
    public function dashboard(): void {
        // SECTION 1 : Récupération des statistiques en temps réel
        // ---------------------------------------------------------
        // Ces métriques fournissent une vue d'ensemble de l'activité du blog
        $stats = [
            'total_posts' => $this->articleModel->countAll(),        // EF-ADMIN-01
            'total_comments' => $this->commentModel->countAll(),     // EF-ADMIN-01
            'pending_comments' => $this->commentModel->countPending(), // Indicateur de modération nécessaire
            'total_users' => $this->userModel->countAll()            // EF-ADMIN-01
        ];
        
        // Validation : Vérification que les statistiques sont cohérentes
        // (Ex: pending_comments ne peut pas être > total_comments)
        if ($stats['pending_comments'] > $stats['total_comments']) {
            $this->logger->warning("Incohérence dans les statistiques de commentaires");
        }

        // SECTION 2 : Récupération des activités récentes
        // -------------------------------------------------
        // Fil d'activité pour surveillance en temps réel (EF-ADMIN-03)
        
        // Articles récemment créés/modifiés (limité à 5 pour performance)
        $recentPosts = $this->articleModel->findRecent(5);
        
        // Commentaires récents avec contexte des articles (EF-ADMIN-03)
        // Inclut les informations de l'article pour un meilleur contexte
        $recentComments = $this->commentModel->findRecentWithArticles(5);

        // SECTION 3 : Rendu de la vue
        // ----------------------------
        // Transmission des données au template Twig pour affichage
        $this->render('admin/dashboard.twig', [
            'page_title' => 'Tableau de Bord Admin',   // Titre de la page
            'stats' => $stats,                         // Statistiques à afficher
            'recent_posts' => $recentPosts,            // Articles récents
            'recent_comments' => $recentComments       // Commentaires récents avec contexte
        ]);
        
        // Journalisation d'audit (bonne pratique de sécurité)
        $this->logger->info("Tableau de bord consulté par: " . $this->session->get('user_name'));
    }
}