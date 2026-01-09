<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\AuthMiddleware;
use App\Models\TagModel;

/**
 * AdminTagController
 * 
 * Contrôleur pour la gestion administrative des tags (catégories thématiques).
 * Implémente les opérations CRUD pour les tags et leur association aux articles.
 * 
 * Conformité avec les exigences :
 * - EF-TAG-01 : CRUD complet des tags (nom, URL slug)
 * - EF-TAG-02 : Affichage des tags avec nombre d'articles associés
 * - EF-ARTICLE-04 : Association d'articles à un ou plusieurs tags
 * 
 * @package App\Controllers
 */
class AdminTagController extends BaseController {
    /**
     * @var TagModel Modèle pour les opérations sur les tags
     */
    private TagModel $tagModel;

    /**
     * Constructeur
     * 
     * Initialise le modèle de tags et vérifie les permissions d'accès.
     * Utilise le pattern Middleware pour la sécurité.
     */
    public function __construct() {
        parent::__construct();
        
        // Middleware de sécurité : vérification d'authentification
        AuthMiddleware::requireAuth();
        
        // Note: La permission spécifique 'tag_gerer' devrait être activée
        // AuthMiddleware::requirePermission('tag_gerer');
        // Cette ligne est commentée pour permettre le développement,
        // mais devrait être activée en production pour le RBAC complet
        
        // Initialisation du modèle via Dependency Injection
        $this->tagModel = new TagModel();
    }

    /**
     * Liste tous les tags (Action Index)
     * 
     * Affiche l'interface de gestion avec tous les tags existants.
     * Doit idéalement inclure le nombre d'articles associés à chaque tag.
     * 
     * @return void
     */
    public function index(): void {
        // Récupération de tous les tags depuis la base de données
        // Note: Pour EF-TAG-02, ajouter le compte d'articles associés
        $tags = $this->tagModel->findAll();
        
        // Rendu de la vue d'administration des tags
        $this->render('admin/tags/index.twig', [
            'page_title' => 'Gestion des Tags',
            'tags' => $tags
        ]);
    }

    /**
     * Affiche le formulaire de création de tag (Action Create - GET)
     * 
     * Prépare et affiche le formulaire vide pour créer un nouveau tag.
     * 
     * @return void
     */
    public function create(): void {
        // Rendu d'un formulaire simple pour la création de tag
        $this->render('admin/tags/create.twig', [
            'page_title' => 'Créer un Tag'
        ]);
    }

    /**
     * Traite la création d'un tag (Action Store - POST)
     * 
     * Gère la soumission du formulaire de création.
     * Valide les données, génère le slug et crée le tag en base.
     * 
     * @return void
     */
    public function store(): void {
        // Vérification de la méthode HTTP (pattern Post/Redirect/Get)
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /3A2526-Blog/public/admin/tags');
            exit;
        }

        // Récupération et nettoyage des données
        $nom_tag = trim($_POST['nom_tag'] ?? '');

        // Validation des données via méthode privée (réutilisation de code)
        $errors = $this->validateTagData($nom_tag);

        // Si validation réussie, procéder à la création
        if (empty($errors)) {
            $tagId = $this->tagModel->create([
                'nom_tag' => $nom_tag
                // Note: Le slug est généré automatiquement par le modèle
            ]);

            if ($tagId) {
                // Journalisation de l'action
                $this->logger->info("Tag créé ID: $tagId");
                
                // Message flash de succès
                $this->session->set('flash_success', 'Tag créé avec succès !');
                header('Location: /3A2526-Blog/public/admin/tags');
                exit;
            } else {
                $errors[] = "Erreur lors de la création du tag";
            }
        }

