<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Models\ArticleModel;
use App\Core\AuthMiddleware;
use App\Models\TagModel;
use App\Core\HtmlPurifier;
use App\Core\ImageUploader;


class AdminArticleController extends BaseController {
    private ArticleModel $articleModel;
    private TagModel $tagModel;
    private HtmlPurifier $htmlPurifier;
    private ImageUploader $imageUploader;

    public function __construct() {
    parent::__construct();
    
    // Vérifier que l'utilisateur est connecté et a les permissions
    AuthMiddleware::requireAuth();
    
    // Optionnel : Vérifier des permissions spécifiques
    // AuthMiddleware::requirePermission('article_creer');
    
    $this->articleModel = new ArticleModel();
    $this->tagModel = new TagModel();
    $this->htmlPurifier = HtmlPurifier::getInstance();
    $this->imageUploader = ImageUploader::getInstance();
}

    /**
     * Liste tous les articles
     */
    public function index(): void {
        $articles = $this->articleModel->findAll();
        
        $this->render('admin/articles/index.twig', [
            'page_title' => 'Gestion des Articles',
            'articles' => $articles
        ]);
    }

    /**
     * Affiche le formulaire de création
     */
    public function create(): void {
        $tags = $this->tagModel->findAll();
        
        $this->render('admin/articles/create.twig', [
            'page_title' => 'Créer un Article',
            'tags' => $tags
        ]);
    }

    /**
     * Traite la création d'un article
     */
    public function store(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /3A2526-Blog/public/admin/articles');
            exit;
        }

        $titre = trim($_POST['titre'] ?? '');
        $contenu = trim($_POST['contenu'] ?? '');
        $statut = $_POST['statut'] ?? 'Brouillon';
        $tags = $_POST['tags'] ?? [];
        $imageFile = $_FILES['image_une'] ?? null;

        // Nettoyer le HTML pour la sécurité
        $contenu = $this->htmlPurifier->purify($contenu);

        // Validation
        $errors = [];
        if (empty($titre)) $errors[] = "Le titre est obligatoire";
        if (empty($contenu)) $errors[] = "Le contenu est obligatoire";

        // Gestion de l'upload d'image
        $imageFilename = null;
        if ($imageFile && $imageFile['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = $this->imageUploader->upload($imageFile);
            if (!$uploadResult['success']) {
                $errors[] = "Erreur image: " . $uploadResult['error'];
            } else {
                $imageFilename = $uploadResult['filename'];
            }
        }

        if (empty($errors)) {
            $articleId = $this->articleModel->create([
                'user_id' => $this->session->get('user_id'),
                'titre' => $titre,
                'contenu' => $contenu,
                'statut' => $statut,
                'image_une' => $imageFilename
            ]);

            if ($articleId) {
                // Associer les tags sélectionnés
                $this->articleModel->attachTagsToArticle($articleId, $tags);
                
                $this->logger->info("Article créé ID: $articleId par " . $this->session->get('user_name'));
                $this->session->set('flash_success', 'Article créé avec succès !');
                header('Location: /3A2526-Blog/public/admin/articles');
                exit;
            } else {
                // Supprimer l'image si l'article n'a pas été créé
                if ($imageFilename) {
                    $this->imageUploader->delete($imageFilename);
                }
                $errors[] = "Erreur lors de la création de l'article";
            }
        }

        $tags = $this->tagModel->findAll();
        $this->render('admin/articles/create.twig', [
            'page_title' => 'Créer un Article',
            'errors' => $errors,
            'old_input' => $_POST,
            'tags' => $tags
        ]);
    }

    /**
     * Affiche le formulaire d'édition
     */
    public function edit(int $id): void {
        $article = $this->articleModel->findById($id);
        $tags = $this->tagModel->findAll();
        $articleTags = $this->articleModel->getArticleTags($id);
        
        if (!$article) {
            $this->session->set('flash_error', 'Article non trouvé');
            header('Location: /3A2526-Blog/public/admin/articles');
            exit;
        }

        $this->render('admin/articles/edit.twig', [
            'page_title' => 'Modifier l\'Article',
            'article' => $article,
            'tags' => $tags,
            'articleTags' => $articleTags
        ]);
    }

