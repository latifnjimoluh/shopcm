<?php
// ============================================================
// ShopCM — index.php
// Page d'accueil : bannière + 6 derniers produits actifs
// Aucune vulnérabilité sur cette page (vitrine)
// ============================================================

$page_title = 'Accueil — ShopCM';
require_once 'includes/header.php';

$pdo = get_pdo();

// Requête sécurisée uniquement — pas de démonstration de vuln ici
$stmt = $pdo->prepare(
    "SELECT * FROM produits WHERE actif = 1 ORDER BY id DESC LIMIT 6"
);
$stmt->execute();
$produits = $stmt->fetchAll();
?>

<!-- ===== Bannière héro ===== -->
<div class="hero-banner">
    <h1>🛒 Bienvenue sur ShopCM</h1>
    <p>La boutique en ligne du Cameroun — Qualité, confiance et livraison rapide</p>
</div>

<!-- ===== Produits vedettes ===== -->
<h2>🔥 Nouveautés</h2>
<div class="products-grid">
    <?php foreach ($produits as $p): ?>
    <a href="/shopcm/pages/produit.php?id=<?= (int)$p['id'] ?>" class="product-card">
        <img src="/shopcm/assets/images/<?= htmlspecialchars($p['image']) ?>"
             alt="<?= htmlspecialchars($p['nom']) ?>"
             onerror="this.src='/shopcm/assets/images/placeholder.jpg'">
        <div class="card-body">
            <h3><?= htmlspecialchars($p['nom']) ?></h3>
            <p class="price"><?= number_format($p['prix'], 0, ',', ' ') ?> FCFA</p>
        </div>
        <div class="card-footer">
            <span>Stock : <?= (int)$p['stock'] ?></span>
            <button class="btn-add-cart" data-id="<?= (int)$p['id'] ?>"
                    onclick="event.preventDefault(); event.stopPropagation();">
                🛒 Ajouter
            </button>
        </div>
    </a>
    <?php endforeach; ?>
</div>

<p style="margin-top:1.5rem; text-align:center;">
    <a href="/shopcm/pages/produits.php" class="btn-primary">Voir tous les produits →</a>
</p>

<?php require_once 'includes/footer.php'; ?>
