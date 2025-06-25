<?php
require_once 'functions.php';

// Configuration de la session
configureSession();

// Vérifier si des utilisateurs existent déjà
$pdo = connectDB();
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
$userCount = $stmt->fetchColumn();

$message = '';
$messageType = '';

// Formulaire de création d'admin affiché seulement si aucun utilisateur n'existe
if ($userCount == 0) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $prenom = trim($_POST['prenom'] ?? '');
        $nom = trim($_POST['nom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($prenom) || empty($nom) || empty($email) || empty($password)) {
            $message = "Tous les champs sont obligatoires.";
            $messageType = "danger";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "L'adresse email n'est pas valide.";
            $messageType = "danger";
        } else {
            try {
                // Générer le code utilisateur unique
                $lettres = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $prenom), 0, 2)) .
                          strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $nom), 0, 2));
                $chiffres = str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT);
                $code_utilisateur = $lettres . $chiffres;
                
                // Hasher le mot de passe
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Insérer l'utilisateur admin (rôle 1)
                $stmt = $pdo->prepare("
                    INSERT INTO users (prenom, nom, email, password, id_role, code_utilisateur)
                    VALUES (?, ?, ?, ?, 1, ?)
                ");
                $stmt->execute([$prenom, $nom, $email, $hashedPassword, $code_utilisateur]);
                
                $message = "Administrateur créé avec succès ! Vous pouvez maintenant vous connecter.";
                $messageType = "success";
            } catch (PDOException $e) {
                $message = "Erreur lors de la création de l'administrateur : " . $e->getMessage();
                $messageType = "danger";
            }
        }
    }
} else {
    $message = "Des utilisateurs existent déjà dans la base de données. Ce script est désactivé pour des raisons de sécurité.";
    $messageType = "warning";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Initialisation Admin GPI</title>
    <link rel="stylesheet" href="assets/css/bootstrap.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="text-center mb-0">Initialisation du Premier Administrateur</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
                        <?php endif; ?>
                        
                        <?php if ($userCount == 0): ?>
                            <p class="alert alert-info">
                                Ce formulaire permet de créer le premier utilisateur administrateur du système.
                                Il sera automatiquement désactivé une fois qu'un utilisateur existe.
                            </p>
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="prenom" class="form-label">Prénom :</label>
                                    <input type="text" class="form-control" id="prenom" name="prenom" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="nom" class="form-label">Nom :</label>
                                    <input type="text" class="form-control" id="nom" name="nom" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email :</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">Mot de passe :</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">Créer l'administrateur</button>
                                </div>
                            </form>
                        <?php endif; ?>
                        
                        <div class="mt-3 text-center">
                            <a href="index.php" class="btn btn-outline-secondary">Retour à la page de connexion</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
