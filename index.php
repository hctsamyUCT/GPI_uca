<?php
require_once 'includes/functions.php';

// Configuration de la session avant d'afficher l'en-tête
configureSession();

// Si l'utilisateur est déjà connecté, redirection selon son rôle
if (estConnecte()) {
    // Récupérer le rôle de l'utilisateur
    $role = $_SESSION['user_role'];
    
    // Rediriger selon le rôle
    if ($role == 1 || $role == 2) { // Admin (1) ou Technicien (2)
        header('Location: pages/common/dahbord.php');
    } else { // Utilisateur standard
        header('Location: pages/user/create_ticket.php');
    }
    exit();
}

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
  
    try {
        $pdo = connectDB();
        // 1. Récupérer l'utilisateur par email uniquement
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        // 2. Vérifier si l'utilisateur existe ET si le mot de passe correspond au hachage
        if ($user && password_verify($password, $user['password'])) {
            // Régénération de l'ID de session pour prévenir les attaques de fixation de session
            session_regenerate_id(true);
            
            // Connexion réussie
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['user_role'] = $user['id_role'];
            
            // Redirection selon le rôle
            if ($user['id_role'] == 1 || $user['id_role'] == 2) { // Admin ou Technicien
                header('Location: pages/common/dahbord.php');
            } else { // Utilisateur standard
                header('Location: pages/user/create_ticket.php');
            }
            exit();
        } else {
            // Connexion échouée
            $error = "Email ou mot de passe incorrect";
        }
    } catch (PDOException $e) {
        $error = "Erreur de connexion : " . $e->getMessage();
    }
}

// Affichage de la page sans sidebar et sans navbar
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Connexion</title>
    
    <!-- Bootstrap CSS uniquement -->
    <link rel="stylesheet" href="assets/css/bootstrap.css">
    
    <!-- Ajout de Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    
    <!-- Ajout de jQuery et Bootstrap 5 JS pour les fonctionnalités avancées -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <style>
        /* Reset complet pour éliminer tous les espaces */
        *, *::before, *::after {
            box-sizing: border-box;
        }
        
        html, body {
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            width: 100%;
            height: 100%;
        }
        
        /* Reset des styles qui pourraient causer la bande blanche */
        body {
            padding: 0 !important;
            margin: 0 !important;
            position: relative;
            overflow: hidden;
            background: #000; /* Fond noir par défaut pour éviter les flashs blancs */
        }
        
        .login-page {
            background: linear-gradient(-45deg, #ffffff, #e6e6e6, #333333, #000000);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
            min-height: 100vh;
            width: 100vw;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            position: absolute;
            left: 0;
            top: 0;
            right: 0;
            bottom: 0;
        }
        @keyframes gradient {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }
        .login-page::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at center, rgba(255,255,255,0.1) 0%, rgba(0,0,0,0.3) 100%);
            pointer-events: none;
        }
        .login-container {
            padding: 0;
            width: 100%;
            max-width: 450px;
            position: relative;
            z-index: 10;
        }
        .login-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 2rem rgba(0, 0, 0, 0.3);
            overflow: hidden;
            transform: translateY(-20px);
            backdrop-filter: blur(5px);
            background-color: rgba(255, 255, 255, 0.9);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .login-card:hover {
            transform: translateY(-25px);
            box-shadow: 0 0.8rem 2.5rem rgba(0, 0, 0, 0.4);
        }
        .login-card .card-header {
            background: linear-gradient(to right, #4e73df, #224abe);
            border-bottom: 0;
            padding: 1.5rem;
        }
        .login-card .card-body {
            padding: 2rem;
        }
        .login-card .card-footer {
            background-color: rgba(248, 249, 250, 0.8);
            border-top: 0;
            padding: 1rem;
        }
        .login-title {
            color: white;
            font-weight: 700;
            margin-bottom: 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        .login-form .form-group {
            margin-bottom: 1.5rem;
        }
        .login-form .form-control {
            border-radius: 0.35rem;
            font-size: 0.9rem;
            padding: 1.1rem 1rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
            background-color: rgba(255, 255, 255, 0.9);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        .login-form .form-control:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
            background-color: #fff;
        }
        .login-form .btn-primary {
            border-radius: 0.35rem;
            font-size: 0.9rem;
            font-weight: 600;
            padding: 0.75rem 1rem;
            background-color: #4e73df;
            border: none;
            box-shadow: 0 4px 6px rgba(50, 50, 93, 0.11), 0 1px 3px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            width: 100%;
        }
        .login-form .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 14px rgba(50, 50, 93, 0.1), 0 3px 6px rgba(0, 0, 0, 0.08);
            background-color: #3a5ccc;
        }
        .login-form .btn-primary:active {
            transform: translateY(1px);
        }
        .login-form label {
            font-weight: 500;
            color: #4e73df;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
        }
        .login-form .fas {
            margin-right: 0.5rem;
            color: #4e73df;
        }
        .logo-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 10px;
        }
        .logo-text-large {
            font-size: 3rem;
            font-weight: 800;
            color: white;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            letter-spacing: 2px;
        }
        .btn-primary .fas {
            margin-right: 8px;
            color: white;
        }
    </style>
    
    <script>
        // Script pour s'assurer que la page occupe tout l'écran
        document.addEventListener('DOMContentLoaded', function() {
            // Force le body à occuper tout l'écran
            document.body.style.width = '100vw';
            document.body.style.height = '100vh';
            document.body.style.padding = '0';
            document.body.style.margin = '0';
            document.body.style.overflow = 'hidden';
            
            // Supprime toute classe qui pourrait affecter la mise en page
            document.body.className = '';
        });
    </script>
</head>
<body style="margin:0; padding:0; width:100%; height:100%; overflow:hidden;">

<div class="login-page">
    <div class="login-container">
        <div class="card login-card">
            <div class="card-header text-center">
                <div class="logo-wrapper mb-2">
                    <span class="logo-text-large">GPIiii</span> 
                </div>
                <h3 class="login-title">Connexion au Système GPi TEST 1</h3>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <form method="POST" action="" class="login-form">
                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope"></i>Email :</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="Entrez votre email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password"><i class="fas fa-lock"></i>Mot de passe :</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Entrez votre mot de passe" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-sign-in-alt"></i>Se connecter</button>
                </form>
            </div>
            <div class="card-footer text-center text-muted">
                <small>Gestion du Parc Informatique - Accès sécurisé</small>
            </div>
        </div>
    </div>
</div>

</body>
</html>