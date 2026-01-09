<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\AuthMiddleware;
use App\Models\TagModel;

/**
 * AdminTagController
 * Contrôleur pour la gestion administrative des tags (CRUD)
 */
class AdminTagController extends BaseController {

    private TagModel $tagModel;

    public function __construct() {
        parent::__construct();
        
        // Sécurité : Vérification de l'authentification obligatoire
        AuthMiddleware::requireAuth();
        
        $this->tagModel = new TagModel();
    }

    /**
     * Liste tous les tags existants (Action Index)
     */
    public function index(): void {
        $tags = $this->tagModel->findAll();
        
        $this->render('admin/tags/index.twig', [
            'page_title' => 'Gestion des Tags',
            'tags' => $tags
        ]);
    }

    /**
     * Affiche le formulaire de création de tag
     */
    public function create(): void {
        $this->render('admin/tags/create.twig', [
            'page_title' => 'Créer un Tag'
        ]);
    }

    /**
     * Traite la création d'un tag (POST)
     */
    public function store(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /3A2526-Blog/public/admin/tags');
            exit;
        }

        $nom_tag = trim($_POST['nom_tag'] ?? '');
        $errors = $this->validateTagData($nom_tag);

        if (empty($errors)) {
            $tagId = $this->tagModel->create(['nom_tag' => $nom_tag]);

            if ($tagId) {
                $this->logger->info("Tag créé ID: $tagId");
                $this->session->set('flash_success', 'Tag créé avec succès !');
                header('Location: /3A2526-Blog/public/admin/tags');
                exit;
            }
            $errors[] = "Erreur lors de la création du tag";
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
     * Traite la modification d'un tag (POST)
     */
    public function update(int $id): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /3A2526-Blog/public/admin/tags');
            exit;
        }

        $nom_tag = trim($_POST['nom_tag'] ?? '');
        $errors = $this->validateTagData($nom_tag, $id);

        if (empty($errors)) {
            if ($this->tagModel->update($id, ['nom_tag' => $nom_tag])) {
                $this->logger->info("Tag modifié ID: $id");
                $this->session->set('flash_success', 'Tag modifié avec succès !');
                header('Location: /3A2526-Blog/public/admin/tags');
                exit;
            }
            $errors[] = "Erreur lors de la modification";
        }

        $this->render('admin/tags/edit.twig', [
            'page_title' => 'Modifier le Tag',
            'errors' => $errors,
            'tag' => $this->tagModel->findById($id) ?: (object) $_POST
        ]);
    }

    /**
     * Supprime un tag (Suppression en cascade gérée par la BDD)
     */
    public function delete(int $id): void {
        if ($this->tagModel->delete($id)) {
            $this->logger->info("Tag supprimé ID: $id");
            $this->session->set('flash_success', 'Tag supprimé avec succès !');
        } else {
            $this->session->set('flash_error', 'Erreur lors de la suppression');
        }
        
        header('Location: /3A2526-Blog/public/admin/tags');
        exit;
    }

    /**
     * Valide les données d'un tag (Contraintes BDD et unicité)
     */
    private function validateTagData(string $nom_tag, ?int $excludeId = null): array {
        $errors = [];

        if (empty($nom_tag)) {
            $errors[] = "Le nom du tag est obligatoire";
        } elseif (strlen($nom_tag) < 2 || strlen($nom_tag) > 50) {
            $errors[] = "Le nom doit comporter entre 2 et 50 caractères";
        }

        // Vérification de l'unicité via le slug
        $existingTag = $this->tagModel->findBySlug($this->generateSlug($nom_tag));
        if ($existingTag && $existingTag->id != $excludeId) {
            $errors[] = "Ce tag existe déjà";
        }

        return $errors;
    }

    /**
     * Génère un slug URL-friendly
     */
    private function generateSlug(string $nomTag): string {
        $slug = strtolower($nomTag);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        return trim($slug, '-');
    }
}