<?php
// ============================================================
// ShopCM — admin/login.php
// Authentification administrateur
// VULNÉRABILITÉ #11 — Auth bypass + UNION SELECT sur table admins
// ============================================================

session_start();
require_once '../includes/db.php';
require_once '../includes/mode.php';
require_once '../includes/sql_logger.php';

// Sécurités non pédagogiques
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

// Token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$pdo   = get_pdo();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // ==========================================================
    // VULNÉRABILITÉ #11 — Auth bypass sur table admins
    // Payload bypass : admin' OR '1'='1'-- -
    // Payload UNION  : ' UNION SELECT 1,'pirate','pirate_hash',4,5 FROM admins WHERE '1'='1'-- -
    // ==========================================================

    if (mode_vulnerable()) {
        // ⚠️ VULNÉRABLE : concaténation directe + connexion sans password_verify
        $query = "SELECT * FROM admins WHERE username='$username' AND password='$password'";
        try {
            $admin = $pdo->query($query)->fetch();
        } catch (PDOException $e) {
            $admin = null;
            $error = $e->getMessage();
        }
        log_sql($query);

        if ($admin) {
            // Connexion directe sans vérification du hash
            $_SESSION['admin_id']       = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            header('Location: dashboard.php');
            exit;
        } else {
            if (!$error) {
                $error = "Identifiants incorrects.";
            }
        }

    } else {
        // SÉCURISÉ : requête sur username seul + password_verify() sur le hash bcrypt
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
        try {
            $stmt->execute([$username]);
            $admin = $stmt->fetch();
        } catch (PDOException $e) {
            $admin = null;
            $error = "Erreur serveur — veuillez réessayer.";
        }
        log_sql("SELECT * FROM admins WHERE username = ?", [$username]);

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id']       = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = "Identifiants incorrects.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration — ShopCM</title>
    <link rel="stylesheet" href="/shopcm/assets/css/style.css">
    <style>
        body { background: #E8EAF6; }
        .admin-header {
            background: #1A237E;
            color: #fff;
            padding: 1rem 2rem;
            text-align: center;
            font-size: 1.2rem;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="admin-header">
    🛡️ ShopCM — Administration
</div>

<!-- Barre mode (même toggle que le site public) -->
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

<!-- Encart SQL -->
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

<main class="container">
<div class="auth-box" style="margin-top:2rem;">
    <h1 style="color:#1A237E;">🔐 Connexion Admin</h1>

    <?php if ($error): ?>
    <div class="alert alert-danger">
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form action="/shopcm/admin/login.php" method="post">
        <div class="form-group">
            <label for="username">Nom d'utilisateur</label>
            <input type="text" id="username" name="username"
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                   placeholder="admin"
                   autocomplete="username">
        </div>
        <div class="form-group">
            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password"
                   placeholder="••••••••"
                   autocomplete="current-password">
        </div>
        <button type="submit" class="btn-primary" style="width:100%;background:#1A237E;">
            Se connecter
        </button>
    </form>

    <p style="text-align:center;margin-top:1rem;">
        <a href="/shopcm/index.php">← Retour au site</a>
    </p>
</div>
</main>

<footer>
    <p>ShopCM &mdash; TP Sécurité Web &mdash; Administration &mdash; Mai 2026</p>
</footer>
<script src="/shopcm/assets/js/script.js"></script>
</body>
</html>
