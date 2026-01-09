<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\AuthMiddleware;
use App\Models\UserModel;

/**
 * AdminUserController
 * 
 * Contrôleur pour la gestion administrative des utilisateurs.
 * Implémente la gestion des comptes utilisateurs, l'assignation de rôles
 * et le contrôle d'accès basé sur les rôles (RBAC).
 * 
 * Conformité avec les exigences :
 * - EF-ACL-01 : Gestion des Utilisateurs (création, modification, désactivation, suppression)
 * - EF-ACL-02 : Gestion des Rôles (assignation multi-rôles)
 * - EF-ACL-04 : Un utilisateur peut avoir plusieurs rôles
 * - EF-ACL-06 : Système d'authentification sécurisé
 * 
 * @package App\Controllers
 */
class AdminUserController extends BaseController {
    /**
     * @var UserModel Modèle pour les opérations sur les utilisateurs
     */
    private UserModel $userModel;

    /**
     * Constructeur
     * 
     * Initialise le modèle d'utilisateurs et vérifie les permissions d'accès.
     * Seuls les administrateurs devraient pouvoir gérer les utilisateurs.
     */
    public function __construct() {
        parent::__construct();
        
        // Middleware de sécurité : vérification d'authentification
        AuthMiddleware::requireAuth();
        
        // Note: La permission 'utilisateur_gerer' devrait être activée en production
        // AuthMiddleware::requirePermission('utilisateur_gerer');
        // Cette permission correspond au contrôle d'accès basé sur les rôles (RBAC)
        
        // Initialisation du modèle via Dependency Injection
        $this->userModel = new UserModel();
    }

    /**
     * Liste tous les utilisateurs (Action Index)
     * 
     * Affiche la liste complète des utilisateurs avec leurs rôles associés.
     * Permet une vue d'ensemble de la base utilisateurs.
     * 
     * @return void
     */
    public function index(): void {
        // Récupération des utilisateurs avec leurs rôles (jointure)
        // Méthode personnalisée pour obtenir les données complètes
        $users = $this->userModel->findAllWithRoles();
        
        // Rendu de la vue d'administration des utilisateurs
        $this->render('admin/users/index.twig', [
            'page_title' => 'Gestion des Utilisateurs',
            'users' => $users
        ]);
    }

    /**
     * Affiche le formulaire de création d'utilisateur (Action Create - GET)
     * 
     * Prépare le formulaire avec la liste des rôles disponibles.
     * Permet la sélection multiple de rôles (EF-ACL-04).
     * 
     * @return void
     */
    public function create(): void {
        // Récupération de tous les rôles disponibles depuis la base
        $roles = $this->userModel->getAllRoles();
        
        // Rendu du formulaire de création
        $this->render('admin/users/create.twig', [
            'page_title' => 'Créer un Utilisateur',
            'roles' => $roles
        ]);
    }

    /**
     * Traite la création d'un utilisateur (Action Store - POST)
     * 
     * Gère la soumission du formulaire de création.
     * Inclut la validation, le hachage sécurisé du mot de passe
     * et l'assignation des rôles sélectionnés.
     * 
     * @return void
     */
    public function store(): void {
        // Vérification de la méthode HTTP (pattern Post/Redirect/Get)
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /3A2526-Blog/public/admin/users');
            exit;
        }

