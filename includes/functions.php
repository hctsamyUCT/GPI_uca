<?php // Ouverture du script PHP

// Définition de la fonction de connexion à la base de données
function connectDB() {
    $host = 'localhost';    // Définition de l'hôte de la base de données
    $db   = 'gpii';          // Nom de la base de données
    $user = 'root';         // Nom d'utilisateur pour la connexion MySQL
    $pass = '';             // Mot de passe pour la connexion MySQL
    $charset = 'utf8mb4';   // Encodage des caractères utilisé

    // Construction de la chaîne de connexion PDO
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset"; // Format DSN pour MySQL

    // Configuration des options PDO
    $options = [ // Tableau d'options pour PDO
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Active le mode exception pour les erreurs
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC // Définit le mode de récupération par défaut
    ];

    // Création et retour de l'objet PDO
    return new PDO($dsn, $user, $pass, $options); // Instancie la connexion à la BD
}

// Fonction pour afficher l'en-tête HTML
function afficherHeader($titre, $niveau = 1, $afficher_sidebar = true) {
    $prefix = str_repeat('../', $niveau);
    
    echo '<!DOCTYPE html>';
    echo '<html lang="fr">';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . htmlspecialchars($titre) . '</title>';
    
    // Chemins CSS dynamiques selon le niveau
    echo '<link rel="stylesheet" href="' . $prefix . 'assets/css/bootstrap.css">';
    echo '<link rel="stylesheet" href="' . $prefix . 'assets/css/custom.css">';
    
    // Ajout des librairies d'icônes (Font Awesome + Bootstrap Icons)
    echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">';
    echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">';
    
    // Ajout de jQuery et Bootstrap 5 JS pour les fonctionnalités avancées
    echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
    echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>';
    
    echo '</head>';
    echo '<body>';
    
    // Sidebar à la GLPI (navbar verticale) - seulement si afficher_sidebar est true
    if ($afficher_sidebar) {
        echo '<div class="sidebar-glpi">';
        echo '<div class="sidebar-header">';
        echo '<a href="' . $prefix . 'index.php" class="logo-container">';
        echo '<span class="logo-text">GPI</span>';
        echo '</a>';
        echo '</div>';
        
        echo '<nav class="sidebar-nav">';
        echo '<ul class="sidebar-menu">';
        
        // Menu Parc (visible uniquement pour les admins et techniciens)
        if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] == 1 || $_SESSION['user_role'] == 2)) {
            echo '<li class="sidebar-item">';
            echo '<a href="#" class="sidebar-link">';
            echo '<i class="fas fa-box sidebar-icon"></i>';
            echo '<span>Parc</span>';
            echo '<i class="fas fa-chevron-right sidebar-arrow"></i>';
            echo '</a>';
            echo '<ul class="sidebar-submenu">';
            echo '<li><a href="' . $prefix . 'pages/common/gestion_appareil.php"><i class="fas fa-print"></i>Projecteurs & Imprimantes</a></li>';
            echo '<li><a href="' . $prefix . 'pages/common/gestion_unit.php"><i class="fas fa-server"></i>Unités centrales</a></li>';
            echo '<li><a href="' . $prefix . 'pages/common/gestion_peripherique.php"><i class="fas fa-keyboard"></i>Périphériques</a></li>';
            echo '<li><a href="' . $prefix . 'pages/common/gestion_poste.php"><i class="fas fa-desktop"></i>Postes</a></li>';
            echo '</ul>';
            echo '</li>';
        }
        
        // Menu Assistance (tous les utilisateurs peuvent voir cette section)
        echo '<li class="sidebar-item">';
        echo '<a href="#" class="sidebar-link">';
        echo '<i class="fas fa-headset sidebar-icon"></i>';
        echo '<span>Assistance</span>';
        echo '<i class="fas fa-chevron-right sidebar-arrow"></i>';
        echo '</a>';
        echo '<ul class="sidebar-submenu">';
        echo '<li><a href="' . $prefix . 'pages/user/create_ticket.php"><i class="fas fa-ticket-alt"></i>Créer un ticket</a></li>';
        
        // Option Gestion des tickets (visible uniquement pour les admins et techniciens)
        if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] == 1 || $_SESSION['user_role'] == 2)) {
            echo '<li><a href="' . $prefix . 'pages/admin/manage_tickets.php"><i class="fas fa-tasks"></i>Gestion des tickets</a></li>';
        }
        
        echo '</ul>';
        echo '</li>';
        
        // Menu Configuration (visible uniquement pour les admins)
        if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 1) {
            echo '<li class="sidebar-item">';
            echo '<a href="#" class="sidebar-link">';
            echo '<i class="fas fa-cog sidebar-icon"></i>';
            echo '<span>Configuration</span>';
            echo '<i class="fas fa-chevron-right sidebar-arrow"></i>';
            echo '</a>';
            echo '<ul class="sidebar-submenu">';
            echo '<li><a href="' . $prefix . 'pages/admin/gestion_references.php"><i class="fas fa-tags"></i>Gestion des salles/marques</a></li>';
            echo '<li><a href="' . $prefix . 'pages/common/views_all.php"><i class="fas fa-th-list"></i>Vue globale sur le stock</a></li>';
            echo '<li><a href="' . $prefix . 'pages/admin/gestion_users.php"><i class="fas fa-user-tag"></i>Gestion des utilisateurs</a></li>';
            echo '<li><a href="' . $prefix . 'pages/admin/statistiques.php"><i class="fas fa-chart-bar"></i>Statistiques</a></li>';
            echo '<li><a href="' . $prefix . 'pages/tickets_par_salle/selection_page.php"><i class="fas fa-door-open"></i>Sélection salle (tickets)</a></li>';
            echo '</ul>';
            echo '</li>';
        }
        
        echo '</ul>';
        echo '</nav>';
        
        // Bouton pour réduire le menu
        echo '<div class="sidebar-footer">';
        echo '<button class="btn-collapse" id="collapseMenu">';
        echo '<i class="fas fa-chevron-left"></i>';
        echo '<span>Réduire le menu</span>';
        echo '</button>';
        echo '</div>';
        echo '</div>';
    }
    
    // Navbar horizontale avec les informations utilisateur - affichée sur toutes les pages
    echo '<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-1">';
    echo '<div class="container d-flex justify-content-between align-items-center">';
    
    // Logo ou titre pour la navbar horizontale
    echo '<a class="navbar-brand" href="' . $prefix . 'index.php">GPI</a>';
    
    // Partie droite de la navbar
    echo '<div class="d-flex align-items-center">';
    
    // Ajout des informations utilisateur et du bouton de déconnexion si l'utilisateur est connecté
    if (isset($_SESSION['user_id'])) {
        // Récupérer les informations de l'utilisateur
        $pdo = connectDB();
        $stmt = $pdo->prepare("SELECT u.nom, u.prenom, r.role_name FROM users u JOIN roles r ON u.id_role = r.id_role WHERE u.id_user = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        echo '<div class="navbar-user-info mr-3">';
        echo '<span class="user-name text-white mr-2">' . htmlspecialchars($user['prenom'] . ' ' . $user['nom']) . '</span>';
        echo '<span class="user-role badge badge-light">' . htmlspecialchars($user['role_name']) . '</span>';
        echo '</div>';
        
        echo '<a href="' . $prefix . 'includes/logout.php" class="btn btn-outline-light"><i class="fas fa-sign-out-alt mr-1"></i>Déconnexion</a>';
    } else {
        // Afficher le bouton de connexion si l'utilisateur n'est pas connecté
        echo '<a href="' . $prefix . 'index.php" class="btn btn-outline-light"><i class="fas fa-sign-in-alt mr-1"></i>Connexion</a>';
    }
    
    echo '</div>'; // Fin de la partie droite
    echo '</div>'; // Fin du container
    echo '</nav>';
    
    // Contenu principal - l'espacement est géré par le padding-top du body
    // Ajuster la classe du conteneur en fonction de la présence de la sidebar
    if ($afficher_sidebar) {
        echo '<div class="container">';
    } else {
        echo '<div class="container-fluid">';
    }
}

