<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\UserModel;

/**
 * AuthController
 * Gestion de l'authentification et des sessions utilisateurs
 * Conformité : EF-ACL-04, EF-ACL-05, EF-ACL-06
 */
class AuthController extends BaseController {

    private UserModel $userModel;

    public function __construct() {
        parent::__construct();
        $this->userModel = new UserModel();
    }

    /**
     * Affiche le formulaire de connexion
     */
    public function login(): void {
        // Redirection si déjà authentifié
        if ($this->session->get('user_id')) {
            header('Location: /3A2526-Blog/public/');
            exit;
        }

        $this->render('auth/login.twig', [
            'page_title' => 'Connexion'
        ]);
    }

    /**
     * Traite la tentative de connexion (POST)
     */
    public function processLogin(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /3A2526-Blog/public/login');
            exit;
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Vérification sécurisée des identifiants via le modèle
        $user = $this->userModel->verifyCredentials($email, $password);

        if ($user) {
            // Initialisation de la session utilisateur
            $this->session->set('user_id', $user->id);
            $this->session->set('user_name', $user->nom_utilisateur);
            $this->session->set('user_email', $user->email);
            
            // Gestion du RBAC : Récupération et stockage des rôles
            $roles = $this->userModel->getUserRoles($user->id);
            $roleNames = array_map(fn($role) => $role->nom_role, $roles);
            $this->session->set('user_roles', $roleNames);

            // Détermination du rôle principal pour redirection et UI
            $mainRole = in_array('Administrateur', $roleNames) ? 'Administrateur' : 
                       (in_array('Éditeur', $roleNames) ? 'Éditeur' : 'Contributeur');
            $this->session->set('user_role', $mainRole);

            $this->logger->info("Connexion réussie: $email");
            $this->session->set('flash_success', 'Connexion réussie !');

            // Redirection basée sur les permissions (EF-ACL-05)
            if (in_array($mainRole, ['Administrateur', 'Éditeur'])) {
                header('Location: /3A2526-Blog/public/admin');
            } else {
                header('Location: /3A2526-Blog/public/');
            }
            exit;
        } else {
            $this->logger->warning("Échec de connexion: $email");
            $this->session->set('flash_error', 'Email ou mot de passe incorrect.');
            header('Location: /3A2526-Blog/public/login');
            exit;
        }
    }

    /**
     * Déconnecte l'utilisateur et détruit la session
     */
    public function logout(): void {
        $this->logger->info("Déconnexion: " . $this->session->get('user_email'));
        $this->session->destroy();
        
        header('Location: /3A2526-Blog/public/');
        exit;
    }
}