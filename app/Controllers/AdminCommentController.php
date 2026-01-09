<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\AuthMiddleware;
use App\Models\CommentModel;

/**
 * AdminCommentController
 * Contrôleur dédié à la modération administrative des commentaires
 */
class AdminCommentController extends BaseController {

    private CommentModel $commentModel;

    public function __construct() {
        parent::__construct();
        
        // Sécurité : Vérification de l'authentification obligatoire
        AuthMiddleware::requireAuth();
        
        $this->commentModel = new CommentModel();
    }

    /**
     * Liste tous les commentaires pour modération
     */
    public function index(): void {
        // Récupération des commentaires avec les informations des articles liés
        $comments = $this->commentModel->findAllWithArticles();
        
        $this->render('admin/comments/index.twig', [
            'page_title' => 'Modération des Commentaires',
            'comments' => $comments
        ]);
    }

    /**
     * Approuve un commentaire pour affichage public
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
     * Rejette un commentaire (masqué du public)
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
     * Supprime définitivement un commentaire de la base de données
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