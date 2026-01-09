<?php

namespace App\Models;

use App\Core\BaseModel;
use PDOException;

/**
 * CommentModel - Modèle pour la gestion des commentaires du blog
 * 
 * Implémente toutes les opérations de gestion des commentaires
 * conformément aux exigences fonctionnelles du système de blog.
 * 
 * Implémente les exigences suivantes du cahier des charges :
 * - EF-COMMENT-01 : Affichage des commentaires associés aux articles
 * - EF-COMMENT-02 : Post de commentaires par utilisateurs non connectés
 * - EF-COMMENT-03 : Modération des commentaires (Approuver/Désapprouver/Supprimer)
 * - EF-COMMENT-04 : Notification des nouveaux commentaires en attente
 * - EF-ADMIN-01 : Statistiques des commentaires (en attente)
 * - EF-ADMIN-03 : Affichage des commentaires récents dans tableau de bord
 * - Sécurité : Requêtes préparées PDO (2.2.1)
 * - Logger : Journalisation des opérations critiques (2.2.1)
 * 
 * @package App\Models
 * @conformité EF-COMMENT : Gestion complète des commentaires
 */
class CommentModel extends BaseModel {
    
    /**
     * Compte tous les commentaires
     * 
     * Utilisé pour les statistiques d'administration
     * et les métriques du tableau de bord.
     * 
     * @return int Nombre total de commentaires
     * @conformité EF-ADMIN-01 : Statistiques clés (nombre de commentaires)
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
     * Compte les commentaires en attente de modération
     * 
     * Essentiel pour la gestion de la modération
     * et l'affichage des indicateurs dans l'administration.
     * 
     * @return int Nombre de commentaires en attente
     * @conformité EF-COMMENT-03 : Gestion de la modération
     * @conformité EF-ADMIN-01 : Commentaires en attente (indicateur)
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
     * Récupère les commentaires les plus récents
     * 
     * Utilisé pour :
     * - Tableau de bord administrateur (EF-ADMIN-03)
     * - Widget "Commentaires récents" sur le blog
     * - Suivi de l'activité récente
     * 
     * @param int $limit Nombre maximum de commentaires (défaut : 5)
     * @return array Commentaires récents avec titres d'articles
     * @conformité EF-ADMIN-03 : Affichage d'activité récente
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
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logger->error("Erreur récupération commentaires récents", $e);
            return [];
        }
    }

    /**
     * Récupère tous les commentaires (avec pagination optionnelle)
     * 
     * Utilisé principalement dans l'interface d'administration
     * pour la modération et la gestion globale des commentaires.
     * 
     * @param int|null $limit Limite pour pagination
     * @param int|null $offset Offset pour pagination
     * @return array Tous les commentaires avec infos articles
     * @conformité EF-COMMENT-03 : Interface de modération complète
     */
    public function findAll(?int $limit = null, ?int $offset = null): array {
        try {
            $sql = "
                SELECT c.*, a.titre as article_titre 
                FROM commentaires c
                LEFT JOIN articles a ON c.article_id = a.id
                ORDER BY c.date_commentaire DESC
            ";
            
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
            $this->logger->error("Erreur récupération commentaires", $e);
            return [];
        }
    }

