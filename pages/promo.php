<?php
// ============================================================
// ShopCM — promo.php
// Vérification de code promo
// VULNÉRABILITÉ #10 — Boolean blind SQLi via code promo
// ============================================================

$page_title = 'Code Promotionnel — ShopCM';
require_once '../includes/header.php';

$pdo    = get_pdo();
$code   = $_POST['code'] ?? $_GET['code'] ?? '';
$promo  = null;
$error  = null;
$tested = false;

// ============================================================
// VULNÉRABILITÉ #10 — Boolean blind via code promo
//
// BYPASS (code inexistant rendu valide) :
//   FAUX' OR '1'='1'-- -  → retourne le premier code valide en base → "Code valide ! 20%"
//
// BOOLEAN BLIND (exfiltration caractère par caractère) :
//   Vrai  : NOEL2025' AND 1=1-- -             → "Code valide !"
//   Faux  : NOEL2025' AND 1=2-- -             → "Code invalide."
//   Exfil : NOEL2025' AND SUBSTRING((SELECT password FROM admins LIMIT 1),1,1)='$'-- -
// ============================================================

if ($code !== '') {
    $tested = true;

    if (mode_vulnerable()) {
        // ⚠️ VULNÉRABLE : $code injecté directement dans la requête
        $query = "SELECT * FROM codes_promo WHERE code = '$code'
                  AND actif = 1 AND date_expiration >= CURDATE()";
        try {
            $promo = $pdo->query($query)->fetch();
        } catch (PDOException $e) {
            $error = $e->getMessage();
        }
        log_sql($query);

    } else {
        // ✅ SÉCURISÉ : paramètre lié PDO — toute injection est traitée comme texte
        $stmt = $pdo->prepare(
            "SELECT * FROM codes_promo WHERE code = ?
             AND actif = 1 AND date_expiration >= CURDATE()"
        );
        try {
            $stmt->execute([$code]);
            $promo = $stmt->fetch();
        } catch (PDOException $e) {
            $error = "Erreur serveur — veuillez réessayer.";
        }
        log_sql(
            "SELECT * FROM codes_promo WHERE code = ? AND actif = 1 AND date_expiration >= CURDATE()",
            [$code]
        );
    }
}
?>

<h1>🎟️ Code Promotionnel</h1>

<div class="info-encart">
    <strong>🎓 Note pédagogique — Bypass + Boolean blind (Vulnérabilité #10)</strong>
    <strong>Attaque 1 — Bypass :</strong> un code inexistant peut être rendu valide :<br>
    <code>FAUX' OR '1'='1'-- -</code> → la requête retourne toujours une ligne → <em>"Code valide ! Réduction : 20%"</em><br><br>
    <strong>Attaque 2 — Boolean blind :</strong> la réponse binaire valide/invalide suffit à
    exfiltrer des données caractère par caractère sans jamais rien afficher directement.
</div>

<form action="/shopcm/pages/promo.php" method="post" style="max-width:400px;margin:1.5rem 0;">
    <div class="form-group">
        <label for="code">Entrez votre code promo</label>
        <input type="text" id="code" name="code"
               value="<?= htmlspecialchars($code) ?>"
               placeholder="Ex: NOEL2025"
               autocomplete="off">
    </div>
    <button type="submit" class="btn-primary">Vérifier le code</button>
</form>

<?php if ($error): ?>
<div class="alert alert-danger">
    <strong>Erreur SQL :</strong> <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<?php if ($tested && !$error): ?>
    <?php if ($promo): ?>
    <div class="promo-result alert alert-success">
        ✅ Code valide ! Réduction : <strong><?= (int)$promo['reduction_pourcent'] ?>%</strong>
    </div>
    <?php else: ?>
    <div class="promo-result alert alert-danger">
        ❌ Code invalide ou expiré.
    </div>
    <?php endif; ?>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
