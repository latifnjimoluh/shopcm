<?php
// ============================================================
// ShopCM — includes/header.php
// En-tête HTML commun à toutes les pages
// ============================================================

// 1. Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Dépendances
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mode.php';
require_once __DIR__ . '/sql_logger.php';

// Sécurités non pédagogiques (dans les deux modes)
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

// Token CSRF — généré une seule fois par session
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? 'ShopCM') ?></title>

    <!-- PWA -->
    <link rel="manifest" href="/shopcm/manifest.json">
    <meta name="theme-color" content="#1B5E20">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="ShopCM">
    <link rel="apple-touch-icon" href="/shopcm/assets/images/icons/icon-192.png">

    <link rel="stylesheet" href="/shopcm/assets/css/style.css">
</head>
<body>

<header>
    <!-- ===== Barre principale : logo + recherche + liens ===== -->
    <div class="header-top container">
        <a href="/shopcm/index.php" class="logo">
            🛒 <strong>ShopCM</strong>
            <span class="slogan">La boutique camerounaise</span>
        </a>

        <form class="search-form" action="/shopcm/pages/produits.php" method="get">
            <input type="text" name="search"
                   placeholder="Rechercher un produit..."
                   value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            <button type="submit">🔍</button>
        </form>

        <div class="header-links">
            <?php if (!empty($_SESSION['user_id'])): ?>
                <span>👤 <?= htmlspecialchars($_SESSION['user_nom'] ?? 'Mon compte') ?></span>
                <a href="/shopcm/pages/deconnexion.php">Déconnexion</a>
            <?php else: ?>
                <a href="/shopcm/pages/connexion.php">👤 Mon compte</a>
            <?php endif; ?>
            <a href="/shopcm/pages/panier.php">🛒 Panier</a>
            <button id="pwa-install-btn" title="Installer l'application"
                    style="display:none;align-items:center;gap:0.4rem;background:var(--secondary);
                           color:#212121;border:none;padding:0.35rem 0.85rem;border-radius:4px;
                           cursor:pointer;font-size:0.8rem;font-weight:bold;">
                📲 Installer
            </button>
        </div>
    </div>

    <!-- ===== Navigation principale ===== -->
    <nav class="main-nav container">
        <a href="/shopcm/index.php">Accueil</a>
        <a href="/shopcm/pages/produits.php">Produits</a>
        <a href="/shopcm/pages/promo.php">Promotions</a>
        <a href="/shopcm/pages/contact.php">Contact</a>
    </nav>

    <!-- ===== Barre toggle Mode Vulnérable / Sécurisé ===== -->
    <div class="mode-bar <?= get_mode_class() ?>">
        <div class="container mode-bar-inner">
            <span class="mode-badge">
                <?= mode_vulnerable() ? '⚠️ MODE: VULNÉRABLE' : '🛡️ MODE: SÉCURISÉ' ?>
            </span>

            <form action="/shopcm/toggle.php" method="post" style="display:inline;">
                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <button type="submit" class="toggle-btn">
                    Basculer en mode <?= mode_vulnerable() ? 'SÉCURISÉ' : 'VULNÉRABLE' ?>
                </button>
            </form>
        </div>
    </div>

    <!-- ===== Encart SQL pédagogique ===== -->
    <div class="<?= get_mode_class() ?>">
        <div class="container">
            <details class="sql-encart">
                <summary>📋 Voir la dernière requête SQL exécutée ▼</summary>
                <?php $last = get_last_sql(); ?>
                <?php if (!empty($last)): ?>
                    <pre>-- Timestamp : <?= htmlspecialchars($last['timestamp']) ?>
-- Mode : <?= get_mode_label() ?>

<?= htmlspecialchars($last['query']) ?>

<?php if (!empty($last['params'])): ?>-- Paramètres liés : <?= htmlspecialchars(json_encode($last['params'], JSON_UNESCAPED_UNICODE)) ?>
<?php endif; ?>
<?php if (isset($last['duree_ms'])): ?>-- Temps d'exécution de la requête : <?= (int)$last['duree_ms'] ?>ms
<?php endif; ?></pre>
                <?php else: ?>
                    <p>Aucune requête SQL loggée sur cette page.</p>
                <?php endif; ?>
            </details>
        </div>
    </div>
</header>

<main class="container">
