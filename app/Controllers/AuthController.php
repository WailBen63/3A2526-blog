<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Models\UserModel;

class AuthController extends BaseController {
    private UserModel $userModel;

    public function __construct() {
        parent::__construct();
        $this->userModel = new UserModel();
    }

    public function login(): void {
        // Si déjà connecté, rediriger vers l'accueil
        if ($this->session->get('user_id')) {
            header('Location: /3A2526-blog/public/');
            exit;
        }

        $this->render('auth/login.twig', [
            'page_title' => 'Connexion'
        ]);
    }

    public function processLogin(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /3A2526-blog/public/login');
            exit;
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $user = $this->userModel->verifyCredentials($email, $password);

        if ($user) {
            // Connexion réussie
            $this->session->set('user_id', $user->id);
            $this->session->set('user_name', $user->nom_utilisateur);
            $this->session->set('user_email', $user->email);
            
            // Récupérer les rôles
            $roles = $this->userModel->getUserRoles($user->id);
            $roleNames = array_map(fn($role) => $role->nom_role, $roles);
            $this->session->set('user_roles', $roleNames);

            // Déterminer le rôle principal
            $mainRole = in_array('Administrateur', $roleNames) ? 'Administrateur' : 
                       (in_array('Éditeur', $roleNames) ? 'Éditeur' : 'Contributeur');
            $this->session->set('user_role', $mainRole);

            $this->logger->info("Connexion réussie: $email");
            $this->session->set('flash_success', 'Connexion réussie !');

            // Redirection selon le rôle
            if (in_array($mainRole, ['Administrateur', 'Éditeur'])) {
                header('Location: /3A2526-blog/public/admin');
            } else {
                header('Location: /3A2526-blog/public/');
            }
            exit;
        } else {
            // Échec connexion
            $this->logger->warning("Tentative de connexion échouée: $email");
            $this->session->set('flash_error', 'Email ou mot de passe incorrect.');
            
            header('Location: /3A2526-blog/public/login');
            exit;
        }
    }

    public function logout(): void {
        $this->logger->info("Déconnexion: " . $this->session->get('user_email'));
        $this->session->destroy();
        
        header('Location: /3A2526-blog/public/');
        exit;
    }
}