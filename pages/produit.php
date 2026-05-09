<?php
// ============================================================
// ShopCM — produit.php
// Fiche produit + formulaire d'avis
// VULNÉRABILITÉS #5 (injection numérique ?id=) et #6 (Stored SQLi avis)
// ============================================================

$page_title = 'Produit — ShopCM';
require_once '../includes/header.php';

$pdo     = get_pdo();
$id      = $_GET['id'] ?? '1';
$produit = null;
$error   = null;
$msg     = null;

// ============================================================
// VULNÉRABILITÉ #5 — Injection numérique via ?id=
// Payload 1 : 1 OR 1=1     → affiche produits inactifs (actif=0)
// Payload 2 : -1 UNION SELECT 1,cle,valeur,4,5,6,7,8 FROM secrets_internes-- -
// ============================================================

if (mode_vulnerable()) {
    // ⚠️ VULNÉRABLE : $id injecté directement — pas de cast, pas de paramètre lié
    $query = "SELECT * FROM produits WHERE id = $id";
    try {
        $result  = $pdo->query($query);
        $produit = $result->fetch();
    } catch (PDOException $e) {
        $error = $e->getMessage();
    }
    log_sql($query);

} else {
    // ✅ SÉCURISÉ : cast en entier + paramètre lié PDO + restriction actif=1
    $stmt = $pdo->prepare("SELECT * FROM produits WHERE id = ? AND actif = 1");
    try {
        $stmt->execute([(int)$id]);
        $produit = $stmt->fetch();
    } catch (PDOException $e) {
        $error = "Erreur serveur — veuillez réessayer.";
    }
    log_sql("SELECT * FROM produits WHERE id = ? AND actif = 1", [(int)$id]);
}

// ============================================================
// VULNÉRABILITÉ #6 — Stored SQLi via formulaire d'avis (POST)
// Payload dans le champ commentaire :
//   test', NOW()), (1, 1, 5, (SELECT password FROM admins LIMIT 1), NOW())-- -
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['commentaire'])) {
    $commentaire = $_POST['commentaire'] ?? '';
    $note        = $_POST['note']        ?? '5';
    $produit_id  = $_POST['produit_id']  ?? $id;
    $user_id     = $_SESSION['user_id']  ?? 1; // Utilise user_id=1 si non connecté (démo)

    if (mode_vulnerable()) {
        // ⚠️ VULNÉRABLE : concaténation directe — le commentaire peut fermer la chaîne SQL
        $query = "INSERT INTO avis (produit_id, user_id, note, commentaire, date_avis)
                  VALUES ($produit_id, $user_id, $note, '$commentaire', NOW())";
        try {
            $pdo->exec($query);
            $msg = "Avis publié avec succès.";
        } catch (PDOException $e) {
            $error = $e->getMessage();
        }
        log_sql($query);

    } else {
        // ✅ SÉCURISÉ : requête préparée — le commentaire est traité comme donnée pure
        $stmt = $pdo->prepare(
            "INSERT INTO avis (produit_id, user_id, note, commentaire, date_avis)
             VALUES (?, ?, ?, ?, NOW())"
        );
        try {
            $stmt->execute([$produit_id, $user_id, (int)$note, $commentaire]);
            $msg = "Avis publié avec succès.";
        } catch (PDOException $e) {
            $error = "Erreur serveur — veuillez réessayer.";
        }
        log_sql(
            "INSERT INTO avis (produit_id, user_id, note, commentaire, date_avis) VALUES (?, ?, ?, ?, NOW())",
            [$produit_id, $user_id, (int)$note, $commentaire]
        );
    }
}

// Chargement des avis du produit
$avis = [];
if ($produit) {
    $avis_stmt = $pdo->prepare(
        "SELECT a.*, u.nom, u.prenom FROM avis a
         LEFT JOIN users u ON a.user_id = u.id
         WHERE a.produit_id = ?
         ORDER BY a.date_avis DESC"
    );
    $avis_stmt->execute([(int)($produit['id'] ?? $id)]);
    $avis = $avis_stmt->fetchAll();
}
?>

<?php if ($error): ?>
<div class="alert alert-danger">
    <strong>Erreur SQL :</strong> <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<?php if ($msg): ?>
<div class="alert alert-success" data-autohide>
    <?= htmlspecialchars($msg) ?>
</div>
<?php endif; ?>

<?php if (!$produit && !$error): ?>
    <!-- Mode sécurisé : produit non trouvé ou inactif -->
    <div class="alert alert-warning">
        <strong>Produit introuvable.</strong> Ce produit n'existe pas ou n'est plus disponible.
    </div>
    <p><a href="/shopcm/pages/produits.php">← Retour au catalogue</a></p>

