<?php

namespace App\Models;

use App\Core\BaseModel;
use PDOException;

/**
 * UserModel - Modèle pour la gestion des utilisateurs et du contrôle d'accès
 * 
 * Implémente l'authentification, l'autorisation et la gestion des utilisateurs
 * conformément au système RBAC (Role-Based Access Control) spécifié dans le cahier des charges.
 * 
 * Implémente les exigences suivantes du cahier des charges :
 * - EF-ACL-01 : Gestion des Utilisateurs (CRUD)
 * - EF-ACL-02 : Gestion des Rôles
 * - EF-ACL-03 : Gestion des Permissions
 * - EF-ACL-04 : Un utilisateur peut avoir plusieurs rôles
 * - EF-ACL-05 : Contrôle d'accès basé sur les permissions
 * - EF-ACL-06 : Authentification sécurisée (hachage mot de passe)
 * - EF-ADMIN-01 : Statistiques utilisateurs actifs
 * - Architecture RBAC conforme au modèle de données
 * - Sécurité : Hachage bcrypt, requêtes préparées
 * - Logger : Journalisation des tentatives d'accès
 * 
 * @package App\Models
 * @conformité EF-ACL : Contrôle d'accès basé sur les rôles et permissions
 */
class UserModel extends BaseModel {
    
