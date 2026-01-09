<?php

namespace App\Models;

use App\Core\BaseModel;
use PDOException;

/**
 * ArticleModel - Modèle pour la gestion des articles du blog
 * 
 * Implémente les opérations CRUD complètes sur les articles
 * conformément aux exigences fonctionnelles du système de blog.
 * 
 * Implémente les exigences suivantes du cahier des charges :
 * - EF-ARTICLE-01 : CRUD complet des articles
 * - EF-ARTICLE-02 : Contenu formaté (HTML purifié)
 * - EF-ARTICLE-03 : Gestion des métadonnées (titre, slug, image, statut)
 * - EF-ARTICLE-04 : Association avec tags
 * - EF-ADMIN-03 : Affichage d'articles récents dans le tableau de bord
 * - Architecture MVC : Couche Modèle pour l'accès aux données
 * - Sécurité : Requêtes préparées PDO (2.2.1)
 * - Logger : Journalisation des erreurs (2.2.1)
 * 
 * @package App\Models
 * @conformité EF-ARTICLE : Gestion complète des articles
 */
class ArticleModel extends BaseModel {

    /**
     * Récupère tous les articles (avec pagination optionnelle)
     * 
     * Utilisé principalement dans l'administration pour :
     * - Liste complète des articles (EF-ADMIN-03)
     * - Pagination des résultats pour performance
     * - Accès aux articles quel que soit leur statut
     * 
     * @param int|null $limit Limite de résultats (pour pagination)
     * @param int|null $offset Décalage (pour pagination)
     * @return array Liste des articles avec auteurs
     * @conformité EF-ADMIN-03 : Affichage d'articles dans tableau de bord
     */
    public function findAll(?int $limit = null, ?int $offset = null): array {
        try {
            // Requête avec jointure pour récupérer l'auteur
            // Conformité EF-ARTICLE-03 : métadonnées complètes
            $sql = "SELECT a.*, u.nom_utilisateur 
                    FROM Articles a 
                    JOIN Utilisateurs u ON a.utilisateur_id = u.id 
                    ORDER BY a.date_creation DESC";
            
            // Ajout de la pagination si demandée
            if ($limit !== null) {
                $sql .= " LIMIT :limit";
                if ($offset !== null) {
                    $sql .= " OFFSET :offset";
                }
            }
            
            $stmt = $this->db->prepare($sql);
            
            // Bind sécurisé des paramètres de pagination
            // Conformité 2.2.1 : Accès DB sécurisé avec PDO
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
     * 
     * Garantit que seuls les articles avec statut 'Public' sont accessibles
     * aux visiteurs du blog. Les statuts 'Brouillon' et 'Archivé' sont exclus.
     * 
     * @param int|null $limit Nombre maximum d'articles à récupérer
     * @return array Articles publiés seulement
     * @conformité EF-ARTICLE-03 : Filtrage par statut (Public seulement)
     */
    public function findPublished(?int $limit = null): array {
        try {
            $sql = "
                SELECT a.*, u.nom_utilisateur 
                FROM Articles a 
                JOIN Utilisateurs u ON a.utilisateur_id = u.id 
                WHERE a.statut = 'Public'  -- Seulement les articles Public
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
     * 
     * Utilisé pour :
     * - Affichage détaillé d'un article (lecture)
     * - Édition d'un article existant (EF-ARTICLE-01)
     * - Visualisation d'article avant publication
     * 
     * @param int $id ID de l'article
     * @return object|false Article ou false si non trouvé
     * @conformité EF-ARTICLE-01 : Lecture (R) du CRUD
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
     * 
     * Implémente la création (C) du CRUD.
     * Gère automatiquement la génération du slug et le statut par défaut.
     * 
     * @param array $data Données de l'article
     * @return int ID du nouvel article ou 0 en cas d'erreur
     * @conformité EF-ARTICLE-01 : Création (C) du CRUD
     * @conformité EF-ARTICLE-03 : Génération de slug unique
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
     * Met à jour un article existant
     * 
     * Implémente la mise à jour (U) du CRUD.
     * Met automatiquement à jour le timestamp et régénère le slug.
     * 
     * @param int $id ID de l'article à mettre à jour
     * @param array $data Nouvelles données
     * @return bool Succès de l'opération
     * @conformité EF-ARTICLE-01 : Mise à jour (U) du CRUD
     * @conformité EF-ARTICLE-03 : Mise à jour du slug si titre changé
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
     * 
     * Implémente la suppression (D) du CRUD.
     * Les cascades définies dans la base suppriment automatiquement
     * les tags associés (via article_tag) et les commentaires.
     * 
     * @param int $id ID de l'article à supprimer
     * @return bool Succès de l'opération
     * @conformité EF-ARTICLE-01 : Suppression (D) du CRUD
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
     * Génère un slug URL-friendly à partir du titre
     * 
     * Utilisé pour créer des URLs propres et SEO-friendly.
     * Exemple : "Mon Super Article" → "mon-super-article"
     * 
     * @param string $titre Titre de l'article
     * @return string Slug généré
     * @conformité EF-ARTICLE-03 : Génération de slug pour URLs
     * @private
     */
    private function generateSlug(string $titre): string {
        $slug = strtolower($titre);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug;
    }

    /**
     * Compte tous les articles (tous statuts confondus)
     * 
     * Utilisé principalement pour :
     * - Statistiques du tableau de bord (EF-ADMIN-01)
     * - Calcul de pagination
     * - Métriques d'administration
     * 
     * @return int Nombre total d'articles
     * @conformité EF-ADMIN-01 : Statistiques clés (nombre d'articles)
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
     * Récupère les articles les plus récents
     * 
     * Utilisé pour :
     * - Page d'accueil du blog (articles récents)
     * - Widget "Articles récents" (sidebar)
     * - Tableau de bord (EF-ADMIN-03 : fil d'activité récent)
     * 
     * @param int $limit Nombre d'articles à récupérer (défaut : 5)
     * @return array Articles les plus récents
     * @conformité EF-ADMIN-03 : Affichage d'articles récents
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
     * Récupère les tags associés à un article
     * 
     * Implémente la relation many-to-many entre articles et tags.
     * 
     * @param int $articleId ID de l'article
     * @return array Tags associés à l'article
     * @conformité EF-ARTICLE-04 : Association article-tags
     * @conformité EF-TAG-02 : Affichage des tags avec articles
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
     * 
     * Gère la relation many-to-many en deux étapes :
     * 1. Suppression des associations existantes
     * 2. Ajout des nouvelles associations
     * 
     * @param int $articleId ID de l'article
     * @param array $tagIds IDs des tags à associer
     * @return bool Succès de l'opération
     * @conformité EF-ARTICLE-04 : Association d'un article à plusieurs tags
     */
    public function attachTagsToArticle(int $articleId, array $tagIds): bool {
        try {
            // 1. Supprimer les associations existantes
            $stmt = $this->db->prepare("DELETE FROM article_tag WHERE article_id = ?");
            $stmt->execute([$articleId]);
            
            // 2. Ajouter les nouvelles associations
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
     * 
     * Utilisé pour :
     * - Liste des tags dans les formulaires d'édition
     * - Administration des tags (EF-TAG-01)
     * - Nuage de tags sur le blog public
     * 
     * @return array Tous les tags triés par nom
     * @conformité EF-TAG-01 : CRUD des tags
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
     * Récupère tous les articles publiés avec leurs tags et images
     * 
     * Optimisé pour l'affichage public du blog.
     * Charge les tags pour chaque article en une seule requête.
     * 
     * @param int|null $limit Limite de résultats
     * @return array Articles publiés avec leurs tags
     * @conformité EF-ARTICLE-04 : Articles avec leurs tags associés
     */
    public function findPublishedWithTags(?int $limit = null): array {
        try {
            $sql = "SELECT a.*, u.nom_utilisateur 
                    FROM articles a 
                    JOIN utilisateurs u ON a.utilisateur_id = u.id 
                    WHERE a.statut = 'Public'
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
     * Récupère les articles publiés par tag spécifique
     * 
     * Utilisé pour :
     * - Page de filtrage par tag
     * - Navigation thématique
     * - Affichage des articles liés à un sujet
     * 
     * @param int $tagId ID du tag
     * @return array Articles publiés avec le tag spécifié
     * @conformité EF-TAG : Filtrage des articles par tag
     */
    public function findPublishedByTag(int $tagId): array {
        try {
            $stmt = $this->db->prepare("
                SELECT a.*, u.nom_utilisateur 
                FROM articles a
                JOIN utilisateurs u ON a.utilisateur_id = u.id
                JOIN article_tag at ON a.id = at.article_id
                WHERE at.tag_id = ? AND a.statut = 'Public'
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
     * 
     * Permet d'associer ou de changer l'image principale d'un article.
     * 
     * @param int $id ID de l'article
     * @param string|null $filename Nom du fichier image (null pour supprimer)
     * @return bool Succès de l'opération
     * @conformité EF-ARTICLE-03 : Gestion de l'image à la une
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
     * 
     * @param int $articleId ID de l'article
     * @return string|null Nom du fichier image ou null
     * @conformité EF-ARTICLE-03 : Récupération de l'image associée
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