<?php elseif ($produit): ?>

<!-- ===== Fiche produit ===== -->
<div class="product-detail">
    <div>
        <img src="/shopcm/assets/images/<?= htmlspecialchars($produit['image'] ?? 'placeholder.jpg') ?>"
             alt="<?= htmlspecialchars($produit['nom'] ?? '') ?>"
             onerror="this.src='/shopcm/assets/images/placeholder.jpg'">
    </div>
    <div class="product-info">
        <p style="color:#757575;font-size:0.85rem;margin-bottom:0.5rem;">
            <a href="/shopcm/pages/produits.php">← Catalogue</a>
        </p>
        <h1><?= htmlspecialchars($produit['nom'] ?? '') ?></h1>
        <p class="price-large">
            <?= is_numeric($produit['prix'] ?? null)
                ? number_format($produit['prix'], 0, ',', ' ') . ' FCFA'
                : htmlspecialchars($produit['prix'] ?? '') ?>
        </p>
        <p><?= htmlspecialchars($produit['description'] ?? '') ?></p>
        <p style="margin-top:1rem;color:#757575;">
            Stock disponible : <strong><?= htmlspecialchars($produit['stock'] ?? '') ?></strong>
        </p>
        <?php if (!($produit['actif'] ?? 1)): ?>
        <div class="alert alert-warning" style="margin-top:1rem;">
            ⚠️ Ce produit est <strong>inactif</strong> — visible uniquement en mode vulnérable.
        </div>
        <?php endif; ?>
        <button class="btn-primary btn-add-cart"
                data-id="<?= (int)($produit['id'] ?? 0) ?>"
                style="margin-top:1.5rem;">
            🛒 Ajouter au panier
        </button>
    </div>
</div>

<!-- ===== Avis ===== -->
<hr style="margin:2rem 0;">
<h2>Avis clients (<?= count($avis) ?>)</h2>

<?php if (!empty($avis)): ?>
<div class="avis-list">
    <?php foreach ($avis as $a): ?>
    <div class="avis-item">
        <div class="avis-header">
            <span>
                <strong>
                    <?php if (mode_vulnerable()): ?>
                        <?= $a['prenom'] . ' ' . $a['nom'] ?>
                    <?php else: ?>
                        <?= htmlspecialchars($a['prenom'] . ' ' . $a['nom']) ?>
                    <?php endif; ?>
                </strong>
            </span>
            <span class="stars">
                <?= str_repeat('★', (int)($a['note'] ?? 0)) ?>
                <?= str_repeat('☆', 5 - (int)($a['note'] ?? 0)) ?>
            </span>
            <span><?= htmlspecialchars($a['date_avis'] ?? '') ?></span>
        </div>
        <p>
            <?php if (mode_vulnerable()): ?>
                <?php /* ⚠️ VULNÉRABLE : commentaire affiché sans htmlspecialchars — l'injection stored est visible */ ?>
                <?= $a['commentaire'] ?>
            <?php else: ?>
                <?php /* ✅ SÉCURISÉ : htmlspecialchars neutralise tout contenu injecté */ ?>
                <?= htmlspecialchars($a['commentaire'] ?? '') ?>
            <?php endif; ?>
        </p>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<p style="color:#757575;">Aucun avis pour ce produit. Soyez le premier !</p>
<?php endif; ?>

<!-- ===== Formulaire d'avis ===== -->
<hr style="margin:2rem 0;">
<h2>Laisser un avis</h2>

<form action="/shopcm/pages/produit.php?id=<?= htmlspecialchars($id) ?>" method="post"
      style="max-width:600px;">
    <input type="hidden" name="produit_id" value="<?= htmlspecialchars($id) ?>">

    <div class="form-group">
        <label for="note">Note</label>
        <select id="note" name="note">
            <option value="5">★★★★★ — Excellent</option>
            <option value="4">★★★★☆ — Bien</option>
            <option value="3">★★★☆☆ — Moyen</option>
            <option value="2">★★☆☆☆ — Décevant</option>
            <option value="1">★☆☆☆☆ — Mauvais</option>
        </select>
    </div>

    <div class="form-group">
        <label for="commentaire">Commentaire</label>
        <textarea id="commentaire" name="commentaire" rows="4"
                  placeholder="Partagez votre expérience..."></textarea>
    </div>

    <button type="submit" class="btn-primary">Publier mon avis</button>
</form>

<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
