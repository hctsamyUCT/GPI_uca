<?php
require_once '../../includes/functions.php';
require_once '../../includes/filters.php';

// Affichage de l'en-tête
afficherHeader('Gestion des Unités', 2);

// Connexion à la base de données
try {
    $pdo = connectDB();
    
    // Récupération des filtres
    $filters = [
        'ram' => $_GET['ram'] ?? '',
        'etat' => $_GET['etat'] ?? '',
        'id_salle' => $_GET['id_salle'] ?? '',
        'processeur' => $_GET['processeur'] ?? '',
        'disque_dur_1_type' => $_GET['disque_dur_1_type'] ?? ''
    ];

    $search = $_GET['search'] ?? '';

    // Affichage du formulaire de filtrage
    echo getFilterForm('unite', $filters);

    $searchColumns = [
        'numero_inventaire' => 'N° Inventaire'
    ];

    echo getSearchForm($search, $searchColumns);

    // Construction de la requête avec filtres
    $baseQuery = "SELECT u.*, p.nom_poste, p.id_salle, s.nom_salle,
                  CASE WHEN pu.id_poste IS NOT NULL THEN 1 ELSE 0 END as est_affecte
                  FROM unite u
                  LEFT JOIN poste_unite pu ON u.id_unite = pu.id_unite
                  LEFT JOIN poste p ON pu.id_poste = p.id_poste
                  LEFT JOIN salles s ON p.id_salle = s.id_salle
                  WHERE u.supprime_le IS NULL";

    $filterData = buildFilterQuery('unite', $filters);
    $finalQuery = $baseQuery . ($filterData['clause'] ? ' AND' . substr($filterData['clause'], 6) : '');
    
    if (!empty($search)) {
        $selectedColumns = $_GET['search_columns'] ?? [];
        $whereClauses = [];
        $searchParams = [];
        
        if (empty($selectedColumns) || in_array('all', $selectedColumns)) {
            $whereClauses = [
                "u.numero_inventaire LIKE ?",
                "u.processeur LIKE ?"
            ];
            $searchParams = array_fill(0, 2, "%$search%");
        } else {
            foreach ($selectedColumns as $column) {
                switch($column) {
                    case 'numero_inventaire':
                        $whereClauses[] = "u.numero_inventaire LIKE ?";
                        break;
                    case 'processeur':
                        $whereClauses[] = "u.processeur LIKE ?";
                        break;
                }
                $searchParams[] = "%$search%";
            }
        }
        
        if (!empty($whereClauses)) {
            $finalQuery .= " AND (" . implode(" OR ", $whereClauses) . ")";
            $params = array_merge($filterData['params'], $searchParams);
        }
    } else {
        $params = $filterData['params'];
    }
    
    $stmt = $pdo->prepare($finalQuery);
    $stmt->execute($params);
    $unites = $stmt->fetchAll();

    // Titre et bouton d'ajout
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Liste des unités</h1>
    <div>
        <a href="../../includes/gestion.php?action=ajouter_unite" class="btn btn-primary mr-2"><i class="fas fa-plus mr-2"></i>Ajouter une unité</a>
        <a href="../archives/archives_unite.php" class="btn btn-secondary"><i class="fas fa-trash-restore mr-2"></i>Voir archives</a>
    </div>
</div>

<?php
    // Affichage des messages
    afficherMessage();

    if (count($unites) > 0) {
        // Affichage du tableau des unités
?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="thead-dark">
                    <tr>
                        <th>N° Inventaire</th>
                        <th>RAM</th>
                        <th>Processeur</th>
                        <th>Disque 1</th>
                        <th>Disque 2</th>
                        <th>État</th>
                        <th>Date d'achat</th>
                        <th>Affecté</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
<?php
        foreach ($unites as $unite) {
?>
                    <tr>
                        <td><?php echo htmlspecialchars($unite['numero_inventaire']); ?></td>
                        <td><?php echo htmlspecialchars($unite['ram']); ?></td>
                        <td><?php echo htmlspecialchars(str_replace('_', ' ', $unite['processeur'])); ?></td>
                        <td><?php echo htmlspecialchars($unite['disque_dur_1_type'] . ' - ' . $unite['disque_dur_1_capacite']); ?></td>
                        <td><?php echo ($unite['disque_dur_2_type'] ? htmlspecialchars($unite['disque_dur_2_type'] . ' - ' . $unite['disque_dur_2_capacite']) : 'N/A'); ?></td>
                        <td><span class="badge badge-<?php echo ($unite['etat'] === 'Operationnel' ? 'success' : 'danger'); ?>"><?php echo htmlspecialchars($unite['etat']); ?></span></td>
                        <td><?php echo ($unite['date_achat'] ? date('d/m/Y', strtotime($unite['date_achat'])) : 'N/A'); ?></td>
                        <td><span class="badge badge-<?php echo ($unite['est_affecte'] ? 'info' : 'secondary'); ?>"><?php echo ($unite['est_affecte'] ? 'Oui' : 'Non'); ?></span></td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="../../includes/gestion.php?action=modifier_unite&id=<?php echo $unite['id_unite']; ?>"
                                    class="btn btn-info btn-sm" title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="../../includes/gestion.php?action=supprimer_unite&id=<?php echo $unite['id_unite']; ?>"
                                    onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette unité ?')"
                                    class="btn btn-danger btn-sm" title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </a>
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
        <div class="alert alert-info">Aucune unité trouvée.</div>
<?php
    }

} catch (PDOException $e) {
?>
    <div class="alert alert-danger">Une erreur est survenue : <?php echo htmlspecialchars($e->getMessage()); ?></div>
<?php
}

// Affichage du pied de page
afficherFooter();
?>
