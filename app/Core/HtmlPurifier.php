<?php

namespace App\Core;

/**
 * HtmlPurifier - Singleton pour la purification et la sécurisation du HTML
 * 
 * Service de nettoyage HTML pour prévenir les attaques XSS (Cross-Site Scripting).
 * Implémente une approche de liste blanche (whitelisting) pour autoriser uniquement
 * les balises et attributs HTML sécurisés dans le contenu généré par les utilisateurs.
 * 
 * Conformité avec les exigences de sécurité :
 * - Prévention des attaques XSS (Cross-Site Scripting)
 * - Validation stricte du contenu HTML utilisateur
 * - Approche "liste blanche" pour maximiser la sécurité
 * - Singleton pour performance et consistance
 * 
 * @package App\Core
 */
class HtmlPurifier {
    /**
     * @var self|null Instance unique du purificateur HTML (Singleton)
     * @private
     * @static
     */
    private static ?self $instance = null;

    /**
     * Constructeur privé - Empêche l'instanciation directe
     * 
     * Pattern Singleton : le constructeur est privé pour forcer l'utilisation
     * de la méthode getInstance() et garantir une seule instance.
     */
    private function __construct() {
        // Constructeur vide - l'instance n'a pas d'état à initialiser
    }

    /**
     * Point d'accès unique à l'instance du purificateur HTML
     * 
     * Implémente le pattern Singleton avec lazy loading.
     * 
     * @return self Instance unique de HtmlPurifier
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Nettoie le HTML pour prévenir les attaques XSS
     * 
     * Applique une purification en plusieurs étapes :
     * 1. Liste blanche des balises autorisées (strip_tags)
     * 2. Suppression de tous les attributs potentiellement dangereux
     * 3. Réapplication sécurisée des attributs autorisés
     * 4. Validation spécifique par type d'attribut
     * 
     * Approche "Zero Trust" : tout est interdit sauf ce qui est explicitement autorisé.
     * 
     * @param string $html Contenu HTML à purifier (ex: contenu d'article ou commentaire)
     * @return string HTML purifié et sécurisé
     */
    public function purify(string $html): string {
        // SECTION 1 : Liste blanche des balises HTML autorisées
        // -------------------------------------------------------
        // Seules ces balises seront conservées, toutes les autres seront supprimées
        $allowedTags = [
            'p', 'br', 'strong', 'b', 'em', 'i', 'u',              // Formatage texte
            'ul', 'ol', 'li',                                      // Listes
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6',                    // Titres
            'blockquote', 'code', 'pre',                           // Code et citations
            'a', 'img'                                            // Liens et images
        ];

        // SECTION 2 : Liste blanche des attributs autorisés par balise
        // -------------------------------------------------------------
        // Définit quels attributs sont autorisés pour chaque type de balise
        $allowedAttributes = [
            'a' => ['href', 'target'],                    // Liens : URL et cible
            'img' => ['src', 'alt', 'width', 'height']    // Images : source, texte alternatif, dimensions
        ];

        // ÉTAPE 1 : Suppression de toutes les balises non autorisées
        // Utilisation de strip_tags() avec liste blanche
        $html = strip_tags($html, '<' . implode('><', $allowedTags) . '>');
        
        // ÉTAPE 2 : Suppression de TOUS les attributs (approche agressive)
        // Cela supprime même les attributs potentiellement dangereux comme onclick, onload, etc.
        $html = preg_replace('/<([a-z][a-z0-9]*)[^>]*?(\/?)>/i', '<$1$2>', $html);
        
        // ÉTAPE 3 : Réapplication sécurisée des attributs autorisés
        // Pour chaque balise avec attributs autorisés, on réapplique ces attributs
        // après validation stricte de leurs valeurs
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

        // Retourne le HTML purifié avec espaces superflus supprimés
        return trim($html);
    }

    /**
     * Préserve un attribut spécifique de manière sécurisée
     * 
     * Méthode privée utilisée dans les callbacks pour valider et réappliquer
     * les attributs autorisés après la purge générale.
     * 
     * Implémente une validation stricte selon le type d'attribut :
     * - href : protocoles autorisés seulement (http, https, mailto, etc.)
     * - src : URLs d'images valides seulement
     * - target : seulement "_blank" pour les liens externes
     * 
     * @param string $tag La balise HTML complète
     * @param string $tagName Le nom de la balise (ex: 'a', 'img')
     * @param string $attribute L'attribut à préserver (ex: 'href', 'src')
     * @return string La balise avec l'attribut validé et préservé
     * @private
     */
    private function preserveAttribute(string $tag, string $tagName, string $attribute): string {
        // Recherche de l'attribut dans la balise
        if (preg_match('/' . $attribute . '=(["\'])(.*?)\1/i', $tag, $matches)) {
            $value = $matches[2]; // Valeur de l'attribut (sans les guillemets)
            
            // VALIDATION SPÉCIFIQUE PAR TYPE D'ATTRIBUT
            // Approche "Zero Trust" : chaque type a ses règles strictes
            switch ($attribute) {
                case 'href': // Attribut des liens
                    // Autoriser seulement :
                    // - http:// et https:// (sites web)
                    // - mailto: (liens email)
                    // - / (liens relatifs internes)
                    // - # (ancres)
                    if (!preg_match('/^(https?:\\/\\/|mailto:|\\/|#)/', $value)) {
                        // Protocole non autorisé → suppression de l'attribut
                        return preg_replace('/\s*' . $attribute . '=(["\']).*?\1/i', '', $tag);
                    }
                    break;
                    
                case 'src': // Attribut des images
                    // Autoriser seulement :
                    // - http:// et https:// (images externes)
                    // - data:image (images en base64)
                    // - / (images relatives internes)
                    if (!preg_match('/^(https?:\\/\\/|data:image|\\/)/', $value)) {
                        // Source non autorisée → suppression de l'attribut
                        return preg_replace('/\s*' . $attribute . '=(["\']).*?\1/i', '', $tag);
                    }
                    break;
                    
                case 'target': // Attribut de cible des liens
                    // Autoriser seulement "_blank" (ouvrir dans nouvel onglet)
                    // Bloque "_self", "_parent", "_top" et les noms de frames personnalisés
                    if ($value !== '_blank') {
                        // Valeur non autorisée → suppression de l'attribut
                        return preg_replace('/\s*' . $attribute . '=(["\']).*?\1/i', '', $tag);
                    }
                    break;
                    
                // Pour les attributs 'alt', 'width', 'height' : validation moins stricte
                // mais ils seront échappés par le navigateur de toute façon
            }
            
            // Si validation réussie, on conserve la balise avec l'attribut
            return $tag;
        }
        
        // Attribut non trouvé → on retourne la balise sans modification
        return $tag;
    }

    /**
     * Empêche le clonage de l'instance (partie du pattern Singleton)
     * 
     * @return void
     * @private
     */
    private function __clone() {}

    /**
     * Empêche la désérialisation de l'instance
     * 
     * @throws \Exception Toujours lancée pour préserver l'intégrité du Singleton
     */
    public function __wakeup() {
        throw new \Exception("Cannot unserialize a singleton.");
    }
}