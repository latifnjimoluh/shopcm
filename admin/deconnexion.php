<?php
// ============================================================
// ShopCM — admin/deconnexion.php
// Déconnexion administrateur
// ============================================================

session_start();
session_destroy();
header('Location: login.php');
exit;
