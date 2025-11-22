<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\AuthMiddleware;
use App\Models\CommentModel;

class AdminCommentController extends BaseController {
    private CommentModel $commentModel;

    public function __construct() {
        parent::__construct();
        
        // Vérifier l'authentification et les permissions
        AuthMiddleware::requireAuth();
        // AuthMiddleware::requirePermission('commentaire_gerer');
        
        $this->commentModel = new CommentModel();
    }

    /**
     * Liste tous les commentaires avec pagination
     */
    public function index(): void {
        // Récupérer tous les commentaires avec infos articles
        $comments = $this->commentModel->findAllWithArticles();
        
        $this->render('admin/comments/index.twig', [
            'page_title' => 'Modération des Commentaires',
            'comments' => $comments
        ]);
    }

    /**
     * Approuver un commentaire
     */
    public function approve(int $id): void {
        if ($this->commentModel->updateStatus($id, 'Approuvé')) {
            $this->logger->info("Commentaire approuvé ID: $id");
            $this->session->set('flash_success', 'Commentaire approuvé !');
        } else {
            $this->session->set('flash_error', 'Erreur lors de l\'approbation');
        }
        
        header('Location: /3A2526-Blog/public/admin/comments');
        exit;
    }

    /**
     * Rejeter un commentaire
     */
    public function reject(int $id): void {
        if ($this->commentModel->updateStatus($id, 'Rejeté')) {
            $this->logger->info("Commentaire rejeté ID: $id");
            $this->session->set('flash_success', 'Commentaire rejeté !');
        } else {
            $this->session->set('flash_error', 'Erreur lors du rejet');
        }
        
        header('Location: /3A2526-Blog/public/admin/comments');
        exit;
    }

    /**
     * Supprimer un commentaire
     */
    public function delete(int $id): void {
        if ($this->commentModel->delete($id)) {
            $this->logger->info("Commentaire supprimé ID: $id");
            $this->session->set('flash_success', 'Commentaire supprimé !');
        } else {
            $this->session->set('flash_error', 'Erreur lors de la suppression');
        }
        
        header('Location: /3A2526-Blog/public/admin/comments');
        exit;
    }
}