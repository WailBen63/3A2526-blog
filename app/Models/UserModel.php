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
}