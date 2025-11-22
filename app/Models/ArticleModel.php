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
        $sql = "
            SELECT a.*, u.nom_utilisateur 
            FROM Articles a 
            JOIN Utilisateurs u ON a.utilisateur_id = u.id 
            WHERE a.statut = 'Public'  -- Seulement les articles Public, pas Archivé
            ORDER BY a.date_creation DESC
        ";
        
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
    /**
 * Récupère les tags d'un article
 */
public function getArticleTags(int $articleId): array {
    try {
        $stmt = $this->db->prepare("
            SELECT t.* 
            FROM tags t
            INNER JOIN article_tag at ON t.id = at.tag_id
            WHERE at.article_id = ?
            ORDER BY t.nom_tag ASC
        ");
        $stmt->execute([$articleId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        $this->logger->error("Erreur récupération tags article ID: $articleId", $e);
        return [];
    }
}

/**
 * Associe des tags à un article
 */
public function attachTagsToArticle(int $articleId, array $tagIds): bool {
    try {
        // D'abord supprimer les associations existantes
        $stmt = $this->db->prepare("DELETE FROM article_tag WHERE article_id = ?");
        $stmt->execute([$articleId]);
        
        // Puis ajouter les nouvelles associations
        if (!empty($tagIds)) {
            $stmt = $this->db->prepare("INSERT INTO article_tag (article_id, tag_id) VALUES (?, ?)");
            foreach ($tagIds as $tagId) {
                $stmt->execute([$articleId, $tagId]);
            }
        }
        return true;
    } catch (PDOException $e) {
        $this->logger->error("Erreur association tags article ID: $articleId", $e);
        return false;
    }
}

/**
 * Récupère tous les tags disponibles
 */
public function getAllTags(): array {
    try {
        $stmt = $this->db->query("SELECT * FROM tags ORDER BY nom_tag ASC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        $this->logger->error("Erreur récupération tous les tags", $e);
        return [];
    }
}

/**
 * Récupère tous les articles publiés avec leurs tags
 */
/**
 * Récupère tous les articles publiés avec leurs tags et image
 */
public function findPublishedWithTags(?int $limit = null): array {
    try {
        $sql = "SELECT a.*, u.nom_utilisateur 
                FROM articles a 
                JOIN utilisateurs u ON a.utilisateur_id = u.id 
                WHERE a.statut = 'Public'  -- Seulement les articles Public
                ORDER BY a.date_creation DESC";
        
        if ($limit !== null) {
            $sql .= " LIMIT :limit";
        }
        
        $stmt = $this->db->prepare($sql);
        
        if ($limit !== null) {
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        }
        
        $stmt->execute();
        $articles = $stmt->fetchAll();

        // Charger les tags pour chaque article
        foreach ($articles as $article) {
            $article->tags = $this->getArticleTags($article->id);
        }

        return $articles;
    } catch (PDOException $e) {
        $this->logger->error("Erreur récupération articles publiés avec tags", $e);
        return [];
    }
}

/**
 * Récupère les articles publiés par tag ID
 */
public function findPublishedByTag(int $tagId): array {
    try {
        $stmt = $this->db->prepare("
            SELECT a.*, u.nom_utilisateur 
            FROM articles a
            JOIN utilisateurs u ON a.utilisateur_id = u.id
            JOIN article_tag at ON a.id = at.article_id
            WHERE at.tag_id = ? AND a.statut = 'Public'  -- Seulement les articles Public
            ORDER BY a.date_creation DESC
        ");
        $stmt->execute([$tagId]);
        $articles = $stmt->fetchAll();

        // Ajouter les tags à chaque article
        foreach ($articles as $article) {
            $article->tags = $this->getArticleTags($article->id);
        }

        return $articles;
    } catch (PDOException $e) {
        $this->logger->error("Erreur récupération articles par tag ID: $tagId", $e);
        return [];
    }
}

/**
 * Met à jour l'image à la une d'un article
 */
public function updateImage(int $id, ?string $filename): bool {
    try {
        $stmt = $this->db->prepare("UPDATE articles SET image_une = ? WHERE id = ?");
        return $stmt->execute([$filename, $id]);
    } catch (PDOException $e) {
        $this->logger->error("Erreur mise à jour image article ID: $id", $e);
        return false;
    }
}

/**
 * Récupère l'image à la une d'un article
 */
public function getArticleImage(int $articleId): ?string {
    try {
        $stmt = $this->db->prepare("SELECT image_une FROM articles WHERE id = ?");
        $stmt->execute([$articleId]);
        $result = $stmt->fetch();
        return $result ? $result->image_une : null;
    } catch (PDOException $e) {
        $this->logger->error("Erreur récupération image article ID: $articleId", $e);
        return null;
    }
}





}