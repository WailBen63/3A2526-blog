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
 * Gestion administrative du cycle de vie des articles (CRUD)
 */
class AdminArticleController extends BaseController {
    
    private ArticleModel $articleModel;
    private TagModel $tagModel;
    private HtmlPurifier $htmlPurifier;
    private ImageUploader $imageUploader;

    public function __construct() {
        parent::__construct();
        
        // Sécurité : Vérification de l'authentification
        AuthMiddleware::requireAuth();
        
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
     * Formulaire de création d'article
     */
    public function create(): void {
        $tags = $this->tagModel->findAll();
        
        $this->render('admin/articles/create.twig', [
            'page_title' => 'Créer un Article',
            'tags' => $tags
        ]);
    }

    /**
     * Enregistrement d'un nouvel article
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

        // Protection XSS : Nettoyage du contenu HTML
        $contenu = $this->htmlPurifier->purify($contenu);

        $errors = [];
        if (empty($titre)) $errors[] = "Le titre est obligatoire";
        if (empty($contenu)) $errors[] = "Le contenu est obligatoire";

        // Traitement de l'image
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
                $this->articleModel->attachTagsToArticle($articleId, $tags);
                $this->logger->info("Article créé ID: $articleId par " . $this->session->get('user_name'));
                $this->session->set('flash_success', 'Article créé avec succès !');
                header('Location: /3A2526-Blog/public/admin/articles');
                exit;
            } else {
                if ($imageFilename) {
                    $this->imageUploader->delete($imageFilename);
                }
                $errors[] = "Erreur lors de la création de l'article";
            }
        }

        $this->render('admin/articles/create.twig', [
            'page_title' => 'Créer un Article',
            'errors' => $errors,
            'old_input' => $_POST,
            'tags' => $this->tagModel->findAll()
        ]);
    }

    /**
     * Formulaire d'édition d'article
     */
    public function edit(int $id): void {
        $article = $this->articleModel->findById($id);
        
        if (!$article) {
            $this->session->set('flash_error', 'Article non trouvé');
            header('Location: /3A2526-Blog/public/admin/articles');
            exit;
        }

        $this->render('admin/articles/edit.twig', [
            'page_title' => 'Modifier l\'Article',
            'article' => $article,
            'tags' => $this->tagModel->findAll(),
            'articleTags' => $this->articleModel->getArticleTags($id)
        ]);
    }

    /**
     * Mise à jour de l'article
     */
    public function update(int $id): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /3A2526-Blog/public/admin/articles');
            exit;
        }

        $titre = trim($_POST['titre'] ?? '');
        $contenu = $this->htmlPurifier->purify(trim($_POST['contenu'] ?? ''));
        $statut = $_POST['statut'] ?? 'Brouillon';
        $tags = $_POST['tags'] ?? [];
        $imageFile = $_FILES['image_une'] ?? null;
        $deleteImage = isset($_POST['delete_image']);

        $errors = [];
        if (empty($titre)) $errors[] = "Le titre est obligatoire";
        if (empty($contenu)) $errors[] = "Le contenu est obligatoire";

        $currentImage = $this->articleModel->getArticleImage($id);
        $imageFilename = $currentImage;

        // Gestion de l'image (Suppression ou Remplacement)
        if ($deleteImage && $currentImage) {
            $this->imageUploader->delete($currentImage);
            $imageFilename = null;
        }

        if ($imageFile && $imageFile['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = $this->imageUploader->upload($imageFile);
            if ($uploadResult['success']) {
                if ($currentImage) {
                    $this->imageUploader->delete($currentImage);
                }
                $imageFilename = $uploadResult['filename'];
            } else {
                $errors[] = "Erreur image: " . $uploadResult['error'];
            }
        }

        if (empty($errors)) {
            $success = $this->articleModel->update($id, [
                'titre' => $titre,
                'contenu' => $contenu,
                'statut' => $statut
            ]);

            if ($success) {
                $this->articleModel->updateImage($id, $imageFilename);
                $this->articleModel->attachTagsToArticle($id, $tags);
                $this->logger->info("Article modifié ID: $id");
                $this->session->set('flash_success', 'Article modifié avec succès !');
                header('Location: /3A2526-Blog/public/admin/articles');
                exit;
            } else {
                $errors[] = "Erreur lors de la modification";
            }
        }

        $this->render('admin/articles/edit.twig', [
            'page_title' => 'Modifier l\'Article',
            'errors' => $errors,
            'article' => $this->articleModel->findById($id) ?: (object) $_POST,
            'tags' => $this->tagModel->findAll(),
            'articleTags' => $this->articleModel->getArticleTags($id)
        ]);
    }

    /**
     * Suppression de l'article
     */
    public function delete(int $id): void {
        $imageFilename = $this->articleModel->getArticleImage($id);
        
        if ($this->articleModel->delete($id)) {
            if ($imageFilename) {
                $this->imageUploader->delete($imageFilename);
            }
            $this->logger->info("Article supprimé ID: $id");
            $this->session->set('flash_success', 'Article supprimé avec succès !');
        } else {
            $this->session->set('flash_error', 'Erreur lors de la suppression');
        }
        
        header('Location: /3A2526-Blog/public/admin/articles');
        exit;
    }
}