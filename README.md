# Projet Blog VTT - Architecture MVC (Binôme :BENSALEM Wail et BONNEFOND Cyprien )

Ce projet est notre interprétation d'un blog complet dédié au VTT. On l'a codé de A à Z en PHP pour bien comprendre comment fonctionne la logique MVC .

---

## 1. Comment lancer le projet sur votre PC ?

Pour que tout fonctionne chez vous, voici la marche à suivre :

1.  **Dossier & Dépendances** : Clonez le projet dans votre dossier `htdocs`. Ouvrez un terminal dedans et tapez `composer install` (indispensable pour charger Twig).
2.  **Base de données** : Créez une base `blog_db` sur phpMyAdmin et importez le fichier **`blog_db.sql`** qui est à la racine. On y a déjà mis des articles et des comptes de test pour vous faire gagner du temps.
3.  **Config SQL** : Si votre mot de passe SQL n'est pas vide, ça se change dans `app/Core/Database.php`.
4.  **Lancement** : Allez sur `http://localhost/3A2526-Blog/public/`. 
    *Note : On passe par le dossier /public pour sécuriser l'accès au reste du code.*

**Comptes pour tester l'Admin :**
- **Admin** : `admin@vtt.com` / `password`
- **Éditeur** : `editeur@vtt.com` / `password`

---

## 2. Côté technique : Nos choix 

On a essayé de faire un code le plus propre et modulaire possible :

* **L'architecture** : On a séparé les données (Models), la logique (Controllers) et l'affichage (Templates Twig). Ça nous a permis de bosser à deux sans se marcher dessus.
* **Le moteur Twig** : On a utilisé Twig 3 pour éviter les injections XSS et pour pouvoir faire de l'héritage de templates (très pratique pour garder le même menu partout).
* **Design Patterns** : 
    - On a utilisé un **Singleton** pour la base de données afin de ne pas ouvrir 50 connexions pour rien.
    - On a créé un **Front Controller** (`index.php`) qui gère tout le routage du site avec des expressions régulières (Regex).
* **Sécurité RBAC** : On a mis en place des rôles. Un éditeur ne peut pas faire les mêmes choses qu'un admin (par exemple supprimer un utilisateur).

* **Rendu Markdown (Parsedown)** :Au lieu d'utiliser un éditeur de texte classique, nous avons intégré la bibliothèque Parsedown. Cela permet d'écrire les articles en Markdown (gestion des titres, gras, listes) tout en garantissant un rendu HTML propre et sécurisé sur le front-end.

* **Filtrage Dynamique** :Nous avons développé un système de filtrage par tags. En cliquant sur un badge (ex: #VTT), le contrôleur intercepte l'ID via une Regex et filtre dynamiquement l'affichage pour ne montrer que les articles liés.

---

## 3. Notre bilan sur ce projet 

### Qui a fait quoi ?
* **[BENSALEM Wail]** : S'est concentré sur le "moteur" : la structure des classes, le routeur, la base de données et le CRUD de l'administration.
* **[BONNEFOND Cyprien]** : S'est chargé de tout le design avec Bootstrap et l'interactivité. C'est lui qui a géré le système de commentaires et les fonctionnalités Alpine.js (le thème sombre et la police pour dyslexiques).

### Les difficultés qu'on a eues
Le plus dur a été de gérer le **routage dynamique**. Faire en sorte que `/post/3` affiche le bon article sans framework nous a demandé pas mal de tests sur les Regex. On a aussi passé pas mal de temps sur les jointures SQL pour afficher les bons tags sous chaque article dans la liste.

### Ce qu'on a appris
Coder son propre MVC "à la main" est super formateur. On comprend mieux comment fonctionnent Symfony ou Laravel maintenant. On a aussi réalisé l'importance de bien sécuriser chaque formulaire pour éviter les mauvaises surprises.

---
