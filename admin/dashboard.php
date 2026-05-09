<?php
// ============================================================
// ShopCM — admin/dashboard.php
// Tableau de bord administrateur
// Aucune vulnérabilité — toutes les requêtes sont sécurisées
// ============================================================

session_start();
require_once '../includes/db.php';
require_once '../includes/mode.php';
require_once '../includes/sql_logger.php';

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

// Vérification accès admin
if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$pdo = get_pdo();

// Statistiques — toutes les requêtes sont sécurisées (pas de démo de vuln ici)
$nb_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$nb_commandes = $pdo->query("SELECT COUNT(*) FROM commandes")->fetchColumn();
$nb_produits = $pdo->query("SELECT COUNT(*) FROM produits WHERE actif = 1")->fetchColumn();
$nb_avis = $pdo->query("SELECT COUNT(*) FROM avis")->fetchColumn();

// 5 derniers logs de connexion
$logs_stmt = $pdo->prepare(
    "SELECT * FROM logs_connexion ORDER BY date_log DESC LIMIT 5"
);
$logs_stmt->execute();
$derniers_logs = $logs_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — ShopCM Admin</title>
    <link rel="stylesheet" href="/shopcm/assets/css/style.css">
    <style>
        .admin-header-bar {
            background: #1A237E;
            color: #fff;
            padding: 0.8rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .admin-header-bar a { color: #fff; }
    </style>
</head>
<body>

<div class="admin-header-bar">
    <span>🛡️ <strong>ShopCM Administration</strong></span>
    <div style="display:flex;gap:1.5rem;font-size:0.9rem;">
        <span>Connecté : <?= htmlspecialchars($_SESSION['admin_username'] ?? '') ?></span>
        <a href="/shopcm/admin/logs.php">Journal de connexions</a>
        <a href="/shopcm/admin/deconnexion.php">Déconnexion</a>
    </div>
</div>

<!-- Barre mode toggle -->
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

<main class="container" style="padding-top:2rem;">
    <h1 style="color:#1A237E;">📊 Tableau de bord</h1>

    <!-- Statistiques -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?= (int)$nb_users ?></div>
            <div class="stat-label">Clients</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= (int)$nb_commandes ?></div>
            <div class="stat-label">Commandes</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= (int)$nb_produits ?></div>
            <div class="stat-label">Produits actifs</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= (int)$nb_avis ?></div>
            <div class="stat-label">Avis clients</div>
        </div>
    </div>

    <!-- Derniers logs -->
    <h2>5 dernières connexions</h2>
    <table class="admin-table" style="margin-top:1rem;">
        <thead>
            <tr>
                <th>#</th>
                <th>IP</th>
                <th>User-Agent</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($derniers_logs as $log): ?>
            <tr>
                <td><?= (int)$log['id'] ?></td>
                <td><?= htmlspecialchars($log['ip']) ?></td>
                <td style="max-width:400px;word-break:break-all;font-size:0.8rem;">
                    <?= htmlspecialchars(mb_substr($log['user_agent'] ?? '', 0, 120)) ?>
                </td>
                <td><?= htmlspecialchars($log['date_log']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p style="margin-top:1.5rem;">
        <a href="/shopcm/admin/logs.php" class="btn-primary">Voir tous les logs →</a>
        <a href="/shopcm/index.php" class="btn-secondary" style="margin-left:1rem;">
            ← Retour au site
        </a>
    </p>
</main>

<footer>
    <p>ShopCM &mdash; TP Sécurité Web &mdash; Administration &mdash; Mai 2026</p>
</footer>
<script src="/shopcm/assets/js/script.js"></script>
</body>
</html>
