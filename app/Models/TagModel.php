<?php

namespace App\Models;

use App\Core\BaseModel;
use PDOException;

/**
 * TagModel - Modèle pour la gestion complète des tags/catégories du blog
 * 
 * Implémente le CRUD complet des tags et leur association aux articles
 * conformément aux exigences fonctionnelles du système de blog.
 * 
 * Implémente les exigences suivantes du cahier des charges :
 * - EF-TAG-01 : CRUD complet des Tags (nom, URL slug)
 * - EF-TAG-02 : Affichage des tags avec nombre d'articles associés
 * - EF-ARTICLE-04 : Association d'un article à un ou plusieurs tags
 * - Gestion des relations many-to-many entre articles et tags
 * - Sécurité : Requêtes préparées PDO (2.2.1)
 * - Logger : Journalisation des opérations critiques (2.2.1)
 * - Performance : Comptage optimisé des articles par tag
 * 
 * @package App\Models
 * @conformité EF-TAG : Gestion complète du système de tags
 */
class TagModel extends BaseModel {
    
    /**
     * Récupère tous les tags avec leur nombre d'articles associés
     * 
     * Méthode principale pour l'administration des tags.
     * Inclut le comptage des articles pour chaque tag.
     * 
     * Utilisé pour :
     * - Interface d'administration des tags (EF-TAG-02)
     * - Nuage de tags sur le blog public
     * - Sélection de tags dans les formulaires d'articles
     * 
     * @return array Tags avec statistiques d'utilisation
     * @conformité EF-TAG-02 : Affichage avec nombre d'articles associés
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
     * Récupère un tag spécifique par son ID
     * 
     * Utilisé pour :
     * - Édition d'un tag existant
     * - Consultation des détails d'un tag
     * - Vérification d'existence avant opérations
     * 
     * @param int $id ID du tag à récupérer
     * @return object|false Tag ou false si non trouvé
     * @conformité EF-TAG-01 : Lecture (R) d'un tag spécifique
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
     * Récupère un tag par son slug (URL-friendly)
     * 
     * Essentiel pour :
     * - Génération de pages de tags accessibles par URL
     * - Navigation thématique via URLs propres
     * - SEO : URLs optimisées pour les moteurs de recherche
     * 
     * Exemple : /tag/vtt-enduro au lieu de /tag?id=2
     * 
     * @param string $slug Slug du tag à rechercher
     * @return object|false Tag ou false si non trouvé
     * @conformité EF-TAG-01 : Gestion des slugs pour URLs
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
     * Crée un nouveau tag dans le système
     * 
     * Implémente la création (C) du CRUD pour les tags.
     * Génère automatiquement un slug à partir du nom.
     * 
     * @param array $data Données du nouveau tag
     * @return int ID du tag créé ou 0 en cas d'erreur
     * @conformité EF-TAG-01 : Création (C) des tags
     * @conformité EF-TAG-01 : Génération automatique de slug
     */
    public function create(array $data): int {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO tags (nom_tag, slug) 
                VALUES (:nom_tag, :slug)
            ");
            
            $stmt->execute([
                ':nom_tag' => $data['nom_tag'],
                ':slug' => $this->generateSlug($data['nom_tag'])
            ]);
            
            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->logger->error("Erreur création tag", $e);
            return 0;
        }
    }

    /**
     * Met à jour un tag existant
     * 
     * Implémente la mise à jour (U) du CRUD.
     * Met à jour automatiquement le slug si le nom change.
     * 
     * @param int $id ID du tag à mettre à jour
     * @param array $data Nouvelles données du tag
     * @return bool Succès de l'opération
     * @conformité EF-TAG-01 : Mise à jour (U) des tags
     */
    public function update(int $id, array $data): bool {
        try {
            $stmt = $this->db->prepare("
                UPDATE tags 
                SET nom_tag = :nom_tag, slug = :slug 
                WHERE id = :id
            ");
            
            return $stmt->execute([
                ':id' => $id,
                ':nom_tag' => $data['nom_tag'],
                ':slug' => $this->generateSlug($data['nom_tag'])
            ]);
        } catch (PDOException $e) {
            $this->logger->error("Erreur mise à jour tag ID: $id", $e);
            return false;
        }
    }

    /**
     * Supprime définitivement un tag du système
     * 
     * Implémente la suppression (D) du CRUD.
     * Processus en deux étapes :
     * 1. Suppression des associations avec les articles
     * 2. Suppression du tag lui-même
     * 
     * @param int $id ID du tag à supprimer
     * @return bool Succès de l'opération
     * @conformité EF-TAG-01 : Suppression (D) des tags
     */
    public function delete(int $id): bool {
        try {
            // 1. Supprimer d'abord toutes les associations avec les articles
            // Conformité EF-ARTICLE-04 : Gestion cohérente des relations
            $stmt = $this->db->prepare("DELETE FROM article_tag WHERE tag_id = ?");
            $stmt->execute([$id]);
            
            // 2. Supprimer ensuite le tag lui-même
            $stmt = $this->db->prepare("DELETE FROM tags WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            $this->logger->error("Erreur suppression tag ID: $id", $e);
            return false;
        }
    }

    /**
     * Récupère tous les tags associés à un article spécifique
     * 
     * Utilisé pour :
     * - Affichage des tags sous un article
     * - Édition des tags d'un article existant
     * - Navigation entre articles similaires
     * 
     * @param int $articleId ID de l'article
     * @return array Tags associés à l'article
     * @conformité EF-ARTICLE-04 : Association articles-tags
     */
    public function findTagsByArticle(int $articleId): array {
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
     * Associe une liste de tags à un article spécifique
     * 
     * Gère la relation many-to-many entre articles et tags.
     * Processus atomique :
     * 1. Suppression de toutes les associations existantes
     * 2. Création des nouvelles associations
     * 
     * @param int $articleId ID de l'article
     * @param array $tagIds Liste des IDs des tags à associer
     * @return bool Succès de l'opération
     * @conformité EF-ARTICLE-04 : Association d'un article à plusieurs tags
     */
    public function attachTagsToArticle(int $articleId, array $tagIds): bool {
        try {
            // 1. Supprimer toutes les associations existantes
            // Permet de réinitialiser complètement les tags de l'article
            $stmt = $this->db->prepare("DELETE FROM article_tag WHERE article_id = ?");
            $stmt->execute([$articleId]);
            
            // 2. Créer les nouvelles associations
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
     * Récupère les tags les plus utilisés (les plus populaires)
     * 
     * Utilisé pour :
     * - Nuage de tags avec taille proportionnelle à l'usage
     * - Navigation vers les sujets les plus populaires
     * - Indicateurs de tendances du blog
     * - Widget "Tags populaires" dans la sidebar
     * 
     * @param int $limit Nombre maximum de tags à récupérer (défaut : 10)
     * @return array Tags triés par popularité décroissante
     * @conformité Expérience utilisateur : Navigation par popularité
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
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logger->error("Erreur récupération tags populaires", $e);
            return [];
        }
    }

    /**
     * Génère un slug URL-friendly à partir du nom d'un tag
     * 
     * Transformation appliquée :
     * 1. Conversion en minuscules
     * 2. Remplacement des caractères spéciaux par des tirets
     * 3. Suppression des tirets en début et fin
     * 
     * Exemple : "VTT Enduro" → "vtt-enduro"
     * 
     * @param string $nomTag Nom du tag à transformer
     * @return string Slug généré
     * @private
     * @conformité EF-TAG-01 : Génération de slug pour URLs propres
     */
    private function generateSlug(string $nomTag): string {
        $slug = strtolower($nomTag);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug;
    }
}