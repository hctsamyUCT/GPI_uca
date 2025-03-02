<?php
require_once '../../includes/functions.php';
require_once '../../includes/views.php';

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

afficherHeader('Vue Globale du Matériel', 2);

try {
    $pdo = connectDB();
    
    // Statistiques périphériques
    $statsPeripheriques = getStatsPeripheriques($pdo);
    
    // Statistiques unités centrales
    $statsUnites = getStatsUnites($pdo);

?>
<div class="container mt-4">

    <!-- Section Périphériques -->
    <h3 class="mb-3">Statistiques des Périphériques</h3>
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th>Type</th>
                    <th>Opérationnels</th>
                    <th>En panne</th>
                    <th>Assignés</th>
                    <th>Non assignés</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
<?php
    foreach($statsPeripheriques as $stat) {
?>
                <tr>
                    <td><?php echo htmlspecialchars($stat['type']); ?></td>
                    <td><?php echo $stat['operationnel']; ?></td>
                    <td><?php echo $stat['panne']; ?></td>
                    <td><?php echo $stat['affecte']; ?></td>
                    <td><?php echo $stat['non_affecte']; ?></td>
                    <td><?php echo $stat['total']; ?></td>
                </tr>
<?php
    }
?>
            </tbody>
        </table>
    </div>

    <!-- Section Unités Centrales -->
    <h3 class="mb-3 mt-5">Statistiques des Unités Centrales</h3>
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th>Type</th>
                    <th>Opérationnels</th>
                    <th>En panne</th>
                    <th>Assignées</th>
                    <th>Non assignées</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Unités Centrales</td>
                    <td><?php echo $statsUnites['operationnel']; ?></td>
                    <td><?php echo $statsUnites['panne']; ?></td>
                    <td><?php echo $statsUnites['affecte']; ?></td>
                    <td><?php echo $statsUnites['non_affecte']; ?></td>
                    <td><?php echo $statsUnites['total']; ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Section Postes -->
    <h3 class="mb-3 mt-5">Statistiques des Postes</h3>
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th>Type</th>
                    <th>Opérationnels</th>
                    <th>En panne</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
<?php
    $stmtPostes = $pdo->query("
        SELECT
            SUM(etat = 'opérationnel') AS operationnel,
            SUM(etat = 'en_panne') AS en_panne,
            COUNT(*) AS total
        FROM poste
    ");
    $statsPostes = $stmtPostes->fetch(PDO::FETCH_ASSOC);
    if ($statsPostes) {
?>
                <tr>
                    <td>Postes</td>
                    <td><?php echo $statsPostes['operationnel']; ?></td>
                    <td><?php echo $statsPostes['en_panne']; ?></td>
                    <td><?php echo $statsPostes['total']; ?></td>
                </tr>
<?php
    } else {
?>
                <tr><td colspan="4">Erreur lors de la récupération des statistiques des postes.</td></tr>
<?php
    }
?>
            </tbody>
        </table>
    </div>
</div>

<?php
} catch(PDOException $e) {
?>
    <div class="alert alert-danger">Erreur : <?php echo htmlspecialchars($e->getMessage()); ?></div>
<?php
}

afficherFooter();
?>
