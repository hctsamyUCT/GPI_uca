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

// Vérification des droits d'accès (admin uniquement)
if ($_SESSION['user_role'] != 1) {
    header('Location: ../user/create_ticket.php?erreur=Accès non autorisé');
    exit();
}

$pdo = connectDB();

// Suppression d'un utilisateur
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id_user = ?");
    $stmt->execute([$_GET['delete']]);
    header('Location: gestion_users.php?succes=1');
    exit();
}

// Récupération de tous les utilisateurs
$stmt = $pdo->query("
    SELECT u.*, r.role_name 
    FROM users u 
    JOIN roles r ON u.id_role = r.id_role 
    ORDER BY u.nom, u.prenom
");
$users = $stmt->fetchAll();

// Affichage de la page
afficherHeader('Gestion des utilisateurs', 2);
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Gestion des utilisateurs</h2>
        <a href="ajouter_user.php" class="btn btn-primary">Ajouter un utilisateur</a>
    </div>

    <?php if (isset($_GET['succes'])): ?>
        <div class="alert alert-success">Opération réussie</div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Date de création</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['nom']) ?></td>
                        <td><?= htmlspecialchars($user['prenom']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= htmlspecialchars($user['role_name']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($user['date_created'])) ?></td>
                        <td>
                            <a href="modifier_user.php?id=<?= $user['id_user'] ?>" class="btn btn-sm btn-warning">Modifier</a>
                            <a href="gestion_users.php?delete=<?= $user['id_user'] ?>" 
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')">Supprimer</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
afficherFooter();
?> 