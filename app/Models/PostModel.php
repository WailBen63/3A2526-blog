<?php
namespace App\Models;

use App\Core\BaseModel;
use PDOException;

class PostModel extends BaseModel {

    /**
     * Récupère tous les articles de blog.
     */
    public function findAll(): array {
        try {
            $stmt = $this->db->query("SELECT * FROM posts ORDER BY created_at DESC");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logger->error("Erreur lors de la récupération de tous les posts", $e);
            return [];
        }
    }

    /**
     * Récupère un article par son ID.
     */
    public function findById(int $id): object|false {
        try {
            $stmt = $this->db->prepare("SELECT * FROM posts WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            $this->logger->error("Erreur lors de la récupération du post ID $id", $e);
            return false;
        }

    
    }

    /**
     * Compte tous les articles
     */
    public function countAll(): int {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM articles");
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            $this->logger->error("Erreur comptage articles", $e);
            return 0;
        }
    }

    /**
     * Récupère les articles récents
     */
    public function findRecent(int $limit = 5): array {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM articles 
                ORDER BY date_creation DESC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logger->error("Erreur récupération articles récents", $e);
            return [];
        }
    }
}
