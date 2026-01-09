# Projet VTT Blog - Architecture MVC PHP 

Ce projet est une plateforme de blog dynamique développée en PHP natif, structurée de manière professionnelle pour démontrer la maîtrise du pattern MVC et de la programmation orientée objet (POO).

---

## 1. Guide d'Installation Rapide

Pour lancer l'application sur votre environnement local (XAMPP/WAMP) :

1.  **Clonage & Dépendances** :
    ```bash
    git clone [URL_DU_DEPOT]
    composer install
    ```
2.  **Base de données** :
    - Créer une base nommée `blog_db` dans phpMyAdmin.
    - Importer le fichier **`blog_db.sql`** (situé à la racine).
3.  **Configuration** :
    - Vérifier les accès SQL dans `app/Core/Database.php` (par défaut : root / vide).
4.  **Accès** :
    - L'application est accessible via : `http://localhost/3A2526-blog/public/`

**Identifiants de test  :**
- **Admin** : `admin@vtt.com` / `password`
- **Éditeur** : `editeur@vtt.com` / `password`

---

## 2. Documentation Technique 

### Architecture MVC
L'application respecte strictement la séparation des préoccupations :
- **Modèles (`app/Models`)** : Gestion de la persistance des données et requêtes SQL complexes (Jointures, Group By).
- **Vues (`templates/`)** : Utilisation de **Twig 3** pour un rendu sécurisé (protection XSS) et une gestion efficace de l'héritage de templates.
- **Contrôleurs (`app/Controllers`)** : Logique métier et orchestration entre les modèles et les vues.

### Design Patterns & Concepts mis en œuvre
- **Singleton** : Appliqué aux classes `Database` et `SessionManager` pour optimiser les ressources et garantir une instance unique.
- **Front Controller** : Toutes les requêtes sont centralisées dans `public/index.php`, qui fait office de routeur unique via des expressions régulières (Regex).
- **RBAC (Role-Based Access Control)** : Système de permissions gérant différents niveaux d'accès (Admin, Éditeur) via une table pivot dans la base de données.

---

## 3. Bilan et Répartition 

### Répartition du travail au sein du binôme
- **Développement Backend & Architecture** : Mise en place du noyau (Core), du système de routage, de la base de données relationnelle et du système d'authentification sécurisé.
- **Développement Frontend & UX** : Création des templates Twig, intégration de Bootstrap 5, interactivité avec **Alpine.js** (Thème sombre/clair persistant et police pour dyslexiques).

### Difficultés rencontrées
La gestion du **routage dynamique** sans framework a été le principal défi technique, notamment pour capturer les paramètres d'URL (ex: `/post/12`) tout en conservant une structure de code propre.

### Conclusion et Apprentissages
Ce projet nous a permis de comprendre "sous le capot" le fonctionnement d'un framework moderne. Nous avons renforcé nos compétences en sécurité PHP (validation, hachage, protection XSS) et en organisation de projet collaboratif via Git.


