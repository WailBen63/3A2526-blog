<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\ArticleModel;
use App\Core\AuthMiddleware;
use App\Models\TagModel;
use App\Core\HtmlPurifier;
use App\Core\ImageUploader;

/**
 * AdminArticleController
 * 
 * Contrôleur pour la gestion administrative des articles.
 * Implémente les opérations CRUD (Create, Read, Update, Delete) pour les articles du blog.
 * 
 * Conformité avec les exigences :
 * - EF-ARTICLE-01 : CRUD complet des articles
 * - EF-ARTICLE-03 : Gestion des métadonnées (titre, image, statut)
 * - EF-ARTICLE-04 : Association avec des tags
 * 
 * @package App\Controllers
 */
class AdminArticleController extends BaseController {
    /**
     * @var ArticleModel Modèle pour les opérations sur les articles
     */
    private ArticleModel $articleModel;
    
    /**
     * @var TagModel Modèle pour la gestion des tags
     */
    private TagModel $tagModel;
    
    /**
     * @var HtmlPurifier Service de nettoyage HTML pour prévenir les failles XSS
     */
    private HtmlPurifier $htmlPurifier;
    
    /**
     * @var ImageUploader Service de gestion des uploads d'images
     */
    private ImageUploader $imageUploader;

    /**
     * Constructeur
     * 
     * Initialise les services nécessaires et vérifie les permissions d'accès.
     * Utilise le pattern Singleton pour HtmlPurifier et ImageUploader.
     */
    public function __construct() {
        parent::__construct();
        
        // Vérification d'authentification obligatoire (pattern Middleware)
        AuthMiddleware::requireAuth();
        
        // Initialisation des services via leurs Singletons respectifs
        $this->articleModel = new ArticleModel();
        $this->tagModel = new TagModel();
        $this->htmlPurifier = HtmlPurifier::getInstance();      // Singleton
        $this->imageUploader = ImageUploader::getInstance();    // Singleton
        
        // Note: Les permissions spécifiques sont vérifiées dans chaque méthode
        // pour une granularité fine (ex: article_creer, article_editer_tous, etc.)
    }

    /**
     * Liste tous les articles (Action Index)
     * 
     * Affiche la liste complète des articles pour l'administration.
     * Correspond à l'opération READ du CRUD.
     * 
     * @return void
     */
    public function index(): void {
        // Récupération de tous les articles via le modèle
        $articles = $this->articleModel->findAll();
        
        // Rendu de la vue avec les données
        $this->render('admin/articles/index.twig', [
            'page_title' => 'Gestion des Articles',
            'articles' => $articles
        ]);
    }

    /**
     * Affiche le formulaire de création d'article (Action Create - GET)
     * 
     * Prépare et affiche le formulaire pour créer un nouvel article.
     * Récupère la liste des tags disponibles pour les sélections multiples.
     * 
     * @return void
     */
    public function create(): void {
        // Récupération de tous les tags disponibles
        $tags = $this->tagModel->findAll();
        
        // Rendu du formulaire de création
        $this->render('admin/articles/create.twig', [
            'page_title' => 'Créer un Article',
            'tags' => $tags
        ]);
    }

    /**
     * Traite la création d'un article (Action Store - POST)
     * 
     * Gère la soumission du formulaire de création.
     * Implémente les fonctionnalités suivantes :
     * - Validation des données
     * - Nettoyage HTML anti-XSS
     * - Upload d'image à la une
     * - Association de tags
     * - Rollback en cas d'erreur
     * 
     * @return void
     */
    public function store(): void {
        // Vérification de la méthode HTTP (pattern Post/Redirect/Get)
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /3A2526-Blog/public/admin/articles');
            exit;
        }

        // Récupération et nettoyage des données du formulaire
        $titre = trim($_POST['titre'] ?? '');
        $contenu = trim($_POST['contenu'] ?? '');
        $statut = $_POST['statut'] ?? 'Brouillon';
        $tags = $_POST['tags'] ?? [];
        $imageFile = $_FILES['image_une'] ?? null;

