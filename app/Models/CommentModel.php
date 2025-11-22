<?php
namespace App\Models;

use App\Core\BaseModel;
use PDOException;

class CommentModel extends BaseModel {
    
    /**
     * Compte tous les commentaires
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
     * Compte les commentaires en attente
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
     * Récupère les commentaires récents
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
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logger->error("Erreur récupération commentaires récents", $e);
            return [];
        }
    }

    /**
     * Récupère tous les commentaires avec pagination optionnelle
     */
    public function findAll(?int $limit = null, ?int $offset = null): array {
        try {
            $sql = "
                SELECT c.*, a.titre as article_titre 
                FROM commentaires c
                LEFT JOIN articles a ON c.article_id = a.id
                ORDER BY c.date_commentaire DESC
            ";
            
            if ($limit !== null) {
                $sql .= " LIMIT :limit";
                if ($offset !== null) {
                    $sql .= " OFFSET :offset";
                }
            }
            
            $stmt = $this->db->prepare($sql);
            
            if ($limit !== null) {
                $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
                if ($offset !== null) {
                    $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
                }
            }
            
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logger->error("Erreur récupération commentaires", $e);
            return [];
        }
    }

    /**
     * Supprime un commentaire
     */
    public function delete(int $id): bool {
        try {
            $stmt = $this->db->prepare("DELETE FROM commentaires WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            $this->logger->error("Erreur suppression commentaire ID: $id", $e);
            return false;
        }
    }

    /**
     * Met à jour le statut d'un commentaire
     */
    public function updateStatus(int $id, string $status): bool {
        try {
            $stmt = $this->db->prepare("UPDATE commentaires SET statut = ? WHERE id = ?");
            return $stmt->execute([$status, $id]);
        } catch (PDOException $e) {
            $this->logger->error("Erreur mise à jour statut commentaire ID: $id", $e);
            return false;
        }
    }

    /**
     * Récupère un commentaire par son ID
     */
    public function findById(int $id): object|false {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, a.titre as article_titre 
                FROM commentaires c
                LEFT JOIN articles a ON c.article_id = a.id
                WHERE c.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            $this->logger->error("Erreur récupération commentaire ID: $id", $e);
            return false;
        }
    }

    public function findAllWithArticles(): array {
    try {
        $stmt = $this->db->prepare("
            SELECT c.*, a.titre as article_titre, a.slug as article_slug
            FROM commentaires c
            LEFT JOIN articles a ON c.article_id = a.id
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
 * Crée un nouveau commentaire et envoie une notification
 */
public function create(array $data): int {
    try {
        $stmt = $this->db->prepare("
            INSERT INTO commentaires (article_id, nom_auteur, email_auteur, contenu, statut) 
            VALUES (:article_id, :nom_auteur, :email_auteur, :contenu, :statut)
        ");
        
        $stmt->execute([
            ':article_id' => $data['article_id'],
            ':nom_auteur' => $data['nom_auteur'],
            ':email_auteur' => $data['email_auteur'] ?? null,
            ':contenu' => $data['contenu'],
            ':statut' => $data['statut'] ?? 'En attente'
        ]);
        
        $commentId = (int) $this->db->lastInsertId();
        
        // Envoyer une notification email si le commentaire est en attente
        if (($data['statut'] ?? 'En attente') === 'En attente') {
            $this->sendNewCommentNotification($commentId);
        }
        
        return $commentId;
    } catch (PDOException $e) {
        $this->logger->error("Erreur création commentaire", $e);
        return 0;
    }
}

/**
 * Envoie une notification pour un nouveau commentaire
 */
private function sendNewCommentNotification(int $commentId): void {
    try {
        // Récupérer les informations du commentaire et de l'article
        $comment = $this->getCommentWithArticleInfo($commentId);
        
        if (!$comment) {
            $this->logger->error("Commentaire non trouvé pour notification ID: $commentId");
            return;
        }
        
        // Récupérer l'email de l'administrateur
        $adminEmail = $this->getAdminEmail();
        
        if (!$adminEmail) {
            $this->logger->error("Aucun administrateur trouvé pour notification");
            return;
        }
        
        // Préparer les données pour l'email
        $commentData = [
            'nom_auteur' => $comment->nom_auteur,
            'email_auteur' => $comment->email_auteur,
            'contenu' => $comment->contenu
        ];
        
        $articleData = [
            'titre' => $comment->article_titre,
            'id' => $comment->article_id
        ];
        
        // Envoyer la notification
        $emailService = \App\Core\EmailService::getInstance();
        $emailSent = $emailService->sendCommentNotification($commentData, $articleData, $adminEmail);
        
        if ($emailSent) {
            $this->logger->info("Notification commentaire envoyée ID: $commentId à: $adminEmail");
        } else {
            $this->logger->error("Échec envoi notification commentaire ID: $commentId");
        }
        
    } catch (\Exception $e) {
        $this->logger->error("Erreur notification commentaire ID: $commentId", $e);
    }
}

/**
 * Récupère un commentaire avec les infos de l'article
 */
private function getCommentWithArticleInfo(int $commentId): object|false {
    try {
        $stmt = $this->db->prepare("
            SELECT c.*, a.titre as article_titre, a.id as article_id
            FROM commentaires c
            JOIN articles a ON c.article_id = a.id
            WHERE c.id = ?
        ");
        $stmt->execute([$commentId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        $this->logger->error("Erreur récupération commentaire avec article ID: $commentId", $e);
        return false;
    }
}

/**
 * Récupère l'email d'un administrateur
 */
private function getAdminEmail(): ?string {
    try {
        $stmt = $this->db->prepare("
            SELECT u.email 
            FROM utilisateurs u
            JOIN role_user ru ON u.id = ru.user_id
            JOIN roles r ON ru.role_id = r.id
            WHERE r.nom_role = 'Administrateur' AND u.est_actif = 1
            LIMIT 1
        ");
        $stmt->execute();
        $result = $stmt->fetch();
        return $result ? $result->email : null;
    } catch (PDOException $e) {
        $this->logger->error("Erreur récupération email administrateur", $e);
        return null;
    }
}
/**
 * Récupère les commentaires approuvés d'un article
 */
public function findApprovedByArticle(int $articleId): array {
    try {
        $stmt = $this->db->prepare("
            SELECT * FROM commentaires 
            WHERE article_id = ? AND statut = 'Approuvé'
            ORDER BY date_commentaire DESC
        ");
        $stmt->execute([$articleId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        $this->logger->error("Erreur récupération commentaires approuvés article ID: $articleId", $e);
        return [];
    }
}

/**
 * Récupère les commentaires récents avec informations des articles
 */
public function findRecentWithArticles(int $limit = 5): array {
    try {
        $stmt = $this->db->prepare("
            SELECT c.*, a.titre as article_titre, a.slug as article_slug
            FROM commentaires c
            LEFT JOIN articles a ON c.article_id = a.id
            ORDER BY c.date_commentaire DESC 
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        $this->logger->error("Erreur récupération commentaires récents avec articles", $e);
        return [];
    }
}
}