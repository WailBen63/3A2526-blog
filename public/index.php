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
}
