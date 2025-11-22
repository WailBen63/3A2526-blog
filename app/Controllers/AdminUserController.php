<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\AuthMiddleware;
use App\Models\UserModel;

class AdminUserController extends BaseController {
    private UserModel $userModel;

    public function __construct() {
        parent::__construct();
        
        // Vérifier l'authentification et les permissions admin
        AuthMiddleware::requireAuth();
        // AuthMiddleware::requirePermission('utilisateur_gerer');
        
        $this->userModel = new UserModel();
    }

    /**
     * Liste tous les utilisateurs
     */
    public function index(): void {
        $users = $this->userModel->findAllWithRoles();
        
        $this->render('admin/users/index.twig', [
            'page_title' => 'Gestion des Utilisateurs',
            'users' => $users
        ]);
    }

    /**
     * Affiche le formulaire de création
     */
    public function create(): void {
        $roles = $this->userModel->getAllRoles();
        
        $this->render('admin/users/create.twig', [
            'page_title' => 'Créer un Utilisateur',
            'roles' => $roles
        ]);
    }

    /**
     * Traite la création d'un utilisateur
     */
    public function store(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /3A2526-Blog/public/admin/users');
            exit;
        }

        $nom_utilisateur = trim($_POST['nom_utilisateur'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $roles = $_POST['roles'] ?? [];

        // Validation
        $errors = $this->validateUserData($nom_utilisateur, $email, $password, $roles);

        if (empty($errors)) {
            $userId = $this->userModel->create([
                'nom_utilisateur' => $nom_utilisateur,
                'email' => $email,
                'password' => $password
            ]);

            if ($userId) {
                // Assigner les rôles
                $this->userModel->assignRolesToUser($userId, $roles);
                
                $this->logger->info("Utilisateur créé ID: $userId");
                $this->session->set('flash_success', 'Utilisateur créé avec succès !');
                header('Location: /3A2526-Blog/public/admin/users');
                exit;
            } else {
                $errors[] = "Erreur lors de la création de l'utilisateur";
            }
        }

        $roles = $this->userModel->getAllRoles();
        $this->render('admin/users/create.twig', [
            'page_title' => 'Créer un Utilisateur',
            'errors' => $errors,
            'old_input' => $_POST,
            'roles' => $roles
        ]);
    }

    /**
     * Active/désactive un utilisateur
     */
    public function toggleStatus(int $id): void {
        $user = $this->userModel->findById($id);
        
        if (!$user) {
            $this->session->set('flash_error', 'Utilisateur non trouvé');
            header('Location: /3A2526-Blog/public/admin/users');
            exit;
        }

        $newStatus = $user->est_actif ? 0 : 1;
        
        if ($this->userModel->updateStatus($id, $newStatus)) {
            $action = $newStatus ? 'activé' : 'désactivé';
            $this->logger->info("Utilisateur $action ID: $id");
            $this->session->set('flash_success', "Utilisateur $action avec succès !");
        } else {
            $this->session->set('flash_error', 'Erreur lors du changement de statut');
        }
        
        header('Location: /3A2526-Blog/public/admin/users');
        exit;
    }

    /**
     * Supprime un utilisateur
     */
    public function delete(int $id): void {
        // Empêcher l'auto-suppression
        if ($id == $this->session->get('user_id')) {
            $this->session->set('flash_error', 'Vous ne pouvez pas supprimer votre propre compte');
            header('Location: /3A2526-Blog/public/admin/users');
            exit;
        }

        if ($this->userModel->delete($id)) {
            $this->logger->info("Utilisateur supprimé ID: $id");
            $this->session->set('flash_success', 'Utilisateur supprimé avec succès !');
        } else {
            $this->session->set('flash_error', 'Erreur lors de la suppression');
        }
        
        header('Location: /3A2526-Blog/public/admin/users');
        exit;
    }

    /**
     * Validation des données utilisateur
     */
    private function validateUserData(string $username, string $email, string $password, array $roles): array {
        $errors = [];

        if (empty($username)) $errors[] = "Le nom d'utilisateur est obligatoire";
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "L'email est invalide";
        if (empty($password) || strlen($password) < 6) $errors[] = "Le mot de passe doit faire au moins 6 caractères";
        if (empty($roles)) $errors[] = "Au moins un rôle doit être sélectionné";

        // Vérifier si l'email existe déjà
        $existingUser = $this->userModel->findByEmail($email);
        if ($existingUser) $errors[] = "Cet email est déjà utilisé";

        return $errors;
    }
}