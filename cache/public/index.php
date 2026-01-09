<?php
// Point d'entrée unique de l'application
// Fichier : /public/index.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// LOG DE DEBUG
file_put_contents(__DIR__ . '/../logs/debug-route.txt', 
    date('H:i:s') . " - URL: " . ($_GET['url'] ?? 'VIDE') . "\n", 
    FILE_APPEND
);

// 1. Constantes
define('ROOT_PATH', dirname(__DIR__));
define('LOG_PATH', ROOT_PATH . '/logs');

// 2. Autoloader Composer
require_once ROOT_PATH . '/vendor/autoload.php';

// 3. Démarrer la session
App\Core\SessionManager::getInstance();

// 4. Récupérer l'URL
$url = $_GET['url'] ?? '/';
$url = rtrim($url, '/');
if ($url === '') {
    $url = '/';
}

// 5. Routeur simple avec IF
// Page d'accueil
if ($url === '/') {
    $controller = new App\Controllers\HomeController();
    $controller->index();
    exit;
}

// Connexion
if ($url === 'login') {
    $controller = new App\Controllers\AuthController();
    $controller->login();
    exit;
}

if ($url === 'login/process') {
    $controller = new App\Controllers\AuthController();
    $controller->processLogin();
    exit;
}

if ($url === 'logout') {
    $controller = new App\Controllers\AuthController();
    $controller->logout();
    exit;
}

// À propos
if ($url === 'a-propos') {
    $controller = new App\Controllers\HomeController();
    $controller->about();
    exit;
}

// Contact
if ($url === 'contact') {
    $controller = new App\Controllers\HomeController();
    $controller->contact();
    exit;
}

// Tableau de bord admin
if ($url === 'admin') {
    App\Core\AuthMiddleware::requireAuth();
    $controller = new App\Controllers\AdminController();
    $controller->dashboard();
    exit;
}

// === GESTION DES ARTICLES ===

// Liste des articles
if ($url === 'admin/articles') {
    App\Core\AuthMiddleware::requireAuth();
    $controller = new App\Controllers\AdminArticleController();
    $controller->index();
    exit;
}

// Créer un article (formulaire)
if ($url === 'admin/articles/create') {
    App\Core\AuthMiddleware::requireAuth();
    $controller = new App\Controllers\AdminArticleController();
    $controller->create();
    exit;
}

// Créer un article (traitement)
if ($url === 'admin/articles/store') {
    App\Core\AuthMiddleware::requireAuth();
    $controller = new App\Controllers\AdminArticleController();
    $controller->store();
    exit;
}

// Modifier un article (formulaire)
if (preg_match('#^admin/articles/(\d+)/edit$#', $url, $matches)) {
    App\Core\AuthMiddleware::requireAuth();
    $articleId = (int) $matches[1];
    $controller = new App\Controllers\AdminArticleController();
    $controller->edit($articleId);
    exit;
}

// Modifier un article (traitement)
if (preg_match('#^admin/articles/(\d+)/update$#', $url, $matches)) {
    App\Core\AuthMiddleware::requireAuth();
    $articleId = (int) $matches[1];
    $controller = new App\Controllers\AdminArticleController();
    $controller->update($articleId);
    exit;
}

// Supprimer un article
if (preg_match('#^admin/articles/(\d+)/delete$#', $url, $matches)) {
    App\Core\AuthMiddleware::requireAuth();
    $articleId = (int) $matches[1];
    $controller = new App\Controllers\AdminArticleController();
    $controller->delete($articleId);
    exit;
}

// Voir un article
if (preg_match('#^post/(\d+)$#', $url, $matches)) {
    $postId = (int) $matches[1];
    $controller = new App\Controllers\PostController();
    $controller->show($postId);
    exit;
}

// 404 - Aucune route trouvée
$controller = new App\Controllers\HomeController();
$controller->error404();
exit;