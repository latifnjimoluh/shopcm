# Concepts théoriques — Injection SQL

> Document pédagogique du projet **ShopCM** — Plateforme d'apprentissage de la sécurité des applications web.

---

## 1. Définition et historique de l'injection SQL

L'**injection SQL** (SQLi) est une technique d'attaque qui consiste à insérer ou à « injecter » du code SQL malveillant dans une requête légitime envoyée à une base de données. Lorsqu'une application web construit ses requêtes SQL en concaténant directement les données fournies par l'utilisateur, un attaquant peut manipuler la logique de ces requêtes pour lire, modifier ou supprimer des données, voire prendre le contrôle du serveur.

La première description publique formelle de cette vulnérabilité remonte à **1998**, dans un article de Jeff Forristal (alias « rain.forest.puppy ») publié dans le magazine *Phrack*. Dès les années 2000, l'injection SQL devient l'une des attaques les plus répandues sur le Web. Des incidents majeurs ont marqué l'histoire : le piratage de Sony Pictures (2011), la fuite de données de LinkedIn (2012) ou encore l'exploitation massive d'applications PHP mal sécurisées via des outils automatisés comme **sqlmap**. Aujourd'hui, malgré des décennies de sensibilisation, l'injection SQL figure encore systématiquement dans les classements des vulnérabilités les plus critiques.

---

## 2. Classification complète des injections SQL

### 2.1 In-band — Error-based et UNION-based

Les injections **in-band** sont les plus simples à exploiter : les résultats de l'attaque sont renvoyés directement dans la réponse HTTP de l'application.

- **Error-based** : l'attaquant provoque volontairement des erreurs SQL pour extraire des informations (nom de la base, version du SGBD, structure des tables) directement depuis les messages d'erreur affichés.
- **UNION-based** : en ajoutant une clause `UNION SELECT`, l'attaquant concatène des résultats provenant d'autres tables à la requête originale, récupérant ainsi des données sensibles (mots de passe, e-mails, etc.).

### 2.2 Blind — Boolean-based et Time-based

Les injections **blind** (aveugles) s'appliquent lorsque l'application n'affiche aucun message d'erreur ni aucune donnée brute.

- **Boolean-based** : l'attaquant formule des conditions vraies ou fausses (`AND 1=1`, `AND 1=2`) et déduit les informations à partir du comportement différent de l'application (page affichée / page vide).
- **Time-based** : l'attaquant utilise des fonctions de délai (`SLEEP(5)`, `WAITFOR DELAY`) pour inférer des données selon le temps de réponse du serveur.

### 2.3 Out-of-band — DNS et HTTP

Les injections **out-of-band** exfiltrent les données via un canal de communication distinct (DNS, HTTP). Elles sont utilisées lorsque ni les réponses directes ni le timing ne sont exploitables. Par exemple, la fonction `load_file()` combinée à un chemin UNC peut déclencher une requête DNS vers un serveur contrôlé par l'attaquant.

### 2.4 Stored / Persistante

Une injection **stockée** (ou persistante) se produit lorsque la charge malveillante est enregistrée dans la base de données (commentaire, pseudo, champ adresse) et exécutée plus tard, lors d'une consultation par un autre utilisateur ou un administrateur. Elle est particulièrement dangereuse car l'attaque peut toucher de nombreuses victimes à retardement.

---

## 3. Causes profondes

### 3.1 Concaténation directe de chaînes en SQL

La cause principale reste la construction de requêtes SQL par simple concaténation de chaînes de caractères, comme dans l'exemple suivant :

```php
$query = "SELECT * FROM users WHERE login = '" . $_GET['login'] . "'";
```

Un attaquant saisit `' OR '1'='1` et la requête devient logiquement vraie pour tous les enregistrements.

### 3.2 Absence de validation et de typage

