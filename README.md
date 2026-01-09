# Projet VTT Blog - Architecture MVC PHP

Bienvenue sur le dépôt de notre plateforme de blog. Ce projet est une application web full-stack développée en PHP natif, mettant l'accent sur la programmation orientée objet (POO) et la sécurité.

## Documentation Technique

### Architecture Globale
L'application est structurée selon le patron **MVC** (Modèle-Vue-Contrôleur) afin de garantir une séparation stricte des préoccupations :
* **Modèles (`app/Models`)** : Gèrent l'interaction avec la base de données MariaDB via PDO.
* **Vues (`templates/`)** : Utilisent le moteur de rendu **Twig 3**, permettant un affichage sécurisé (protection native contre les failles XSS) et modulaire grâce à l'héritage de templates.
* **Contrôleurs (`app/Controllers`)** : Orchestrent la logique métier et font le lien entre les données et l'affichage.

### Design Patterns (Patrons de Conception)
* **Singleton** : Implémenté dans les classes `Database` et `SessionManager` pour garantir qu'une seule instance de connexion ou de session n'est active simultanément, optimisant ainsi les ressources serveur.
* **Front Controller** : Toutes les requêtes sont centralisées dans `public/index.php`. Ce fichier fait office de routeur unique, facilitant la gestion de la sécurité et des URLs propres.
* **RBAC (Role-Based Access Control)** : Un système de gestion des droits vérifie les permissions des utilisateurs (Administrateur, Éditeur) avant d'autoriser l'accès aux fonctionnalités sensibles.



## Répartition et Bilan

### Répartition du travail au sein du binôme
* **Développement Back-end & Architecture** : Mise en place du Front Controller, du système de routage par Regex, du Singleton PDO, et développement des fonctionnalités CRUD (Articles, Utilisateurs, Tags).
* **Développement Front-end & UX** : Création des templates Twig (Héritage), intégration de Bootstrap 5, interactivité Alpine.js (Gestion des thèmes clair/sombre, accessibilité dyslexique) et système de commentaires.

### Difficultés rencontrées
* **Routage Dynamique** : La gestion manuelle des URLs complexes (ex: `/post/12` ou `/tag/vtt-xc`) sans framework a nécessité une gestion précise des expressions régulières.
* **Persistance des thèmes** : L'utilisation de `localStorage` avec Alpine.js pour maintenir le choix du thème de l'utilisateur sur l'ensemble du site.

### Retours sur le projet
Ce projet nous a permis de comprendre les mécanismes internes des frameworks PHP modernes (comme Symfony). Nous avons acquis une solide expérience en manipulation de templates Twig et en sécurisation d'une application web (validation de formulaires, hachage de mots de passe, protection XSS).

---

## Installation rapide
1.  **Dépendances** : `composer install`
2.  **Base de données** : Importer le fichier `blog_db.sql` dans MariaDB.
3.  **Configuration** : Paramétrer les accès DB dans `app/Core/Database.php`.
4.  **Lancement** : Pointer votre serveur local vers le dossier `/public`.