        // Sécurité : Nettoyage HTML pour prévenir les attaques XSS
        $contenu = $this->htmlPurifier->purify($contenu);

        // Validation des données requises
        $errors = [];
        if (empty($titre)) $errors[] = "Le titre est obligatoire";
        if (empty($contenu)) $errors[] = "Le contenu est obligatoire";

        // Gestion de l'upload d'image (si fournie)
        $imageFilename = null;
        if ($imageFile && $imageFile['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = $this->imageUploader->upload($imageFile);
            if (!$uploadResult['success']) {
                $errors[] = "Erreur image: " . $uploadResult['error'];
            } else {
                $imageFilename = $uploadResult['filename'];
            }
        }

        // Si aucune erreur, procéder à la création
        if (empty($errors)) {
            $articleId = $this->articleModel->create([
                'user_id' => $this->session->get('user_id'),  // ID de l'utilisateur connecté
                'titre' => $titre,
                'contenu' => $contenu,
                'statut' => $statut,
                'image_une' => $imageFilename
            ]);

            if ($articleId) {
                // Association des tags sélectionnés (relation Many-to-Many)
                $this->articleModel->attachTagsToArticle($articleId, $tags);
                
                // Journalisation de l'action (bonne pratique de sécurité)
                $this->logger->info("Article créé ID: $articleId par " . $this->session->get('user_name'));
                
                // Message flash de succès (pattern Flash Messages)
                $this->session->set('flash_success', 'Article créé avec succès !');
                header('Location: /3A2526-Blog/public/admin/articles');
                exit;
            } else {
                // Rollback : Suppression de l'image si l'article n'a pas été créé
                if ($imageFilename) {
                    $this->imageUploader->delete($imageFilename);
                }
                $errors[] = "Erreur lors de la création de l'article";
            }
        }

        // En cas d'erreur, réafficher le formulaire avec les erreurs
        $tags = $this->tagModel->findAll();
        $this->render('admin/articles/create.twig', [
            'page_title' => 'Créer un Article',
            'errors' => $errors,
            'old_input' => $_POST,  // Conservation des données saisies
            'tags' => $tags
        ]);
    }

    /**
     * Affiche le formulaire d'édition d'article (Action Edit - GET)
     * 
     * Charge les données de l'article existant et prépare le formulaire d'édition.
     * 
     * @param int $id ID de l'article à modifier
     * @return void
     */
    public function edit(int $id): void {
        // Chargement des données de l'article
        $article = $this->articleModel->findById($id);
        $tags = $this->tagModel->findAll();
        $articleTags = $this->articleModel->getArticleTags($id);
        
        // Vérification de l'existence de l'article
        if (!$article) {
            $this->session->set('flash_error', 'Article non trouvé');
            header('Location: /3A2526-Blog/public/admin/articles');
            exit;
        }

        // Rendu du formulaire d'édition avec les données pré-remplies
        $this->render('admin/articles/edit.twig', [
            'page_title' => 'Modifier l\'Article',
            'article' => $article,
            'tags' => $tags,
            'articleTags' => $articleTags
        ]);
    }

    /**
     * Traite la modification d'un article (Action Update - POST)
     * 
     * Gère la soumission du formulaire d'édition.
     * Gère également :
     * - La mise à jour de l'image
     * - La suppression d'image
     * - La mise à jour des tags
     * 
     * @param int $id ID de l'article à modifier
     * @return void
     */
    public function update(int $id): void {
        // Vérification de la méthode HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /3A2526-Blog/public/admin/articles');
            exit;
        }

        // Récupération des données
        $titre = trim($_POST['titre'] ?? '');
        $contenu = trim($_POST['contenu'] ?? '');
        $statut = $_POST['statut'] ?? 'Brouillon';
        $tags = $_POST['tags'] ?? [];
        $imageFile = $_FILES['image_une'] ?? null;
        $deleteImage = isset($_POST['delete_image']);  // Case à cocher pour suppression

