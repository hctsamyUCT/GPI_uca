<?php
function getStatsPeripheriques($pdo) {
    $sql = "SELECT
        type,
        SUM(etat = 'Operationnel') as operationnel,
        SUM(etat = 'En panne') as panne,
        SUM(etat = 'Operationnel' OR etat = 'En panne') as total,
        SUM(est_affecte) as affecte,
        SUM(NOT est_affecte) as non_affecte
        FROM peripherique
        WHERE supprime_le IS NULL
        GROUP BY type";

    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getStatsUnites($pdo) {
    $sql = "SELECT
        'Unite' as type,
        SUM(etat = 'Operationnel') as operationnel,
        SUM(etat = 'En panne') as panne,
        SUM(etat = 'Operationnel' OR etat = 'En panne') as total,
        SUM(est_affecte) as affecte,
        SUM(NOT est_affecte) as non_affecte
        FROM unite
        WHERE supprime_le IS NULL";

    $stmt = $pdo->query($sql);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
