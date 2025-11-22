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
use App\Core\AuthMiddleware;
use App\Controllers\AdminArticleController;
use App\Controllers\AdminCommentController;
use App\Controllers\AdminUserController;
use App\Controllers\AdminTagController;
use App\Controllers\TagController;
use App\Controllers\CommentController;


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

    // Routes d'authentification
    case $url === 'login':
        (new AuthController())->login();
        break;

    case $url === 'login/process':
        (new AuthController())->processLogin();
        break;

    case $url === 'logout':
        (new AuthController())->logout();
        break;

    // Route tableau de bord admin
    case $url === 'admin':
    (new AdminController())->dashboard();
    break;

    // Route 2 : Article unique (ex: /post/12)
    case preg_match('/^post\/(\d+)$/', $url, $matches):
        $postId = (int) $matches[1];
        (new PostController())->show($postId);
        break;

    // Route 3 : Page À Propos (NOUVEAU)
    case $url === 'a-propos':
        (new HomeController())->about();
        break;

        // Route 4 : Page de Contact (NOUVEAU)
    case $url === 'contact':
        (new HomeController())->contact();
        break;

    // Route 5 : 404
    default:
        (new HomeController())->error404();
        break;

    // Routes Admin Articles
    case $url === 'admin/articles':
        (new AdminArticleController())->index();
        break;
        
    case $url === 'admin/articles/create':
        (new AdminArticleController())->create();
        break;
        
    case $url === 'admin/articles/store':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            (new AdminArticleController())->store();
        } else {
            header('Location: /3A2526-Blog/public/admin/articles');
        }
        break;
        
    case preg_match('/^admin\/articles\/(\d+)\/edit$/', $url, $matches):
        $articleId = (int) $matches[1];
        (new AdminArticleController())->edit($articleId);
        break;
        
    case preg_match('/^admin\/articles\/(\d+)\/update$/', $url, $matches):
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $articleId = (int) $matches[1];
            (new AdminArticleController())->update($articleId);
        } else {
            header('Location: /3A2526-Blog/public/admin/articles');
        }
        break;
        
    case preg_match('/^admin\/articles\/(\d+)\/delete$/', $url, $matches):
        $articleId = (int) $matches[1];
        (new AdminArticleController())->delete($articleId);
        break;

    // Routes Admin Commentaires
    case $url === 'admin/comments':
        (new AdminCommentController())->index();
        break;
    
    case preg_match('/^admin\/comments\/(\d+)\/approve$/', $url, $matches):
        $commentId = (int) $matches[1];
        (new AdminCommentController())->approve($commentId);
        break;
    
    case preg_match('/^admin\/comments\/(\d+)\/reject$/', $url, $matches):
        $commentId = (int) $matches[1];
        (new AdminCommentController())->reject($commentId);
        break;
    
    case preg_match('/^admin\/comments\/(\d+)\/delete$/', $url, $matches):
       $commentId = (int) $matches[1];
       (new AdminCommentController())->delete($commentId);
       break;

    // Routes Admin Utilisateurs
    case $url === 'admin/users':
       (new AdminUserController())->index();
       break;
    
    case $url === 'admin/users/create':
       (new AdminUserController())->create();
       break;
    
    case $url === 'admin/users/store':
       if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        (new AdminUserController())->store();
       } else {
        header('Location: /3A2526-Blog/public/admin/users');
       }
       break;
    
    case preg_match('/^admin\/users\/(\d+)\/toggle-status$/', $url, $matches):
       $userId = (int) $matches[1];
       (new AdminUserController())->toggleStatus($userId);
       break;
    
    case preg_match('/^admin\/users\/(\d+)\/delete$/', $url, $matches):
       $userId = (int) $matches[1];
       (new AdminUserController())->delete($userId);
       break;

    
    // Routes Admin Tags
    case $url === 'admin/tags':
       (new AdminTagController())->index();
       break;
    
    case $url === 'admin/tags/create':
       (new AdminTagController())->create();
       break;
    
    case $url === 'admin/tags/store':
       if ($_SERVER['REQUEST_METHOD'] === 'POST') {
           (new AdminTagController())->store();
       } else {
           header('Location: /3A2526-Blog/public/admin/tags');
       }
       break;
    
    case preg_match('/^admin\/tags\/(\d+)\/edit$/', $url, $matches):
       $tagId = (int) $matches[1];
       (new AdminTagController())->edit($tagId);
       break;
    
    case preg_match('/^admin\/tags\/(\d+)\/update$/', $url, $matches):
       if ($_SERVER['REQUEST_METHOD'] === 'POST') {
           $tagId = (int) $matches[1];
           (new AdminTagController())->update($tagId);
       } else {
           header('Location: /3A2526-Blog/public/admin/tags');
       }
       break;
    
    case preg_match('/^admin\/tags\/(\d+)\/delete$/', $url, $matches):
       $tagId = (int) $matches[1];
       (new AdminTagController())->delete($tagId);
       break;

    // Routes Tags Public
    case $url === 'tags':
       (new TagController())->index();
       break;
    
    case preg_match('/^tag\/([a-z0-9-]+)$/', $url, $matches):
       $tagSlug = $matches[1];
       (new TagController())->show($tagSlug);
       break; 

    // Routes Commentaires Publics
    case preg_match('/^comments\/(\d+)\/store$/', $url, $matches):
       $articleId = (int) $matches[1];
       (new CommentController())->store($articleId);
       break;



}
