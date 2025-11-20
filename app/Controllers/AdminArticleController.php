<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Models\ArticleModel;

class AdminArticleController extends BaseController {
    private ArticleModel $articleModel;

    public function __construct() {
        parent::__construct();
        $this->articleModel = new ArticleModel();
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
        $this->render('admin/articles/create.twig', [
            'page_title' => 'Créer un Article'
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

        // Validation
        $errors = [];
        if (empty($titre)) $errors[] = "Le titre est obligatoire";
        if (empty($contenu)) $errors[] = "Le contenu est obligatoire";

        if (empty($errors)) {
            $articleId = $this->articleModel->create([
                'user_id' => $this->session->get('user_id'),
                'titre' => $titre,
                'contenu' => $contenu,
                'statut' => $statut
            ]);

            if ($articleId) {
                $this->logger->info("Article créé ID: $articleId par " . $this->session->get('user_name'));
                $this->session->set('flash_success', 'Article créé avec succès !');
                header('Location: /3A2526-Blog/public/admin/articles');
                exit;
            } else {
                $errors[] = "Erreur lors de la création de l'article";
            }
        }

        $this->render('admin/articles/create.twig', [
            'page_title' => 'Créer un Article',
            'errors' => $errors,
            'old_input' => $_POST
        ]);
    }

    /**
     * Affiche le formulaire d'édition
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
            'article' => $article
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

        // Validation
        $errors = [];
        if (empty($titre)) $errors[] = "Le titre est obligatoire";
        if (empty($contenu)) $errors[] = "Le contenu est obligatoire";

        if (empty($errors)) {
            $success = $this->articleModel->update($id, [
                'titre' => $titre,
                'contenu' => $contenu,
                'statut' => $statut
            ]);

            if ($success) {
                $this->logger->info("Article modifié ID: $id par " . $this->session->get('user_name'));
                $this->session->set('flash_success', 'Article modifié avec succès !');
                header('Location: /3A2526-Blog/public/admin/articles');
                exit;
            } else {
                $errors[] = "Erreur lors de la modification de l'article";
            }
        }

        $this->render('admin/articles/edit.twig', [
            'page_title' => 'Modifier l\'Article',
            'errors' => $errors,
            'article' => (object) $_POST,
            'article_id' => $id
        ]);
    }

    /**
     * Supprime un article
     */
    public function delete(int $id): void {
        $success = $this->articleModel->delete($id);
        
        if ($success) {
            $this->logger->info("Article supprimé ID: $id par " . $this->session->get('user_name'));
            $this->session->set('flash_success', 'Article supprimé avec succès !');
        } else {
            $this->session->set('flash_error', 'Erreur lors de la suppression');
        }
        
        header('Location: /3A2526-Blog/public/admin/articles');
        exit;
    }
}