<?php
// ============================================================
// ShopCM — admin/logs.php
// Journal des connexions (User-Agent)
// VULNÉRABILITÉ #12 — Injection SQL via header HTTP User-Agent
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

$pdo   = get_pdo();
$error = null;

// ============================================================
// VULNÉRABILITÉ #12 — Injection via header HTTP User-Agent
// Le User-Agent est inséré en base sans nettoyage en mode vuln.
// Payload de démonstration (via terminal) :
//   curl -A "Mozilla', NOW()), ('127.0.0.1', (SELECT password FROM admins LIMIT 1), NOW())-- -" \
//     http://localhost/shopcm/admin/logs.php
// (Nécessite d'être connecté — ou de désactiver la vérification de session pour le test)
// ============================================================

$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

if (mode_vulnerable()) {
    // ⚠️ VULNÉRABLE : User-Agent HTTP inséré brut — vecteur d'injection non visible dans l'UI
    // Un attaquant peut envoyer un User-Agent contenant du SQL via curl/Burp
    $query = "INSERT INTO logs_connexion (ip, user_agent, date_log)
              VALUES ('$ip', '$ua', NOW())";
    try {
        $pdo->exec($query);
    } catch (PDOException $e) {
        $error = $e->getMessage();
    }
    log_sql($query);

} else {
    // ✅ SÉCURISÉ : User-Agent traité comme donnée pure via paramètre lié PDO
    // Le payload SQL est stocké comme texte brut, jamais interprété
    $stmt = $pdo->prepare(
        "INSERT INTO logs_connexion (ip, user_agent, date_log) VALUES (?, ?, NOW())"
    );
    try {
        $stmt->execute([$ip, $ua]);
    } catch (PDOException $e) {
        $error = "Erreur serveur — veuillez réessayer.";
    }
    log_sql(
        "INSERT INTO logs_connexion (ip, user_agent, date_log) VALUES (?, ?, NOW())",
        [$ip, $ua]
    );
}

// Récupération des 20 derniers logs
$logs = [];
$logs_stmt = $pdo->prepare(
    "SELECT * FROM logs_connexion ORDER BY date_log DESC LIMIT 20"
);
$logs_stmt->execute();
$logs = $logs_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs de connexion — ShopCM Admin</title>
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
    <span>🛡️ <strong>ShopCM — Journal de connexions</strong></span>
    <div style="display:flex;gap:1.5rem;font-size:0.9rem;">
        <a href="/shopcm/admin/dashboard.php">Dashboard</a>
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

<!-- Encart SQL pédagogique -->
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
<?php endif; ?></pre>
            <?php else: ?>
                <p>Aucune requête SQL loggée sur cette page.</p>
            <?php endif; ?>
        </details>
    </div>
</div>

<main class="container" style="padding-top:1.5rem;">
    <h1 style="color:#1A237E;">📋 Journal de connexions (20 dernières)</h1>

    <?php if ($error): ?>
    <div class="alert alert-danger">
        <strong>Erreur SQL :</strong> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- Encart pédagogique obligatoire -->
    <div class="info-encart">
        <strong>🎓 Vecteur d'attaque : header HTTP User-Agent (Vulnérabilité #12)</strong>
        Toute donnée envoyée par le client HTTP (headers, cookies, body JSON...)
        est un vecteur d'injection potentiel — y compris des données
        <strong>jamais saisies dans un formulaire visible</strong>.<br><br>
        <strong>Payload de démonstration (terminal) :</strong><br>
        <code>curl -A "Mozilla', NOW()), ('127.0.0.1', (SELECT password FROM admins LIMIT 1), NOW())-- -" \
  http://localhost/shopcm/admin/logs.php</code><br><br>
        En mode vulnérable, ce payload apparaît dans la colonne User-Agent ci-dessous.
    </div>

    <!-- Tableau des logs -->
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
            <?php foreach ($logs as $log): ?>
            <tr>
                <td><?= (int)$log['id'] ?></td>
                <td><?= htmlspecialchars($log['ip']) ?></td>
                <td style="max-width:450px;word-break:break-all;font-size:0.8rem;">
                    <?php if (mode_vulnerable()): ?>
                        <?php /* ⚠️ VULNÉRABLE : User-Agent affiché sans htmlspecialchars — injection XSS possible aussi */ ?>
                        <?= $log['user_agent'] ?>
                    <?php else: ?>
                        <?php /* ✅ SÉCURISÉ : htmlspecialchars neutralise tout contenu injecté */ ?>
                        <?= htmlspecialchars($log['user_agent'] ?? '') ?>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($log['date_log']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p style="margin-top:1.5rem;">
        <a href="/shopcm/admin/dashboard.php" class="btn-secondary">← Dashboard</a>
    </p>
</main>

<footer>
    <p>ShopCM &mdash; TP Sécurité Web &mdash; Administration &mdash; Mai 2026</p>
</footer>
<script src="/shopcm/assets/js/script.js"></script>
</body>
</html>
