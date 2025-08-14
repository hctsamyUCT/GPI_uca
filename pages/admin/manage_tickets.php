<?php
require_once '../../includes/functions.php';

// Configuration de la session
configureSession();

// Vérifier si l'utilisateur est connecté et a les droits d'accès (admin ou technicien)
if (!estConnecte()) {
    header('Location: ../../index.php?error=Vous devez être connecté pour accéder à cette page');
    exit();
}

// Utiliser la nouvelle fonction pour vérifier les rôles
verifierRole([1, 2], '../../index.php', 'Vous n\'avez pas les droits nécessaires pour accéder à cette page');

// Initialiser la connexion à la base de données
$pdo = connectDB();

// Récupérer l'ID de l'utilisateur connecté
$current_user_id = $_SESSION['user_id'];

// Traitement de l'ajout d'une intervention
if (isset($_POST['add_intervention'])) {
    $ticket_id = $_POST['ticket_id'];
    $description = $_POST['description'];
    $action = $_POST['action_effectuee'];
    
    // Insérer l'intervention dans la base de données
    $query = "INSERT INTO interventions (id_ticket, id_user, description, action_effectuee) 
              VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$ticket_id, $current_user_id, $description, $action]);
    
    if ($stmt->rowCount() > 0) {
        // Mise à jour de l'état du ticket en 'En cours'
        $update_query = "UPDATE tickets SET etat = 'En cours' WHERE id_ticket = ? AND etat = 'Ouvert'";
        $update_stmt = $pdo->prepare($update_query);
        $update_stmt->execute([$ticket_id]);
        
        $_SESSION['success'] = "Intervention ajoutée avec succès";
    } else {
        $_SESSION['error'] = "Erreur lors de l'ajout de l'intervention: " . $pdo->errorInfo()[2];
    }
    
    header("Location: manage_tickets.php");
    exit();
}

// Traitement de la mise à jour de l'état d'un ticket
if (isset($_POST['update_ticket'])) {
    $ticket_id = $_POST['ticket_id'];
    $etat = $_POST['etat'];
    $priorite = $_POST['priorite'];
    $categorie = $_POST['categorie'];
    
    $query = "UPDATE tickets SET etat = ?, priorite = ?, categorie = ? WHERE id_ticket = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$etat, $priorite, $categorie, $ticket_id]);
    
    if ($stmt->rowCount() > 0) {
        $_SESSION['success'] = "Ticket mis à jour avec succès";
    } else {
        $_SESSION['error'] = "Erreur lors de la mise à jour du ticket: " . $pdo->errorInfo()[2];
    }
    
    header("Location: manage_tickets.php");
    exit();
}

// Filtres
$where_clauses = [];
$params = [];
$types = "";

// Filtre par état
if (isset($_GET['etat']) && $_GET['etat'] != '') {
    $where_clauses[] = "t.etat = ?";
    $params[] = $_GET['etat'];
    $types .= "s";
}

// Filtre par priorité
if (isset($_GET['priorite']) && $_GET['priorite'] != '') {
    $where_clauses[] = "t.priorite = ?";
    $params[] = $_GET['priorite'];
    $types .= "s";
}

// Filtre par catégorie
if (isset($_GET['categorie']) && $_GET['categorie'] != '') {
    $where_clauses[] = "t.categorie = ?";
    $params[] = $_GET['categorie'];
    $types .= "s";
}

// Filtre par salle
if (isset($_GET['salle']) && $_GET['salle'] != '') {
    $where_clauses[] = "t.id_salle = ?";
    $params[] = $_GET['salle'];
    $types .= "i";
}

// Construction de la clause WHERE
$where = "";
if (!empty($where_clauses)) {
    $where = "WHERE " . implode(" AND ", $where_clauses);
}

// Récupération des tickets
$query = "SELECT t.*, u.prenom, u.nom, s.nom_salle, s.etage, p.nom_poste,
          a.type AS appareil_type, a.modele AS appareil_modele,
          pe.type AS peripherique_type, 
          uc.numero_inventaire AS unite_numero, uc.processeur AS unite_processeur, uc.ram AS unite_ram,
          (SELECT COUNT(*) FROM interventions i WHERE i.id_ticket = t.id_ticket) as nb_interventions
          FROM tickets t
          LEFT JOIN users u ON t.id_user = u.id_user
          LEFT JOIN salles s ON t.id_salle = s.id_salle
          LEFT JOIN poste p ON t.id_poste = p.id_poste
          LEFT JOIN appareils a ON t.id_appareil = a.id_appareil
          LEFT JOIN peripherique pe ON t.id_peripherique = pe.id_peripherique
          LEFT JOIN unite uc ON t.id_unite = uc.id_unite
          $where
          ORDER BY t.date_creation DESC";

$stmt = $pdo->prepare($query);

