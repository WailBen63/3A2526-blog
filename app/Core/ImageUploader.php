<?php

namespace App\Core;

/**
 * ImageUploader - Singleton pour la gestion sécurisée des uploads d'images
 * Gère la validation, le stockage et la suppression des images (Image à la une)
 */
class ImageUploader {
    private static ?self $instance = null;
    private string $uploadPath;
    private array $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private int $maxSize = 2 * 1024 * 1024; // Limite fixée à 2MB (EF-ARTICLE-03)

    /**
     * Constructeur privé (Pattern Singleton)
     * Initialise le répertoire de stockage et définit les droits d'accès
     */
    private function __construct() {
        $this->uploadPath = dirname(__DIR__) . '/../public/uploads/articles/';
        
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
    }

    /**
     * Point d'accès unique à l'instance (Lazy Loading)
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Procède à l'upload sécurisé d'un fichier image
     */
    public function upload(array $file): array {
        $result = ['success' => false, 'filename' => null, 'error' => null];

        // 1. Validation des erreurs système PHP
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $result['error'] = $this->getUploadError($file['error']);
            return $result;
        }

        // 2. Validation de la taille du fichier
        if ($file['size'] > $this->maxSize) {
            $result['error'] = "L'image est trop volumineuse (max: 2MB)";
            return $result;
        }

        // 3. Validation stricte du type MIME réel (Prévention des injections)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mime, $allowedMimes)) {
            $result['error'] = "Type de fichier non autorisé";
            return $result;
        }

        // 4. Génération d'un nom de fichier unique et sécurisé
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . strtolower($extension);
        $filepath = $this->uploadPath . $filename;

        // 5. Déplacement définitif du fichier uploadé
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $result['success'] = true;
            $result['filename'] = $filename;
        } else {
            $result['error'] = "Erreur lors du transfert final";
        }

        return $result;
    }

    /**
     * Supprime physiquement un fichier image du serveur
     */
    public function delete(string $filename): bool {
        $path = $this->uploadPath . $filename;
        if ($filename && file_exists($path)) {
            return unlink($path);
        }
        return true;
    }

    /**
     * Retourne l'URL publique de l'image pour l'affichage (Frontend/Admin)
     */
    public function getPublicUrl(?string $filename): ?string {
        return $filename ? '/3A2526-Blog/public/uploads/articles/' . $filename : null;
    }

    /**
     * Mappe les codes d'erreurs PHP en messages explicites en français
     */
    private function getUploadError(int $errorCode): string {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => "Fichier trop volumineux",
            UPLOAD_ERR_PARTIAL  => "Upload interrompu",
            UPLOAD_ERR_NO_FILE  => "Aucun fichier reçu",
            default             => "Erreur serveur lors de l'upload",
        };
    }

    private function __clone() {}
    public function __wakeup() { throw new \Exception("Cannot unserialize a singleton."); }
}