    /**
     * Traite la modification d'un article
     */
    public function update(int $id): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /3A2526-Blog/public/admin/articles');
            exit;
        }

        $titre = trim($_POST['titre'] ?? '');
        $contenu = trim($_POST['contenu'] ?? '');
        $statut = $_POST['statut'] ?? 'Brouillon';
        $tags = $_POST['tags'] ?? [];
        $imageFile = $_FILES['image_une'] ?? null;
        $deleteImage = isset($_POST['delete_image']);

        // Nettoyer le HTML pour la sécurité
        $contenu = $this->htmlPurifier->purify($contenu);

        // Validation
        $errors = [];
        if (empty($titre)) $errors[] = "Le titre est obligatoire";
        if (empty($contenu)) $errors[] = "Le contenu est obligatoire";

        // Gestion de l'image
        $currentImage = $this->articleModel->getArticleImage($id);
        $imageFilename = $currentImage;

        if ($deleteImage && $currentImage) {
            // Supprimer l'image existante
            $this->imageUploader->delete($currentImage);
            $imageFilename = null;
        }

        if ($imageFile && $imageFile['error'] !== UPLOAD_ERR_NO_FILE) {
            // Upload nouvelle image
            $uploadResult = $this->imageUploader->upload($imageFile);
            if (!$uploadResult['success']) {
                $errors[] = "Erreur image: " . $uploadResult['error'];
            } else {
                // Supprimer l'ancienne image si elle existe
                if ($currentImage) {
                    $this->imageUploader->delete($currentImage);
                }
                $imageFilename = $uploadResult['filename'];
            }
        }

        if (empty($errors)) {
            $success = $this->articleModel->update($id, [
                'titre' => $titre,
                'contenu' => $contenu,
                'statut' => $statut
            ]);

            if ($success) {
                // Mettre à jour l'image si elle a changé
                if ($imageFilename !== $currentImage) {
                    $this->articleModel->updateImage($id, $imageFilename);
                }

                // Mettre à jour les tags associés
                $this->articleModel->attachTagsToArticle($id, $tags);
                
                $this->logger->info("Article modifié ID: $id par " . $this->session->get('user_name'));
                $this->session->set('flash_success', 'Article modifié avec succès !');
                header('Location: /3A2526-Blog/public/admin/articles');
                exit;
            } else {
                // Supprimer la nouvelle image si la modification a échoué
                if ($imageFilename && $imageFilename !== $currentImage) {
                    $this->imageUploader->delete($imageFilename);
                }
                $errors[] = "Erreur lors de la modification de l'article";
            }
        }

        $tags = $this->tagModel->findAll();
        $articleTags = $this->articleModel->getArticleTags($id);
        $article = $this->articleModel->findById($id);
        
        $this->render('admin/articles/edit.twig', [
            'page_title' => 'Modifier l\'Article',
            'errors' => $errors,
            'article' => $article ?: (object) $_POST,
            'tags' => $tags,
            'articleTags' => $articleTags,
            'article_id' => $id,
            'currentImage' => $imageFilename
        ]);
    }


    /**
     * Supprime un article
     */

    public function delete(int $id): void {
        // Récupérer l'image avant suppression
        $imageFilename = $this->articleModel->getArticleImage($id);
        
        $success = $this->articleModel->delete($id);
        
        if ($success) {
            // Supprimer l'image associée
            if ($imageFilename) {
                $this->imageUploader->delete($imageFilename);
            }
            
            $this->logger->info("Article supprimé ID: $id par " . $this->session->get('user_name'));
            $this->session->set('flash_success', 'Article supprimé avec succès !');
        } else {
            $this->session->set('flash_error', 'Erreur lors de la suppression');
        }
        
        header('Location: /3A2526-Blog/public/admin/articles');
        exit;
    }
}
