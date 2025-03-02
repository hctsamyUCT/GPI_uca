<?php
require_once '../../includes/functions.php';
require_once '../../includes/filters.php';

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
        <a href="../archives/archives_peripherique.php" class="btn btn-secondary"><i class="fas fa-trash-restore mr-2"></i>Voir archives</a>
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

afficherFooter();
?>