Accepter n'importe quelle valeur sans vérifier son type (entier attendu ? chaîne de longueur limitée ?), sans rejeter les caractères spéciaux SQL (`'`, `--`, `;`, `/*`) ouvre la porte aux injections. L'absence de typage strict en PHP, par exemple, aggrave le risque.

### 3.3 Messages d'erreur SQL verbeux en production

Afficher en production des messages d'erreur complets comme `You have an error in your SQL syntax near '...'` offre à l'attaquant une carte précieuse : nom du SGBD, version, structure partielle de la requête. Ces informations accélèrent considérablement une attaque error-based.

---

## 4. Vecteurs d'injection

Les données contrôlées par l'utilisateur peuvent transiter par de nombreux canaux :

- **Paramètres URL (GET)** : `?id=1`, `?search=produit` — le vecteur le plus classique et le plus exposé.
- **Corps POST** : formulaires de connexion, d'inscription, de recherche — souvent négligés car moins visibles.
- **Cookies** : valeurs stockées côté client et renvoyées automatiquement, rarement validées côté serveur.
- **Headers HTTP** : `X-Forwarded-For`, `Host`, `Authorization` — injectés dans des logs ou des requêtes de traçabilité.
- **Données JSON et XML** : dans les API REST ou SOAP, les champs JSON/XML peuvent être mal désérialisés avant d'être intégrés à une requête.
- **User-Agent et Referer** : certaines applications enregistrent ces en-têtes dans la base (statistiques, logs) sans les assainir, créant un vecteur d'injection stockée.

---

## 5. Méthodes de défense

### 5.1 Requêtes préparées — la défense absolue

L'utilisation de **requêtes préparées avec paramètres liés** (PDO, MySQLi en PHP, PreparedStatement en Java) est la protection la plus efficace. La requête et les données sont séparées ; le SGBD ne peut jamais interpréter les données comme du code SQL.

```php
$stmt = $pdo->prepare("SELECT * FROM users WHERE login = ?");
$stmt->execute([$_POST['login']]);
```

### 5.2 Validation par liste blanche (whitelist)

Plutôt que de blacklister des caractères dangereux (approche fragile), on définit précisément ce qui est autorisé : un identifiant numérique doit être un entier, un nom de colonne ne peut appartenir qu'à un ensemble prédéfini.

### 5.3 Principe du moindre privilège SQL

L'utilisateur de base de données utilisé par l'application ne doit disposer que des droits strictement nécessaires (`SELECT`, `INSERT` selon le besoin). Il ne doit jamais pouvoir exécuter `DROP`, `GRANT` ou accéder à `information_schema`.

### 5.4 Web Application Firewall (WAF)

Un WAF analyse le trafic HTTP et bloque les requêtes contenant des patterns caractéristiques d'une injection SQL. Il constitue une couche de défense complémentaire, mais ne remplace pas la sécurisation du code source.

### 5.5 Désactivation des erreurs détaillées en production

La directive `display_errors = Off` en PHP (et son équivalent dans les autres langages) empêche l'affichage des messages d'erreur SQL au visiteur. Les erreurs doivent être journalisées côté serveur, jamais exposées côté client.

---

## 6. OWASP Top 10 — A03:2021 : Injection

L'**OWASP** (Open Web Application Security Project) publie tous les trois à quatre ans un classement des dix risques les plus critiques pour les applications web. Depuis 2021, la catégorie **A03 — Injection** regroupe l'injection SQL, l'injection de commandes OS, l'injection LDAP et d'autres variantes.

**Définition officielle (OWASP 2021)** : *« Une application est vulnérable à une attaque par injection lorsque des données fournies par l'utilisateur ne sont pas validées, filtrées ou assainies par l'application ; lorsque des requêtes dynamiques ou des appels non paramétrés sont utilisés directement sans échappement contextuel. »*

**Exemple typique** : un champ de connexion non protégé permet à un attaquant de saisir `admin'--` pour contourner l'authentification et accéder au compte administrateur sans connaître le mot de passe.

**Score CVSS type** : une injection SQL permettant une exfiltration de données complète est généralement scorée entre **9.0 et 10.0 (Critique)** sur l'échelle CVSS v3.1, en raison de son impact élevé sur la confidentialité, l'intégrité et la disponibilité des données.

---

## 7. Cadre légal camerounais

La **Loi N° 2010/012 du 21 décembre 2010** relative à la cybersécurité et à la cybercriminalité au Cameroun constitue le cadre juridique de référence en matière d'attaques informatiques.

Les **articles 65 et suivants** de cette loi sanctionnent notamment :

- **L'accès frauduleux à un système d'information** (art. 65) : toute intrusion non autorisée dans un système de traitement automatisé de données est punie d'un emprisonnement de **1 à 2 ans** et d'une amende de **500 000 à 1 000 000 FCFA**, ou de l'une de ces deux peines seulement.
- **L'accès avec altération ou suppression de données** (art. 65, al. 2) : lorsque l'intrusion entraîne une modification ou une destruction de données, les peines sont portées à **2 à 10 ans** d'emprisonnement et à une amende de **1 000 000 à 5 000 000 FCFA**.
- **Les infractions commises contre des systèmes d'État ou des infrastructures critiques** (art. 68 et suivants) exposent à des peines encore plus lourdes, pouvant aller jusqu'à **20 ans** d'emprisonnement.

Dans le cadre du projet **ShopCM**, toutes les manipulations de vulnérabilités SQL sont réalisées exclusivement dans un **environnement local contrôlé**, à des fins pédagogiques, et ne constituent en aucun cas une infraction à cette législation. Toute tentative de reproduire ces techniques sur des systèmes réels sans autorisation explicite engage la responsabilité pénale de son auteur.

---

*Document rédigé à des fins pédagogiques dans le cadre du projet ShopCM — Mai 2026.*
