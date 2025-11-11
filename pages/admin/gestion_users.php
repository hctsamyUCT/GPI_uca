<?php
require_once '../../includes/functions.php';

// Vérifier si l'utilisateur est connecté et est administrateur
verifierRole(1); // 1 = ID du rôle Administrateur

$pdo = connectDB();

// *** Bloc d'archivage incorrect supprimé ici ***

// Traitement de la suppression si demandée
if (isset($_GET['delete']) && filter_var($_GET['delete'], FILTER_VALIDATE_INT)) {
    $id_to_delete = $_GET['delete']; // Renommé pour plus de clarté
    try {
        // 1. Vérifier si l'utilisateur essaie de se supprimer lui-même
        if (isset($_SESSION['user_id']) && $id_to_delete == $_SESSION['user_id']) {
             header('Location: gestion_users.php?erreur=self_delete');
             exit();
        }

        // 2. Vérifier le rôle avant de supprimer
        $stmt_check_role = $pdo->prepare("SELECT id_role FROM users WHERE id_user = ?");
        $stmt_check_role->execute([$id_to_delete]);
        $user_to_delete = $stmt_check_role->fetch();

        if ($user_to_delete) {
            $role_id = $user_to_delete['id_role'];
            // Empêcher uniquement la suppression des Administrateurs (ID 1)
            // Les techniciens (ID 2) peuvent être supprimés par un administrateur
            if ($role_id == 1) {
                header('Location: gestion_users.php?erreur=role_protected');
                exit();
            }

            // 3. Effectuer la suppression (DELETE) si le rôle est autorisé
            $stmt_delete = $pdo->prepare("DELETE FROM users WHERE id_user = ?");
            $stmt_delete->execute([$id_to_delete]);

            // Vérifier si la suppression a réussi
            if ($stmt_delete->rowCount() > 0) {
                header('Location: gestion_users.php?succes=delete');
            } else {
                // L'utilisateur n'existait peut-être plus
                header('Location: gestion_users.php?erreur=not_found');
            }
            exit();

        } else {
            // Utilisateur non trouvé
            header('Location: gestion_users.php?erreur=not_found');
            exit();
        }

    } catch (PDOException $e) {
        // Gérer l'erreur (par exemple, afficher un message)
        error_log("Erreur suppression user: " . $e->getMessage()); // Log l'erreur pour le débogage
        header('Location: gestion_users.php?erreur=delete'); // Garder le code erreur delete
        exit();
    }
}


// Récupération de tous les utilisateurs
// La requête existante est correcte car elle ne filtre pas sur 'supprime_le'
$query = '
    SELECT u.*, r.role_name
    FROM users u
    JOIN roles r ON u.id_role = r.id_role
    ORDER BY u.nom, u.prenom
';
$stmt = $pdo->query($query); // Execute the query
$users = $stmt->fetchAll();

// Affichage de la page
afficherHeader('Gestion des utilisateurs', 2); // Make sure this line is correct

?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Gestion des utilisateurs</h2>
        <a href="ajouter_user.php" class="btn btn-primary">Ajouter un utilisateur</a>
    </div>

    <?php if (isset($_GET['succes'])): ?>
        <div class="alert alert-success">
            <?php
            if ($_GET['succes'] == 'add') echo "Utilisateur ajouté avec succès !";
            elseif ($_GET['succes'] == 'edit') echo "Utilisateur modifié avec succès !";
            elseif ($_GET['succes'] == 'delete') echo "Utilisateur supprimé avec succès !";
            else echo "Opération réussie !";
            ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['erreur'])): ?>
        <div class="alert alert-danger">
             <?php
            if ($_GET['erreur'] == 'delete') echo "Erreur lors de la suppression de l'utilisateur.";
            elseif ($_GET['erreur'] == 'self_delete') echo "Vous ne pouvez pas supprimer votre propre compte."; // Message ajouté/modifié
            elseif ($_GET['erreur'] == 'role_protected') echo "Vous ne pouvez pas supprimer un administrateur."; // Nouveau message
            elseif ($_GET['erreur'] == 'invalid_id') echo "ID utilisateur invalide.";
            elseif ($_GET['erreur'] == 'not_found') echo "Utilisateur non trouvé.";
            else echo "Une erreur est survenue.";
            ?>
        </div>
    <?php endif; ?>


    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="thead-light">
                <tr>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Email</th>
                    <th>Code Utilisateur</th> <!-- Nouvelle colonne -->
                    <th>Rôle</th>
                    <th>Date de création</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="7" class="text-center">Aucun utilisateur trouvé.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['nom']) ?></td>
                            <td><?= htmlspecialchars($user['prenom']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['code_utilisateur'] ?? 'N/A') ?></td> <!-- Affichage du code -->
                            <td><?= htmlspecialchars($user['role_name']) ?></td>
                            <td><?= !empty($user['date_created']) ? date('d/m/Y H:i', strtotime($user['date_created'])) : 'N/A' ?></td>
                            <td>
                                <a href="modifier_user.php?id=<?= $user['id_user'] ?>" class="btn btn-sm btn-warning" title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="gestion_users.php?delete=<?= $user['id_user'] ?>"
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.')" title="Supprimer">
                                   <i class="fas fa-trash-alt"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
afficherFooter();
?>
