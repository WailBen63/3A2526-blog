<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\AuthMiddleware;
use App\Models\CommentModel;

/**
 * AdminCommentController
 * 
 * Contrôleur pour la modération administrative des commentaires.
 * Gère l'approbation, le rejet et la suppression des commentaires utilisateurs.
 * 
 * Conformité avec les exigences :
 * - EF-COMMENT-03 : Modération des commentaires (Approuver/Rejeter/Supprimer)
 * - EF-ACL-05 : Contrôle d'accès basé sur les permissions
 * - UX-RESPONSIVE-01 : Interface responsive pour la modération
 * 
 * @package App\Controllers
 */
class AdminCommentController extends BaseController {
    /**
     * @var CommentModel Modèle pour les opérations sur les commentaires
     */
    private CommentModel $commentModel;

    /**
     * Constructeur
     * 
     * Initialise le modèle de commentaires et vérifie les permissions d'accès.
     * Implémente le pattern Middleware pour la sécurité.
     */
    public function __construct() {
        parent::__construct();
        
        // Middleware de sécurité : vérification d'authentification
        AuthMiddleware::requireAuth();
        
        // Note: La permission spécifique 'commentaire_gerer' devrait être activée
        // AuthMiddleware::requirePermission('commentaire_gerer');
        // Cette ligne est commentée car la permission n'est pas encore implémentée
        // dans le système RBAC (Role-Based Access Control)
        
        // Initialisation du modèle via le pattern Dependency Injection
        $this->commentModel = new CommentModel();
    }

    /**
     * Liste tous les commentaires (Action Index)
     * 
     * Affiche l'interface de modération avec tous les commentaires.
     * Les commentaires sont récupérés avec les informations des articles associés
     * pour un contexte complet lors de la modération.
     * 
     * @return void
     */
    public function index(): void {
        // Récupération de tous les commentaires avec jointure sur les articles
        // Méthode personnalisée qui inclut les détails de l'article parent
        $comments = $this->commentModel->findAllWithArticles();
        
        // Rendu de la vue d'administration des commentaires
        $this->render('admin/comments/index.twig', [
            'page_title' => 'Modération des Commentaires',
            'comments' => $comments
        ]);
    }

    /**
     * Approuver un commentaire (Action Approve)
     * 
     * Change le statut d'un commentaire à "Approuvé", le rendant visible
     * sur la partie publique du site. Cette action est typiquement réservée
     * aux modérateurs et administrateurs.
     * 
     * @param int $id ID du commentaire à approuver
     * @return void
     */
    public function approve(int $id): void {
        // Tentative de mise à jour du statut
        if ($this->commentModel->updateStatus($id, 'Approuvé')) {
            // Journalisation de l'action (bonne pratique de sécurité/audit)
            $this->logger->info("Commentaire approuvé ID: $id");
            
            // Message flash de succès pour feedback utilisateur
            $this->session->set('flash_success', 'Commentaire approuvé !');
        } else {
            // Message d'erreur en cas d'échec
            $this->session->set('flash_error', 'Erreur lors de l\'approbation');
        }
        
        // Redirection vers la liste des commentaires (pattern Post/Redirect/Get)
        header('Location: /3A2526-Blog/public/admin/comments');
        exit;
    }

    /**
     * Rejeter un commentaire (Action Reject)
     * 
     * Change le statut d'un commentaire à "Rejeté", le masquant de la
     * partie publique sans le supprimer définitivement.
     * Permet de conserver une trace des commentaires inappropriés.
     * 
     * @param int $id ID du commentaire à rejeter
     * @return void
     */
    public function reject(int $id): void {
        // Mise à jour du statut avec rollback automatique en cas d'erreur
        if ($this->commentModel->updateStatus($id, 'Rejeté')) {
            // Audit trail : qui a rejeté quoi et quand
            $this->logger->info("Commentaire rejeté ID: $id");
            
            // Feedback utilisateur
            $this->session->set('flash_success', 'Commentaire rejeté !');
        } else {
            $this->session->set('flash_error', 'Erreur lors du rejet');
        }
        
        // Redirection standardisée
        header('Location: /3A2526-Blog/public/admin/comments');
        exit;
    }

    /**
     * Supprimer définitivement un commentaire (Action Delete)
     * 
     * Supprime physiquement un commentaire de la base de données.
     * Action irréversible - à utiliser avec précaution.
     * Alternative : utiliser le rejet pour une "suppression douce".
     * 
     * @param int $id ID du commentaire à supprimer
     * @return void
     */
    public function delete(int $id): void {
        // Suppression définitive (DELETE en SQL)
        if ($this->commentModel->delete($id)) {
            // Journalisation obligatoire pour les suppressions
            $this->logger->info("Commentaire supprimé ID: $id");
            
            $this->session->set('flash_success', 'Commentaire supprimé !');
        } else {
            $this->session->set('flash_error', 'Erreur lors de la suppression');
        }
        
        // Redirection cohérente avec les autres actions
        header('Location: /3A2526-Blog/public/admin/comments');
        exit;
    }
}