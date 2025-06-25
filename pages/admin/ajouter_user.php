<?php
require_once '../../includes/functions.php';

// Vérifier si l'utilisateur est connecté et est administrateur
verifierRole(1); // Correction: Utiliser verifierRole() au lieu de verifierAuthentification()

$pdo = connectDB();
$error = null;
$success = null;

// Fonction pour générer le code utilisateur unique
function genererCodeUtilisateur($prenom, $nom, $pdo) {
    do {
        // Prend les 2 premières lettres du prénom et du nom en majuscules
        $lettres = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $prenom), 0, 2)) .
                   strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $nom), 0, 2));
        // Génère 2 chiffres aléatoires (00-99)
        $chiffres = str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT);
        // Combine pour former le code de 6 caractères
        $code = $lettres . $chiffres;

        // Vérifier l'unicité dans la base de données
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE code_utilisateur = ?");
        $stmt->execute([$code]);
        $existe = $stmt->fetchColumn();

        // Si le code existe, la boucle continue pour en générer un nouveau
    } while ($existe > 0);

    return $code;
}


// Traitement du formulaire d'ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et nettoyage des données
    $prenom = trim($_POST['prenom'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? ''; // Ne pas trimmer le mot de passe
    $id_role = $_POST['id_role'] ?? '';

    // Validations simples (vous pouvez ajouter des validations plus robustes)
    if (empty($prenom) || empty($nom) || empty($email) || empty($password) || empty($id_role)) {
        $error = "Tous les champs sont obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "L'adresse email n'est pas valide.";
    } else {
        try {
            // Vérifier si l'email existe déjà
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                $error = "Cette adresse email est déjà utilisée.";
            } else {
                // Générer le code utilisateur unique
                $code_utilisateur = genererCodeUtilisateur($prenom, $nom, $pdo); // Appel de la fonction

                // Hasher le mot de passe (IMPORTANT pour la sécurité)
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Préparer et exécuter la requête d'insertion
                $stmt = $pdo->prepare("
                    INSERT INTO users (prenom, nom, email, password, id_role, code_utilisateur)
                    VALUES (?, ?, ?, ?, ?, ?)
                "); // La colonne code_utilisateur est bien présente
                $stmt->execute([
                    $prenom,
                    $nom,
                    $email,
                    $hashedPassword, // Utiliser le mot de passe hashé
                    $id_role,
                    $code_utilisateur // La variable $code_utilisateur est utilisée ici
                ]);

                $success = "Utilisateur ajouté avec succès ! Son code utilisateur est : " . htmlspecialchars($code_utilisateur);
                // Optionnel : rediriger vers la page de gestion après succès
                // header('Location: gestion_users.php?succes=add');
                // exit();
            }
        } catch (PDOException $e) {
            $error = "Erreur lors de l'ajout de l'utilisateur : " . $e->getMessage();
            // En production, logguer l'erreur détaillée et afficher un message générique
            // error_log("Erreur PDO: " . $e->getMessage());
            // $error = "Une erreur technique est survenue. Veuillez réessayer plus tard.";
        }
    }
}

// Récupération des rôles pour le formulaire
try {
    $roles = $pdo->query("SELECT * FROM roles ORDER BY role_name")->fetchAll();
} catch (PDOException $e) {
    $roles = [];
    $error = "Impossible de charger les rôles.";
    // error_log("Erreur PDO roles: " . $e->getMessage());
}


// Affichage de la page
afficherHeader('Ajouter un utilisateur', 2); // 2 = Niveau d'accès Admin
?>

<div class="container mt-4">
    <h2>Ajouter un nouvel utilisateur</h2>

    <div class="card">
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST" action="ajouter_user.php">
                <div class="form-group mb-3">
                    <label for="prenom">Prénom :</label>
                    <input type="text" class="form-control" id="prenom" name="prenom" required value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>">
                </div>

                <div class="form-group mb-3">
                    <label for="nom">Nom :</label>
                    <input type="text" class="form-control" id="nom" name="nom" required value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">
                </div>

                <div class="form-group mb-3">
                    <label for="email">Email :</label>
                    <input type="email" class="form-control" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="form-group mb-3">
                    <label for="password">Mot de passe :</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>

                <div class="form-group mb-3">
                    <label for="id_role">Rôle :</label>
                    <select class="form-control" id="id_role" name="id_role" required>
                        <option value="">-- Sélectionner un rôle --</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= $role['id_role'] ?>" <?= (isset($_POST['id_role']) && $_POST['id_role'] == $role['id_role']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($role['role_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Ajouter l'utilisateur</button>
                <a href="gestion_users.php" class="btn btn-secondary">Annuler</a>
            </form>
        </div>
    </div>
</div>

<?php
afficherFooter();
?>