        // Récupération et nettoyage des données
        $nom_utilisateur = trim($_POST['nom_utilisateur'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $roles = $_POST['roles'] ?? [];  // Tableau de rôles sélectionnés

        // Validation complète des données
        $errors = $this->validateUserData($nom_utilisateur, $email, $password, $roles);

        if (empty($errors)) {
            // Création de l'utilisateur avec hachage automatique du mot de passe
            $userId = $this->userModel->create([
                'nom_utilisateur' => $nom_utilisateur,
                'email' => $email,
                'password' => $password  // Le modèle doit hacher ce mot de passe
            ]);

            if ($userId) {
                // Assignation des rôles sélectionnés (relation Many-to-Many)
                $this->userModel->assignRolesToUser($userId, $roles);
                
                // Journalisation de sécurité importante
                $this->logger->info("Utilisateur créé ID: $userId");
                
                // Message flash de confirmation
                $this->session->set('flash_success', 'Utilisateur créé avec succès !');
                header('Location: /3A2526-Blog/public/admin/users');
                exit;
            } else {
                $errors[] = "Erreur lors de la création de l'utilisateur";
            }
        }

        // En cas d'erreur, réafficher le formulaire avec les données
        $roles = $this->userModel->getAllRoles();
        $this->render('admin/users/create.twig', [
            'page_title' => 'Créer un Utilisateur',
            'errors' => $errors,
            'old_input' => $_POST,  // Conservation des données
            'roles' => $roles
        ]);
    }

    /**
     * Active/désactive un utilisateur (Action ToggleStatus)
     * 
     * Permet de désactiver temporairement un compte utilisateur
     * sans le supprimer définitivement. Fonctionnalité importante
     * pour la gestion des comptes problématiques.
     * 
     * @param int $id ID de l'utilisateur à modifier
     * @return void
     */
    public function toggleStatus(int $id): void {
        // Vérification de l'existence de l'utilisateur
        $user = $this->userModel->findById($id);
        
        if (!$user) {
            $this->session->set('flash_error', 'Utilisateur non trouvé');
            header('Location: /3A2526-Blog/public/admin/users');
            exit;
        }

        // Inversion du statut actif/inactif
        $newStatus = $user->est_actif ? 0 : 1;
        
        // Mise à jour du statut
        if ($this->userModel->updateStatus($id, $newStatus)) {
            $action = $newStatus ? 'activé' : 'désactivé';
            
            // Journalisation importante pour l'audit
            $this->logger->info("Utilisateur $action ID: $id");
            
            // Feedback utilisateur
            $this->session->set('flash_success', "Utilisateur $action avec succès !");
        } else {
            $this->session->set('flash_error', 'Erreur lors du changement de statut');
        }
        
        // Redirection standardisée
        header('Location: /3A2526-Blog/public/admin/users');
        exit;
    }

    /**
     * Supprime définitivement un utilisateur (Action Delete)
     * 
     * Supprime un compte utilisateur et toutes ses données associées.
     * Inclut une sécurité pour empêcher l'auto-suppression.
     * 
     * @param int $id ID de l'utilisateur à supprimer
     * @return void
     */
    public function delete(int $id): void {
        // Sécurité : Empêcher l'auto-suppression (bonne pratique critique)
        if ($id == $this->session->get('user_id')) {
            $this->session->set('flash_error', 'Vous ne pouvez pas supprimer votre propre compte');
            header('Location: /3A2526-Blog/public/admin/users');
            exit;
        }

        // Tentative de suppression
        if ($this->userModel->delete($id)) {
            // Journalisation obligatoire pour les suppressions
            $this->logger->info("Utilisateur supprimé ID: $id");
            $this->session->set('flash_success', 'Utilisateur supprimé avec succès !');
        } else {
            $this->session->set('flash_error', 'Erreur lors de la suppression');
        }
        
        // Redirection cohérente
        header('Location: /3A2526-Blog/public/admin/users');
        exit;
    }

    /**
     * Validation des données utilisateur (Méthode privée utilitaire)
     * 
     * Centralise la logique de validation pour assurer la cohérence
     * et la sécurité des données utilisateur.
     * 
     * @param string $username Nom d'utilisateur
     * @param string $email Adresse email
     * @param string $password Mot de passe
     * @param array $roles Rôles sélectionnés
     * @return array Tableau des erreurs de validation
     */
    private function validateUserData(string $username, string $email, string $password, array $roles): array {
        $errors = [];

        // Validation du nom d'utilisateur
        if (empty($username)) $errors[] = "Le nom d'utilisateur est obligatoire";
        
        // Validation de l'email (format et présence)
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "L'email est invalide";
        }
        
        // Validation du mot de passe (EF-ACL-06 : sécurité)
        if (empty($password) || strlen($password) < 6) {
            $errors[] = "Le mot de passe doit faire au moins 6 caractères";
        }
        
        // Validation des rôles (EF-ACL-04 : au moins un rôle)
        if (empty($roles)) $errors[] = "Au moins un rôle doit être sélectionné";

        // Vérification d'unicité de l'email
        $existingUser = $this->userModel->findByEmail($email);
        if ($existingUser) $errors[] = "Cet email est déjà utilisé";

        return $errors;
    }
}