<?php
/**
 * Front Controller - Point d'entrée unique de l'application
 * Gère l'initialisation, l'autoloader et le routage des requêtes.
 */

// 1. Configuration et constantes
define('ROOT_PATH', dirname(__DIR__));
define('LOG_PATH', ROOT_PATH . '/logs');

// 2. Autoloading (Composer)
require_once ROOT_PATH . '/vendor/autoload.php';

// 3. Import des namespaces
use App\Core\SessionManager;
use App\Controllers\{
    HomeController, PostController, AuthController, AdminController,
    AdminArticleController, AdminCommentController, AdminUserController,
    AdminTagController, TagController, CommentController
};

// 4. Initialisation de la session (Singleton)
SessionManager::getInstance();

// 5. Analyse de l'URL pour le routage
$url = $_GET['url'] ?? '/';
$url = rtrim($url, '/');
if ($url === '') $url = '/';

// 6. Routeur : Dirige la requête vers le bon contrôleur
switch (true) {
    
    // --- ROUTES PUBLIQUES (FRONT-OFFICE) ---
    case $url === '/':
        (new HomeController())->index();
        break;

    case $url === 'a-propos':
        (new HomeController())->about();
        break;

    case $url === 'contact':
        (new HomeController())->contact();
        break;

    case preg_match('/^post\/(\d+)$/', $url, $matches):

        (new PostController())->show((int)$matches[1]); 

        break;

    case preg_match('/^tag\/(\d+)$/', $url, $matches):
        (new App\Controllers\HomeController())->index((int)$matches[1]);
        break;
    

    case preg_match('/^comments\/(\d+)\/store$/', $url, $matches):
        (new CommentController())->store((int)$matches[1]);
        break;

    // --- AUTHENTIFICATION ---
    case $url === 'login':
        (new AuthController())->login();
        break;

    case $url === 'login/process':
        (new AuthController())->processLogin();
        break;

    case $url === 'logout':
        (new AuthController())->logout();
        break;

    // --- ADMINISTRATION (BACK-OFFICE) ---
    case $url === 'admin':
        (new AdminController())->dashboard();
        break;

    // Admin Articles
    case $url === 'admin/articles':
        (new AdminArticleController())->index();
        break;
        
    case $url === 'admin/articles/create':
        (new AdminArticleController())->create();
        break;
        
    case $url === 'admin/articles/store':
        (new AdminArticleController())->store();
        break;
        
    case preg_match('/^admin\/articles\/(\d+)\/edit$/', $url, $matches):
        (new AdminArticleController())->edit((int)$matches[1]);
        break;
        
    case preg_match('/^admin\/articles\/(\d+)\/update$/', $url, $matches):
        (new AdminArticleController())->update((int)$matches[1]);
        break;
        
    case preg_match('/^admin\/articles\/(\d+)\/delete$/', $url, $matches):
        (new AdminArticleController())->delete((int)$matches[1]);
        break;

    // Admin Commentaires
    case $url === 'admin/comments':
        (new AdminCommentController())->index();
        break;
    
    case preg_match('/^admin\/comments\/(\d+)\/(approve|reject|delete)$/', $url, $matches):
        $action = $matches[2];
        (new AdminCommentController())->$action((int)$matches[1]);
        break;

    // Admin Utilisateurs (RBAC)
    case $url === 'admin/users':
        (new AdminUserController())->index();
        break;
    
    case $url === 'admin/users/create':
        (new AdminUserController())->create();
        break;
    
    case $url === 'admin/users/store':
        (new AdminUserController())->store();
        break;
    
    case preg_match('/^admin\/users\/(\d+)\/(toggle-status|delete)$/', $url, $matches):
        $method = ($matches[2] === 'toggle-status') ? 'toggleStatus' : 'delete';
        (new AdminUserController())->$method((int)$matches[1]);
        break;

    // Admin Tags
    case $url === 'admin/tags':
        (new AdminTagController())->index();
        break;
    
    case $url === 'admin/tags/create':
        (new AdminTagController())->create();
        break;
    
    case $url === 'admin/tags/store':
        (new AdminTagController())->store();
        break;
    
    case preg_match('/^admin\/tags\/(\d+)\/(edit|update|delete)$/', $url, $matches):
        $action = $matches[2];
        (new AdminTagController())->$action((int)$matches[1]);
        break;

    // --- ERREUR 404 ---
    default:
        (new HomeController())->error404();
        break;
}