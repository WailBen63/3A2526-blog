<?php
namespace App\Models;

use App\Core\BaseModel;
use PDOException;

class UserModel extends BaseModel {
    
    /**
     * Trouve un utilisateur par email
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
     * Vérifie les identifiants de connexion
     */
    public function verifyCredentials(string $email, string $password): object|false {
        $user = $this->findByEmail($email);
        
        if ($user && password_verify($password, $user->mot_de_passe)) {
            return $user;
        }
        
        return false;
    }

    /**
     * Récupère les rôles d'un utilisateur
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
     * Vérifie si un utilisateur a une permission spécifique
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
     * Compte tous les utilisateurs
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
 * Récupère tous les utilisateurs avec leurs rôles
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
 * Crée un nouvel utilisateur
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
 * Met à jour le statut d'un utilisateur
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
 * Supprime un utilisateur
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
 * Récupère un utilisateur par son ID
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
 * Récupère tous les rôles disponibles
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
 */
public function assignRolesToUser(int $userId, array $roleIds): bool {
    try {
        // Supprimer les rôles existants
        $stmt = $this->db->prepare("DELETE FROM role_user WHERE user_id = ?");
        $stmt->execute([$userId]);

        // Ajouter les nouveaux rôles
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