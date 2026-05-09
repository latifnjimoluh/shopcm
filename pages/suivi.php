<?php
// ============================================================
// ShopCM — suivi.php
// Suivi de commande par numéro
// VULNÉRABILITÉ #9 — Time-based blind SQLi via ?numero=
// ============================================================

$page_title = 'Suivi de commande — ShopCM';
require_once '../includes/header.php';

$pdo      = get_pdo();
$numero   = $_GET['numero'] ?? '';
$commande = null;
$error    = null;
$duree_ms = null;
?>

<h1>📦 Suivi de commande</h1>

<form action="/shopcm/pages/suivi.php" method="get" style="max-width:500px;">
    <div class="form-group">
        <label for="numero">Numéro de suivi</label>
        <input type="text" id="numero" name="numero"
               value="<?= htmlspecialchars($numero) ?>"
               placeholder="Ex: SHOP-2025-0001">
    </div>
    <button type="submit" class="btn-primary">Rechercher</button>
</form>

<?php
// ============================================================
// VULNÉRABILITÉ #9 — Time-based blind SQLi
// Payload : SHOP-2025-0001' AND SLEEP(5)-- -
// En mode vulnérable : ~5000ms
// En mode sécurisé   : ~3ms (SLEEP ignoré car paramètre lié)
// ============================================================

if ($numero !== '') {

    if (mode_vulnerable()) {
        // ⚠️ VULNÉRABLE : $numero injecté directement — SLEEP(5) exécuté par MySQL
        $t_start = microtime(true);
        $query   = "SELECT * FROM commandes WHERE numero_suivi = '$numero'";
        try {
            $commande = $pdo->query($query)->fetch();
        } catch (PDOException $e) {
            $error = $e->getMessage();
        }
        $duree_ms = round((microtime(true) - $t_start) * 1000);
        log_sql($query, [], (int)$duree_ms);

    } else {
        // ✅ SÉCURISÉ : paramètre lié PDO — SLEEP() traité comme texte, pas exécuté
        $t_start = microtime(true);
        $stmt    = $pdo->prepare("SELECT * FROM commandes WHERE numero_suivi = ?");
        try {
            $stmt->execute([$numero]);
            $commande = $stmt->fetch();
        } catch (PDOException $e) {
            $error = "Erreur serveur — veuillez réessayer.";
        }
        $duree_ms = round((microtime(true) - $t_start) * 1000);
        log_sql("SELECT * FROM commandes WHERE numero_suivi = ?", [$numero], (int)$duree_ms);
    }
}
?>

<?php if ($error): ?>
<div class="alert alert-danger" style="margin-top:1rem;">
    <strong>Erreur SQL :</strong> <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<?php if ($numero !== '' && $duree_ms !== null): ?>
<div class="info-encart" style="margin-top:1rem;">
    ⏱️ Temps d'exécution de la requête : <strong><?= $duree_ms ?>ms</strong>
    <?php if ($duree_ms >= 4000): ?>
        — <span style="color:#C62828;font-weight:bold;">⚠️ Délai suspect ! SLEEP(N) détecté.</span>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($numero !== '' && !$commande && !$error): ?>
<div class="alert alert-warning" style="margin-top:1rem;">
    Aucune commande trouvée avec le numéro : <strong><?= htmlspecialchars($numero) ?></strong>
</div>
<?php endif; ?>

<?php if ($commande): ?>
<div class="suivi-result" style="margin-top:1rem;">
    <h2>Commande #<?= htmlspecialchars($commande['numero_suivi'] ?? '') ?></h2>

    <?php
    $statut = $commande['statut'] ?? '';
    $badge_map = [
        'livree'     => 'badge-success',
        'expediee'   => 'badge-info',
        'en_attente' => 'badge-warning',
        'annulee'    => 'badge-danger',
    ];
    $label_map = [
        'livree'     => '✅ Livrée',
        'expediee'   => '🚚 Expédiée',
        'en_attente' => '⏳ En attente',
        'annulee'    => '❌ Annulée',
    ];
    $badge_class  = isset($badge_map[$statut]) ? $badge_map[$statut] : 'badge-info';
    $statut_label = isset($label_map[$statut]) ? $label_map[$statut] : htmlspecialchars($statut);
    ?>

    <p>
        Statut :
        <span class="badge <?= $badge_class ?>"><?= $statut_label ?></span>
    </p>
    <p>Date de commande : <?= htmlspecialchars($commande['date_commande'] ?? '') ?></p>
    <p>Total : <strong><?= is_numeric($commande['total'] ?? null)
        ? number_format($commande['total'], 0, ',', ' ') . ' FCFA'
        : htmlspecialchars($commande['total'] ?? '') ?></strong>
    </p>
    <p>Adresse : <?= htmlspecialchars($commande['adresse_livraison'] ?? '') ?></p>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
