<?php
namespace App\Models;

use App\Core\BaseModel;
use PDO;
use PDOException;

class PostModel extends BaseModel {

    /**
     * Récupère UNIQUEMENT les articles PUBLIÉS (pour la partie publique du blog)
     */
    public function findAll(): array {
        try {
            $stmt = $this->db->query("
                SELECT a.*, u.nom_utilisateur 
                FROM Articles a 
                JOIN Utilisateurs u ON a.utilisateur_id = u.id 
                WHERE a.statut = 'Publié'
                ORDER BY a.date_creation DESC
            ");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logger->error("Erreur récupération articles publiés", $e);
            return [];
        }
    }

    /**
     * Récupère un article par son ID (même s'il n'est pas publié)
     */
    public function findById(int $id): object|false {
    try {
        $stmt = $this->db->prepare("
            SELECT a.*, u.nom_utilisateur 
            FROM articles a 
            JOIN utilisateurs u ON a.utilisateur_id = u.id 
            WHERE a.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        $this->logger->error("Erreur récupération article ID $id", $e);
        return false;
    }
}

    /**
     * Compte tous les articles
     */
    public function countAll(): int {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM Articles");
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            $this->logger->error("Erreur comptage articles", $e);
            return 0;
        }
    }

    /**
     * Récupère les articles récents (pour le tableau de bord)
     */
    public function findRecent(int $limit = 5): array {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM Articles 
                ORDER BY date_creation DESC 
                LIMIT ?
            ");
            $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logger->error("Erreur récupération articles récents", $e);
            return [];
        }
    }
}