    /**
     * Supprime définitivement un commentaire
     * 
     * Action de modération pour supprimer les commentaires
     * inappropriés ou non désirés.
     * 
     * @param int $id ID du commentaire à supprimer
     * @return bool Succès de l'opération
     * @conformité EF-COMMENT-03 : Suppression de commentaires
     */
    public function delete(int $id): bool {
        try {
            $stmt = $this->db->prepare("DELETE FROM commentaires WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            $this->logger->error("Erreur suppression commentaire ID: $id", $e);
            return false;
        }
    }

    /**
     * Met à jour le statut d'un commentaire
     * 
     * Permet de modérer les commentaires en changeant leur statut :
     * - 'En attente' → Nouveau, nécessite modération
     * - 'Approuvé' → Visible publiquement
     * - 'Rejeté' → Non visible, conservé pour historique
     * 
     * @param int $id ID du commentaire
     * @param string $status Nouveau statut
     * @return bool Succès de l'opération
     * @conformité EF-COMMENT-03 : Approuver/Désapprouver commentaires
     */
    public function updateStatus(int $id, string $status): bool {
        try {
            $stmt = $this->db->prepare("UPDATE commentaires SET statut = ? WHERE id = ?");
            return $stmt->execute([$status, $id]);
        } catch (PDOException $e) {
            $this->logger->error("Erreur mise à jour statut commentaire ID: $id", $e);
            return false;
        }
    }

    /**
     * Récupère un commentaire spécifique par son ID
     * 
     * Utilisé pour :
     * - Visualisation détaillée d'un commentaire
     * - Édition/modération spécifique
     * - Vérification avant action
     * 
     * @param int $id ID du commentaire
     * @return object|false Commentaire ou false si non trouvé
     * @conformité EF-COMMENT-03 : Consultation détaillée pour modération
     */
    public function findById(int $id): object|false {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, a.titre as article_titre 
                FROM commentaires c
                LEFT JOIN articles a ON c.article_id = a.id
                WHERE c.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            $this->logger->error("Erreur récupération commentaire ID: $id", $e);
            return false;
        }
    }