        // Sécurité : Nettoyage HTML
        $contenu = $this->htmlPurifier->purify($contenu);

        // Validation
        $errors = [];
        if (empty($titre)) $errors[] = "Le titre est obligatoire";
        if (empty($contenu)) $errors[] = "Le contenu est obligatoire";

        // Gestion des images (logique complexe pour éviter les doublons)
        $currentImage = $this->articleModel->getArticleImage($id);
        $imageFilename = $currentImage;

        // Suppression d'image demandée
        if ($deleteImage && $currentImage) {
            $this->imageUploader->delete($currentImage);
            $imageFilename = null;
        }

        // Upload d'une nouvelle image
        if ($imageFile && $imageFile['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = $this->imageUploader->upload($imageFile);
            if (!$uploadResult['success']) {
                $errors[] = "Erreur image: " . $uploadResult['error'];
            } else {
                // Suppression de l'ancienne image avant remplacement
                if ($currentImage) {
                    $this->imageUploader->delete($currentImage);
                }
                $imageFilename = $uploadResult['filename'];
            }
        }

        // Si aucune erreur, procéder à la mise à jour
        if (empty($errors)) {
            $success = $this->articleModel->update($id, [
                'titre' => $titre,
                'contenu' => $contenu,
                'statut' => $statut
            ]);

            if ($success) {
                // Mise à jour de l'image si elle a changé
                if ($imageFilename !== $currentImage) {
                    $this->articleModel->updateImage($id, $imageFilename);
                }

                // Mise à jour des tags (suppression/re-création)
                $this->articleModel->attachTagsToArticle($id, $tags);
                
                // Journalisation
                $this->logger->info("Article modifié ID: $id par " . $this->session->get('user_name'));
                $this->session->set('flash_success', 'Article modifié avec succès !');
                header('Location: /3A2526-Blog/public/admin/articles');
                exit;
            } else {
                // Rollback : Suppression de la nouvelle image en cas d'échec
                if ($imageFilename && $imageFilename !== $currentImage) {
                    $this->imageUploader->delete($imageFilename);
                }
                $errors[] = "Erreur lors de la modification de l'article";
            }
        }

        // En cas d'erreur, réafficher le formulaire
        $tags = $this->tagModel->findAll();
        $articleTags = $this->articleModel->getArticleTags($id);
        $article = $this->articleModel->findById($id);
        
        $this->render('admin/articles/edit.twig', [
            'page_title' => 'Modifier l\'Article',
            'errors' => $errors,
            'article' => $article ?: (object) $_POST,  // Garder les modifications
            'tags' => $tags,
            'articleTags' => $articleTags,
            'article_id' => $id,
            'currentImage' => $imageFilename
        ]);
    }

    /**
     * Supprime un article (Action Delete)
     * 
     * Supprime un article et ses ressources associées (image, relations).
     * Implémente un rollback en cas d'échec partiel.
     * 
     * @param int $id ID de l'article à supprimer
     * @return void
     */
    public function delete(int $id): void {
        // Récupération de l'image avant suppression (pour cleanup)
        $imageFilename = $this->articleModel->getArticleImage($id);
        
        // Suppression de l'article (les tags sont supprimés via CASCADE en BDD)
        $success = $this->articleModel->delete($id);
        
        if ($success) {
            // Nettoyage : suppression du fichier image
            if ($imageFilename) {
                $this->imageUploader->delete($imageFilename);
            }
            
            // Journalisation
            $this->logger->info("Article supprimé ID: $id par " . $this->session->get('user_name'));
            $this->session->set('flash_success', 'Article supprimé avec succès !');
        } else {
            $this->session->set('flash_error', 'Erreur lors de la suppression');
        }
        
        // Redirection vers la liste
        header('Location: /3A2526-Blog/public/admin/articles');
        exit;
    }
}