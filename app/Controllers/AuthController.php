<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\UserModel;

/**
 * AuthController
 * 
 * Contrôleur pour la gestion de l'authentification des utilisateurs.
 * Gère la connexion, la déconnexion et la gestion des sessions utilisateurs.
 * Implémente un système RBAC (Role-Based Access Control) pour les redirections.
 * 
 * Conformité avec les exigences :
 * - EF-ACL-06 : Système d'authentification sécurisé
 * - EF-ACL-04 : Gestion des rôles multiples par utilisateur
 * - EF-ACL-05 : Redirection basée sur les permissions/accès
 * 
 * @package App\Controllers
 */
class AuthController extends BaseController {
    /**
     * @var UserModel Modèle pour les opérations d'authentification
     */
    private UserModel $userModel;

    /**
     * Constructeur
     * 
     * Initialise le modèle utilisateur nécessaire pour l'authentification.
     * Hérite de BaseController pour accéder aux services communs (session, logger).
     */
    public function __construct() {
        parent::__construct();
        
        // Initialisation du modèle via Dependency Injection
        $this->userModel = new UserModel();
    }

    /**
     * Affiche le formulaire de connexion (Action Login - GET)
     * 
     * Affiche la page de connexion si l'utilisateur n'est pas déjà connecté.
     * Si l'utilisateur est déjà authentifié, redirige vers la page d'accueil.
     * 
     * @return void
     */
    public function login(): void {
        // Vérification de session existante (évite la double connexion)
        if ($this->session->get('user_id')) {
            // Utilisateur déjà connecté → redirection vers l'accueil
            header('Location: /3A2526-Blog/public/');
            exit;
        }

        // Rendu du formulaire de connexion (template auth/login.twig)
        $this->render('auth/login.twig', [
            'page_title' => 'Connexion'
        ]);
    }

    /**
     * Traite la tentative de connexion (Action ProcessLogin - POST)
     * 
     * Valide les identifiants, crée la session utilisateur et gère
     * les rôles RBAC. Inclut la journalisation de sécurité.
     * 
     * Sécurité : Utilise password_verify() au niveau modèle,
     * journalise les tentatives échouées, gère les sessions sécurisées.
     * 
     * @return void
     */
    public function processLogin(): void {
        // Vérification de la méthode HTTP (pattern Post/Redirect/Get)
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /3A2526-Blog/public/login');
            exit;
        }

        // Récupération et nettoyage des identifiants
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Vérification des credentials via le modèle
        // Note: UserModel::verifyCredentials() utilise password_verify()
        $user = $this->userModel->verifyCredentials($email, $password);

        // SECTION : Connexion réussie
        if ($user) {
            // ----- Étape 1 : Stockage des informations de base -----
            $this->session->set('user_id', $user->id);
            $this->session->set('user_name', $user->nom_utilisateur);
            $this->session->set('user_email', $user->email);
            
            // ----- Étape 2 : Gestion des rôles (RBAC) -----
            // Récupération de tous les rôles de l'utilisateur (EF-ACL-04)
            $roles = $this->userModel->getUserRoles($user->id);
            
            // Extraction des noms de rôles pour stockage en session
            $roleNames = array_map(fn($role) => $role->nom_role, $roles);
            $this->session->set('user_roles', $roleNames);

            // ----- Étape 3 : Détermination du rôle principal -----
            // Hiérarchie des rôles : Administrateur > Éditeur > Contributeur
            // Ce rôle principal détermine la redirection et l'interface
            $mainRole = in_array('Administrateur', $roleNames) ? 'Administrateur' : 
                       (in_array('Éditeur', $roleNames) ? 'Éditeur' : 'Contributeur');
            $this->session->set('user_role', $mainRole);

            // ----- Étape 4 : Journalisation et feedback -----
            $this->logger->info("Connexion réussie: $email");
            $this->session->set('flash_success', 'Connexion réussie !');

            // ----- Étape 5 : Redirection basée sur le rôle (EF-ACL-05) -----
            // Administrateurs et Éditeurs → Tableau de bord admin
            // Contributeurs → Page d'accueil publique
            if (in_array($mainRole, ['Administrateur', 'Éditeur'])) {
                header('Location: /3A2526-Blog/public/admin');
            } else {
                header('Location: /3A2526-Blog/public/');
            }
            exit;
        }
        // SECTION : Échec de connexion
        else {
            // Journalisation de sécurité importante (détection d'attaques)
            $this->logger->warning("Tentative de connexion échouée: $email");
            
            // Message d'erreur générique (ne pas révéler si l'email existe)
            $this->session->set('flash_error', 'Email ou mot de passe incorrect.');
            
            // Redirection vers le formulaire de connexion
            header('Location: /3A2526-Blog/public/login');
            exit;
        }
    }

    /**
     * Déconnecte l'utilisateur (Action Logout)
     * 
     * Détruit proprement la session utilisateur et nettoie les données.
     * Inclut la journalisation pour l'audit de sécurité.
     * 
     * @return void
     */
    public function logout(): void {
        // Journalisation de la déconnexion (qui s'est déconnecté et quand)
        $this->logger->info("Déconnexion: " . $this->session->get('user_email'));
        
        // Destruction propre de la session via SessionManager
        // Invalide le cookie de session et détruit les données
        $this->session->destroy();
        
        // Redirection vers la page d'accueil
        header('Location: /3A2526-Blog/public/');
        exit;
    }
}