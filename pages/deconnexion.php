<?php
// ============================================================
// ShopCM — deconnexion.php
// Déconnexion utilisateur client
// ============================================================

session_start();
session_destroy();
header('Location: /shopcm/index.php');
exit;
