<?php
require_once 'includes/functions.php';

// Configuration de la session avant d'afficher l'en-tête
configureSession();

// Niveau 0 car nous sommes à la racine
afficherHeader('Accueil GPI', 0);

?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar Navigation -->
        <div class="col-md-3 col-lg-2 sidebar">
            <div class="category-title">Common</div>
            <nav class="nav flex-column">
                <a class="nav-link" href="pages/common/gestion_appareil.php">
                    <i class="fas fa-print"></i>Gestion des Projecteurs & Imprimantes
                </a>
                <a class="nav-link" href="pages/common/Gestion_unit.php">
                    <i class="fas fa-server"></i>Gestion des unités centrales
                </a>
                <a class="nav-link" href="pages/common/gestion_peripherique.php">
                    <i class="fas fa-keyboard"></i>Gestion des peripheriques
                </a>
                <a class="nav-link" href="pages/common/gestion_poste.php">
                    <i class="fas fa-desktop"></i>Gestion des postes
                </a>
                <a class="nav-link" href="pages/common/views_all.php">
                    <i class="fas fa-th-list"></i>Vue globale du matériel
                </a>
            </nav>
            
            <div class="category-title">User</div>
            <nav class="nav flex-column">
                <a class="nav-link" href="pages/user/create_ticket.php">
                    <i class="fas fa-ticket-alt"></i>Ajouter un ticket
                </a>
                <a class="nav-link" href="pages/login.php">
                    <i class="fas fa-sign-in-alt"></i>Login
                </a>
            </nav>
            
            <div class="category-title">Admin</div>
            <nav class="nav flex-column">
                <a class="nav-link" href="pages/admin/gestion_users.php">
                    <i class="fas fa-users"></i>Gestion des utilisateurs
                </a>
                <a class="nav-link" href="pages/admin/gestion_references.php">
                    <i class="fas fa-tags"></i>Gestion des références
                </a>
                <a class="nav-link" href="pages/admin/manage_tickets.php">
                    <i class="fas fa-tasks"></i>Gestion des tickets
                </a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 main-content">
            <h1 class="text-center mb-4">Gestion du Parc Informatique</h1>
        </div>
    </div>
</div>

<?php
afficherFooter();
?>