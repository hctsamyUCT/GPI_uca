<?php
require_once '../../includes/functions.php';

// Vérifier si l'utilisateur est connecté et est administrateur
verifierRole(1); // Correction: Utiliser verifierRole() au lieu de verifierAuthentification()

$pdo = connectDB();
$error = null;
$success = null; // Ajout pour message de succès

// Récupération de l'utilisateur à modifier
$id = $_GET['id'] ?? null;
if (!$id || !filter_var($id, FILTER_VALIDATE_INT)) {
    header('Location: gestion_users.php?erreur=invalid_id');
    exit();
}

// Traitement du formulaire (si la méthode est POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et nettoyage des données
    $prenom = trim($_POST['prenom'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? ''; // Ne pas trimmer
    $id_role = $_POST['id_role'] ?? '';
    // Le code utilisateur n'est pas récupéré du POST car il n'est pas modifiable ici

    // Validations
    if (empty($prenom) || empty($nom) || empty($email) || empty($id_role)) {
        $error = "Les champs Prénom, Nom, Email et Rôle sont obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "L'adresse email n'est pas valide.";
    } else {
        try {
            // Vérifier si l'email existe déjà POUR UN AUTRE utilisateur
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id_user != ?");
            $stmt->execute([$email, $id]);
            if ($stmt->fetchColumn() > 0) {
                $error = "Cette adresse email est déjà utilisée par un autre utilisateur.";
            } else {
                // Construire la requête UPDATE
                $sql = "UPDATE users SET prenom = ?, nom = ?, email = ?, id_role = ?";
                $params = [$prenom, $nom, $email, $id_role];

                // Ajouter le mot de passe à la requête s'il est fourni
                if (!empty($password)) {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $sql .= ", password = ?";
                    $params[] = $hashedPassword;
                }

                $sql .= " WHERE id_user = ?";
                $params[] = $id;

                // Exécuter la mise à jour
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);

                // Rediriger vers la liste avec un message de succès
                header('Location: gestion_users.php?succes=edit');
                exit();
            }
        } catch (PDOException $e) {
            $error = "Erreur lors de la modification de l'utilisateur : " . $e->getMessage();
            // error_log("Erreur PDO update user: " . $e->getMessage());
            // $error = "Une erreur technique est survenue.";
        }
    }
    // Si on arrive ici (erreur de validation ou PDO), on doit recharger les données utilisateur pour réafficher le formulaire
    // Il est préférable de le faire après le bloc try/catch pour avoir les données à jour en cas d'erreur PDO aussi
}

// Recharger les données utilisateur (soit pour l'affichage initial, soit après une erreur POST)
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id_user = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    if (!$user) {
        header('Location: gestion_users.php?erreur=not_found');
        exit();
    }
} catch (PDOException $e) {
    // Gérer l'erreur de chargement
    afficherHeader('Erreur', 2);
    echo "<div class='container mt-4'><div class='alert alert-danger'>Impossible de charger les informations de l'utilisateur.</div></div>";
    afficherFooter();
    exit();
}


// Récupération des rôles pour le formulaire
try {
    $roles = $pdo->query("SELECT * FROM roles ORDER BY role_name")->fetchAll();
} catch (PDOException $e) {
    $roles = [];
    // Afficher une erreur ou logguer si les rôles ne peuvent pas être chargés
    $error = $error ? $error . "<br>Impossible de charger les rôles." : "Impossible de charger les rôles.";
}


// Affichage de la page
afficherHeader('Modifier un utilisateur', 2);
?>

<div class="container mt-4">
    <h2>Modifier l'utilisateur : <?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></h2>

    <div class="card">
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error // Pas besoin de htmlspecialchars ici si les erreurs PDO sont gérées proprement ?></div>
            <?php endif; ?>
            <?php if ($success): // Bien que la redirection empêche son affichage ici, on le garde pour la cohérence ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <!-- Le formulaire pointe vers lui-même (modifier_user.php?id=...) -->
            <form method="POST" action="modifier_user.php?id=<?= $id ?>">
                <div class="form-group mb-3">
                    <label for="prenom">Prénom :</label>
                    <input type="text" class="form-control" id="prenom" name="prenom"
                           value="<?= htmlspecialchars($user['prenom']) ?>" required>
                </div>

                <div class="form-group mb-3">
                    <label for="nom">Nom :</label>
                    <input type="text" class="form-control" id="nom" name="nom"
                           value="<?= htmlspecialchars($user['nom']) ?>" required>
                </div>

                <div class="form-group mb-3">
                    <label for="email">Email :</label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>

                <div class="form-group mb-3">
                    <label for="code_utilisateur">Code Utilisateur :</label>
                    <input type="text" class="form-control" id="code_utilisateur" name="code_utilisateur_display"
                           value="<?= htmlspecialchars($user['code_utilisateur'] ?? 'N/A') ?>" readonly>
                    <small class="form-text text-muted">Le code utilisateur est généré automatiquement et ne peut pas être modifié.</small>
                </div>

                <div class="form-group mb-3">
                    <label for="password">Nouveau mot de passe :</label>
                    <input type="password" class="form-control" id="password" name="password" aria-describedby="passwordHelp">
                    <small id="passwordHelp" class="form-text text-muted">Laissez ce champ vide si vous ne souhaitez pas modifier le mot de passe.</small>
                </div>

                <div class="form-group mb-3">
                    <label for="id_role">Rôle :</label>
                    <select class="form-control" id="id_role" name="id_role" required>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= $role['id_role'] ?>"
                                    <?= ($role['id_role'] == $user['id_role']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($role['role_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                <a href="gestion_users.php" class="btn btn-secondary">Annuler</a>
            </form>
        </div>
    </div>
</div>

<?php
afficherFooter();
?>