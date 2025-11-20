<?php
// Fichier : /public/index.php

// TOUT EN HAUT
file_put_contents('C:/xampp/htdocs/test_debug.txt', 
    date('Y-m-d H:i:s') . " ROUTEUR APPELÉ\n" . 
    "GET url = " . ($_GET['url'] ?? 'VIDE') . "\n",
    FILE_APPEND
);

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

// === DEBUG DIRECT À L'ÉCRAN ===
echo "<pre>";
echo "URL GET brute: " . ($_GET['url'] ?? 'VIDE') . "\n";
echo "URL traitée: " . $url . "\n";
echo "Test exact 'admin/articles': " . ($url === 'admin/articles' ? 'OUI' : 'NON') . "\n";
echo "</pre>";
die(); // ARRÊT ICI POUR DEBUG
// === FIN DEBUG ===


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
        AuthMiddleware::requireAuth();
        (new AdminController())->dashboard();
        break;

    // Routes de gestion des articles (ADMIN)
    case $url === 'admin/articles':
        AuthMiddleware::requireAuth();
        (new AdminArticleController())->index();
        break;

    case $url === 'admin/articles/create':
        AuthMiddleware::requireAuth();
        (new AdminArticleController())->create();
        break;

    case $url === 'admin/articles/store':
        AuthMiddleware::requireAuth();
        (new AdminArticleController())->store();
        break;

    case preg_match('/^admin\/articles\/edit\/(\d+)$/', $url, $matches):
        AuthMiddleware::requireAuth();
        (new AdminArticleController())->edit((int)$matches[1]);
        break;

    case preg_match('/^admin\/articles\/update\/(\d+)$/', $url, $matches):
        AuthMiddleware::requireAuth();
        (new AdminArticleController())->update((int)$matches[1]);
        break;

    case preg_match('/^admin\/articles\/delete\/(\d+)$/', $url, $matches):
        AuthMiddleware::requireAuth();
        (new AdminArticleController())->delete((int)$matches[1]);
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

    // Route 5 : 404
    default:
        (new HomeController())->error404();
        break;
}