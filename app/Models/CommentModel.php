<?php

namespace App\Models;

use App\Core\BaseModel;
use PDOException;

/**
 * CommentModel - Gestion des commentaires et de la modération
 * Implémente le cycle de vie des commentaires (création, approbation, suppression)
 * @conformité EF-COMMENT-01, EF-COMMENT-02, EF-COMMENT-03, EF-COMMENT-04
 */
class CommentModel extends BaseModel {
    
    /**
     * Retourne le nombre total de commentaires (Statistiques Admin)
     */
    public function countAll(): int {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM commentaires");
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            $this->logger->error("Erreur comptage commentaires", $e);
            return 0;
        }
    }

    /**
     * Retourne le nombre de commentaires nécessitant une modération
     */
    public function countPending(): int {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM commentaires WHERE statut = 'En attente'");
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            $this->logger->error("Erreur comptage commentaires en attente", $e);
            return 0;
        }
    }

    /**
     * Récupère les X commentaires les plus récents (Fil d'activité)
     */
    public function findRecent(int $limit = 5): array {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, a.titre as article_titre 
                FROM commentaires c
                LEFT JOIN articles a ON c.article_id = a.id
                ORDER BY c.date_commentaire DESC 
                LIMIT ?
            ");
            $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logger->error("Erreur récupération commentaires récents", $e);
            return [];
        }
    }

    /**
     * Récupère tous les commentaires avec pagination (Interface Admin)
     */
    public function findAll(?int $limit = null, ?int $offset = null): array {
        try {
            $sql = "SELECT c.*, a.titre as article_titre 
                    FROM commentaires c
                    LEFT JOIN articles a ON c.article_id = a.id
                    ORDER BY c.date_commentaire DESC";
            
            if ($limit !== null) {
                $sql .= " LIMIT :limit";
                if ($offset !== null) $sql .= " OFFSET :offset";
            }
            
            $stmt = $this->db->prepare($sql);
            if ($limit !== null) {
                $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
                if ($offset !== null) $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            }
            
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logger->error("Erreur récupération commentaires", $e);
            return [];
        }
    }

    /**
     * Supprime définitivement un commentaire (Action de modération)
     */
    public function delete(int $id): bool {
        try {
            return $this->db->prepare("DELETE FROM commentaires WHERE id = ?")->execute([$id]);
        } catch (PDOException $e) {
            $this->logger->error("Erreur suppression commentaire ID: $id", $e);
            return false;
        }
    }

    /**
     * Met à jour le statut d'un commentaire (Approuvé, Rejeté, En attente)
     */
    public function updateStatus(int $id, string $status): bool {
        try {
            return $this->db->prepare("UPDATE commentaires SET statut = ? WHERE id = ?")->execute([$status, $id]);
        } catch (PDOException $e) {
            $this->logger->error("Erreur mise à jour statut commentaire ID: $id", $e);
            return false;
        }
    }

    /**
     * Recherche un commentaire par son identifiant unique
     */
    public function findById(int $id): object|false {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, a.titre as article_titre FROM commentaires c
                LEFT JOIN articles a ON c.article_id = a.id WHERE c.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            $this->logger->error("Erreur récupération commentaire ID: $id", $e);
            return false;
        }
    }

    /**
     * Récupère tous les commentaires enrichis des informations de l'article parent
     */
    public function findAllWithArticles(): array {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, a.titre as article_titre, a.slug as article_slug
                FROM commentaires c LEFT JOIN articles a ON c.article_id = a.id
                ORDER BY c.date_commentaire DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logger->error("Erreur récupération commentaires avec articles", $e);
            return [];
        }
    }

    /**
     * Insère un nouveau commentaire et déclenche une notification si nécessaire
     */
    public function create(array $data): int {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO commentaires (article_id, nom_auteur, email_auteur, contenu, statut) 
                VALUES (:article_id, :nom_auteur, :email_auteur, :contenu, :statut)
            ");
            
            $status = $data['statut'] ?? 'En attente';
            $stmt->execute([
                ':article_id'  => $data['article_id'],
                ':nom_auteur'  => $data['nom_auteur'],
                ':email_auteur' => $data['email_auteur'] ?? null,
                ':contenu'     => $data['contenu'],
                ':statut'      => $status
            ]);
            
            $commentId = (int) $this->db->lastInsertId();
            
            // Notification automatique (EF-COMMENT-04)
            if ($status === 'En attente') $this->sendNewCommentNotification($commentId);
            
            return $commentId;
        } catch (PDOException $e) {
            $this->logger->error("Erreur création commentaire", $e);
            return 0;
        }
    }

    /**
     * Orchestre l'envoi de l'email de notification aux administrateurs
     */
    private function sendNewCommentNotification(int $commentId): void {
        try {
            $comment = $this->getCommentWithArticleInfo($commentId);
            $adminEmail = $this->getAdminEmail();
            
            if ($comment && $adminEmail) {
                $commentData = ['nom_auteur' => $comment->nom_auteur, 'email_auteur' => $comment->email_auteur, 'contenu' => $comment->contenu];
                $articleData = ['titre' => $comment->article_titre, 'id' => $comment->article_id];
                
                \App\Core\EmailService::getInstance()->sendCommentNotification($commentData, $articleData, $adminEmail);
                $this->logger->info("Notification commentaire envoyée ID: $commentId");
            }
        } catch (\Exception $e) {
            $this->logger->error("Erreur notification commentaire ID: $commentId", $e);
        }
    }

    /**
     * Récupère un commentaire couplé aux données de son article
     */
    private function getCommentWithArticleInfo(int $commentId): object|false {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, a.titre as article_titre, a.id as article_id
                FROM commentaires c JOIN articles a ON c.article_id = a.id WHERE c.id = ?
            ");
            $stmt->execute([$commentId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Identifie l'email du premier administrateur actif pour les notifications
     */
    private function getAdminEmail(): ?string {
        try {
            $stmt = $this->db->prepare("
                SELECT u.email FROM utilisateurs u
                JOIN role_user ru ON u.id = ru.user_id JOIN roles r ON ru.role_id = r.id
                WHERE r.nom_role = 'Administrateur' AND u.est_actif = 1 LIMIT 1
            ");
            $stmt->execute();
            $result = $stmt->fetch();
            return $result ? $result->email : null;
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Récupère les commentaires approuvés pour l'affichage public d'un article
     */
    public function findApprovedByArticle(int $articleId): array {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM commentaires 
                WHERE article_id = ? AND statut = 'Approuvé' ORDER BY date_commentaire DESC
            ");
            $stmt->execute([$articleId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logger->error("Erreur récupération commentaires approuvés article: $articleId", $e);
            return [];
        }
    }

    /**
     * Récupère les derniers commentaires avec slugs articles (Tableau de bord)
     */
    public function findRecentWithArticles(int $limit = 5): array {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, a.titre as article_titre, a.slug as article_slug
                FROM commentaires c LEFT JOIN articles a ON c.article_id = a.id
                ORDER BY c.date_commentaire DESC LIMIT ?
            ");
            $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logger->error("Erreur récupération commentaires récents enrichis", $e);
            return [];
        }
    }
}