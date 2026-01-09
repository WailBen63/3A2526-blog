<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\CommentModel;
use App\Models\ArticleModel;

/**
 * CommentController
 * 
 * Contrôleur pour la gestion des commentaires sur la partie publique du blog.
 * Gère la soumission de nouveaux commentaires par les visiteurs (connectés ou non).
 * 
 * Conformité avec les exigences :
 * - EF-COMMENT-01 : Affichage des commentaires associés à un article
 * - EF-COMMENT-02 : Possibilité de poster un commentaire sans être connecté
 * - EF-COMMENT-03 : Tous les commentaires nécessitent modération (statut "En attente")
 * 
 * @package App\Controllers
 */
class CommentController extends BaseController {
    /**
     * @var CommentModel Modèle pour les opérations sur les commentaires
     */
    private CommentModel $commentModel;
    
    /**
     * @var ArticleModel Modèle pour vérifier l'existence et le statut des articles
     */
    private ArticleModel $articleModel;

    /**
     * Constructeur
     * 
     * Initialise les modèles nécessaires pour la gestion des commentaires.
     * Note: Pas de vérification d'authentification nécessaire car les
     * commentaires peuvent être postés par des utilisateurs non connectés.
     */
    public function __construct() {
        parent::__construct();
        
        // Initialisation des modèles via Dependency Injection
        $this->commentModel = new CommentModel();
        $this->articleModel = new ArticleModel();
        
        // Note: Pas de AuthMiddleware ici car EF-COMMENT-02 permet
        // aux utilisateurs non connectés de poster des commentaires
    }

    /**
     * Traite la soumission d'un nouveau commentaire (Action Store - POST)
     * 
     * Gère la création d'un commentaire sur un article spécifique.
     * Implémente un workflow de modération où tous les commentaires
     * sont d'abord mis "En attente" avant publication.
     * 
     * Sécurité : Validation stricte, protection contre le spam,
     * vérification de l'existence de l'article.
     * 
     * @param int $articleId ID de l'article recevant le commentaire
     * @return void
     */
    public function store(int $articleId): void {
        // Vérification de la méthode HTTP (pattern Post/Redirect/Get)
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /3A2526-Blog/public/post/' . $articleId);
            exit;
        }

        // SECTION 1 : Vérification préalable de l'article
        // -------------------------------------------------
        // S'assurer que l'article existe et est publié (statut 'Public')
        $article = $this->articleModel->findById($articleId);
        if (!$article || $article->statut !== 'Public') {
            // Message générique pour ne pas révéler trop d'informations
            $this->session->set('flash_error', 'Article non trouvé.');
            header('Location: /3A2526-Blog/public/');
            exit;
        }

        // SECTION 2 : Récupération et nettoyage des données
        // --------------------------------------------------
        $nom_auteur = trim($_POST['nom_auteur'] ?? '');
        $email_auteur = trim($_POST['email_auteur'] ?? '');
        $contenu = trim($_POST['contenu'] ?? '');

        // SECTION 3 : Validation des données
        // -----------------------------------
        $errors = $this->validateComment($nom_auteur, $contenu);

        // SECTION 4 : Traitement si validation réussie
        // ---------------------------------------------
        if (empty($errors)) {
            // Création du commentaire avec statut "En attente" par défaut
            $commentId = $this->commentModel->create([
                'article_id' => $articleId,                // Article parent
                'nom_auteur' => $nom_auteur,               // Nom obligatoire
                'email_auteur' => !empty($email_auteur) ? $email_auteur : null, // Email optionnel
                'contenu' => $contenu,                     // Contenu validé
                'statut' => 'En attente'                   // EF-COMMENT-03 : Modération requise
            ]);

            if ($commentId) {
                // Journalisation pour suivi et modération
                $this->logger->info("Nouveau commentaire ID: $commentId sur article ID: $articleId");
                
                // Message informatif pour l'utilisateur
                $this->session->set('flash_success', 
                    'Votre commentaire a été soumis et est en attente de modération.'
                );
            } else {
                // Erreur technique (probablement base de données)
                $this->session->set('flash_error', 'Erreur lors de l\'envoi du commentaire.');
            }
        } else {
            // Gestion des erreurs de validation
            foreach ($errors as $error) {
                $this->session->set('flash_error', $error);
            }
        }

        // SECTION 5 : Redirection (pattern Post/Redirect/Get)
        // ----------------------------------------------------
        // Toujours rediriger vers l'article après traitement
        header('Location: /3A2526-Blog/public/post/' . $articleId);
        exit;
    }

    /**
     * Validation des données du commentaire (Méthode privée utilitaire)
     * 
     * Centralise la logique de validation pour assurer :
     * - La qualité des commentaires (longueur minimale)
     * - La protection contre le spam (longueur maximale)
     * - Le respect des contraintes de la base de données
     * 
     * @param string $nom_auteur Nom de l'auteur du commentaire
     * @param string $contenu Contenu du commentaire
     * @return array Tableau des erreurs de validation
     */
    private function validateComment(string $nom_auteur, string $contenu): array {
        $errors = [];

        // Validation du nom d'auteur
        if (empty($nom_auteur)) {
            $errors[] = "Le nom est obligatoire.";
        } elseif (strlen($nom_auteur) < 2) {
            // Empêche les noms trop courts (comme "A", "..", etc.)
            $errors[] = "Le nom doit contenir au moins 2 caractères.";
        }
        // Note: Pas de limite maximale explicite car VARCHAR(100) en BDD

        // Validation du contenu du commentaire
        if (empty($contenu)) {
            $errors[] = "Le commentaire ne peut pas être vide.";
        } elseif (strlen($contenu) < 10) {
            // Encourage des commentaires substantiels (anti-spam basique)
            $errors[] = "Le commentaire doit contenir au moins 10 caractères.";
        } elseif (strlen($contenu) > 1000) {
            // Limite pour éviter les commentaires trop longs/spam
            $errors[] = "Le commentaire ne peut pas dépasser 1000 caractères.";
        }

        // Points d'extension possibles :
        // - Validation de l'email (format)
        // - Filtrage de mots interdits
        // - Détection de spam (liens multiples, etc.)
        // - Vérification de la fréquence des commentaires (anti-flood)

        return $errors;
    }
}