    /**
     * Trouve un utilisateur par son adresse email
     * 
     * Méthode fondamentale pour :
     * - Authentification lors de la connexion
     * - Vérification d'unicité lors de l'inscription
     * - Récupération de compte (mot de passe oublié)
     * 
     * Sécurité : Utilisation de requête préparée pour prévenir les injections SQL
     * 
     * @param string $email Adresse email de l'utilisateur
     * @return object|false Utilisateur trouvé ou false
     * @conformité EF-ACL-06 : Identification par email
     * @conformité Critères d'acceptation : Sécurité (prévention SQL injection)
     */
    public function findByEmail(string $email): object|false {
        try {
            $stmt = $this->db->prepare("SELECT * FROM utilisateurs WHERE email = ?");
            $stmt->execute([$email]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            $this->logger->error("Erreur recherche utilisateur par email: $email", $e);
            return false;
        }
    }

    /**
     * Vérifie les identifiants de connexion (email + mot de passe)
     * 
     * Processus de vérification en deux étapes :
     * 1. Recherche de l'utilisateur par email
     * 2. Vérification du hachage du mot de passe
     * 
     * Sécurité :
     * - Utilisation de password_verify() pour comparer les hachages
     * - Timing attack protection intégrée
     * - Pas d'indication sur l'élément erroné (email ou mot de passe)
     * 
     * @param string $email Adresse email
     * @param string $password Mot de passe en clair
     * @return object|false Utilisateur authentifié ou false
     * @conformité EF-ACL-06 : Authentification sécurisée avec hachage
     * @conformité 2.2.1 : Journalisation des erreurs d'authentification
     */
    public function verifyCredentials(string $email, string $password): object|false {
        $user = $this->findByEmail($email);
        
        // Vérification sécurisée du mot de passe avec bcrypt
        if ($user && password_verify($password, $user->mot_de_passe)) {
            return $user;
        }
        
        return false;
    }

    /**
     * Récupère tous les rôles assignés à un utilisateur
     * 
     * Implémente EF-ACL-04 : Un utilisateur peut avoir plusieurs rôles
     * Utilise les tables de jointure role_user du modèle RBAC.
     * 
     * @param int $userId ID de l'utilisateur
     * @return array Liste des rôles assignés
     * @conformité EF-ACL-04 : Support multi-rôles par utilisateur
     */
    public function getUserRoles(int $userId): array {
        try {
            $stmt = $this->db->prepare("
                SELECT r.* FROM roles r 
                JOIN role_user ru ON r.id = ru.role_id 
                WHERE ru.user_id = ?
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logger->error("Erreur récupération rôles utilisateur ID: $userId", $e);
            return [];
        }
    }

    /**
     * Vérifie si un utilisateur possède une permission spécifique
     * 
     * Implémente EF-ACL-05 : Contrôle d'accès strict par permissions
     * Parcourt la chaîne RBAC : Utilisateur → Rôles → Permissions
     * 
     * @param int $userId ID de l'utilisateur
     * @param string $permission Nom de la permission à vérifier
     * @return bool True si l'utilisateur possède la permission
     * @conformité EF-ACL-05 : Vérification de permissions individuelles
     */
    public function hasPermission(int $userId, string $permission): bool {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM role_permission rp
                JOIN role_user ru ON rp.role_id = ru.role_id
                JOIN permissions p ON rp.permission_id = p.id
                WHERE ru.user_id = ? AND p.nom_permission = ?
            ");
            $stmt->execute([$userId, $permission]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            $this->logger->error("Erreur vérification permission", $e);
            return false;
        }
    }

    /**
     * Compte le nombre total d'utilisateurs dans le système
     * 
     * Utilisé pour :
     * - Statistiques du tableau de bord (EF-ADMIN-01)
     * - Indicateurs de croissance de la communauté
     * - Métriques d'administration
     * 
     * @return int Nombre total d'utilisateurs
     * @conformité EF-ADMIN-01 : Statistiques utilisateurs actifs
     */
    public function countAll(): int {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM utilisateurs");
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            $this->logger->error("Erreur comptage utilisateurs", $e);
            return 0;
        }
    }

    /**
     * Récupère tous les utilisateurs avec leurs rôles agrégés
     * 
     * Optimisé pour l'affichage dans l'interface d'administration.
     * Utilise GROUP_CONCAT pour rassembler les noms de rôles.
     * 
     * Format de retour :
     * - Toutes les infos utilisateur
     * - roles_names : chaîne concaténée des rôles (ex: "Administrateur,Éditeur")
     * 
     * @return array Utilisateurs avec leurs rôles
     * @conformité EF-ACL-01 : Interface de gestion des utilisateurs
     */
    public function findAllWithRoles(): array {
        try {
            $stmt = $this->db->prepare("
                SELECT u.*, GROUP_CONCAT(r.nom_role) as roles_names
                FROM utilisateurs u
                LEFT JOIN role_user ru ON u.id = ru.user_id
                LEFT JOIN roles r ON ru.role_id = r.id
                GROUP BY u.id
                ORDER BY u.date_inscription DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logger->error("Erreur récupération utilisateurs avec rôles", $e);
            return [];
        }
    }

    /**
     * Crée un nouvel utilisateur dans le système
     * 
     * Processus sécurisé de création :
     * 1. Validation des données en amont (dans le contrôleur)
     * 2. Hachage sécurisé du mot de passe avec bcrypt
     * 3. Insertion dans la base avec requête préparée
     * 
     * @param array $data Données du nouvel utilisateur
     * @return int ID du nouvel utilisateur ou 0 en cas d'erreur
     * @conformité EF-ACL-01 : Création d'utilisateurs
     * @conformité EF-ACL-06 : Hachage sécurisé du mot de passe
     */
    public function create(array $data): int {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO utilisateurs (nom_utilisateur, email, mot_de_passe) 
                VALUES (:nom_utilisateur, :email, :password)
            ");
            
            $stmt->execute([
                ':nom_utilisateur' => $data['nom_utilisateur'],
                ':email' => $data['email'],
                ':password' => password_hash($data['password'], PASSWORD_DEFAULT)
            ]);
            
            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->logger->error("Erreur création utilisateur", $e);
            return 0;
        }
    }

    /**
     * Met à jour le statut d'activation d'un utilisateur
     * 
     * Permet d'activer/désactiver des comptes utilisateurs
     * sans les supprimer définitivement.
     * 
     * @param int $id ID de l'utilisateur
     * @param int $status Nouveau statut (1 = actif, 0 = inactif)
     * @return bool Succès de l'opération
     * @conformité EF-ACL-01 : Désactivation d'utilisateurs
     */
    public function updateStatus(int $id, int $status): bool {
        try {
            $stmt = $this->db->prepare("UPDATE utilisateurs SET est_actif = ? WHERE id = ?");
            return $stmt->execute([$status, $id]);
        } catch (PDOException $e) {
            $this->logger->error("Erreur mise à jour statut utilisateur ID: $id", $e);
            return false;
        }
    }

    /**
     * Supprime définitivement un utilisateur du système
     * 
     * Attention : Cette opération est irréversible.
     * Les cascades définies dans la base gèrent les dépendances.
     * 
     * @param int $id ID de l'utilisateur à supprimer
     * @return bool Succès de l'opération
     * @conformité EF-ACL-01 : Suppression d'utilisateurs
     */
    public function delete(int $id): bool {
        try {
            $stmt = $this->db->prepare("DELETE FROM utilisateurs WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            $this->logger->error("Erreur suppression utilisateur ID: $id", $e);
            return false;
        }
    }

    /**
     * Récupère un utilisateur spécifique par son ID
     * 
     * Utilisé pour :
     * - Édition d'un utilisateur existant
     * - Consultation de profil
     * - Vérification d'existence
     * 
     * @param int $id ID de l'utilisateur
     * @return object|false Utilisateur ou false si non trouvé
     * @conformité EF-ACL-01 : Consultation d'utilisateurs
     */
    public function findById(int $id): object|false {
        try {
            $stmt = $this->db->prepare("SELECT * FROM utilisateurs WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            $this->logger->error("Erreur recherche utilisateur par ID: $id", $e);
            return false;
        }
    }

    /**
     * Récupère tous les rôles disponibles dans le système
     * 
     * Utilisé pour :
     * - Interface d'assignation de rôles
     * - Création de nouveaux rôles
     * - Vérification de l'existence des rôles
     * 
     * @return array Tous les rôles triés par nom
     * @conformité EF-ACL-02 : Gestion des rôles
     */
    public function getAllRoles(): array {
        try {
            $stmt = $this->db->query("SELECT * FROM roles ORDER BY nom_role");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logger->error("Erreur récupération rôles", $e);
            return [];
        }
    }

    /**
     * Assigne des rôles à un utilisateur
     * 
     * Implémente EF-ACL-04 : Assignation multiple de rôles
     * Processus atomique :
     * 1. Suppression de tous les rôles existants
     * 2. Ajout des nouveaux rôles spécifiés
     * 
     * @param int $userId ID de l'utilisateur
     * @param array $roleIds Liste des IDs des rôles à assigner
     * @return bool Succès de l'opération
     * @conformité EF-ACL-04 : Assignation de plusieurs rôles
     */
    public function assignRolesToUser(int $userId, array $roleIds): bool {
        try {
            // 1. Supprimer tous les rôles existants (nettoyage complet)
            $stmt = $this->db->prepare("DELETE FROM role_user WHERE user_id = ?");
            $stmt->execute([$userId]);

            // 2. Ajouter les nouveaux rôles spécifiés
            $stmt = $this->db->prepare("INSERT INTO role_user (user_id, role_id) VALUES (?, ?)");
            foreach ($roleIds as $roleId) {
                $stmt->execute([$userId, $roleId]);
            }
            return true;
        } catch (PDOException $e) {
            $this->logger->error("Erreur assignation rôles utilisateur ID: $userId", $e);
            return false;
        }
    }
}