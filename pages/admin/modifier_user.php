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

// Récupération de l'utilisateur
$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: gestion_users.php');
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id_user = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: gestion_users.php');
    exit();
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Si un nouveau mot de passe est fourni
        if (!empty($_POST['password'])) {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET prenom = ?, nom = ?, email = ?, password = ?, id_role = ?
                WHERE id_user = ?
            ");
            $params = [
                $_POST['prenom'],
                $_POST['nom'],
                $_POST['email'],
                $_POST['password'], // En production, il faudrait hasher le mot de passe
                $_POST['id_role'],
                $id
            ];
        } else {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET prenom = ?, nom = ?, email = ?, id_role = ?
                WHERE id_user = ?
            ");
            $params = [
                $_POST['prenom'],
                $_POST['nom'],
                $_POST['email'],
                $_POST['id_role'],
                $id
            ];
        }
        
        $stmt->execute($params);
        header('Location: gestion_users.php?succes=1');
        exit();
    } catch (PDOException $e) {
        $error = "Erreur lors de la modification de l'utilisateur";
    }
}

// Récupération des rôles
$roles = $pdo->query("SELECT * FROM roles ORDER BY role_name")->fetchAll();

// Affichage de la page
afficherHeader('Modifier un utilisateur', 2);
?>

<div class="container mt-4">
    <h2>Modifier un utilisateur</h2>

    <div class="card">
        <div class="card-body">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="prenom">Prénom :</label>
                    <input type="text" class="form-control" id="prenom" name="prenom" 
                           value="<?= htmlspecialchars($user['prenom']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="nom">Nom :</label>
                    <input type="text" class="form-control" id="nom" name="nom" 
                           value="<?= htmlspecialchars($user['nom']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email :</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="password">Nouveau mot de passe (laisser vide pour ne pas modifier) :</label>
                    <input type="password" class="form-control" id="password" name="password">
                </div>

                <div class="form-group">
                    <label for="id_role">Rôle :</label>
                    <select class="form-control" id="id_role" name="id_role" required>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= $role['id_role'] ?>" 
                                    <?= $role['id_role'] == $user['id_role'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($role['role_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Modifier</button>
                <a href="gestion_users.php" class="btn btn-secondary">Annuler</a>
            </form>
        </div>
    </div>
</div>

<?php
afficherFooter();
?> 