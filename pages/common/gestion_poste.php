<?php
require_once '../../includes/functions.php';
require_once '../../includes/filters.php';

// Configuration de la session avant d'afficher l'en-tête
configureSession();

// Vérifier si l'utilisateur est connecté
if (!estConnecte()) {
    // Redirection explicite avec un message d'erreur
    header('Location: ../../index.php?erreur=Vous devez être connecté');
    exit();
}

// Vérifier si l'utilisateur a le rôle approprié (admin ou technicien)
if ($_SESSION['user_role'] != 1 && $_SESSION['user_role'] != 2) {
    // Redirection vers la page de création de ticket pour les utilisateurs standard
    header('Location: ../user/create_ticket.php?erreur=Accès non autorisé');
    exit();
}

afficherHeader('Gestion des Postes', 2);

try {
    $pdo = connectDB();
    
    // Vérification de la structure de la table poste
    $checkColumnStmt = $pdo->query("SHOW COLUMNS FROM poste LIKE 'supprime_le'");
    $hasSupprimeLe = $checkColumnStmt->rowCount() > 0;
    
    // Récupération des filtres
    $filters = [
        'etat' => $_GET['etat'] ?? '',
        'id_salle' => $_GET['id_salle'] ?? ''
    ];
    
    // Enregistrement des filtres dans la session pour l'exportation
    $_SESSION['filters']['poste'] = $filters;

    $search = $_GET['search'] ?? '';

    $searchColumns = [
        'nom_poste' => 'Nom du poste'
    ];

    echo getFilterForm('poste', $filters);
    echo getSearchForm($search, $searchColumns);

    if (!empty($search)) {
        $selectedColumns = $_GET['search_columns'] ?? [];
        $whereClauses = [];
        $searchParams = [];
        
        if (empty($selectedColumns) || in_array('all', $selectedColumns)) {
            $whereClauses = ["p.nom_poste LIKE ?"];
            $searchParams = ["%$search%"];
        } else {
            foreach ($selectedColumns as $column) {
                if ($column === 'nom_poste') {
                    $whereClauses[] = "p.nom_poste LIKE ?";
                    $searchParams[] = "%$search%";
                }
            }
        }
        
        if (!empty($whereClauses)) {
            $finalQuery .= " AND (" . implode(" OR ", $whereClauses) . ")";
            $params = array_merge($filterData['params'], $searchParams);
        }
    }

    // Construction de la requête avec filtres
    $baseQuery = "SELECT p.*, s.nom_salle, 
                  GROUP_CONCAT(periph.type SEPARATOR ', ') AS peripheriques,
                  u.numero_inventaire AS unite
                  FROM poste p
                  JOIN salles s ON p.id_salle = s.id_salle
                  LEFT JOIN poste_peripherique pp ON p.id_poste = pp.id_poste
                  LEFT JOIN peripherique periph ON pp.id_peripherique = periph.id_peripherique";
    
    if ($hasSupprimeLe) {
        $baseQuery .= " AND periph.supprime_le IS NULL";
    }
    
    $baseQuery .= " LEFT JOIN poste_unite pu ON p.id_poste = pu.id_poste
                    LEFT JOIN unite u ON pu.id_unite = u.id_unite";
    
    if ($hasSupprimeLe) {
        $baseQuery .= " AND u.supprime_le IS NULL
                        WHERE p.supprime_le IS NULL";
    } else {
        $baseQuery .= " WHERE 1=1";
    }

    $filterData = buildFilterQuery('poste', $filters);
    $finalQuery = $baseQuery . $filterData['clause'] . " GROUP BY p.id_poste ORDER BY p.nom_poste";
    
    $stmt = $pdo->prepare($finalQuery);
    $stmt->execute($filterData['params']);
    $postes = $stmt->fetchAll();

    // Statistiques des postes
    $stmt = $pdo->query("
        SELECT 
            SUM(etat = 'opérationnel') AS operationnel,
            SUM(etat = 'en_panne') AS en_panne,
            COUNT(*) AS total
        FROM poste
    ");
    $stats = $stmt->fetch();

?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Liste des postes</h1>
    <div>
        <a href="../../includes/gestion.php?action=ajouter_poste" class="btn btn-primary mr-2">
            <i class="fas fa-plus mr-2"></i>Ajouter un poste
        </a>
        <div class="dropdown d-inline-block">
            <button class="btn btn-success dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-download mr-2"></i>Exporter
            </button>
            <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                <li><a class="dropdown-item" href="../../includes/export.php?source=poste&type=csv"><i class="fas fa-file-csv mr-2"></i>CSV</a></li>
                <li><a class="dropdown-item" href="../../includes/export.php?source=poste&type=pdf_paysage"><i class="fas fa-file-pdf mr-2"></i>Page courante en PDF paysage</a></li>
                <li><a class="dropdown-item" href="../../includes/export.php?source=poste&type=pdf_portrait"><i class="fas fa-file-pdf mr-2"></i>Page courante en PDF portrait</a></li>
            </ul>
        </div>
    </div>
</div>

<?php
    afficherMessage();

    if (count($postes) > 0) {
?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="thead-dark">
                    <tr>
                        <th>Nom</th>
                        <th>Salle</th>
                        <th>Périphériques</th>
                        <th>Unité centrale</th>
                        <th>État</th>
                        <th>Date création</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
<?php
        foreach ($postes as $poste) {
?>
                    <tr>
                        <td><?php echo htmlspecialchars($poste['nom_poste']); ?></td>
                        <td><?php echo htmlspecialchars($poste['nom_salle']); ?></td>
                        <td><?php echo ($poste['peripheriques'] ? htmlspecialchars($poste['peripheriques']) : 'Aucun'); ?></td>
                        <td><?php echo ($poste['unite'] ? htmlspecialchars($poste['unite']) : 'Non affectée'); ?></td>
                        <td>
                            <?php if ($poste['etat'] === 'opérationnel'): ?>
                                <span class="badge badge-success">Opérationnel</span>
                            <?php else: ?>
                                <span class="badge badge-danger">En panne</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($poste['date_creation'])); ?></td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="../../includes/gestion.php?action=modifier_poste&id=<?php echo $poste['id_poste']; ?>" class="btn btn-info btn-sm"><i class="fas fa-edit"></i></a>
                                <a href="../../includes/gestion.php?action=supprimer_poste&id=<?php echo $poste['id_poste']; ?>" onclick="return confirm('Êtes-vous sûr ?')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></a>
                            </div>
                        </td>
                    </tr>
<?php
        }
?>
                </tbody>
            </table>
        </div>
<?php
    } else {
?>
        <div class="alert alert-info">Aucun poste trouvé</div>
<?php
    }
} catch (PDOException $e) {
?>
    <div class="alert alert-danger">Erreur : <?php echo htmlspecialchars($e->getMessage()); ?></div>
<?php
}

// Ajout d'un script pour initialiser les dropdowns de Bootstrap 5
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialisation des dropdowns Bootstrap 5
    var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    var dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
        return new bootstrap.Dropdown(dropdownToggleEl);
    });
});
</script>
<?php

afficherFooter();
?>