if (!empty($params)) {
    $stmt->execute($params);
} else {
    $stmt->execute();
}

$tickets = $stmt->fetchAll();

// Après la récupération des tickets, ajouter ces requêtes spécifiques
$metrics = [];

// Requête pour les états des tickets
$query_metrics = "SELECT 
    SUM(etat = 'Ouvert') AS ouverts,
    SUM(etat = 'En cours') AS en_cours,
    SUM(etat = 'Probleme Résoulu(fermé)') AS resolus,
    SUM(etat = 'probleme non resoulu(fermé)') AS non_resolus 
FROM tickets";
$stmt_metrics = $pdo->query($query_metrics);
$metrics = $stmt_metrics->fetch(PDO::FETCH_ASSOC);

// Requête pour les matériels supprimés
$query_supprimes = "SELECT COUNT(*) AS supprimes FROM archives_appareils";
$stmt_supprimes = $pdo->query($query_supprimes);
$supprimes = $stmt_supprimes->fetch(PDO::FETCH_COLUMN);

// Récupération des salles pour le filtre
$query_salles = "SELECT * FROM salles ORDER BY nom_salle";
$stmt_salles = $pdo->query($query_salles);
$salles = $stmt_salles->fetchAll();

// Récupération des interventions pour chaque ticket
$ticket_interventions = [];
foreach ($tickets as $ticket) {
    $query_interventions = "SELECT i.*, u.prenom, u.nom 
                           FROM interventions i 
                           JOIN users u ON i.id_user = u.id_user 
                           WHERE i.id_ticket = ? 
                           ORDER BY i.date_intervention DESC";
    $stmt_interventions = $pdo->prepare($query_interventions);
    $stmt_interventions->execute([$ticket['id_ticket']]);
    $ticket_interventions[$ticket['id_ticket']] = $stmt_interventions->fetchAll();
}

