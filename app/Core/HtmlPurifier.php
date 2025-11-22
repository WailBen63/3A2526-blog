<?php
namespace App\Core;

class HtmlPurifier {
    private static ?self $instance = null;

    private function __construct() {}

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Nettoie le HTML pour prévenir les XSS
     */
    public function purify(string $html): string {
        // Liste blanche des balises autorisées
        $allowedTags = [
            'p', 'br', 'strong', 'b', 'em', 'i', 'u',
            'ul', 'ol', 'li', 
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'blockquote', 'code', 'pre',
            'a', 'img'
        ];

        // Liste blanche des attributs autorisés
        $allowedAttributes = [
            'a' => ['href', 'target'],
            'img' => ['src', 'alt', 'width', 'height']
        ];

        // Nettoyage basique - en production utiliser une librairie comme htmlpurifier
        $html = strip_tags($html, '<' . implode('><', $allowedTags) . '>');
        
        // Supprimer les attributs dangereux
        $html = preg_replace('/<([a-z][a-z0-9]*)[^>]*?(\/?)>/i', '<$1$2>', $html);
        
        // Réappliquer les attributs autorisés de manière sécurisée
        foreach ($allowedAttributes as $tag => $attributes) {
            foreach ($attributes as $attr) {
                $html = preg_replace_callback(
                    '/<' . $tag . '[^>]*>/i',
                    function($matches) use ($tag, $attr) {
                        return $this->preserveAttribute($matches[0], $tag, $attr);
                    },
                    $html
                );
            }
        }

        return trim($html);
    }

    /**
     * Préserve un attribut spécifique de manière sécurisée
     */
    private function preserveAttribute(string $tag, string $tagName, string $attribute): string {
        if (preg_match('/' . $attribute . '=(["\'])(.*?)\1/i', $tag, $matches)) {
            $value = $matches[2];
            
            // Validation selon l'attribut
            switch ($attribute) {
                case 'href':
                    // Autoriser seulement http, https, mailto, et liens relatifs
                    if (!preg_match('/^(https?:\\/\\/|mailto:|\\/|#)/', $value)) {
                        return preg_replace('/\s*' . $attribute . '=(["\']).*?\1/i', '', $tag);
                    }
                    break;
                    
                case 'src':
                    // Autoriser seulement les URLs valides et data-URL pour les images
                    if (!preg_match('/^(https?:\\/\\/|data:image|\\/)/', $value)) {
                        return preg_replace('/\s*' . $attribute . '=(["\']).*?\1/i', '', $tag);
                    }
                    break;
                    
                case 'target':
                    // Autoriser seulement _blank
                    if ($value !== '_blank') {
                        return preg_replace('/\s*' . $attribute . '=(["\']).*?\1/i', '', $tag);
                    }
                    break;
            }
            
            return $tag;
        }
        
        return $tag;
    }

    private function __clone() {}
    public function __wakeup() {
        throw new \Exception("Cannot unserialize a singleton.");
    }
}