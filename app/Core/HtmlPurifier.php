<?php

namespace App\Core;

/**
 * HtmlPurifier - Singleton pour la sécurisation du HTML (Anti-XSS)
 * Nettoie le contenu utilisateur via une approche de "liste blanche".
 */
class HtmlPurifier {
    private static ?self $instance = null;

    /**
     * Constructeur privé pour forcer l'usage du Singleton
     */
    private function __construct() {}

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
     * Purifie une chaîne HTML pour prévenir les injections de scripts
     */
    public function purify(string $html): string {
        // Balises HTML autorisées (Formatage, Listes, Titres, Liens, Images)
        $allowedTags = [
            'p', 'br', 'strong', 'b', 'em', 'i', 'u',
            'ul', 'ol', 'li',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'blockquote', 'code', 'pre',
            'a', 'img'
        ];

        // Attributs autorisés par balise spécifique
        $allowedAttributes = [
            'a' => ['href', 'target'],
            'img' => ['src', 'alt', 'width', 'height']
        ];

        // 1. Suppression des balises non listées
        $html = strip_tags($html, '<' . implode('><', $allowedTags) . '>');
        
        // 2. Suppression de tous les attributs par défaut (Approche Zero Trust)
        $html = preg_replace('/<([a-z][a-z0-9]*)[^>]*?(\/?)>/i', '<$1$2>', $html);
        
        // 3. Réapplication après validation des attributs autorisés
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
     * Valide et réinjecte un attribut autorisé si sa valeur est sécurisée
     */
    private function preserveAttribute(string $tag, string $tagName, string $attribute): string {
        if (preg_match('/' . $attribute . '=(["\'])(.*?)\1/i', $tag, $matches)) {
            $value = $matches[2];
            
            // Règles de validation strictes par type d'attribut
            switch ($attribute) {
                case 'href':
                    // Autorise uniquement les protocoles web sécurisés, mailto et liens relatifs
                    if (!preg_match('/^(https?:\\/\\/|mailto:|\\/|#)/', $value)) {
                        return preg_replace('/\s*' . $attribute . '=(["\']).*?\1/i', '', $tag);
                    }
                    break;
                    
                case 'src':
                    // Autorise les images distantes, base64 et relatives
                    if (!preg_match('/^(https?:\\/\\/|data:image|\\/)/', $value)) {
                        return preg_replace('/\s*' . $attribute . '=(["\']).*?\1/i', '', $tag);
                    }
                    break;
                    
                case 'target':
                    // Seul l'attribut "_blank" est toléré pour les liens
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