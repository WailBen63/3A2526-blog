<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\AuthMiddleware;
use App\Models\TagModel;

class AdminTagController extends BaseController {
    private TagModel $tagModel;

    public function __construct() {
        parent::__construct();
        
        // Vérifier l'authentification et les permissions
        AuthMiddleware::requireAuth();
        // AuthMiddleware::requirePermission('tag_gerer');
        
        $this->tagModel = new TagModel();
    }

    /**
     * Liste tous les tags
     */
    public function index(): void {
        $tags = $this->tagModel->findAll();
        
        $this->render('admin/tags/index.twig', [
            'page_title' => 'Gestion des Tags',
            'tags' => $tags
        ]);
    }

    /**
     * Affiche le formulaire de création
     */
    public function create(): void {
        $this->render('admin/tags/create.twig', [
            'page_title' => 'Créer un Tag'
        ]);
    }

    /**
     * Traite la création d'un tag
     */
    public function store(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /3A2526-Blog/public/admin/tags');
            exit;
        }

        $nom_tag = trim($_POST['nom_tag'] ?? '');

        // Validation
        $errors = $this->validateTagData($nom_tag);

        if (empty($errors)) {
            $tagId = $this->tagModel->create([
                'nom_tag' => $nom_tag
            ]);

            if ($tagId) {
                $this->logger->info("Tag créé ID: $tagId");
                $this->session->set('flash_success', 'Tag créé avec succès !');
                header('Location: /3A2526-Blog/public/admin/tags');
                exit;
            } else {
                $errors[] = "Erreur lors de la création du tag";
            }
        }

        $this->render('admin/tags/create.twig', [
            'page_title' => 'Créer un Tag',
            'errors' => $errors,
            'old_input' => $_POST
        ]);
    }

    /**
     * Affiche le formulaire d'édition
     */
    public function edit(int $id): void {
        $tag = $this->tagModel->findById($id);
        
        if (!$tag) {
            $this->session->set('flash_error', 'Tag non trouvé');
            header('Location: /3A2526-Blog/public/admin/tags');
            exit;
        }

        $this->render('admin/tags/edit.twig', [
            'page_title' => 'Modifier le Tag',
            'tag' => $tag
        ]);
    }

    /**
     * Traite la modification d'un tag
     */
    public function update(int $id): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: /3A2526-Blog/public/admin/tags');
        exit;
    }

    $nom_tag = trim($_POST['nom_tag'] ?? '');

    // Validation
    $errors = $this->validateTagData($nom_tag, $id);

    if (empty($errors)) {
        $success = $this->tagModel->update($id, [
            'nom_tag' => $nom_tag
        ]);

        if ($success) {
            $this->logger->info("Tag modifié ID: $id");
            $this->session->set('flash_success', 'Tag modifié avec succès !');
            header('Location: /3A2526-Blog/public/admin/tags');
            exit;
        } else {
            $errors[] = "Erreur lors de la modification du tag";
        }
    }

    // Récupérer à nouveau le tag pour l'affichage
    $tag = $this->tagModel->findById($id);
    
    $this->render('admin/tags/edit.twig', [
        'page_title' => 'Modifier le Tag',
        'errors' => $errors,
        'tag' => $tag ?: (object) $_POST
    ]);
}

    /**
     * Supprime un tag
     */
    public function delete(int $id): void {
        $success = $this->tagModel->delete($id);
        
        if ($success) {
            $this->logger->info("Tag supprimé ID: $id");
            $this->session->set('flash_success', 'Tag supprimé avec succès !');
        } else {
            $this->session->set('flash_error', 'Erreur lors de la suppression');
        }
        
        header('Location: /3A2526-Blog/public/admin/tags');
        exit;
    }

    /**
     * Validation des données tag
     */
    private function validateTagData(string $nom_tag, ?int $excludeId = null): array {
        $errors = [];

        if (empty($nom_tag)) {
            $errors[] = "Le nom du tag est obligatoire";
        } elseif (strlen($nom_tag) < 2) {
            $errors[] = "Le nom du tag doit faire au moins 2 caractères";
        } elseif (strlen($nom_tag) > 50) {
            $errors[] = "Le nom du tag ne peut pas dépasser 50 caractères";
        }

        // Vérifier si le tag existe déjà (en excluant l'ID actuel pour l'édition)
        $existingTag = $this->tagModel->findBySlug($this->generateSlug($nom_tag));
        if ($existingTag && $existingTag->id != $excludeId) {
            $errors[] = "Ce tag existe déjà";
        }

        return $errors;
    }

    /**
     * Génère un slug (identique à celui du modèle)
     */
    private function generateSlug(string $nomTag): string {
        $slug = strtolower($nomTag);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug;
    }
}