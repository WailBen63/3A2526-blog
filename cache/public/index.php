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

    // Route 8 : Tableau de bord admin
    case $url === 'admin':
        (new AdminController())->dashboard();
        break;

    // ========== ROUTES GESTION ARTICLES ==========
    
    // Route 9 : Liste des articles
    case $url === 'admin/articles':
        (new AdminArticleController())->index();
        break;

    // Route 10 : Formulaire création article
    case $url === 'admin/articles/create':
        (new AdminArticleController())->create();
        break;

    // Route 11 : Traitement création article
    case $url === 'admin/articles/store':
        (new AdminArticleController())->store();
        break;

    // Route 12 : Formulaire édition article
    case preg_match('/^admin\/articles\/edit\/(\d+)$/', $url, $matches):
        $articleId = (int) $matches[1];
        (new AdminArticleController())->edit($articleId);
        break;

    // Route 13 : Traitement modification article
    case preg_match('/^admin\/articles\/update\/(\d+)$/', $url, $matches):
        $articleId = (int) $matches[1];
        (new AdminArticleController())->update($articleId);
        break;

    // Route 14 : Suppression article
    case preg_match('/^admin\/articles\/delete\/(\d+)$/', $url, $matches):
        $articleId = (int) $matches[1];
        (new AdminArticleController())->delete($articleId);
        break;

    // ========== FIN ROUTES ARTICLES ==========

    // Route 15 : 404
    default:
        (new HomeController())->error404();
        break;
}