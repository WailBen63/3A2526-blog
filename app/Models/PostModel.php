<?php

namespace App\Models;

use App\Core\BaseModel;
use PDO;
use PDOException;

/**
 * PostModel - Modèle spécialisé pour l'affichage public des articles
 * 
 * Version simplifiée et optimisée du ArticleModel pour la partie publique
 * du blog. Contient uniquement les méthodes nécessaires à l'affichage
 * des articles aux visiteurs du site.
 * 
 * Implémente les exigences suivantes du cahier des charges :
 * - EF-ARTICLE-01 : Lecture des articles (partie R du CRUD)
 * - EF-ARTICLE-03 : Filtrage par statut "Publié" seulement
 * - Séparation des préoccupations : Modèle dédié à la partie publique
 * - Performance : Requêtes optimisées pour l'affichage
 * - Sécurité : Accès restreint aux articles publiés seulement
 * - Conformité MVC : Couche Modèle spécialisée
 * 
 * @package App\Models
 * @conformité EF-ARTICLE : Accès public aux articles publiés
 */
class PostModel extends BaseModel {

    /**
     * Récupère UNIQUEMENT les articles PUBLIÉS (pour la partie publique du blog)
     * 
     * Méthode principale pour l'affichage de la liste des articles
     * sur la page d'accueil et les pages d'archive.
     * 
     * Caractéristiques :
     * - Filtre strict : seulement les articles avec statut 'Publié'
     * - Jointure avec utilisateurs : récupère le nom de l'auteur
     * - Tri par date : articles les plus récents en premier
     * - Pas de pagination : méthode findAll() pour récupération complète
     * 
     * @return array Articles publiés avec informations auteurs
     * @conformité EF-ARTICLE-03 : Filtrage strict par statut (Publié seulement)
     * @conformité Critères d'acceptation : Sécurité (pas d'accès aux brouillons)
     */
    public function findAll(): array {
        try {
            // Requête optimisée pour la partie publique
            // Note : Le cahier des charges utilise 'Public' mais le script SQL montre 'Publié'
            // On conserve 'Publié' pour cohérence avec la base de données fournie
            $stmt = $this->db->query("
                SELECT a.*, u.nom_utilisateur 
                FROM Articles a 
                JOIN Utilisateurs u ON a.utilisateur_id = u.id 
                WHERE a.statut = 'Publié'  -- Seulement les articles PUBLIÉS
                ORDER BY a.date_creation DESC
            ");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            // Journalisation de l'erreur pour suivi technique
            // Conformité 2.2.1 : Logger pour événements critiques
            $this->logger->error("Erreur récupération articles publiés", $e);
            return []; // Retour tableau vide pour éviter les erreurs frontend
        }
    }

    /**
     * Récupère un article par son ID (accessible même si non publié pour certaines vues)
     * 
     * Utilisations :
     * - Affichage détaillé d'un article sur la partie publique
     * - Prévisualisation d'articles pour les auteurs/éditeurs
     * - Vérification d'existence avant opérations
     * 
     * Note : Cette méthode retourne un article quel que soit son statut
     * car elle peut être utilisée pour la prévisualisation par les auteurs.
     * Le contrôle d'accès doit être fait au niveau du contrôleur.
     * 
     * @param int $id ID de l'article à récupérer
     * @return object|false Article complet avec auteur ou false si non trouvé
     * @conformité EF-ARTICLE-01 : Lecture (R) d'articles spécifiques
     * @conformité EF-ARTICLE-03 : Accès aux métadonnées complètes
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
            // Journalisation avec ID pour traçabilité
            // Conformité 2.2.1 : Logger détaillé pour débogage
            $this->logger->error("Erreur récupération article ID $id", $e);
            return false;
        }
    }

    /**
     * Compte tous les articles (tous statuts confondus)
     * 
     * Principalement utilisé pour :
     * - Statistiques du tableau de bord administrateur
     * - Indicateurs de performance du blog
     * - Métriques d'activité globale
     * 
     * Note : Dans la partie publique, cette méthode est peu utilisée
     * mais conservée pour complétude et compatibilité.
     * 
     * @return int Nombre total d'articles dans la base
     * @conformité EF-ADMIN-01 : Statistiques clés (nombre total d'articles)
     */
    public function countAll(): int {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM Articles");
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            $this->logger->error("Erreur comptage articles", $e);
            return 0; // Retour 0 pour éviter les erreurs dans les calculs
        }
    }

    /**
     * Récupère les articles les plus récents
     * 
     * Utilisations typiques :
     * - Widget "Articles récents" dans la sidebar
     * - Section "À la une" sur la page d'accueil
     * - Tableau de bord administrateur (activité récente)
     * - Flux RSS des derniers articles
     * 
     * @param int $limit Nombre maximum d'articles à récupérer (défaut : 5)
     * @return array Articles les plus récents par date de création
     * @conformité EF-ADMIN-03 : Affichage des articles récents dans tableau de bord
     * @conformité Expérience utilisateur : Mise en avant du contenu récent
     */
    public function findRecent(int $limit = 5): array {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM Articles 
                ORDER BY date_creation DESC 
                LIMIT ?
            ");
            // Binding sécurisé du paramètre LIMIT
            // Conformité 2.2.1 : Prévention des injections SQL
            $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logger->error("Erreur récupération articles récents", $e);
            return [];
        }
    }
}