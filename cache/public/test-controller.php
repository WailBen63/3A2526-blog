<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';

echo "<h1>Test du Contrôleur</h1>";

// Test 1 : Le fichier existe ?
$file = dirname(__DIR__) . '/app/Controllers/AdminArticleController.php';
echo "<p>Fichier existe ? " . (file_exists($file) ? '✅ OUI' : '❌ NON') . "</p>";
echo "<p>Chemin : $file</p>";

// Test 2 : La classe peut être chargée ?
try {
    $controller = new \App\Controllers\AdminArticleController();
    echo "<p>✅ Classe chargée avec succès !</p>";
    echo "<p>Méthodes disponibles : " . implode(', ', get_class_methods($controller)) . "</p>";
} catch (Exception $e) {
    echo "<p>❌ ERREUR : " . $e->getMessage() . "</p>";
}
