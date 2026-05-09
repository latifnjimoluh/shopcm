<?php
// ============================================================
// ShopCM — connexion.php
// Authentification client
// VULNÉRABILITÉ #7 — Auth bypass via email/password
// ============================================================

$page_title = 'Connexion — ShopCM';
require_once '../includes/header.php';

$pdo   = get_pdo();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email']    ?? '';
    $pass  = $_POST['password'] ?? '';

    // ==========================================================
    // VULNÉRABILITÉ #7 — Auth bypass par injection SQL
    // Payload : email = ' OR 1=1-- -    /  password = n'importe quoi
    // Requête générée : SELECT * FROM users WHERE email='' OR 1=1-- -' AND password='...'
    // ==========================================================

    if (mode_vulnerable()) {
        // ⚠️ VULNÉRABLE : concaténation directe + connexion sans password_verify
        // L'injection dans l'email court-circuite la vérification du mot de passe
        $query = "SELECT * FROM users WHERE email='$email' AND password='$pass'";
        try {
            $user = $pdo->query($query)->fetch();
        } catch (PDOException $e) {
            $user  = null;
            $error = $e->getMessage();
        }
        log_sql($query);

        if ($user) {
            // Connexion directe sans password_verify — le hash n'est jamais vérifié
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['user_nom'] = $user['prenom'] . ' ' . $user['nom'];
            header('Location: /shopcm/index.php');
            exit;
        } else {
            if (!$error) {
                $error = "Email ou mot de passe incorrect.";
            }
        }

    } else {
        // ✅ SÉCURISÉ : requête sur email seul + password_verify() sur le hash bcrypt
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        try {
            $stmt->execute([$email]);
            $user = $stmt->fetch();
        } catch (PDOException $e) {
            $user  = null;
            $error = "Erreur serveur — veuillez réessayer.";
        }
        log_sql("SELECT * FROM users WHERE email = ?", [$email]);

        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['user_nom'] = $user['prenom'] . ' ' . $user['nom'];
            header('Location: /shopcm/index.php');
            exit;
        } else {
            // Message générique — ne jamais distinguer email inconnu / mauvais mot de passe
            $error = "Email ou mot de passe incorrect.";
        }
    }
}
?>

<div class="auth-box">
    <h1>🔐 Connexion</h1>

    <?php if ($error): ?>
    <div class="alert alert-danger" data-autohide>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form action="/shopcm/pages/connexion.php" method="post">
        <div class="form-group">
            <label for="email">Email</label>
            <?php if (mode_vulnerable()): ?>
            <?php /* ⚠️ VULNÉRABLE : type="text" — pas de validation @ du navigateur, permet les payloads SQLi */ ?>
            <input type="text" id="email" name="email"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                   placeholder="' OR 1=1-- -"
                   autocomplete="off">
            <?php else: ?>
            <?php /* ✅ SÉCURISÉ : type="email" — validation navigateur standard */ ?>
            <input type="email" id="email" name="email"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                   placeholder="votre@email.com"
                   autocomplete="email">
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password"
                   placeholder="••••••••"
                   autocomplete="current-password">
        </div>

        <button type="submit" class="btn-primary" style="width:100%;">
            Se connecter
        </button>
    </form>

    <p style="text-align:center;margin-top:1rem;font-size:0.9rem;">
        Pas encore de compte ?
        <a href="/shopcm/pages/inscription.php">S'inscrire</a>
    </p>
</div>

<?php require_once '../includes/footer.php'; ?>
