<?php
session_start();
require_once '../../includes/functions.php';

// Vérification des droits d'accès
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header('Location: ../login.php');
    exit();
}

$pdo = connectDB();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO users (prenom, nom, email, password, id_role)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_POST['prenom'],
            $_POST['nom'],
            $_POST['email'],
            $_POST['password'], // En production, il faudrait hasher le mot de passe
            $_POST['id_role']
        ]);
        
        header('Location: gestion_users.php?succes=1');
        exit();
    } catch (PDOException $e) {
        $error = "Erreur lors de l'ajout de l'utilisateur";
    }
}

// Récupération des rôles
$roles = $pdo->query("SELECT * FROM roles ORDER BY role_name")->fetchAll();

// Affichage de la page
afficherHeader('Ajouter un utilisateur', 2);
?>

<div class="container mt-4">
    <h2>Ajouter un utilisateur</h2>

    <div class="card">
        <div class="card-body">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="prenom">Prénom :</label>
                    <input type="text" class="form-control" id="prenom" name="prenom" required>
                </div>

                <div class="form-group">
                    <label for="nom">Nom :</label>
                    <input type="text" class="form-control" id="nom" name="nom" required>
                </div>

                <div class="form-group">
                    <label for="email">Email :</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="password">Mot de passe :</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>

                <div class="form-group">
                    <label for="id_role">Rôle :</label>
                    <select class="form-control" id="id_role" name="id_role" required>
                        <option value="">Sélectionnez un rôle</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= $role['id_role'] ?>">
                                <?= htmlspecialchars($role['role_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Ajouter</button>
                <a href="gestion_users.php" class="btn btn-secondary">Annuler</a>
            </form>
        </div>
    </div>
</div>

<?php
afficherFooter();
?>