// Après avoir récupéré les tickets, compléter les informations manquantes pour les équipements supprimés
foreach ($tickets as &$ticket) {
    // Vérifier si un équipement est référencé mais pas trouvé (a été supprimé)
    if ($ticket['id_appareil'] && empty($ticket['appareil_type'])) {
        // Rechercher dans la table d'archive des appareils
        $query_archive = "SELECT type, modele, date_suppression FROM appareils_archive WHERE id_appareil = ? ORDER BY date_suppression DESC LIMIT 1";
        $stmt_archive = $pdo->prepare($query_archive);
        $stmt_archive->execute([$ticket['id_appareil']]);
        $archive_info = $stmt_archive->fetch();
        
        if ($archive_info) {
            // Récupérer les informations depuis l'archive
            try {
                $date_suppression = new DateTime($archive_info['date_suppression']);
                $date_formatted = $date_suppression->format('d/m/Y');
                
                $ticket['appareil_type'] = $archive_info['type'] . ' (supprimé le ' . $date_formatted . ')';
            } catch (Exception $e) {
                // En cas d'erreur avec la date, utiliser un format plus simple
                $ticket['appareil_type'] = $archive_info['type'] . ' (supprimé)';
            }
            
            $ticket['appareil_modele'] = $archive_info['modele'];
            
            // Mettre à null l'ID pour éviter les liens vers un équipement supprimé
            // mais conserver les informations pour l'affichage
            $ticket['id_appareil'] = null;
        } else {
            // Si pas trouvé dans l'archive, on garde l'ancienne méthode comme fallback
            $query_deleted = "SELECT description, action_effectuee 
                             FROM interventions 
                             WHERE id_ticket = ? AND (
                                 description LIKE '%Suppression de l%appareil%' 
                                 OR description LIKE '%appareil supprimé%'
                                 OR description LIKE '%Suppression d\'un appareil%'
                                 OR action_effectuee LIKE '%Appareil supprimé%'
                             )
                             ORDER BY date_intervention DESC 
                             LIMIT 1";
            $stmt_deleted = $pdo->prepare($query_deleted);
            $stmt_deleted->execute([$ticket['id_ticket']]);
            $deleted_info = $stmt_deleted->fetch();
            
            if ($deleted_info) {
                // Extraire les informations depuis l'action_effectuee
                $info_text = $deleted_info['action_effectuee'];
                
                // Extraire type et modèle
                if (preg_match('/Appareil supprimé: (.*?) - (.*?)$/m', $info_text, $matches)) {
                    $ticket['appareil_type'] = $matches[1] . ' (supprimé)';
                    $ticket['appareil_modele'] = $matches[2];
                } elseif (preg_match('/Appareil supprimé: (.*?)$/m', $info_text, $matches)) {
                    $ticket['appareil_type'] = $matches[1] . ' (supprimé)';
                } else {
                    // Valeur par défaut si aucune information spécifique n'est trouvée
                    $ticket['appareil_type'] = 'Appareil (supprimé)';
                }
                
                // Mettre à null l'ID pour éviter les liens vers un équipement supprimé
                // mais conserver les informations pour l'affichage
                $ticket['id_appareil'] = null;
            }
        }
    }
}
// Important: rompre la référence pour éviter des duplications/écrasements lors des boucles suivantes
unset($ticket);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Tickets</title>
    <link rel="stylesheet" href="../../assets/css/bootstrap.css">
    <link rel="stylesheet" href="../../assets/css/custom.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <!-- Ajout de Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
    <!-- Sidebar GLPI (navbar verticale) -->
    <div class="sidebar-glpi">
        <div class="sidebar-header">
            <a href="../../index.php" class="logo-container">
                <span class="logo-text">GPI</span>
            </a>
        </div>
        
        <div class="sidebar-search">
            <div class="search-wrapper">
                <i class="fas fa-search search-icon"></i>
                <input type="text" placeholder="Chercher dans le menu" class="search-input">
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <ul class="sidebar-menu">
                <!-- Menu Parc (visible uniquement pour les admins et techniciens) -->
                <?php if ($_SESSION['user_role'] == 1 || $_SESSION['user_role'] == 2): ?>
                <li class="sidebar-item">
                    <a href="#" class="sidebar-link">
                        <i class="fas fa-box sidebar-icon"></i>
                        <span>Parc</span>
                        <i class="fas fa-chevron-right sidebar-arrow"></i>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="../../pages/common/gestion_appareil.php"><i class="fas fa-print"></i>Projecteurs & Imprimantes</a></li>
                        <li><a href="../../pages/common/Gestion_unit.php"><i class="fas fa-server"></i>Unités centrales</a></li>
                        <li><a href="../../pages/common/gestion_peripherique.php"><i class="fas fa-keyboard"></i>Périphériques</a></li>
                        <li><a href="../../pages/common/gestion_poste.php"><i class="fas fa-desktop"></i>Postes</a></li>
                    </ul>
                </li>
                <?php endif; ?>
                
                <!-- Menu Assistance (tous les utilisateurs peuvent voir cette section) -->
                <li class="sidebar-item">
                    <a href="#" class="sidebar-link">
                        <i class="fas fa-headset sidebar-icon"></i>
                        <span>Assistance</span>
                        <i class="fas fa-chevron-right sidebar-arrow"></i>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="../../pages/user/create_ticket.php"><i class="fas fa-ticket-alt"></i>Créer un ticket</a></li>
                        
                        <!-- Option Gestion des tickets (visible uniquement pour les admins et techniciens) -->
                        <?php if ($_SESSION['user_role'] == 1 || $_SESSION['user_role'] == 2): ?>
                        <li><a href="../../pages/admin/manage_tickets.php"><i class="fas fa-tasks"></i>Gestion des tickets</a></li>
                        <?php endif; ?>
                    </ul>
                </li>
                
                <!-- Menu Configuration (visible uniquement pour les admins) -->
                <?php if ($_SESSION['user_role'] == 1): ?>
                <li class="sidebar-item">
                    <a href="#" class="sidebar-link">
                        <i class="fas fa-cog sidebar-icon"></i>
                        <span>Configuration</span>
                        <i class="fas fa-chevron-right sidebar-arrow"></i>
                    </a>
                    <ul class="sidebar-submenu">
                        <li><a href="../../pages/admin/gestion_references.php"><i class="fas fa-tags"></i>Gestion des salles/marques</a></li>
                        <li><a href="../../pages/common/views_all.php"><i class="fas fa-th-list"></i>Vue globale sur le stock</a></li>
                        <li><a href="../../pages/admin/gestion_users.php"><i class="fas fa-user-tag"></i>Gestion des utilisateurs</a></li>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        
        <!-- Bouton pour réduire le menu -->
        <div class="sidebar-footer">
            <button class="btn-collapse" id="collapseMenu">
                <i class="fas fa-chevron-left"></i>
                <span>Réduire le menu</span>
            </button>
        </div>
    </div>

    <!-- Navbar horizontale avec les informations utilisateur -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4 fixed-top" style="right: 250px; margin-right: -251px;">
        <div class="container d-flex justify-content-between align-items-center">
            <!-- Logo ou titre pour la navbar horizontale -->
            <a class="navbar-brand" href="../../index.php">GPI</a>
            
            <!-- Partie droite de la navbar -->
            <div class="d-flex align-items-center">
                <?php
                // Récupérer les informations de l'utilisateur
                $stmt = $pdo->prepare("SELECT u.nom, u.prenom, r.role_name FROM users u JOIN roles r ON u.id_role = r.id_role WHERE u.id_user = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
                ?>
                <div class="navbar-user-info mr-3">
                    <span class="user-name text-white mr-2"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></span>
                    <span class="user-role badge badge-light"><?= htmlspecialchars($user['role_name']) ?></span>
                </div>
                
                <a href="../../includes/logout.php" class="btn btn-outline-light"><i class="fas fa-sign-out-alt mr-1"></i>Déconnexion</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5 pt-5">
        <h1 class="mb-4 text-center text-3xl font-semibold text-gray-800">Gestion des Tickets</h1>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <strong class="font-bold">Succès!</strong>
                <span class="block sm:inline"><?= $_SESSION['success'] ?></span>
                <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                    <svg class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
                </span>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <strong class="font-bold">Erreur!</strong>
                <span class="block sm:inline"><?= $_SESSION['error'] ?></span>
                <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                    <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
                </span>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Section des métriques -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <!-- Tickets Ouverts -->
            <div class="bg-orange-100 p-4 rounded-lg shadow-md border-l-4 border-orange-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-orange-600 font-semibold">Tickets ouverts</p>
                        <p class="text-2xl font-bold text-orange-700">
                            <?= $metrics['ouverts'] ?? 0 ?>
                        </p>
                    </div>
                    <i class="fas fa-door-open text-orange-500 text-3xl"></i>
                </div>
            </div>

            <!-- Tickets en cours -->
            <div class="bg-purple-100 p-4 rounded-lg shadow-md border-l-4 border-purple-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-purple-600 font-semibold">Tickets en cours</p>
                        <p class="text-2xl font-bold text-purple-700">
                            <?= $metrics['en_cours'] ?? 0 ?>
                        </p>
                    </div>
                    <i class="fas fa-tools text-purple-500 text-3xl"></i>
                </div>
            </div>

            <!-- Problèmes résolus -->
            <div class="bg-green-100 p-4 rounded-lg shadow-md border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-green-600 font-semibold">Résolus</p>
                        <p class="text-2xl font-bold text-green-700">
                            <?= $metrics['resolus'] ?? 0 ?>
                        </p>
                    </div>
                    <i class="fas fa-check-circle text-green-500 text-3xl"></i>
                </div>
            </div>

            <!-- Non résolus -->
            <div class="bg-red-100 p-4 rounded-lg shadow-md border-l-4 border-red-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-red-600 font-semibold">Non résolus</p>
                        <p class="text-2xl font-bold text-red-700">
                            <?= $metrics['non_resolus'] ?? 0 ?>
                        </p>
                    </div>
                    <i class="fas fa-times-circle text-red-500 text-3xl"></i>
                </div>
            </div>
        </div>

        <!-- Section de filtres -->
        <div class="bg-white rounded-lg shadow-md mb-6 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-blue-800 px-4 py-3 text-white">
                <h5 class="font-semibold"><i class="fas fa-filter mr-2"></i> Filtres</h5>
            </div>
            <div class="p-4">
                <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label for="etat" class="block text-sm font-medium text-gray-700 mb-1">État</label>
                        <select class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" name="etat" id="etat">
                            <option value="">Tous</option>
                            <option value="Ouvert" <?= (isset($_GET['etat']) && $_GET['etat'] == 'Ouvert') ? 'selected' : '' ?>>Ouvert</option>
                            <option value="En cours" <?= (isset($_GET['etat']) && $_GET['etat'] == 'En cours') ? 'selected' : '' ?>>En cours</option>
                            <option value="Probleme Résoulu(fermé)" <?= (isset($_GET['etat']) && $_GET['etat'] == 'Probleme Résoulu(fermé)') ? 'selected' : '' ?>>Résolu</option>
                            <option value="probleme non resoulu(fermé)" <?= (isset($_GET['etat']) && $_GET['etat'] == 'probleme non resoulu(fermé)') ? 'selected' : '' ?>>Non résolu</option>
                        </select>
                    </div>
                    <div>
                        <label for="priorite" class="block text-sm font-medium text-gray-700 mb-1">Priorité</label>
                        <select class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" name="priorite" id="priorite">
                            <option value="">Toutes</option>
                            <option value="Haute" <?= (isset($_GET['priorite']) && $_GET['priorite'] == 'Haute') ? 'selected' : '' ?>>Haute</option>
                            <option value="Moyenne" <?= (isset($_GET['priorite']) && $_GET['priorite'] == 'Moyenne') ? 'selected' : '' ?>>Moyenne</option>
                            <option value="Faible" <?= (isset($_GET['priorite']) && $_GET['priorite'] == 'Faible') ? 'selected' : '' ?>>Faible</option>
                        </select>
                    </div>
                    <div>
                        <label for="categorie" class="block text-sm font-medium text-gray-700 mb-1">Catégorie</label>
                        <select class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" name="categorie" id="categorie">
                            <option value="">Toutes</option>
                            <option value="Matériel" <?= (isset($_GET['categorie']) && $_GET['categorie'] == 'Matériel') ? 'selected' : '' ?>>Matériel</option>
                            <option value="Logiciel" <?= (isset($_GET['categorie']) && $_GET['categorie'] == 'Logiciel') ? 'selected' : '' ?>>Logiciel</option>
                            <option value="Réseau" <?= (isset($_GET['categorie']) && $_GET['categorie'] == 'Réseau') ? 'selected' : '' ?>>Réseau</option>
                            <option value="Demande" <?= (isset($_GET['categorie']) && $_GET['categorie'] == 'Demande') ? 'selected' : '' ?>>Demande</option>
                        </select>
                    </div>
                    <div>
                        <label for="salle" class="block text-sm font-medium text-gray-700 mb-1">Salle</label>
                        <select class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" name="salle" id="salle">
                            <option value="">Toutes</option>
                            <?php foreach ($salles as $salle): ?>
                                <option value="<?= $salle['id_salle'] ?>" <?= (isset($_GET['salle']) && $_GET['salle'] == $salle['id_salle']) ? 'selected' : '' ?>>
                                    <?= $salle['nom_salle'] ?> (Étage <?= $salle['etage'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition duration-200 ease-in-out transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                            <i class="fas fa-search mr-2"></i>Filtrer
                        </button>
                    </div>
                </form>
                
                <div class="mt-4 flex justify-end">
                    <div class="dropdown">
                        <button class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md transition duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50 dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-download mr-2"></i>Exporter
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                            <li><a class="dropdown-item" href="../../includes/export.php?source=tickets&type=csv<?= isset($_GET['etat']) ? '&etat='.$_GET['etat'] : '' ?><?= isset($_GET['priorite']) ? '&priorite='.$_GET['priorite'] : '' ?><?= isset($_GET['categorie']) ? '&categorie='.$_GET['categorie'] : '' ?><?= isset($_GET['salle']) ? '&salle='.$_GET['salle'] : '' ?>"><i class="fas fa-file-csv mr-2"></i>CSV</a></li>
                            <li><a class="dropdown-item" href="../../includes/export.php?source=tickets&type=pdf_paysage<?= isset($_GET['etat']) ? '&etat='.$_GET['etat'] : '' ?><?= isset($_GET['priorite']) ? '&priorite='.$_GET['priorite'] : '' ?><?= isset($_GET['categorie']) ? '&categorie='.$_GET['categorie'] : '' ?><?= isset($_GET['salle']) ? '&salle='.$_GET['salle'] : '' ?>"><i class="fas fa-file-pdf mr-2"></i>Page courante en PDF paysage</a></li>
                            <li><a class="dropdown-item" href="../../includes/export.php?source=tickets&type=pdf_portrait<?= isset($_GET['etat']) ? '&etat='.$_GET['etat'] : '' ?><?= isset($_GET['priorite']) ? '&priorite='.$_GET['priorite'] : '' ?><?= isset($_GET['categorie']) ? '&categorie='.$_GET['categorie'] : '' ?><?= isset($_GET['salle']) ? '&salle='.$_GET['salle'] : '' ?>"><i class="fas fa-file-pdf mr-2"></i>Page courante en PDF portrait</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liste des tickets -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="bg-gradient-to-r from-blue-600 to-blue-800 px-4 py-3 text-white flex justify-between items-center">
                <h5 class="font-semibold mb-0"><i class="fas fa-ticket-alt mr-2"></i> Liste des tickets (<?= count($tickets) ?>)</h5>
                <a href="../user/create_ticket.php" class="bg-white text-blue-700 hover:bg-gray-100 font-medium py-1 px-3 rounded-md shadow-sm text-sm transition duration-200 ease-in-out">
                    <i class="fas fa-plus mr-1"></i> Nouveau ticket
                </a>
            </div>
            <div class="p-4">
                <?php if (empty($tickets)): ?>
                    <div class="bg-blue-50 text-blue-700 p-4 rounded-md">Aucun ticket ne correspond aux critères sélectionnés.</div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($tickets as $ticket):
                            // Déterminer les classes CSS en fonction de l'état
                            $stateColor = '';
                            $stateBg = '';
                            $stateIcon = '';
                            switch($ticket['etat']) {
                                case 'Ouvert':
                                    $stateColor = 'text-red-600';
                                    $stateBg = 'bg-red-50';
                                    $stateIcon = 'fas fa-exclamation-circle';
                                    break;
                                case 'En cours':
                                    $stateColor = 'text-amber-600';
                                    $stateBg = 'bg-amber-50';
                                    $stateIcon = 'fas fa-tools';
                                    break;
                                case 'Probleme Résoulu(fermé)':
                                    $stateColor = 'text-green-600';
                                    $stateBg = 'bg-green-50';
                                    $stateIcon = 'fas fa-check-circle';
                                    break;
                                case 'probleme non resoulu(fermé)':
                                    $stateColor = 'text-gray-600';
                                    $stateBg = 'bg-gray-50';
                                    $stateIcon = 'fas fa-times-circle';
                                    break;
                                default:
                                    $stateColor = 'text-gray-600';
                                    $stateBg = 'bg-gray-50';
                                    $stateIcon = 'fas fa-question-circle';
                            }

                            // Déterminer les classes CSS en fonction de la priorité
                            $priorityColor = '';
                            $priorityBg = '';
                            $priorityIcon = '';
                            $priorityBorder = ''; // Nouvelle variable pour la couleur de bordure
                            switch($ticket['priorite']) {
                                case 'Haute':
                                    $priorityColor = 'text-red-700';
                                    $priorityBg = 'bg-red-100';
                                    $priorityIcon = 'fas fa-arrow-up';
                                    $priorityBorder = 'border-red-600'; // Bordure rouge pour haute priorité
                                    break;
                                case 'Moyenne':
                                    $priorityColor = 'text-amber-700';
                                    $priorityBg = 'bg-amber-100';
                                    $priorityIcon = 'fas fa-equals';
                                    $priorityBorder = 'border-amber-600'; // Bordure orange pour priorité moyenne
                                    break;
                                case 'Faible':
                                    $priorityColor = 'text-green-700';
                                    $priorityBg = 'bg-green-100';
                                    $priorityIcon = 'fas fa-arrow-down';
                                    $priorityBorder = 'border-green-600'; // Bordure verte pour faible priorité
                                    break;
                            }

                            // Déterminer les classes CSS en fonction de la catégorie
                            $categoryIcon = '';
                            switch($ticket['categorie']) {
                                case 'Matériel':
                                    $categoryIcon = 'fas fa-desktop';
                                    break;
                                case 'Logiciel':
                                    $categoryIcon = 'fas fa-code';
                                    break;
                                case 'Réseau':
                                    $categoryIcon = 'fas fa-network-wired';
                                    break;
                                case 'Demande':
                                    $categoryIcon = 'fas fa-clipboard-list';
                                    break;
                                default:
                                    $categoryIcon = 'fas fa-tag';
                            }
                        ?>
                            <div class="rounded-lg shadow-sm overflow-hidden border-l-4 <?= $priorityBorder ?> transition-all hover:shadow-lg transform hover:-translate-y-1">
                                <div class="flex flex-col md:flex-row md:justify-between p-4 <?= $stateBg ?>">
                                    <div class="flex items-center mb-2 md:mb-0">
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3 <?= $priorityBg ?> <?= $priorityColor ?>">
                                            <span class="font-bold text-xs"><?= htmlspecialchars($ticket['priorite']) ?></span>
                                        </div>
                                        <div>
                                            <h5 class="font-semibold text-gray-900">
                                                <span class="text-blue-600">#<?= $ticket['id_ticket'] ?></span> -
                                                <i class="<?= $categoryIcon ?> mr-1"></i>
                                                <?= htmlspecialchars($ticket['categorie']) ?>
                                            </h5>
                                            <p class="text-gray-500 text-sm">
                                                <i class="fas fa-calendar-alt mr-1"></i> <?= date('d/m/Y H:i', strtotime($ticket['date_creation'])) ?>
                                                <i class="fas fa-user ml-3 mr-1"></i> <?= htmlspecialchars($ticket['prenom'] . ' ' . $ticket['nom']) ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?= $stateColor ?> border border-current mr-2">
                                            <i class="<?= $stateIcon ?> mr-1"></i> <?= htmlspecialchars($ticket['etat']) ?>
                                        </span>
                                        <div class="flex space-x-1">
                                            <a href="ticket_details.php?id=<?= $ticket['id_ticket'] ?>" class="p-1 rounded-full hover:bg-gray-200 text-gray-600 transition-colors" title="Voir les détails">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="manage_tickets.php?action=edit&ticket_id=<?= $ticket['id_ticket'] ?>" class="p-1 rounded-full hover:bg-gray-200 text-gray-600 transition-colors" title="Modifier">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="ticket_details.php?id=<?= $ticket['id_ticket'] ?>" class="p-1 rounded-full hover:bg-gray-200 text-gray-600 transition-colors" title="Ajouter une intervention">
                                                <i class="fas fa-comment-medical"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <?php if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['ticket_id']) && $_GET['ticket_id'] == $ticket['id_ticket']): ?>
                                <div class="bg-white p-4 border-t border-gray-200">
                                    <h6 class="font-semibold text-gray-700 mb-3">Modifier le ticket #<?= $ticket['id_ticket'] ?></h6>
                                    <form method="POST" action="" class="space-y-3">
                                        <input type="hidden" name="ticket_id" value="<?= $ticket['id_ticket'] ?>">

                                        <div>
                                            <label for="etat<?= $ticket['id_ticket'] ?>" class="block text-sm font-medium text-gray-700 mb-1">État:</label>
                                            <select class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" id="etat<?= $ticket['id_ticket'] ?>" name="etat">
                                                <option value="Ouvert" <?= $ticket['etat'] === 'Ouvert' ? 'selected' : '' ?>>Ouvert</option>
                                                <option value="En cours" <?= $ticket['etat'] === 'En cours' ? 'selected' : '' ?>>En cours</option>
                                                <option value="Probleme Résoulu(fermé)" <?= $ticket['etat'] === 'Probleme Résoulu(fermé)' ? 'selected' : '' ?>>Résolu</option>
                                                <option value="probleme non resoulu(fermé)" <?= $ticket['etat'] === 'probleme non resoulu(fermé)' ? 'selected' : '' ?>>Non résolu</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label for="priorite<?= $ticket['id_ticket'] ?>" class="block text-sm font-medium text-gray-700 mb-1">Priorité:</label>
                                            <select class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" id="priorite<?= $ticket['id_ticket'] ?>" name="priorite">
                                                <option value="Haute" <?= $ticket['priorite'] === 'Haute' ? 'selected' : '' ?>>Haute</option>
                                                <option value="Moyenne" <?= $ticket['priorite'] === 'Moyenne' ? 'selected' : '' ?>>Moyenne</option>
                                                <option value="Faible" <?= $ticket['priorite'] === 'Faible' ? 'selected' : '' ?>>Faible</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label for="categorie<?= $ticket['id_ticket'] ?>" class="block text-sm font-medium text-gray-700 mb-1">Catégorie:</label>
                                            <select class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" id="categorie<?= $ticket['id_ticket'] ?>" name="categorie">
                                                <option value="Matériel" <?= $ticket['categorie'] === 'Matériel' ? 'selected' : '' ?>>Matériel</option>
                                                <option value="Logiciel" <?= $ticket['categorie'] === 'Logiciel' ? 'selected' : '' ?>>Logiciel</option>
                                                <option value="Réseau" <?= $ticket['categorie'] === 'Réseau' ? 'selected' : '' ?>>Réseau</option>
                                                <option value="Demande" <?= $ticket['categorie'] === 'Demande' ? 'selected' : '' ?>>Demande</option>
                                            </select>
                                        </div>

                                        <div class="flex space-x-2">
                                            <button type="submit" name="update_ticket" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md transition">
                                                <i class="fas fa-save mr-1"></i> Enregistrer
                                            </button>
                                            <a href="manage_tickets.php" class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-md transition">
                                                Annuler
                                            </a>
                                        </div>
                                    </form>
                                </div>
                                <?php endif; ?>

                                <div class="p-4 bg-white">
                                    <!-- Affichage du matériel concerné -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                        <div class="bg-gray-50 p-3 rounded-md">
                                            <h6 class="text-gray-700 mb-2 font-medium"><i class="fas fa-map-marker-alt mr-1"></i> Emplacement</h6>
                                            <?php if ($ticket['nom_salle']): ?>
                                                <p class="text-gray-600">
                                                    <i class="fas fa-door-open mr-1"></i> Salle <?= htmlspecialchars($ticket['nom_salle']) ?><br>
                                                    <i class="fas fa-building mr-1"></i> Étage <?= $ticket['etage'] ?>
                                                </p>
                                            <?php else: ?>
                                                <p class="text-gray-500 italic">Non spécifié</p>
                                            <?php endif; ?>
                                        </div>

                                        <div class="bg-gray-50 p-3 rounded-md">
                                            <h6 class="text-gray-700 mb-2 font-medium"><i class="fas fa-desktop mr-1"></i> Matériel concerné</h6>
                                            <div class="flex flex-wrap gap-2">
                                                <?php if ($ticket['nom_poste']): ?>
                                                    <?php
                                                    $isDeleted = strpos($ticket['nom_poste'], '(supprimé') !== false;
                                                    $bgColor = $isDeleted ? 'bg-gray-200' : 'bg-blue-100';
                                                    $textColor = $isDeleted ? 'text-gray-700' : 'text-blue-800';
                                                    $icon = $isDeleted ? 'fas fa-archive' : 'fas fa-desktop';
                                                    ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $bgColor ?> <?= $textColor ?>">
                                                        <i class="<?= $icon ?> mr-1"></i> Poste: <?= htmlspecialchars($ticket['nom_poste']) ?>
                                                    </span>
                                                <?php endif; ?>

                                                <?php if ($ticket['appareil_type']): ?>
                                                    <?php
                                                    $isDeleted = strpos($ticket['appareil_type'], '(supprimé') !== false;
                                                    $bgColor = $isDeleted ? 'bg-gray-200' :
                                                               ($ticket['appareil_type'] == 'Imprimante' ? 'bg-purple-100' : 'bg-indigo-100');
                                                    $textColor = $isDeleted ? 'text-gray-700' :
                                                                 ($ticket['appareil_type'] == 'Imprimante' ? 'text-purple-800' : 'text-indigo-800');
                                                    $icon = $isDeleted ? 'fas fa-archive' :
                                                            ($ticket['appareil_type'] == 'Imprimante' ? 'fas fa-print' : 'fas fa-video');
                                                    ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $bgColor ?> <?= $textColor ?>">
                                                        <i class="<?= $icon ?> mr-1"></i> <?= htmlspecialchars($ticket['appareil_modele'] ?? $ticket['appareil_type']) ?>
                                                    </span>
                                                <?php endif; ?>

                                                <?php if ($ticket['peripherique_type']): ?>
                                                    <?php
                                                    $isDeleted = strpos($ticket['peripherique_type'], '(supprimé') !== false;
                                                    $bgColor = $isDeleted ? 'bg-gray-200' : 'bg-green-100';
                                                    $textColor = $isDeleted ? 'text-gray-700' : 'text-green-800';
                                                    $icon = $isDeleted ? 'fas fa-archive' : 'fas fa-keyboard';
                                                    if (!$isDeleted) {
                                                        if (strpos(strtolower($ticket['peripherique_type']), 'souris') !== false) {
                                                            $icon = 'fas fa-mouse';
                                                        } elseif (strpos(strtolower($ticket['peripherique_type']), 'écran') !== false) {
                                                            $icon = 'fas fa-desktop';
                                                        }
                                                    }
                                                    ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $bgColor ?> <?= $textColor ?>">
                                                        <i class="<?= $icon ?> mr-1"></i> <?= htmlspecialchars($ticket['peripherique_type']) ?>
                                                    </span>
                                                <?php endif; ?>

                                                <?php if ($ticket['unite_numero']): ?>
                                                    <?php
                                                    $isDeleted = strpos($ticket['unite_numero'], '(supprimé') !== false;
                                                    $bgColor = $isDeleted ? 'bg-gray-200' : 'bg-red-100';
                                                    $textColor = $isDeleted ? 'text-gray-700' : 'text-red-800';
                                                    $icon = $isDeleted ? 'fas fa-archive' : 'fas fa-server';
                                                    ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $bgColor ?> <?= $textColor ?>">
                                                        <i class="<?= $icon ?> mr-1"></i> UC: <?= htmlspecialchars($ticket['unite_numero']) ?>
                                                    </span>
                                                <?php endif; ?>

                                                <?php if (!$ticket['nom_poste'] && !$ticket['appareil_type'] && !$ticket['peripherique_type'] && !$ticket['unite_numero']): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                        Aucun matériel spécifié
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="border-t border-gray-100 pt-3">
                                        <h6 class="text-gray-700 mb-2 font-medium"><i class="fas fa-clipboard-list mr-1"></i> Description</h6>
                                        <p class="text-gray-600">
                                            <?= isset($ticket['description']) ? (strlen($ticket['description']) > 150 ? substr(htmlspecialchars($ticket['description']), 0, 150) . '...' : htmlspecialchars($ticket['description'])) : '' ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="p-3 bg-gray-50 border-t border-gray-100 flex justify-between items-center">
                                    <div>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <i class="fas fa-comments mr-1"></i> <?= $ticket['nb_interventions'] ?> intervention<?= $ticket['nb_interventions'] > 1 ? 's' : '' ?>
                                        </span>
                                    </div>
                                    <div class="flex space-x-2">
                                        <a href="ticket_details.php?id=<?= $ticket['id_ticket'] ?>" class="inline-flex items-center px-3 py-1 border border-transparent text-sm leading-4 font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 transition">
                                            <i class="fas fa-eye mr-1"></i> Détails
                                        </a>
                                        <a href="ticket_details.php?id=<?= $ticket['id_ticket'] ?>" class="inline-flex items-center px-3 py-1 border border-transparent text-sm leading-4 font-medium rounded-md text-green-700 bg-green-100 hover:bg-green-200 transition">
                                            <i class="fas fa-plus mr-1"></i> Intervention
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Scripts JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script pour la fonctionnalité du menu vertical -->
    <script>
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
                $(this).html('<i class="fas fa-chevron-right"></i>');
                $(".sidebar-item span, .sidebar-footer span, .logo-text").hide();
            } else {
                $(this).html('<i class="fas fa-chevron-left"></i><span>Réduire le menu</span>');
                $(".sidebar-item span, .sidebar-footer span, .logo-text").show();
            }
        });
        
        // Animation des alertes
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 1s ease';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 1000);
            }, 5000);
        });
        
        // Initialisation des dropdowns Bootstrap 5
        var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
        var dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
            return new bootstrap.Dropdown(dropdownToggleEl);
        });
    });
    </script>
</body>
</html>
