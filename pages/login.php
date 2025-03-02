<?php
require_once '../includes/functions.php';

// Configuration des sessions
configureSession();

// Si l'utilisateur est déjà connecté, redirection vers l'accueil
if (estConnecte()) {
    header('Location: ../index.php');
    exit();
}

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    try {
        $pdo = connectDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email AND password = :password");
        $stmt->execute(['email' => $email, 'password' => $password]);
        $user = $stmt->fetch();

        if ($user) {
            // Régénération de l'ID de session pour prévenir les attaques de fixation de session
            session_regenerate_id(true);
            
            // Connexion réussie
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['user_role'] = $user['id_role'];
            
            // Redirection vers l'index après connexion
            header('Location: ../index.php');
            exit();
        } else {
            // Connexion échouée
            $error = "Email ou mot de passe incorrect";
        }
    } catch (PDOException $e) {
        $error = "Erreur de connexion : " . $e->getMessage();
    }
}

// Affichage de la page
afficherHeader('Connexion', 1);
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="text-center">Connexion</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="email">Email :</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Mot de passe :</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-block">Se connecter</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
afficherFooter();
?> 