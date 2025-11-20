<?php
namespace App\Models;

use App\Core\BaseModel;
use PDO;
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
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logger->error("Erreur récupération commentaires récents", $e);
            return [];
        }
    }
}