# ShopCM — Plateforme Pédagogique d'Injections SQL

**ShopCM** est une application web de démonstration (type e-commerce) conçue spécifiquement pour l'enseignement de la cybersécurité, et plus particulièrement des vulnérabilités de type **Injection SQL (SQLi)** en environnement PHP/MySQL.

Cette plateforme permet aux étudiants et aux développeurs de comprendre, tester et corriger 12 types d'injections SQL réelles dans un environnement contrôlé et pédagogique.

---

## 🚀 Fonctionnalités Clés

- **Mode Dual (Toggle) :** Un interrupteur global permet de basculer l'ensemble du site entre un mode **VULNÉRABLE** (requêtes concaténées) et un mode **SÉCURISÉ** (requêtes préparées PDO).
- **Console SQL Temps Réel :** Chaque page affiche la dernière requête SQL exécutée, permettant de visualiser instantanément l'impact d'un payload.
- **12 Vecteurs d'Attaque :** Injections UNION, numériques, aveugles (blind) basées sur le temps ou le contenu, injections dans les headers HTTP, cookies, et injections stockées (stored).
- **Parcours E-commerce Complet :** Catalogue de produits, recherche, filtres de prix, panier (via cookies), avis clients, système de codes promos, suivi de commande et interface d'administration.
- **Support PWA :** L'application est installable (Progressive Web App) avec support du mode hors-ligne.

---

## 🛠️ Installation

### Prérequis
- Un serveur local type **WAMP**, **MAMP**, ou **XAMPP**.
- **PHP 8.0+**
- **MySQL / MariaDB**

### Étapes
1. **Clonage / Copie :** Placez les fichiers du projet dans votre dossier `www` ou `htdocs` (ex: `/wamp64/www/shopcm/`).
2. **Base de données :**
   - Créez une base de données nommée `shopcm_db`.
   - Importez le fichier `sql/shopcm_db.sql` via phpMyAdmin ou en ligne de commande :
     ```bash
     mysql -u root -p shopcm_db < sql/shopcm_db.sql
     ```
3. **Configuration :**
   - Si nécessaire, modifiez les identifiants de connexion dans `includes/db.php`. Par défaut, le script utilise `root` sans mot de passe sur `localhost`.
4. **Accès :** Ouvrez votre navigateur sur `http://localhost/shopcm/`.

---

## 🎓 Guide Pédagogique

L'application est conçue pour être explorée pas à pas. 

1. **Activez le mode VULNÉRABLE** (badge rouge dans l'en-tête).
2. **Consultez la documentation détaillée** dans le dossier `docs/` (notamment `guide_exploitation.md`) pour obtenir les payloads de démonstration.
3. **Testez les payloads** dans les différents formulaires et paramètres d'URL.
4. **Observez la console SQL** en bas de page pour comprendre comment le payload modifie la structure de la requête.
5. **Basculez en mode SÉCURISÉ** pour constater que les mêmes attaques échouent grâce aux requêtes préparées.

### Exemples de vulnérabilités incluses :
- **Authentification Bypass :** Se connecter en admin sans mot de passe via `' OR 1=1-- -`.
- **Exfiltration de données :** Récupérer les hashes des mots de passe des utilisateurs via un `UNION SELECT` sur la barre de recherche.
- **Time-based Blind SQLi :** Extraire des données en mesurant les temps de réponse via `SLEEP()`.
- **Injection de Headers :** Injecter du code via l'en-tête `User-Agent`.

---

## 📁 Structure du Projet

```text
├── admin/          # Interface d'administration (dashboard, logs)
├── assets/         # Ressources statiques (CSS, JS, Images, Icons)
├── docs/           # Documentation pédagogique et guides d'exploitation
├── includes/       # Cœur de l'application (DB, Toggle mode, Header/Footer)
├── pages/          # Pages fonctionnelles du site (Produits, Panier, Login, etc.)
├── sql/            # Script d'installation de la base de données
└── index.php       # Page d'accueil
```

---

## ⚠️ Avertissement Légal

Cette application est destinée **exclusivement à un usage pédagogique**. L'utilisation des techniques présentées sur des systèmes tiers sans autorisation explicite est strictement interdite et peut être illégale. Les auteurs ne sauraient être tenus responsables de tout usage malveillant des informations contenues dans ce projet.

---

*ShopCM — TP Sécurité Web — Mai 2026*
