<?php
namespace App\Core;

class ImageUploader {
    private static ?self $instance = null;
    private string $uploadPath;
    private array $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private int $maxSize = 2 * 1024 * 1024; // 2MB

    private function __construct() {
        $this->uploadPath = dirname(__DIR__) . '/../public/uploads/articles/';
        
        // Créer le dossier s'il n'existe pas
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Upload une image avec validation
     */
    public function upload(array $file): array {
        $result = [
            'success' => false,
            'filename' => null,
            'error' => null
        ];

        // Vérifier s'il y a une erreur d'upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $result['error'] = $this->getUploadError($file['error']);
            return $result;
        }

        // Vérifier la taille
        if ($file['size'] > $this->maxSize) {
            $result['error'] = "L'image est trop volumineuse (max: 2MB)";
            return $result;
        }

        // Vérifier le type MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mime, $allowedMimes)) {
            $result['error'] = "Type de fichier non autorisé";
            return $result;
        }

        // Générer un nom de fichier unique
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . strtolower($extension);
        $filepath = $this->uploadPath . $filename;

        // Déplacer le fichier
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $result['success'] = true;
            $result['filename'] = $filename;
        } else {
            $result['error'] = "Erreur lors de l'upload";
        }

        return $result;
    }

    /**
     * Supprime une image
     */
    public function delete(string $filename): bool {
        if ($filename && file_exists($this->uploadPath . $filename)) {
            return unlink($this->uploadPath . $filename);
        }
        return true;
    }

    /**
     * Récupère l'URL publique d'une image
     */
    public function getPublicUrl(?string $filename): ?string {
        if (!$filename) {
            return null;
        }
        return '/3A2526-Blog/public/uploads/articles/' . $filename;
    }

    /**
     * Convertit les erreurs d'upload en messages
     */
    private function getUploadError(int $errorCode): string {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return "Fichier trop volumineux";
            case UPLOAD_ERR_PARTIAL:
                return "Upload partiel";
            case UPLOAD_ERR_NO_FILE:
                return "Aucun fichier sélectionné";
            case UPLOAD_ERR_NO_TMP_DIR:
                return "Dossier temporaire manquant";
            case UPLOAD_ERR_CANT_WRITE:
                return "Erreur d'écriture";
            case UPLOAD_ERR_EXTENSION:
                return "Extension non autorisée";
            default:
                return "Erreur inconnue";
        }
    }

    private function __clone() {}
    public function __wakeup() {
        throw new \Exception("Cannot unserialize a singleton.");
    }
}