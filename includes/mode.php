<?php
// ============================================================
// ShopCM — includes/mode.php
// Gestion du mode vulnérable / sécurisé
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Mode par défaut : vulnérable
if (!isset($_SESSION['mode'])) {
    $_SESSION['mode'] = 'vulnerable';
}

/**
 * Retourne true si le mode actuel est VULNÉRABLE.
 */
function mode_vulnerable(): bool
{
    return ($_SESSION['mode'] === 'vulnerable');
}

/**
 * Retourne le label lisible du mode actuel.
 */
function get_mode_label(): string
{
    return mode_vulnerable() ? 'VULNÉRABLE' : 'SÉCURISÉ';
}

/**
 * Retourne la classe CSS correspondant au mode actuel.
 */
function get_mode_class(): string
{
    return mode_vulnerable() ? 'mode-vuln' : 'mode-secure';
}