    /**
     * Récupère tous les commentaires avec informations détaillées des articles
     * 
     * Inclut les slugs des articles pour générer des liens cliquables
     * dans l'interface d'administration.
     * 
     * @return array Commentaires avec infos articles complètes
     * @conformité EF-COMMENT-03 : Interface de modération intuitive
     */
    public function findAllWithArticles(): array {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, a.titre as article_titre, a.slug as article_slug
                FROM commentaires c
                LEFT JOIN articles a ON c.article_id = a.id
                ORDER BY c.date_commentaire DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logger->error("Erreur récupération commentaires avec articles", $e);
            return [];
        }
    }

    /**
     * Crée un nouveau commentaire et envoie une notification
     * 
     * Implémente le processus complet de création :
     * 1. Insertion en base avec statut par défaut
     * 2. Notification automatique si commentaire en attente
     * 3. Support des utilisateurs non connectés (EF-COMMENT-02)
     * 
     * @param array $data Données du commentaire
     * @return int ID du nouveau commentaire ou 0 en cas d'erreur
     * @conformité EF-COMMENT-02 : Post par utilisateurs non connectés
     * @conformité EF-COMMENT-04 : Notification des nouveaux commentaires
     */
    public function create(array $data): int {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO commentaires (article_id, nom_auteur, email_auteur, contenu, statut) 
                VALUES (:article_id, :nom_auteur, :email_auteur, :contenu, :statut)
            ");
            
            $stmt->execute([
                ':article_id' => $data['article_id'],
                ':nom_auteur' => $data['nom_auteur'],
                ':email_auteur' => $data['email_auteur'] ?? null,
                ':contenu' => $data['contenu'],
                ':statut' => $data['statut'] ?? 'En attente'
            ]);
            
            $commentId = (int) $this->db->lastInsertId();
            
            // Notification automatique pour les commentaires en attente
            // Conformité EF-COMMENT-04
            if (($data['statut'] ?? 'En attente') === 'En attente') {
                $this->sendNewCommentNotification($commentId);
            }
            
            return $commentId;
        } catch (PDOException $e) {
            $this->logger->error("Erreur création commentaire", $e);
            return 0;
        }
    }

    /**
     * Envoie une notification pour un nouveau commentaire en attente
     * 
     * Processus de notification :
     * 1. Récupère les informations du commentaire et de l'article
     * 2. Trouve l'email de l'administrateur
     * 3. Envoie l'email de notification
     * 4. Log le résultat
     * 
     * @param int $commentId ID du commentaire à notifier
     * @return void
     * @conformité EF-COMMENT-04 : Notification des nouveaux commentaires
     * @private
     */
    private function sendNewCommentNotification(int $commentId): void {
        try {
            // Récupération des informations nécessaires
            $comment = $this->getCommentWithArticleInfo($commentId);
            
            if (!$comment) {
                $this->logger->error("Commentaire non trouvé pour notification ID: $commentId");
                return;
            }
            
            // Recherche de l'administrateur à notifier
            $adminEmail = $this->getAdminEmail();
            
            if (!$adminEmail) {
                $this->logger->error("Aucun administrateur trouvé pour notification");
                return;
            }
            
            // Préparation des données pour l'email
            $commentData = [
                'nom_auteur' => $comment->nom_auteur,
                'email_auteur' => $comment->email_auteur,
                'contenu' => $comment->contenu
            ];
            
            $articleData = [
                'titre' => $comment->article_titre,
                'id' => $comment->article_id
            ];
            
            // Envoi de la notification via le service d'email
            $emailService = \App\Core\EmailService::getInstance();
            $emailSent = $emailService->sendCommentNotification($commentData, $articleData, $adminEmail);
            
            // Log du résultat
            if ($emailSent) {
                $this->logger->info("Notification commentaire envoyée ID: $commentId à: $adminEmail");
            } else {
                $this->logger->error("Échec envoi notification commentaire ID: $commentId");
            }
            
        } catch (\Exception $e) {
            $this->logger->error("Erreur notification commentaire ID: $commentId", $e);
        }
    }

    /**
     * Récupère un commentaire avec les informations de son article
     * 
     * @param int $commentId ID du commentaire
     * @return object|false Commentaire avec infos article ou false
     * @private
     */
    private function getCommentWithArticleInfo(int $commentId): object|false {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, a.titre as article_titre, a.id as article_id
                FROM commentaires c
                JOIN articles a ON c.article_id = a.id
                WHERE c.id = ?
            ");
            $stmt->execute([$commentId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            $this->logger->error("Erreur récupération commentaire avec article ID: $commentId", $e);
            return false;
        }
    }

    /**
     * Récupère l'email du premier administrateur actif
     * 
     * Utilisé pour envoyer les notifications de modération
     * à une personne responsable.
     * 
     * @return string|null Email de l'administrateur ou null
     * @private
     * @conformité EF-COMMENT-04 : Notification à l'administrateur
     */
    private function getAdminEmail(): ?string {
        try {
            $stmt = $this->db->prepare("
                SELECT u.email 
                FROM utilisateurs u
                JOIN role_user ru ON u.id = ru.user_id
                JOIN roles r ON ru.role_id = r.id
                WHERE r.nom_role = 'Administrateur' AND u.est_actif = 1
                LIMIT 1
            ");
            $stmt->execute();
            $result = $stmt->fetch();
            return $result ? $result->email : null;
        } catch (PDOException $e) {
            $this->logger->error("Erreur récupération email administrateur", $e);
            return null;
        }
    }

    /**
     * Récupère les commentaires approuvés d'un article spécifique
     * 
     * Utilisé pour afficher les commentaires validés
     * sous les articles sur la partie publique du blog.
     * 
     * @param int $articleId ID de l'article
     * @return array Commentaires approuvés triés par date
     * @conformité EF-COMMENT-01 : Affichage des commentaires associés aux articles
     */
    public function findApprovedByArticle(int $articleId): array {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM commentaires 
                WHERE article_id = ? AND statut = 'Approuvé'
                ORDER BY date_commentaire DESC
            ");
            $stmt->execute([$articleId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logger->error("Erreur récupération commentaires approuvés article ID: $articleId", $e);
            return [];
        }
    }

    /**
     * Récupère les commentaires récents avec informations complètes des articles
     * 
     * Version enrichie de findRecent() incluant les slugs
     * pour génération de liens complets.
     * 
     * @param int $limit Nombre maximum de commentaires
     * @return array Commentaires récents avec infos articles étendues
     * @conformité EF-ADMIN-03 : Fils d'activité avec navigation
     */
    public function findRecentWithArticles(int $limit = 5): array {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, a.titre as article_titre, a.slug as article_slug
                FROM commentaires c
                LEFT JOIN articles a ON c.article_id = a.id
                ORDER BY c.date_commentaire DESC 
                LIMIT ?
            ");
            $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logger->error("Erreur récupération commentaires récents avec articles", $e);
            return [];
        }
    }
}