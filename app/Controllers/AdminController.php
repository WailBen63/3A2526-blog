<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Models\ArticleModel;
use App\Models\CommentModel;
use App\Models\UserModel;

class AdminController extends BaseController {
    private ArticleModel $articleModel;
    private CommentModel $commentModel;
    private UserModel $userModel;

    public function __construct() {
        parent::__construct();
        $this->articleModel = new ArticleModel();
        $this->commentModel = new CommentModel();
        $this->userModel = new UserModel();
    }

    public function dashboard(): void {
        // Récupérer les statistiques réelles
        $stats = [
            'total_posts' => $this->articleModel->countAll(),
            'total_comments' => $this->commentModel->countAll(),
            'pending_comments' => $this->commentModel->countPending(),
            'total_users' => $this->userModel->countAll()
        ];

        // Récupérer les articles récents
        $recentPosts = $this->articleModel->findRecent(5);

        // Récupérer les commentaires récents
        $recentComments = $this->commentModel->findRecent(5);

        $this->render('admin/dashboard.twig', [
            'page_title' => 'Tableau de Bord Admin',
            'stats' => $stats,
            'recent_posts' => $recentPosts,
            'recent_comments' => $recentComments
        ]);
    }
}