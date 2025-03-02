<?php
require_once '../../includes/functions.php';

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

afficherHeader('Archives des périphériques', 2);

try {
    $pdo = connectDB();
    
    // Après la connexion à la base de données
    if (isset($_GET['success'])) {
        echo '<div class="alert alert-success">Le périphérique a été restauré avec succès.</div>';
    }
    if (isset($_GET['error'])) {
        echo '<div class="alert alert-danger">Erreur lors de la restauration : ' . htmlspecialchars($_GET['error']) . '</div>';
    }

    // Modification du traitement de la restauration
    if (isset($_POST['restaurer']) && isset($_POST['id_archive'])) {
        $id_archive = intval($_POST['id_archive']); // Conversion en entier pour sécurité
        
        // Redirection avec les bons paramètres
        header("Location: ../../includes/gestion.php?action=restaurer_peripherique&id_archive=" . $id_archive);
        exit();
    }

    $stmt = $pdo->query("
        SELECT ap.*, m.nom_marque 
        FROM archives_peripherique ap
        LEFT JOIN marque m ON ap.id_marque = m.id_marque
        ORDER BY date_archive DESC
    ");
    $archives = $stmt->fetchAll();

    echo '<div class="d-flex justify-content-between align-items-center mb-4">';
    echo '<h1>Archives des périphériques supprimés</h1>';
    echo '</div>';

    if (empty($archives)) {
        echo '<div class="alert alert-info">Aucun périphérique archivé.</div>';
    } else {
        echo '<div class="table-responsive">';
        echo '<table class="table table-striped">';
        echo '<thead class="thead-dark">';
        echo '<tr>';
        echo '<th>Type</th>';
        echo '<th>Marque</th>';
        echo '<th>N° Inventaire</th>';
        echo '<th>État</th>';
        echo '<th>Date archivage</th>';
        echo '<th>Affectation</th>';
        echo '<th>Actions</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        foreach ($archives as $archive) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($archive['type']) . '</td>';
            echo '<td>' . htmlspecialchars($archive['nom_marque']) . '</td>';
            echo '<td>' . htmlspecialchars($archive['numero_inventaire']) . '</td>';
            echo '<td><span class="badge badge-' . ($archive['etat'] === 'Operationnel' ? 'success' : 'danger') . '">' . htmlspecialchars($archive['etat']) . '</span></td>';
            echo '<td>' . date('d/m/Y H:i', strtotime($archive['date_archive'])) . '</td>';
            echo '<td><span class="badge badge-' . ($archive['est_affecte'] ? 'info' : 'secondary') . '">' . ($archive['est_affecte'] ? 'Oui' : 'Non') . '</span></td>';
            
            echo '<td>';
            echo '<form method="post" class="d-inline">';
            echo '<input type="hidden" name="id_archive" value="' . $archive['id_archive'] . '">';
            echo '<button type="submit" name="restaurer" class="btn btn-success btn-sm restore-btn">';
            echo '<i class="fas fa-undo"></i> Restaurer</button>';
            echo '</form>';
            echo '</td>';
            
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Erreur : ' . htmlspecialchars($e->getMessage()) . '</div>';
}

afficherFooter();
?>
