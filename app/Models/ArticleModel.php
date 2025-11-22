<?php
namespace App\Models;

use App\Core\BaseModel;
use PDOException;

class ArticleModel extends BaseModel {

    /**
     * Récupère tous les articles (avec pagination optionnelle)
     */
    public function findAll(?int $limit = null, ?int $offset = null): array {
        try {
            $sql = "SELECT a.*, u.nom_utilisateur 
                    FROM Articles a 
                    JOIN Utilisateurs u ON a.utilisateur_id = u.id 
                    ORDER BY a.date_creation DESC";
            
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
            $this->logger->error("Erreur récupération articles", $e);
            return [];
        }
    }

    /**
     * Récupère UNIQUEMENT les articles PUBLIÉS (pour la partie publique)
     */
    public function findPublished(?int $limit = null): array {
        try {
            $sql = "SELECT a.*, u.nom_utilisateur 
                    FROM Articles a 
                    JOIN Utilisateurs u ON a.utilisateur_id = u.id 
                    WHERE a.statut = 'Publié'
                    ORDER BY a.date_creation DESC";
            
            if ($limit !== null) {
                $sql .= " LIMIT :limit";
            }
            
            $stmt = $this->db->prepare($sql);
            
            if ($limit !== null) {
                $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            }
            
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logger->error("Erreur récupération articles publiés", $e);
            return [];
        }
    }

    /**
     * Récupère un article par son ID
     */
    public function findById(int $id): object|false {
        try {
            $stmt = $this->db->prepare("
                SELECT a.*, u.nom_utilisateur 
                FROM Articles a 
                JOIN Utilisateurs u ON a.utilisateur_id = u.id 
                WHERE a.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            $this->logger->error("Erreur récupération article ID: $id", $e);
            return false;
        }
    }

    /**
     * Crée un nouvel article
     */
    public function create(array $data): int {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO Articles (utilisateur_id, titre, slug, contenu, statut) 
                VALUES (:user_id, :titre, :slug, :contenu, :statut)
            ");
            
            $stmt->execute([
                ':user_id' => $data['user_id'],
                ':titre' => $data['titre'],
                ':slug' => $this->generateSlug($data['titre']),
                ':contenu' => $data['contenu'],
                ':statut' => $data['statut'] ?? 'Brouillon'
            ]);
            
            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->logger->error("Erreur création article", $e);
            return 0;
        }
    }

    /**
     * Met à jour un article
     */
    public function update(int $id, array $data): bool {
        try {
            $stmt = $this->db->prepare("
                UPDATE Articles 
                SET titre = :titre, slug = :slug, contenu = :contenu, statut = :statut, 
                    date_mise_a_jour = CURRENT_TIMESTAMP 
                WHERE id = :id
            ");
            
            return $stmt->execute([
                ':id' => $id,
                ':titre' => $data['titre'],
                ':slug' => $this->generateSlug($data['titre']),
                ':contenu' => $data['contenu'],
                ':statut' => $data['statut']
            ]);
        } catch (PDOException $e) {
            $this->logger->error("Erreur mise à jour article ID: $id", $e);
            return false;
        }
    }

    /**
     * Supprime un article
     */
    public function delete(int $id): bool {
        try {
            $stmt = $this->db->prepare("DELETE FROM Articles WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            $this->logger->error("Erreur suppression article ID: $id", $e);
            return false;
        }
    }

    /**
     * Génère un slug à partir du titre
     */
    private function generateSlug(string $titre): string {
        $slug = strtolower($titre);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug;
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
     * Récupère les articles récents
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