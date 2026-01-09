<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\ArticleModel;
use App\Models\CommentModel;
use App\Models\UserModel;

/**
 * AdminController
 * Contrôleur principal gérant le tableau de bord d'administration
 */
class AdminController extends BaseController {

    private ArticleModel $articleModel;
    private CommentModel $commentModel;
    private UserModel $userModel;

    public function __construct() {
        parent::__construct();
        
        // Initialisation des modèles nécessaires au dashboard
        $this->articleModel = new ArticleModel();
        $this->commentModel = new CommentModel();
        $this->userModel = new UserModel();
    }

    /**
     * Affiche le tableau de bord (Vue synthétique et fil d'activité)
     */
    public function dashboard(): void {
        // Récupération des statistiques globales (EF-ADMIN-01)
        $stats = [
            'total_posts'      => $this->articleModel->countAll(),
            'total_comments'   => $this->commentModel->countAll(),
            'pending_comments' => $this->commentModel->countPending(),
            'total_users'      => $this->userModel->countAll()
        ];
        
        // Surveillance de la cohérence des données
        if ($stats['pending_comments'] > $stats['total_comments']) {
            $this->logger->warning("Incohérence détectée dans les statistiques de commentaires");
        }

        // Récupération des activités récentes pour le fil d'actualité (EF-ADMIN-03)
        $recentPosts = $this->articleModel->findRecent(5);
        $recentComments = $this->commentModel->findRecentWithArticles(5);

        // Rendu de la vue avec transmission des données
        $this->render('admin/dashboard.twig', [
            'page_title'      => 'Tableau de Bord Admin',
            'stats'           => $stats,
            'recent_posts'    => $recentPosts,
            'recent_comments' => $recentComments
        ]);
        
        // Journalisation de l'accès au tableau de bord
        $this->logger->info("Tableau de bord consulté par: " . $this->session->get('user_name'));
    }
}