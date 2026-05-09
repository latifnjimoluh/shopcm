<?php
// ============================================================
// ShopCM — produits.php
// Catalogue avec recherche/filtres/tri
// VULNÉRABILITÉS #1, #2, #3, #4
// ============================================================

$page_title = 'Produits — ShopCM';
require_once '../includes/header.php';

$pdo     = get_pdo();
$search  = $_GET['search'] ?? '';
$cat     = $_GET['cat']    ?? '';
$min     = $_GET['min']    ?? '0';
$max     = $_GET['max']    ?? '9999999';
$order   = $_GET['order']  ?? 'nom';

// Chargement des catégories pour le select — toujours sécurisé
$cats_stmt = $pdo->prepare("SELECT * FROM categories ORDER BY nom");
$cats_stmt->execute();
$categories = $cats_stmt->fetchAll();

$produits = [];
$error    = null;
?>

<!-- ===== Formulaire de filtres ===== -->
<h1>Catalogue Produits</h1>

<form class="filters-form" action="/shopcm/pages/produits.php" method="get">
    <div class="form-group">
        <label for="search">Recherche</label>
        <input type="text" id="search" name="search"
               value="<?= htmlspecialchars($search) ?>"
               placeholder="Nom ou description...">
    </div>
    <div class="form-group">
        <label for="cat">Catégorie</label>
        <select id="cat" name="cat">
            <option value="">Toutes</option>
            <?php foreach ($categories as $c): ?>
            <option value="<?= (int)$c['id'] ?>"
                    <?= ($cat == $c['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['nom']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="min">Prix min (FCFA)</label>
        <input type="number" id="min" name="min"
               value="<?= htmlspecialchars($min) ?>" min="0">
    </div>
    <div class="form-group">
        <label for="max">Prix max (FCFA)</label>
        <input type="number" id="max" name="max"
               value="<?= htmlspecialchars($max) ?>" max="9999999">
    </div>
    <div class="form-group">
        <label for="order">Trier par</label>
        <select id="order" name="order">
            <option value="nom"   <?= ($order === 'nom')   ? 'selected' : '' ?>>Nom</option>
            <option value="prix"  <?= ($order === 'prix')  ? 'selected' : '' ?>>Prix</option>
            <option value="stock" <?= ($order === 'stock') ? 'selected' : '' ?>>Stock</option>
        </select>
    </div>
    <div class="form-group">
        <label>&nbsp;</label>
        <button type="submit" class="btn-primary">Filtrer</button>
    </div>
</form>

<?php
// ============================================================
// Logique de requêtage — selon le paramètre actif
// ============================================================

// ── VULNÉRABILITÉ #4 — ORDER BY injection via ?order= ──────
// On doit d'abord déterminer la clause ORDER BY car elle intervient
// dans toutes les requêtes ci-dessous.

if (mode_vulnerable()) {
    // ⚠️ VULNÉRABLE : $order injecté directement sans whitelist
    // Payload : (SELECT SLEEP(3))
    //           prix DESC,(SELECT password FROM admins LIMIT 1)
    $order_clause = $order;
} else {
    // ✅ SÉCURISÉ : whitelist stricte — ORDER BY ne supporte pas les paramètres liés PDO
    $whitelist    = ['nom', 'prix', 'stock'];
    $order_clause = in_array($order, $whitelist) ? $order : 'nom';
}

// ── Choix de la requête selon les paramètres fournis ────────

if ($search !== '') {
    // ── VULNÉRABILITÉ #1 — UNION SELECT via ?search= ────────
    // Payload : %' UNION SELECT id,email,password,nom,prenom,telephone,adresse,date_inscription FROM users-- -

    if (mode_vulnerable()) {
        // ⚠️VULNÉRABLE : concaténation directe de $search dans LIKE
        $query = "SELECT * FROM produits WHERE nom LIKE '%$search%' OR description LIKE '%$search%'";
        try {
            $result = $pdo->query($query);
            $produits = $result->fetchAll();
        } catch (PDOException $e) {
            $error = $e->getMessage();
        }
        log_sql($query);

    } else {
        // ✅ SÉCURISÉ : paramètres liés PDO — les % sont hors de la chaîne SQL
        $stmt = $pdo->prepare(
            "SELECT * FROM produits WHERE nom LIKE ? OR description LIKE ?"
        );
        try {
            $stmt->execute(["%$search%", "%$search%"]);
            $produits = $stmt->fetchAll();
        } catch (PDOException $e) {
            $error = "Erreur serveur — veuillez réessayer.";
        }
        log_sql("SELECT * FROM produits WHERE nom LIKE ? OR description LIKE ?",
                ["%$search%", "%$search%"]);
    }

} elseif ($cat !== '') {
    // ── VULNÉRABILITÉ #2 — information_schema via ?cat= ─────
    // Payload : ' UNION SELECT 1,table_name,table_schema,4,5,6,7,8 FROM information_schema.tables WHERE table_schema='shopcm_db'-- -

    if (mode_vulnerable()) {
        // ⚠️ VULNÉRABLE : $cat concaténé directement (injection numérique/string)
        $query = "SELECT * FROM produits WHERE categorie_id = '$cat'";
        try {
            $result = $pdo->query($query);
            $produits = $result->fetchAll();
        } catch (PDOException $e) {
            $error = $e->getMessage();
        }
        log_sql($query);

    } else {
        // ✅ SÉCURISÉ : paramètre lié PDO
        $stmt = $pdo->prepare(
            "SELECT * FROM produits WHERE categorie_id = ? AND actif = 1"
        );
        try {
            $stmt->execute([$cat]);
            $produits = $stmt->fetchAll();
        } catch (PDOException $e) {
            $error = "Erreur serveur — veuillez réessayer.";
        }
        log_sql("SELECT * FROM produits WHERE categorie_id = ? AND actif = 1", [$cat]);
    }

} elseif ($min !== '0' || $max !== '9999999') {
    // ── VULNÉRABILITÉ #3 — Injection numérique via ?min= et ?max= ─
    // Payload sur ?min= : 0 UNION SELECT 1,cle,valeur,4,5,6,7,8 FROM secrets_internes-- -

    if (mode_vulnerable()) {
        // ⚠️ VULNÉRABLE : $min et $max concaténés directement — injections numériques
        $query = "SELECT * FROM produits WHERE prix >= $min AND prix <= $max AND actif=1";
        try {
            $result = $pdo->query($query);
            $produits = $result->fetchAll();
        } catch (PDOException $e) {
            $error = $e->getMessage();
        }
        log_sql($query);

    } else {
        // ✅ SÉCURISÉ : cast en float + paramètres liés PDO
        $stmt = $pdo->prepare(
            "SELECT * FROM produits WHERE prix >= ? AND prix <= ? AND actif = 1"
        );
        try {
            $stmt->execute([(float)$min, (float)$max]);
            $produits = $stmt->fetchAll();
        } catch (PDOException $e) {
            $error = "Erreur serveur — veuillez réessayer.";
        }
        log_sql("SELECT * FROM produits WHERE prix >= ? AND prix <= ? AND actif = 1",
                [(float)$min, (float)$max]);
    }

} else {
    // ── Requête générale avec ORDER BY (Vulnérabilité #4) ──
    if (mode_vulnerable()) {
        // ⚠️ VULNÉRABLE : $order injecté dans ORDER BY sans whitelist
        // Payload : (SELECT SLEEP(3))
        $query = "SELECT * FROM produits WHERE actif=1 ORDER BY $order_clause";
        try {
            $result = $pdo->query($query);
            $produits = $result->fetchAll();
        } catch (PDOException $e) {
            $error = $e->getMessage();
        }
        log_sql($query);

    } else {
        // ✅ SÉCURISÉ : whitelist appliquée — ORDER BY ne supporte pas PDO paramètres liés
        // La protection est la whitelist (validation d'entrée), pas les paramètres liés
        $query = "SELECT * FROM produits WHERE actif=1 ORDER BY $order_clause";
        try {
            $result = $pdo->query($query);
            $produits = $result->fetchAll();
        } catch (PDOException $e) {
            $error = "Erreur serveur — veuillez réessayer.";
        }
        log_sql($query, [
            'note' => 'ORDER BY ne supporte pas PDO paramètres liés. Protection = WHITELIST.'
        ]);
    }
}
?>

<!-- ===== Affichage des résultats ===== -->
<?php if ($error): ?>
    <div class="alert alert-danger">
        <strong>Erreur SQL :</strong> <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<?php if (!empty($produits)): ?>
<p style="margin-bottom:1rem;color:#757575;"><?= count($produits) ?> produit(s) trouvé(s)</p>
<div class="products-grid">
    <?php foreach ($produits as $p): ?>
    <a href="/shopcm/pages/produit.php?id=<?= htmlspecialchars($p['id'] ?? '') ?>"
       class="product-card">
        <img src="/shopcm/assets/images/<?= htmlspecialchars($p['image'] ?? 'placeholder.jpg') ?>"
             alt="<?= htmlspecialchars($p['nom'] ?? '') ?>"
             onerror="this.src='/shopcm/assets/images/placeholder.jpg'">
        <div class="card-body">
            <h3><?= htmlspecialchars($p['nom'] ?? '') ?></h3>
            <p style="font-size:0.85rem;color:#757575;margin:0.3rem 0;">
                <?= htmlspecialchars(mb_substr($p['description'] ?? '', 0, 80)) ?>...
            </p>
            <p class="price">
                <?= is_numeric($p['prix'] ?? null)
                    ? number_format($p['prix'], 0, ',', ' ') . ' FCFA'
                    : htmlspecialchars($p['prix'] ?? '') ?>
            </p>
        </div>
        <div class="card-footer">
            <span>Stock : <?= htmlspecialchars($p['stock'] ?? '') ?></span>
            <button class="btn-add-cart" data-id="<?= htmlspecialchars($p['id'] ?? '') ?>"
                    onclick="event.preventDefault(); event.stopPropagation();">
                🛒 Ajouter
            </button>
        </div>
    </a>
    <?php endforeach; ?>
</div>

<?php else: ?>
    <?php if (!$error): ?>
    <p style="text-align:center;color:#757575;padding:3rem;">
        Aucun produit trouvé.
    </p>
    <?php endif; ?>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
