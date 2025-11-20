<?php
// Fichier : /public/index.php

// 1. Définir les constantes globales
define('ROOT_PATH', dirname(__DIR__));
define('LOG_PATH', ROOT_PATH . '/logs');

// 2. Charger l'autoloader de Composer
require_once ROOT_PATH . '/vendor/autoload.php';

// 3. Importer les classes nécessaires
use App\Core\SessionManager;
use App\Controllers\HomeController;
use App\Controllers\PostController;
use App\Controllers\AuthController;
use App\Controllers\AdminController;
use App\Controllers\AdminArticleController;
use App\Core\AuthMiddleware;


// 4. Démarrer la session (via le Singleton)
SessionManager::getInstance();

// 5. Récupérer l'URL "propre"
$url = $_GET['url'] ?? '/';
$url = rtrim($url, '/');
if ($url === '') $url = '/';

// 6. Le Routeur
switch (true) {
    // Route 1 : Page d'accueil
    case $url === '/':
        (new HomeController())->index();
        break;

    // Route 2 : Article unique (ex: /post/12)
    case preg_match('/^post\/(\d+)$/', $url, $matches):
        $postId = (int) $matches[1];
        (new PostController())->show($postId);
        break;

    // Route 3 : Page À Propos
    case $url === 'a-propos':
        (new HomeController())->about();
        break;

    // Route 4 : Page de Contact
    case $url === 'contact':
        (new HomeController())->contact();
        break;

    // Route 5 : Connexion
    case $url === 'login':
        (new AuthController())->login();
        break;

    // Route 6 : Traitement connexion
    case $url === 'login/process':
        (new AuthController())->processLogin();
        break;

    // Route 7 : Déconnexion
    case $url === 'logout':
        (new AuthController())->logout();
        break;

    // Route tableau de bord admin
    case $url === 'admin':
        AuthMiddleware::requirePermission('admin_access');
        (new AdminController())->dashboard();
        break;

    // Routes de gestion des articles
    case $url === 'admin/articles':
        AuthMiddleware::requirePermission('article_creer');
        (new AdminArticleController())->index();
        break;

    case $url === 'admin/articles/create':
        AuthMiddleware::requirePermission('article_creer');
        (new AdminArticleController())->create();
        break;

    case $url === 'admin/articles/store':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            AuthMiddleware::requirePermission('article_creer');
            (new AdminArticleController())->store();
        } else {
            header('Location: /3A2526-Blog/public/admin/articles');
            exit;
        }
        break;

    case preg_match('/^admin\/articles\/(\d+)\/edit$/', $url, $matches):
        AuthMiddleware::requirePermission('article_editer_tous');
        (new AdminArticleController())->edit((int)$matches[1]);
        break;

    case preg_match('/^admin\/articles\/(\d+)\/update$/', $url, $matches):
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            AuthMiddleware::requirePermission('article_editer_tous');
            (new AdminArticleController())->update((int)$matches[1]);
        } else {
            header('Location: /3A2526-Blog/public/admin/articles');
            exit;
        }
        break;

    case preg_match('/^admin\/articles\/(\d+)\/delete$/', $url, $matches):
        AuthMiddleware::requirePermission('article_supprimer');
        (new AdminArticleController())->delete((int)$matches[1]);
        break;

    // Routes pour commentaires (à créer)
    case $url === 'admin/comments':
        AuthMiddleware::requirePermission('commentaire_gerer');
        // (new AdminCommentController())->index(); // À créer
        $this->session->set('flash_error', 'Gestion des commentaires - En développement');
        header('Location: /3A2526-Blog/public/admin');
        exit;
        break;

    // Routes pour utilisateurs (à créer)
    case $url === 'admin/users':
        AuthMiddleware::requirePermission('utilisateur_gerer');
        // (new AdminUserController())->index(); // À créer
        $this->session->set('flash_error', 'Gestion des utilisateurs - En développement');
        header('Location: /3A2526-Blog/public/admin');
        exit;
        break;

    // ... reste du switch ...
}


    