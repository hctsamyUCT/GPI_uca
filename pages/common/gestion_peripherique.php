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

afficherHeader('Gestion des Périphériques', 2);

try {
    $pdo = connectDB();
    
    // Récupération des filtres
    $filters = [
        'type' => $_GET['type'] ?? '',
        'etat' => $_GET['etat'] ?? '',
        'id_salle' => $_GET['id_salle'] ?? '',
        'id_marque' => $_GET['id_marque'] ?? ''
    ];
    
    // Enregistrement des filtres dans la session pour l'exportation
    $_SESSION['filters']['peripherique'] = $filters;

    $search = $_GET['search'] ?? '';

    $searchColumns = [
        'numero_inventaire' => 'N° Inventaire'
    ];

    echo getFilterForm('peripherique', $filters);
    echo getSearchForm($search, $searchColumns);

    // Construction de la requête avec filtres
    $baseQuery = "SELECT p.*, m.nom_marque, po.id_salle, s.nom_salle,
                  CASE WHEN pp.id_poste IS NOT NULL THEN 1 ELSE 0 END as est_affecte
                  FROM peripherique p
                  LEFT JOIN marque m ON p.id_marque = m.id_marque
                  LEFT JOIN poste_peripherique pp ON p.id_peripherique = pp.id_peripherique
                  LEFT JOIN poste po ON pp.id_poste = po.id_poste
                  LEFT JOIN salles s ON po.id_salle = s.id_salle
                  WHERE p.supprime_le IS NULL";

    $filterData = buildFilterQuery('peripherique', $filters);
    $finalQuery = $baseQuery . ($filterData['clause'] ? ' AND' . substr($filterData['clause'], 6) : '');
    
    if (!empty($search)) {
        $selectedColumns = $_GET['search_columns'] ?? [];
        $whereClauses = [];
        $searchParams = [];
        
        if (empty($selectedColumns) || in_array('all', $selectedColumns)) {
            $whereClauses = ["p.numero_inventaire LIKE ?"];
            $searchParams = ["%$search%"];
        } else {
            foreach ($selectedColumns as $column) {
                if ($column === 'numero_inventaire') {
                    $whereClauses[] = "p.numero_inventaire LIKE ?";
                    $searchParams[] = "%$search%";
                }
            }
        }
        
        if (!empty($whereClauses)) {
            $finalQuery .= " AND (" . implode(" OR ", $whereClauses) . ")";
            $params = array_merge($filterData['params'], $searchParams);
        } else {
            $params = $filterData['params'];
        }
    } else {
        $params = $filterData['params'];
    }
    
    $stmt = $pdo->prepare($finalQuery);
    $stmt->execute($params);
    $peripheriques = $stmt->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Liste des périphériques</h1>
    <div>
        <a href="../../includes/gestion.php?action=ajouter_peripherique" class="btn btn-primary mr-2"><i class="fas fa-plus mr-2"></i>Ajouter</a>
        <a href="../archives/archives_peripherique.php" class="btn btn-secondary mr-2"><i class="fas fa-trash-restore mr-2"></i>Voir archives</a>
        <div class="dropdown d-inline-block">
            <button class="btn btn-success dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-download mr-2"></i>Exporter
            </button>
            <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                <li><a class="dropdown-item" href="../../includes/export.php?source=peripherique&type=csv"><i class="fas fa-file-csv mr-2"></i>CSV</a></li>
                <li><a class="dropdown-item" href="../../includes/export.php?source=peripherique&type=xlsx"><i class="fas fa-file-excel mr-2"></i>XLSX</a></li>
                <li><a class="dropdown-item" href="../../includes/export.php?source=peripherique&type=pdf_paysage"><i class="fas fa-file-pdf mr-2"></i>Page courante en PDF paysage</a></li>
                <li><a class="dropdown-item" href="../../includes/export.php?source=peripherique&type=pdf_portrait"><i class="fas fa-file-pdf mr-2"></i>Page courante en PDF portrait</a></li>
            </ul>
        </div>
    </div>
</div>

<?php
    afficherMessage();

    if (count($peripheriques) > 0) {
?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="thead-dark">
                    <tr>
                        <th>Type</th>
                        <th>Marque</th>
                        <th>N° Inventaire</th>
                        <th>État</th>
                        <th>Affecté</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
<?php
        foreach ($peripheriques as $p) {
?>
                    <tr>
                        <td><?php echo htmlspecialchars($p['type']); ?></td>
                        <td><?php echo htmlspecialchars($p['nom_marque']); ?></td>
                        <td><?php echo htmlspecialchars($p['numero_inventaire']); ?></td>
                        <td><span class="badge badge-<?php echo ($p['etat'] === 'Operationnel' ? 'success' : 'danger'); ?>"><?php echo htmlspecialchars($p['etat']); ?></span></td>
                        <td><span class="badge badge-<?php echo ($p['est_affecte'] ? 'info' : 'secondary'); ?>"><?php echo ($p['est_affecte'] ? 'Oui' : 'Non'); ?></span></td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="../../includes/gestion.php?action=modifier_peripherique&id=<?php echo $p['id_peripherique']; ?>" class="btn btn-info btn-sm"><i class="fas fa-edit"></i></a>
                                <a href="../../includes/gestion.php?action=supprimer_peripherique&id=<?php echo $p['id_peripherique']; ?>" onclick="return confirm('Êtes-vous sûr ?')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></a>
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
        <div class="alert alert-info">Aucun périphérique trouvé</div>
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