// Fonction pour afficher le pied de page
function afficherFooter() {
    echo '</div>'; // Fermeture du conteneur principal (div class="container" ou "container-fluid")
    
    // Script pour la fonctionnalité du menu
    echo '<script>
    $(document).ready(function() {
        // Toggle submenu
        $(".sidebar-link").click(function(e) {
            e.preventDefault();
            $(this).next(".sidebar-submenu").slideToggle(300);
            $(this).parent().toggleClass("active");
            $(this).find(".sidebar-arrow").toggleClass("rotate");
        });
        
        // Collapse menu
        $("#collapseMenu").click(function() {
            $(".sidebar-glpi").toggleClass("collapsed");
            $("body").toggleClass("sidebar-collapsed");
            
            if ($(".sidebar-glpi").hasClass("collapsed")) {
                $(this).html(\'<i class="fas fa-chevron-right"></i>\');
                $(".sidebar-item span, .sidebar-footer span, .logo-text").hide();
            } else {
                $(this).html(\'<i class="fas fa-chevron-left"></i><span>Réduire le menu</span>\');
                $(".sidebar-item span, .sidebar-footer span, .logo-text").show();
            }
        });
    });
    </script>';
    
    echo '</body>'; // Fermeture du corps du document
    echo '</html>'; // Fermeture de la balise HTML
}

// Fonction pour afficher les messages système (MODIFIÉE pour utiliser la session)
function afficherMessage() {
    // Assurer que la session est active
    if (session_status() == PHP_SESSION_NONE) {
        // Il est préférable que configureSession() ait déjà démarré la session
        session_start();
    }

    // Vérifier si un message est stocké dans la session
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        // Le texte peut contenir du HTML (comme <strong>), donc on n'applique pas htmlspecialchars
        $messageText = $message['text'];
        $messageType = htmlspecialchars($message['type'], ENT_QUOTES, 'UTF-8'); // ex: 'success', 'danger'

        // Afficher l'alerte Bootstrap 5 (avec bouton de fermeture)
        echo '<div class="alert alert-' . $messageType . ' alert-dismissible fade show" role="alert">';
        echo $messageText;
        // Utilisation de la classe btn-close de Bootstrap 5
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';

        // Supprimer le message de la session après l'affichage
        unset($_SESSION['message']);
    }
    // Gérer aussi les messages passés par GET pour la compatibilité descendante
    else if(isset($_GET['succes']) && $_GET['succes'] == '1') {
        // Message de succès générique (peut être utilisé par différentes actions)
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
        echo 'L\'opération s\'est déroulée avec succès.';
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
    }
    else if(isset($_GET['erreur'])) {
        // Gérer les messages d'erreur passés par GET
        $erreur = htmlspecialchars($_GET['erreur'], ENT_QUOTES, 'UTF-8');
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
        echo $erreur;
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
    }
}

// Fonction pour afficher le formulaire d'appareil
function afficherFormulaire($data = null) {
    try {
        $pdo = connectDB();
        
        // Vérification de l'existence des tables nécessaires
        $tables = ['marque', 'salles'];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() === 0) {
                throw new Exception("La table $table n'existe pas");
            }
        }

        // Récupération des marques avec vérification
        $marques = $pdo->query("SELECT * FROM marque ORDER BY nom_marque")->fetchAll();
        if (empty($marques)) {
            throw new Exception("Aucune marque n'est disponible");
        }

        // Récupération des salles avec vérification
        $salles = $pdo->query("SELECT * FROM salles ORDER BY nom_salle")->fetchAll();

        $values = $data ?: [
            'type' => 'Imprimante',
            'modele' => '',
            'numero_inventaire' => '',
            'etat' => 'Operationnel',
            'id_marque' => '',
            'id_salle' => ''
        ];
        ?>
        <div class="card">
            <div class="card-body">
                <form method="POST" action="../includes/gestion.php<?= $data ? '?action=modifier&id=' . $data['id_appareil'] : '?action=ajouter' ?>" class="needs-validation" novalidate>
                    <div class="form-group">
                        <label for="type">Type :</label>
                        <select name="type" id="type" class="form-control" required>
                            <option value="Imprimante" <?= $values['type'] === 'Imprimante' ? 'selected' : '' ?>>Imprimante</option>
                            <option value="Projecteur" <?= $values['type'] === 'Projecteur' ? 'selected' : '' ?>>Projecteur</option>
                        </select>
                        <div class="invalid-feedback">Veuillez sélectionner un type</div>
                    </div>

                    <div class="form-group">
                        <label for="modele">Modèle :</label>
                        <input type="text" class="form-control" id="modele" name="modele" value="<?= htmlspecialchars($values['modele']) ?>" required>
                        <div class="invalid-feedback">Veuillez entrer un modèle</div>
                    </div>

                    <!-- Champ masqué pour le numéro d'inventaire -->
                    <input type="hidden" id="numero_inventaire" name="numero_inventaire" value="<?= htmlspecialchars($values['numero_inventaire']) ?>">

                    <div class="form-group">
                        <label for="etat">État :</label>
                        <select name="etat" id="etat" class="form-control" required>
                            <option value="Operationnel" <?= $values['etat'] === 'Operationnel' ? 'selected' : '' ?>>Opérationnel</option>
                            <option value="En panne" <?= $values['etat'] === 'En panne' ? 'selected' : '' ?>>En panne</option>
                        </select>
                        <div class="invalid-feedback">Veuillez sélectionner un état</div>
                    </div>

                    <div class="form-group">
                        <label for="id_marque">Marque :</label>
                        <select name="id_marque" id="id_marque" class="form-control" required>
                            <option value="">Sélectionnez une marque</option>
                            <?php foreach($marques as $marque): ?>
                            <option value="<?= $marque['id_marque'] ?>" <?= $values['id_marque'] == $marque['id_marque'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($marque['nom_marque']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Veuillez sélectionner une marque</div>
                    </div>

                    <div class="form-group">
                        <label for="id_salle">Salle :</label>
                        <select name="id_salle" id="id_salle" class="form-control">
                            <option value="">Non affecté</option>
                            <?php foreach($salles as $salle): ?>
                            <option value="<?= $salle['id_salle'] ?>" <?= $values['id_salle'] == $salle['id_salle'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($salle['nom_salle']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary"><?= $data ? 'Modifier' : 'Ajouter' ?></button>
                        <a href="../pages/common/Gestion_appareil.php" class="btn btn-secondary">Annuler</a>
                    </div>
                </form>
            </div>
        </div>

        <script>
        // Validation côté client
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
        </script>
        <?php
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">Erreur : ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

// Modifiez la fonction configureSession
function configureSession() {
    // Vérifier si la session n'est pas déjà démarrée
    if (session_status() == PHP_SESSION_NONE) {
        // Configuration des paramètres de session avant de la démarrer
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        
        // Définir le chemin du cookie de session
        session_set_cookie_params(
            1800,      // 30 minutes
            '/',       // Chemin
            '',        // Domaine
            false,     // Secure (mettez true si vous utilisez HTTPS)
            true       // HTTPOnly
        );
        
        // Démarrer la session
        session_start();
    }
}

// Modifiez la fonction estConnecte
function estConnecte() {
    // Appeler configureSession qui vérifiera si la session est déjà démarrée
    configureSession();
    return isset($_SESSION['user_id']);
}

/**
 * Vérifie si l'utilisateur connecté a un rôle spécifique
 * @param int|array $roles Le ou les rôles autorisés (1=admin, 2=technicien, 3=utilisateur)
 * @return bool True si l'utilisateur a le rôle requis, false sinon
 */
function aRoleRequis($roles) {
    // Vérifier si l'utilisateur est connecté
    if (!estConnecte()) {
        return false;
    }
    
    // Convertir en tableau si ce n'est pas déjà le cas
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    
    // Vérifier si le rôle de l'utilisateur est dans la liste des rôles autorisés
    return isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], $roles);
}

/**
 * Redirige l'utilisateur s'il n'a pas le rôle requis
 * @param int|array $roles Le ou les rôles autorisés
 * @param string $redirect_url L'URL de redirection en cas d'échec
 * @param string $error_message Le message d'erreur à afficher
 */
function verifierRole($roles, $redirect_url = '../../index.php', $error_message = 'Accès non autorisé') {
    if (!aRoleRequis($roles)) {
        header("Location: $redirect_url?error=" . urlencode($error_message));
        exit();
    }
}

function restaurerAppareil($id_archive) {
    $pdo = connectDB();
    try {
        $pdo->beginTransaction();

        // 1. Récupérer les données de l'archive
        $stmt = $pdo->prepare("SELECT * FROM archives_appareils WHERE id_archive = ?");
        $stmt->execute([$id_archive]);
        $archive = $stmt->fetch();

        if (!$archive) {
            throw new Exception("Archive non trouvée");
        }

        // 2. Réinsérer dans la table appareils
        $stmt = $pdo->prepare("INSERT INTO appareils (
            id_appareil, type, modele, numero_inventaire, etat, id_marque, id_salle, date_ajout
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $archive['id_appareil'],
            $archive['type'],
            $archive['modele'],
            $archive['numero_inventaire'],
            $archive['etat'],
            $archive['id_marque'],
            $archive['id_salle'],
            $archive['date_ajout']
        ]);

        // 3. Supprimer l'entrée des archives
        $stmt = $pdo->prepare("DELETE FROM archives_appareils WHERE id_archive = ?");
        $stmt->execute([$id_archive]);

        $pdo->commit();
        return true;

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function restaurerPeripherique($id_archive) {
    $pdo = connectDB();
    try {
        $pdo->beginTransaction();

        // 1. Récupérer les données de l'archive
        $stmt = $pdo->prepare("SELECT * FROM archives_peripherique WHERE id_archive = ?");
        $stmt->execute([$id_archive]);
        $archive = $stmt->fetch();

        if (!$archive) {
            throw new Exception("Archive non trouvée");
        }

        // 2. Réinsérer dans la table peripherique
        $stmt = $pdo->prepare("
            INSERT INTO peripherique (
                id_peripherique, type, id_marque, numero_inventaire,
                etat, date_ajout, est_affecte
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $archive['id_peripherique'],
            $archive['type'],
            $archive['id_marque'],
            $archive['numero_inventaire'],
            $archive['etat'],
            $archive['date_ajout'],
            $archive['est_affecte']
        ]);

        // 3. Supprimer l'entrée des archives
        $stmt = $pdo->prepare("DELETE FROM archives_peripherique WHERE id_archive = ?");
        $stmt->execute([$id_archive]);

        $pdo->commit();
        return true;

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function restaurerUnite($id_archive) {
    $pdo = connectDB();
    try {
        $pdo->beginTransaction();

        // 1. Récupérer les données de l'archive
        $stmt = $pdo->prepare("SELECT * FROM archives_unite WHERE id_archive = ?");
        $stmt->execute([$id_archive]);
        $archive = $stmt->fetch();

        if (!$archive) {
            throw new Exception("Archive non trouvée");
        }

        // 2. Réinsérer dans la table unite
        $stmt = $pdo->prepare("
            INSERT INTO unite (
                id_unite, numero_inventaire, ram, processeur,
                disque_dur_1_type, disque_dur_1_capacite,
                disque_dur_2_type, disque_dur_2_capacite,
                etat, date_achat, date_ajout, est_affecte
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $archive['id_unite'],
            $archive['numero_inventaire'],
            $archive['ram'],
            $archive['processeur'],
            $archive['disque_dur_1_type'],
            $archive['disque_dur_1_capacite'],
            $archive['disque_dur_2_type'],
            $archive['disque_dur_2_capacite'],
            $archive['etat'],
            $archive['date_achat'],
            $archive['date_ajout'],
            $archive['est_affecte']
        ]);

        // 3. Supprimer l'entrée des archives
        $stmt = $pdo->prepare("DELETE FROM archives_unite WHERE id_archive = ?");
        $stmt->execute([$id_archive]);

        $pdo->commit();
        return true;

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Sets a message in the session to be displayed on the next request.
 *
 * @param string $message The message text.
 * @param string $type The message type (e.g., 'success', 'danger', 'info', 'warning'). Defaults to 'info'.
 */
function setMessage($message, $type = 'info') {
    // Ensure session is active before using session variables
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['message'] = [
        'text' => $message,
        'type' => $type
    ];
}

?> <!-- Fin du script PHP -->
