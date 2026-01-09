<?php

namespace App\Models;

use App\Core\BaseModel;
use PDOException;

/**
 * TagModel - Gestion des étiquettes et des catégories du blog
 * Implémente le CRUD des tags et les relations avec les articles
 * @conformité EF-TAG-01, EF-TAG-02, EF-ARTICLE-04
 */
class TagModel extends BaseModel {
    
    /**
     * Récupère tous les tags avec le décompte des articles associés
     */
    public function findAll(): array {
        try {
            $stmt = $this->db->query("
                SELECT t.*, COUNT(at.article_id) as nb_articles
                FROM tags t
                LEFT JOIN article_tag at ON t.id = at.tag_id
                GROUP BY t.id
                ORDER BY t.nom_tag ASC
            ");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logger->error("Erreur récupération tags", $e);
            return [];
        }
    }

    /**
     * Recherche un tag par son identifiant unique
     */
    public function findById(int $id): object|false {
        try {
            $stmt = $this->db->prepare("SELECT * FROM tags WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            $this->logger->error("Erreur récupération tag ID: $id", $e);
            return false;
        }
    }

    /**
     * Recherche un tag par son slug (URL-friendly)
     */
    public function findBySlug(string $slug): object|false {
        try {
            $stmt = $this->db->prepare("SELECT * FROM tags WHERE slug = ?");
            $stmt->execute([$slug]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            $this->logger->error("Erreur récupération tag slug: $slug", $e);
            return false;
        }
    }

    /**
     * Crée un nouveau tag et génère son slug (Create)
     */
    public function create(array $data): int {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO tags (nom_tag, slug) 
                VALUES (:nom_tag, :slug)
            ");
            
            $stmt->execute([
                ':nom_tag' => $data['nom_tag'],
                ':slug'    => $this->generateSlug($data['nom_tag'])
            ]);
            
            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->logger->error("Erreur création tag", $e);
            return 0;
        }
    }

    /**
     * Met à jour le nom et le slug d'un tag existant (Update)
     */
    public function update(int $id, array $data): bool {
        try {
            $stmt = $this->db->prepare("
                UPDATE tags 
                SET nom_tag = :nom_tag, slug = :slug 
                WHERE id = :id
            ");
            
            return $stmt->execute([
                ':id'      => $id,
                ':nom_tag' => $data['nom_tag'],
                ':slug'    => $this->generateSlug($data['nom_tag'])
            ]);
        } catch (PDOException $e) {
            $this->logger->error("Erreur mise à jour tag ID: $id", $e);
            return false;
        }
    }

    /**
     * Supprime un tag et nettoie les associations articles (Delete)
     */
    public function delete(int $id): bool {
        try {
            // Nettoyage de la table de liaison Many-to-Many
            $this->db->prepare("DELETE FROM article_tag WHERE tag_id = ?")->execute([$id]);
            
            // Suppression du tag
            $stmt = $this->db->prepare("DELETE FROM tags WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            $this->logger->error("Erreur suppression tag ID: $id", $e);
            return false;
        }
    }

    /**
     * Récupère la liste des tags liés à un article spécifique
     */
    public function findTagsByArticle(int $articleId): array {
        try {
            $stmt = $this->db->prepare("
                SELECT t.* FROM tags t
                INNER JOIN article_tag at ON t.id = at.tag_id
                WHERE at.article_id = ? ORDER BY t.nom_tag ASC
            ");
            $stmt->execute([$articleId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logger->error("Erreur récupération tags article ID: $articleId", $e);
            return [];
        }
    }

    /**
     * Gère la liaison entre un article et plusieurs tags
     */
    public function attachTagsToArticle(int $articleId, array $tagIds): bool {
        try {
            $this->db->prepare("DELETE FROM article_tag WHERE article_id = ?")->execute([$articleId]);
            
            if (!empty($tagIds)) {
                $stmt = $this->db->prepare("INSERT INTO article_tag (article_id, tag_id) VALUES (?, ?)");
                foreach ($tagIds as $tagId) $stmt->execute([$articleId, $tagId]);
            }
            return true;
        } catch (PDOException $e) {
            $this->logger->error("Erreur association tags article ID: $articleId", $e);
            return false;
        }
    }

    /**
     * Récupère les tags les plus utilisés pour le nuage de tags (Cloud)
     */
    public function findPopular(int $limit = 10): array {
        try {
            $stmt = $this->db->prepare("
                SELECT t.*, COUNT(at.article_id) as nb_articles
                FROM tags t
                LEFT JOIN article_tag at ON t.id = at.tag_id
                GROUP BY t.id
                ORDER BY nb_articles DESC, t.nom_tag ASC
                LIMIT ?
            ");
            $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logger->error("Erreur récupération tags populaires", $e);
            return [];
        }
    }

    /**
     * Transforme une chaîne en slug (minuscules, sans caractères spéciaux)
     */
    private function generateSlug(string $nomTag): string {
        $slug = strtolower($nomTag);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        return trim($slug, '-');
    }
}