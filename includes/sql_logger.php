<?php
// ============================================================
// ShopCM — includes/sql_logger.php
// Capture de la dernière requête SQL exécutée (pédagogique)
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Enregistre la dernière requête SQL dans la session.
 *
 * @param string   $query    La requête SQL (brute en mode vuln, avec ? en mode secure)
 * @param array    $params   Paramètres liés (vide en mode vulnérable)
 * @param int|null $duree_ms Temps d'exécution optionnel en millisecondes (pour time-based)
 */
function log_sql(string $query, array $params = [], $duree_ms = null): void
{
    $data = [
        'query'     => $query,
        'params'    => $params,
        'timestamp' => date('H:i:s'),
    ];
    if ($duree_ms !== null) {
        $data['duree_ms'] = (int)$duree_ms;
    }
    $_SESSION['last_sql'] = $data;
}

/**
 * Retourne la dernière requête SQL loggée.
 *
 * @return array  ['query', 'params', 'timestamp'] ou tableau vide
 */
function get_last_sql(): array
{
    return $_SESSION['last_sql'] ?? [];
}