        // En cas d'erreur, réafficher le formulaire avec les erreurs
        $this->render('admin/tags/create.twig', [
            'page_title' => 'Créer un Tag',
            'errors' => $errors,
            'old_input' => $_POST  // Conservation des données saisies
        ]);
    }

    /**
     * Affiche le formulaire d'édition de tag (Action Edit - GET)
     * 
     * Charge les données du tag existant et prépare le formulaire d'édition.
     * 
     * @param int $id ID du tag à modifier
     * @return void
     */
    public function edit(int $id): void {
        // Chargement du tag depuis la base de données
        $tag = $this->tagModel->findById($id);
        
        // Vérification de l'existence du tag
        if (!$tag) {
            $this->session->set('flash_error', 'Tag non trouvé');
            header('Location: /3A2526-Blog/public/admin/tags');
            exit;
        }

        // Rendu du formulaire d'édition avec données pré-remplies
        $this->render('admin/tags/edit.twig', [
            'page_title' => 'Modifier le Tag',
            'tag' => $tag
        ]);
    }

    /**
     * Traite la modification d'un tag (Action Update - POST)
     * 
     * Gère la soumission du formulaire d'édition.
     * Valide les données et met à jour le tag en base.
     * 
     * @param int $id ID du tag à modifier
     * @return void
     */
    public function update(int $id): void {
        // Vérification de la méthode HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /3A2526-Blog/public/admin/tags');
            exit;
        }

        // Récupération des données
        $nom_tag = trim($_POST['nom_tag'] ?? '');

        // Validation avec exclusion de l'ID courant (pour éviter conflit avec soi-même)
        $errors = $this->validateTagData($nom_tag, $id);

        if (empty($errors)) {
            // Tentative de mise à jour
            $success = $this->tagModel->update($id, [
                'nom_tag' => $nom_tag
                // Le slug est regénéré automatiquement par le modèle
            ]);

            if ($success) {
                // Journalisation
                $this->logger->info("Tag modifié ID: $id");
                
                // Feedback utilisateur
                $this->session->set('flash_success', 'Tag modifié avec succès !');
                header('Location: /3A2526-Blog/public/admin/tags');
                exit;
            } else {
                $errors[] = "Erreur lors de la modification du tag";
            }
        }

        // En cas d'erreur, recharger le tag et réafficher le formulaire
        $tag = $this->tagModel->findById($id);
        
        $this->render('admin/tags/edit.twig', [
            'page_title' => 'Modifier le Tag',
            'errors' => $errors,
            'tag' => $tag ?: (object) $_POST  // Fallback sur les données POST
        ]);
    }

    /**
     * Supprime un tag (Action Delete)
     * 
     * Supprime définitivement un tag de la base de données.
     * Attention : les associations avec les articles sont gérées par
     * les contraintes de clé étrangère ON DELETE CASCADE.
     * 
     * @param int $id ID du tag à supprimer
     * @return void
     */
    public function delete(int $id): void {
        // Suppression du tag (les relations sont supprimées en cascade)
        $success = $this->tagModel->delete($id);
        
        if ($success) {
            // Journalisation importante pour les suppressions
            $this->logger->info("Tag supprimé ID: $id");
            $this->session->set('flash_success', 'Tag supprimé avec succès !');
        } else {
            $this->session->set('flash_error', 'Erreur lors de la suppression');
        }
        
        // Redirection vers la liste
        header('Location: /3A2526-Blog/public/admin/tags');
        exit;
    }

    /**
     * Validation des données de tag (Méthode privée utilitaire)
     * 
     * Centralise la logique de validation pour réutilisation entre
     * création et modification. Respecte le principe DRY (Don't Repeat Yourself).
     * 
     * @param string $nom_tag Nom du tag à valider
     * @param int|null $excludeId ID à excluer pour vérification d'unicité (édition)
     * @return array Tableau des erreurs de validation
     */
    private function validateTagData(string $nom_tag, ?int $excludeId = null): array {
        $errors = [];

        // Validation de présence
        if (empty($nom_tag)) {
            $errors[] = "Le nom du tag est obligatoire";
        }
        // Validation de longueur minimale
        elseif (strlen($nom_tag) < 2) {
            $errors[] = "Le nom du tag doit faire au moins 2 caractères";
        }
        // Validation de longueur maximale (correspond au champ VARCHAR(50) en BDD)
        elseif (strlen($nom_tag) > 50) {
            $errors[] = "Le nom du tag ne peut pas dépasser 50 caractères";
        }

        // Vérification d'unicité (basée sur le slug)
        // Pour l'édition, on exclut l'ID courant pour éviter conflit avec soi-même
        $existingTag = $this->tagModel->findBySlug($this->generateSlug($nom_tag));
        if ($existingTag && $existingTag->id != $excludeId) {
            $errors[] = "Ce tag existe déjà";
        }

        return $errors;
    }

    /**
     * Génère un slug à partir d'un nom de tag
     * 
     * Méthode utilitaire pour créer des URLs SEO-friendly.
     * Doit correspondre exactement à la logique du modèle pour la cohérence.
     * 
     * @param string $nomTag Nom du tag à "slugifier"
     * @return string Slug généré (ex: "nom-du-tag")
     */
    private function generateSlug(string $nomTag): string {
        // Conversion en minuscules
        $slug = strtolower($nomTag);
        
        // Remplacement des caractères non alphanumériques par des tirets
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        
        // Suppression des tirets en début et fin
        $slug = trim($slug, '-');
        
        return $slug;
    }
}