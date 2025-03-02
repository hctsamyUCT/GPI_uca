<?php
require_once '../../includes/functions.php';

afficherHeader('Archives des unités centrales', 2);

try {
    $pdo = connectDB();
    
    // Traitement de la confirmation de restauration
    if (isset($_POST['restaurer']) && isset($_POST['id_archive'])) {
        header('Location: ../../includes/gestion.php?action=restaurer_unite&id=' . $_POST['id_archive']);
        exit();
    }
    
    $stmt = $pdo->query("
        SELECT * 
        FROM archives_unite 
        ORDER BY date_archive DESC
    ");
    $archives = $stmt->fetchAll();
?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Archives des unités centrales supprimées</h1>
    </div>

    <?php if (empty($archives)): ?>
        <div class="alert alert-info">Aucune unité archivée.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead class="thead-dark">
                    <tr>
                        <th>N° Inventaire</th>
                        <th>RAM</th>
                        <th>Processeur</th>
                        <th>Disque 1</th>
                        <th>Disque 2</th>
                        <th>État</th>
                        <th>Date archivage</th>
                        <th>Affectation</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($archives as $archive): 
                        $disque2 = $archive['disque_dur_2_type'] ? 
                            htmlspecialchars($archive['disque_dur_2_type']).' - '.htmlspecialchars($archive['disque_dur_2_capacite']) : 
                            'N/A';
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($archive['numero_inventaire']) ?></td>
                            <td><?= htmlspecialchars($archive['ram']) ?></td>
                            <td><?= htmlspecialchars(str_replace('_', ' ', $archive['processeur'])) ?></td>
                            <td><?= htmlspecialchars($archive['disque_dur_1_type'] . ' - ' . $archive['disque_dur_1_capacite']) ?></td>
                            <td><?= $disque2 ?></td>
                            <td>
                                <span class="badge badge-<?= $archive['etat'] === 'Operationnel' ? 'success' : 'danger' ?>">
                                    <?= htmlspecialchars($archive['etat']) ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($archive['date_archive'])) ?></td>
                            <td>
                                <span class="badge badge-<?= $archive['est_affecte'] ? 'info' : 'secondary' ?>">
                                    <?= $archive['est_affecte'] ? 'Oui' : 'Non' ?>
                                </span>
                            </td>
                            <td>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="id_archive" value="<?= $archive['id_archive'] ?>">
                                    <button type="submit" name="restaurer" class="btn btn-success btn-sm restore-btn">
                                        <i class="fas fa-undo"></i> Restaurer
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

<?php
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Erreur : ' . htmlspecialchars($e->getMessage()) . '</div>';
}

afficherFooter();
?>