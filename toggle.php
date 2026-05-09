<?php
// ============================================================
// ShopCM — toggle.php
// Bascule le mode vulnérable / sécurisé
// Endpoint POST uniquement — CSRF protégé
// ============================================================

session_start();

// Accepter uniquement les requêtes POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Vérification CSRF
if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    die('Token CSRF invalide.');
}

// Inversion du mode
$_SESSION['mode'] = ($_SESSION['mode'] === 'vulnerable') ? 'secure' : 'vulnerable';

// Effacer le log SQL (fresh start dans le nouveau mode)
$_SESSION['last_sql'] = [];

// Redirection vers la page précédente
$ref = $_SERVER['HTTP_REFERER'] ?? 'index.php';
header('Location: ' . $ref);
exit;
