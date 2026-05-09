<?php
// ============================================================
// ShopCM — panier.php
// Panier stocké en cookie JSON
// VULNÉRABILITÉ #8 — Injection SQL via cookie panier
// ============================================================

$page_title = 'Panier — ShopCM';
require_once '../includes/header.php';

$pdo = get_pdo();

// Lecture du cookie panier
$panier_raw = $_COOKIE['panier'] ?? '[]';
$panier     = [];
try {
    $decoded = json_decode($panier_raw, true);
    if (is_array($decoded)) {
        $panier = $decoded;
    }
} catch (Exception $e) {
    $panier = [];
}

$items  = [];
$total  = 0;
$errors = [];
?>

<h1>🛒 Mon Panier</h1>

<div class="info-encart">
    <strong>🎓 Note pédagogique — Vecteur cookie (Vulnérabilité #8)</strong>
    Les données du panier proviennent du <strong>cookie client</strong> (<code>panier</code>).
    En mode vulnérable, elles peuvent contenir des injections SQL sans passer par un formulaire.<br>
    <strong>Payload :</strong> modifier le cookie via DevTools → Application → Cookies → panier :
    <code>[{"id":"1 UNION SELECT 1,cle,valeur,4,5,6,7,8 FROM secrets_internes-- -","qty":1}]</code>
</div>

<?php
// ============================================================
// Lecture de chaque item du panier
// ============================================================

foreach ($panier as $item) {
    if (!isset($item['id'])) continue;

    $produit = null;
    $err     = null;

    if (mode_vulnerable()) {
        // ⚠️ VULNÉRABLE : l'id vient du cookie — aucune validation, injection directe
        // Payload cookie : [{"id":"1 UNION SELECT 1,cle,valeur,4,5,6,7,8 FROM secrets_internes-- -","qty":1}]
        $pid   = $item['id'];
        $query = "SELECT * FROM produits WHERE id = $pid";
        try {
            $produit = $pdo->query($query)->fetch();
        } catch (PDOException $e) {
            $err = $e->getMessage();
        }
        log_sql($query);

    } else {
        // ✅ SÉCURISÉ : cast en entier + paramètre lié PDO + restriction actif=1
        $stmt = $pdo->prepare("SELECT * FROM produits WHERE id = ? AND actif = 1");
        try {
            $stmt->execute([(int)$item['id']]);
            $produit = $stmt->fetch();
        } catch (PDOException $e) {
            $err = "Erreur serveur — veuillez réessayer.";
        }
        log_sql("SELECT * FROM produits WHERE id = ? AND actif = 1", [(int)$item['id']]);
    }

    if ($err) {
        $errors[] = $err;
    }

    if ($produit) {
        $qty = max(1, (int)($item['qty'] ?? 1));
        $items[] = [
            'produit' => $produit,
            'qty'     => $qty,
        ];
        if (is_numeric($produit['prix'])) {
            $total += $produit['prix'] * $qty;
        }
    }
}
?>

<?php foreach ($errors as $err): ?>
<div class="alert alert-danger">
    <strong>Erreur SQL :</strong> <?= htmlspecialchars($err) ?>
</div>
<?php endforeach; ?>

<?php if (empty($panier)): ?>
    <p style="text-align:center;padding:3rem;color:#757575;">
        Votre panier est vide. <a href="/shopcm/pages/produits.php">Voir les produits →</a>
    </p>

<?php elseif (empty($items) && empty($errors)): ?>
    <p style="text-align:center;padding:2rem;color:#757575;">
        Aucun produit valide dans le panier.
    </p>

<?php else: ?>
<table class="panier-table">
    <thead>
        <tr>
            <th>Produit</th>
            <th>Prix unitaire</th>
            <th>Quantité</th>
            <th>Sous-total</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $item): ?>
        <tr>
            <td>
                <a href="/shopcm/pages/produit.php?id=<?= (int)($item['produit']['id'] ?? 0) ?>">
                    <?php if (mode_vulnerable()): ?>
                        <?= $item['produit']['nom'] ?>
                    <?php else: ?>
                        <?= htmlspecialchars($item['produit']['nom'] ?? '') ?>
                    <?php endif; ?>
                </a>
            </td>
            <td>
                <?= is_numeric($item['produit']['prix'] ?? null)
                    ? number_format($item['produit']['prix'], 0, ',', ' ') . ' FCFA'
                    : htmlspecialchars($item['produit']['prix'] ?? '') ?>
            </td>
            <td><?= (int)$item['qty'] ?></td>
            <td>
                <?= is_numeric($item['produit']['prix'] ?? null)
                    ? number_format($item['produit']['prix'] * $item['qty'], 0, ',', ' ') . ' FCFA'
                    : '—' ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="panier-total">
    Total : <?= number_format($total, 0, ',', ' ') ?> FCFA
</div>

<div style="display:flex;gap:1rem;flex-wrap:wrap;">
    <a href="/shopcm/pages/produits.php" class="btn-secondary">← Continuer les achats</a>
    <button class="btn-primary" onclick="alert('Fonctionnalité non implémentée dans ce TP.')">
        Passer la commande
    </button>
    <button class="btn-secondary" onclick="viderPanier()">🗑️ Vider le panier</button>
</div>

<script>
function viderPanier() {
    document.cookie = 'panier=[]; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/';
    location.reload();
}
</script>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
