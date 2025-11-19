<?php
namespace App\Models;

use App\Core\BaseModel;
use PDOException;

class ArticleModel extends BaseModel {

    /**
     * Récupère tous les articles (avec pagination optionnelle)
     */
    public function findAll(int $limit = null, int $offset = null): array {
        try {
            $sql = "SELECT a.*, u.nom_utilisateur 
                    FROM articles a 
                    JOIN utilisateurs u ON a.utilisateur_id = u.id 
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
     * Récupère un article par son ID
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
                INSERT INTO articles (utilisateur_id, titre, slug, contenu, statut) 
                VALUES (:user_id, :titre, :slug, :contenu, :statut)
            ");
            
            $stmt->execute([
                ':user_id' => $data['user_id'],
                ':titre' => $data['titre'],
                ':slug' => $this->generateSlug($data['titre']),
                ':contenu' => $data['contenu'],
                ':statut' => $data['statut'] ?? 'Brouillon'
            ]);
            
            return $this->db->lastInsertId();
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
                UPDATE articles 
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
            $stmt = $this->db->prepare("DELETE FROM articles WHERE id = ?");
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
            $stmt = $this->db->query("SELECT COUNT(*) FROM articles");
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            $this->logger->error("Erreur comptage articles", $e);
            return 0;
        }
    }
}