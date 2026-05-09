<?php
// ============================================================
// ShopCM — contact.php
// Formulaire de contact statique — aucune BD
// ============================================================

$page_title = 'Contact — ShopCM';
require_once '../includes/header.php';

$succes = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Formulaire statique — on simule l'envoi pour le réalisme
    $succes = true;
}
?>

<h1>📧 Contactez-nous</h1>

<?php if ($succes): ?>
<div class="alert alert-success" data-autohide>
    Merci, votre message a été envoyé. Nous vous répondrons dans les 24h.
</div>
<?php endif; ?>

<div style="max-width:600px;">
    <form action="/shopcm/pages/contact.php" method="post">
        <div class="form-group">
            <label for="nom">Nom complet</label>
            <input type="text" id="nom" name="nom"
                   value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>"
                   placeholder="Votre nom">
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                   placeholder="votre@email.com">
        </div>

        <div class="form-group">
            <label for="message">Message</label>
            <textarea id="message" name="message" rows="6"
                      placeholder="Votre message..."><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn-primary">Envoyer le message</button>
    </form>

    <div class="info-encart" style="margin-top:2rem;">
        <strong>📍 ShopCM — Informations de contact</strong>
        <p>Adresse : Avenue Kennedy, Yaoundé, Cameroun</p>
        <p>Téléphone : +237 222 123 456</p>
        <p>Email : contact@shopcm.cm</p>
        <p>Horaires : Lun–Ven 8h–18h | Sam 9h–15h</p>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
