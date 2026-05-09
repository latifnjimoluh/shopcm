<?php
// ============================================================
// ShopCM — inscription.php
// Création de compte client — requêtes toutes sécurisées
// ============================================================

$page_title = 'Inscription — ShopCM';
require_once '../includes/header.php';

$pdo    = get_pdo();
$error  = null;
$succes = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom       = trim($_POST['nom']       ?? '');
    $prenom    = trim($_POST['prenom']    ?? '');
    $email     = trim($_POST['email']     ?? '');
    $pass      = $_POST['password']       ?? '';
    $telephone = trim($_POST['telephone'] ?? '');
    $adresse   = trim($_POST['adresse']   ?? '');

    // Validation basique
    if (!$nom || !$prenom || !$email || !$pass) {
        $error = "Tous les champs obligatoires doivent être remplis.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Adresse email invalide.";
    } elseif (strlen($pass) < 6) {
        $error = "Le mot de passe doit contenir au moins 6 caractères.";
    } else {
        // Vérifier l'unicité de l'email — requête préparée
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = "Cette adresse email est déjà utilisée.";
        } else {
            // INSERT sécurisé + bcrypt
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare(
                "INSERT INTO users (email, password, nom, prenom, telephone, adresse, date_inscription, solde_fidelite)
                 VALUES (?, ?, ?, ?, ?, ?, NOW(), 0)"
            );
            try {
                $stmt->execute([$email, $hash, $nom, $prenom, $telephone, $adresse]);
                $succes = true;
            } catch (PDOException $e) {
                $error = "Erreur lors de l'inscription. Veuillez réessayer.";
            }
        }
    }

    if ($succes) {
        header('Location: /shopcm/pages/connexion.php?inscrit=1');
        exit;
    }
}
?>

<div class="auth-box" style="max-width:560px;">
    <h1>📝 Créer un compte</h1>

    <?php if ($error): ?>
    <div class="alert alert-danger" data-autohide>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form action="/shopcm/pages/inscription.php" method="post">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 1rem;">
            <div class="form-group">
                <label for="prenom">Prénom *</label>
                <input type="text" id="prenom" name="prenom"
                       value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>"
                       required>
            </div>
            <div class="form-group">
                <label for="nom">Nom *</label>
                <input type="text" id="nom" name="nom"
                       value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>"
                       required>
            </div>
        </div>

        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                   required>
        </div>

        <div class="form-group">
            <label for="password">Mot de passe * (min. 6 caractères)</label>
            <input type="password" id="password" name="password" required>
        </div>

        <div class="form-group">
            <label for="telephone">Téléphone</label>
            <input type="tel" id="telephone" name="telephone"
                   value="<?= htmlspecialchars($_POST['telephone'] ?? '') ?>"
                   placeholder="+237 6XX XXX XXX">
        </div>

        <div class="form-group">
            <label for="adresse">Adresse de livraison</label>
            <textarea id="adresse" name="adresse" rows="3"><?= htmlspecialchars($_POST['adresse'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn-primary" style="width:100%;">
            Créer mon compte
        </button>
    </form>

    <p style="text-align:center;margin-top:1rem;font-size:0.9rem;">
        Déjà un compte ? <a href="/shopcm/pages/connexion.php">Se connecter</a>
    </p>
</div>

<?php require_once '../includes/footer.php'; ?>
